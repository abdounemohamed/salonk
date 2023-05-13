<?php

namespace App\Service;


use App\Entity\NotAvailableSlots;
use App\Repository\AppointmentRepository;
use App\Repository\NotAvailableSlotsRepository;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;

class AppointmentManager
{
    public function __construct
    (
        private AppointmentRepository $appointmentRepository,
        private NotAvailableSlotsRepository $availableSlotsRepository,
    )
    {
    }

    /**
     * @param string $day
     * @return array
     * @throws Exception
     */
    public function getAvailableSlots(string $day): array
    {
        // Define the start and end times of business hours
        $startTime = strtotime('10:00');
        $endTime = strtotime('19:00');

        $numPeoplePerHour = 2;

        $reservationDuration = 3600 / $numPeoplePerHour;

        $availableSlots = array();

        for ($time = $startTime; $time < $endTime; $time += $reservationDuration) {
            $startTimeStr = date('H:i', $time); // format start time as "hh:mm"
            $endTimeStr = date('H:i', ($time + $reservationDuration)); // format end time as "hh:mm"
            $availableSlots[] =  $startTimeStr.'-'.$endTimeStr; // add reservation slot to array
        }
        $notAvaibleSlots = $this->getUsedSlots($day);

        return  array_diff($availableSlots, $notAvaibleSlots);
    }

    /**
     * @param string $day
     * @return array
     * @throws Exception
     */
    public function getUsedSlots(string $day): array
    {
        $data = $this->appointmentRepository->findBy(['date' => new \DateTime($day)]);
        $results = [];
        foreach ($data as $slot){
            if (isset($results[$slot->getSlot()])){
                $results[$slot->getSlot()]++;
            }else{
                $results[$slot->getSlot()] = 1;
            }
        }

        $filtedData = array_filter($results, function($value) {
            return $value >= 2;
        });

        return array_keys($filtedData);
    }

    /**
     * @param string $day
     * @return array
     * @throws Exception
     */
    public function getAvailableSlots2(string $day): array
    {
        $slotsByDay = [
            "Monday" => ["14:00-19:00"],
            "Tuesday" => ["14:00-19:00"],
            "Wednesday" => ["12:00-19:00"],
            "Thursday" => ["12:00-19:00"],
            "Friday" => ["10:00-13:00", "15:00-19:00"],
            "Saturday" => ["10:00-19:00"],
        ];

        $numPeoplePerHour = 2;
        $reservationDuration = 3600 / $numPeoplePerHour;

        $availableSlots = [];

        $formattedDay = (new \DateTime($day))->format("l"); // Convertir la date en jour de la semaine (lundi, mardi, etc.)

        if (array_key_exists($formattedDay, $slotsByDay)) {
            $slots = $slotsByDay[$formattedDay];

            foreach ($slots as $slot) {
                list($startTimeStr, $endTimeStr) = explode('-', $slot);

                $startTime = strtotime($startTimeStr);
                $endTime = strtotime($endTimeStr);

                for ($time = $startTime; $time < $endTime; $time += $reservationDuration) {
                    // Skip the time range between 13:00 and 14:00 on Fridays
                    if ($formattedDay === "Friday" && date("H:i", $time) === "13:00") {
                        $time += 7200;
                        continue;
                    }

                    $slotStartTimeStr = date('H:i', $time);
                    $slotEndTimeStr = date('H:i', ($time + $reservationDuration));
                    $slot = $slotStartTimeStr . '-' . $slotEndTimeStr;

                    // Check if the slot is not already booked for this day
                    $notAvailableSlots = $this->getUsedSlots($day);
                    if (in_array($slot, $notAvailableSlots)) {
                        continue; // Skip this slot
                    }

                    // Check if the slot is not within a time range marked as "congé"
                    $congeRanges = $this->getCongeRanges($day);
                    foreach ($congeRanges as $congeRange) {
                        $congeStart = strtotime($congeRange['start_time']);
                        $congeEnd = strtotime($congeRange['end_time']);
                        if ($time >= $congeStart && $time + $reservationDuration <= $congeEnd) {
                            continue 2; // Skip this slot and move to the next time range
                        }
                    }

                    $availableSlots[] = $slot;
                }
            }
        }

        return $availableSlots;


    }


    /**
     * @param string $day
     * @return array
     * @throws Exception
     */
    private function getCongeRanges(string $day): array
    {
        $results = [];
        $data = $this->availableSlotsRepository->findByDate($day);

        if ($data) {
            foreach ($data as $notAvailableSlot) {
                if ($notAvailableSlot instanceof NotAvailableSlots) {
                    $start = $notAvailableSlot->getStart();
                    $end = $notAvailableSlot->getEnd();

                    // Vérifier si la date de début de la plage horaire de congé correspond au jour donné en paramètre
                    if ($start->format('Y-m-d') === $day) {
                        $results[] = ['start_time' => $start->format('H:i'), 'end_time' => '19:00'];
                    }

                    // Ajouter la plage horaire de congé à la liste correspondante si la date correspond également au jour donné en paramètre
                    if ($end->format('Y-m-d') === $day) {
                        $results[] = ['start_time' => '09:00', 'end_time' => $end->format('H:i')];
                    }

                    // Si la plage horaire de congé s'étend sur plusieurs jours, ajouter les plages horaires manquantes
                    if ($start < $end) {
                        $interval = new \DateInterval('P1D');
                        $period = new \DatePeriod($start->add($interval), $interval, $end);
                        foreach ($period as $date) {
                            if ($date->format('Y-m-d') === $day) {
                                if ($date < $end->sub($interval)) {
                                    $results[] = ['start_time' => '10:00', 'end_time' => '19:00'];
                                } else {
                                    $results[] = ['start_time' => '10:00', 'end_time' => $end->format('H:i')];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $results;
    }






}