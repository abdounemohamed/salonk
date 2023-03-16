<?php

namespace App\Service;


use App\Repository\AppointmentRepository;
use Exception;

class AppointmentManager
{
    public function __construct
    (
        private AppointmentRepository $appointmentRepository
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

}