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
        $hoursByDay = array(
            "lundi" => array("10:00", "19:00"),
            "mardi" => array("09:00", "18:30"),
            "mercredi" => array("11:00", "20:00"),
            // Ajoutez les autres jours de la semaine avec leurs heures d'ouverture
        );

        $numPeoplePerHour = 2;
        $reservationDuration = 3600 / $numPeoplePerHour;

        $availableSlots = array();

        $startTime = strtotime($hoursByDay[$day][0]);
        $endTime = strtotime($hoursByDay[$day][1]);

        for ($time = $startTime; $time < $endTime; $time += $reservationDuration) {
            $startTimeStr = date('H:i', $time);
            $endTimeStr = date('H:i', ($time + $reservationDuration));
            $slot = $startTimeStr . '-' . $endTimeStr;

            // Vérifiez si le créneau n'est pas déjà réservé pour ce jour
            $notAvailableSlots = $this->getUsedSlots($day);
            if (in_array($slot, $notAvailableSlots)) {
                continue; // Passez à l'itération suivante
            }

            // Vérifiez si le créneau ne se trouve pas dans une plage horaire marquée comme "congé"
            $congeRanges = $this->getCongeRanges($day);
            foreach ($congeRanges as $congeRange) {
                $congeStart = strtotime($congeRange['start_time']);
                $congeEnd = strtotime($congeRange['end_time']);
                if ($time >= $congeStart && $time + $reservationDuration <= $congeEnd) {
                    continue 2; // Passez à l'itération suivante de la boucle externe
                }
            }

            $availableSlots[] = $slot;
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