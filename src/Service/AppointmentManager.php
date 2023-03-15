<?php

namespace App\Service;

class AppointmentManager
{
    /**
     * @return array
     */
    public function getAviableSlots(): array
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
        return $availableSlots;
    }

}