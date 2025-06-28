<?php
namespace App\Helper;

class Schedules
{
    public const basic = [
        'base' => [
            'Monday' => ['20:25'],
            'Wednesday' => ['20:15'],
            'Thursday' => ['19:38']
        ],
        '1' => [
            'Tuesday' => ['08:14']
        ],
        '2' => [
            'Monday' => ['07:45']
        ],
        '3' => [
            'Wednesday' => ['12:15']
        ],
        '4' => [
            'Thursday' => ['21:17']
        ],
        '5' => [
            'Friday' => ['15:25']
        ]
    ];

    public const lite = [
        'base' => [
            'Monday' => ['20:25'],
            'Tuesday' => ['18:31'],
            'Wednesday' => ['20:15', '21:05'],
            'Thursday' => ['19:38'],
            'Friday' => ['20:33']
        ],
        '1' => [
            'Monday' => ['07:05'],
            'Tuesday' => ['08:14']
        ],
        '2' => [
            'Monday' => ['07:45'],
            'Wednesday' => ['12:15']
        ],
        '3' => [
            'Wednesday' => ['12:15'],
            'Friday' => ['16:45']
        ],
        '4' => [
            'Thursday' => ['13:05', '21:17']
        ],
        '5' => [
            'Tuesday' => ['13:21'],
            'Friday' => ['15:25']
        ]
    ];

    public const plus = [
        'base' => [
            'Monday' => ['20:25'],
            'Tuesday' => ['18:31'],
            'Wednesday' => ['20:15', '21:05'],
            'Thursday' => ['19:38'],
            'Friday' => ['20:33'],
        ],
        '1' => [
            'Monday' => ['07:05'],
            'Tuesday' => ['08:14'],
        ],
        '2' => [
            'Monday' => ['07:45'],
            'Wednesday' => ['12:15'],
        ],
        '3' => [
            'Wednesday' => ['12:15'],
            'Friday' => ['16:45'],
        ],
        '4' => [
            'Thursday' => ['13:05', '21:17'],
        ],
        '5' => [
            'Tuesday' => ['13:21'],
            'Friday' => ['15:25'],
        ],
    ];

    public const turbo = [
        'base' => [
            'Monday' => ['21:15', '19:34'],
            'Tuesday' => ['18:41', '21:21'],
            'Wednesday' => ['20:15', '21:05', '22:35'],
            'Thursday' => ['18:48', '22:30'],
            'Friday' => ['20:15', '21:15'],
            'Saturday' => ['21:34']
        ],
        '1' => [
            'Monday' => ['07:05'],
            'Tuesday' => ['08:14', '10:19']
        ],
        '2' => [
            'Monday' => ['07:45'],
            'Tuesday' => ['08:41'],
            'Wednesday' => ['12:15']
        ],
        '3' => [
            'Wednesday' => ['12:15', '12:45'],
            'Friday' => ['16:45']
        ],
        '4' => [
            'Tuesday' => ['12:05'],
            'Thursday' => ['13:05', '21:17']
        ],
        '5' => [
            'Monday' => ['10:21'],
            'Tuesday' => ['13:21'],
            'Thursday' => ['15:25']
        ]
    ];

    public const test = [
        '1' => [
        ],
        '2' => [
        ],
        '3' => [
        ],
        '4' => [
        ],
        '5' => [
        ],
        'base' => [
        ]
    ];

    public function getNextPublishDatetime($currentDatetime, $schedule, $nextShiftMinutes) {
        // Current day of the week
        $currentDay = date('l', strtotime($currentDatetime));
        $currentDate = date('Y-m-d', strtotime($currentDatetime));
        $currentTime = date('H:i', strtotime($currentDatetime));

        // Find the next publication time
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $currentIndex = array_search($currentDay, $daysOfWeek);

        for ($i = 0; $i <= 7; $i++) {
            $dayIndex = ($currentIndex + $i) % 7;
            $day = $daysOfWeek[$dayIndex];

            if (!isset($schedule[$day])) {
                continue; // Skip days without a schedule
            }

            foreach ($schedule[$day] as $time) {
                if ($i === 0) {
                    // Handle the current day
                    if ($time > $currentTime) {
                        $fullDatetime = date('Y-m-d H:i', strtotime("$currentDate $time"));

                        // Shift the time
                        $shiftedDatetime = date('Y-m-d H:i', strtotime("+$nextShiftMinutes minutes", strtotime($fullDatetime)));

                        // Check if the time has shifted to the next hour
                        $originalHour = date('H', strtotime($fullDatetime));
                        $shiftedHour = date('H', strtotime($shiftedDatetime));
                        if ($shiftedHour != $originalHour) {
                            $shiftedDatetime = $fullDatetime; // if the time has shifted to the next hour, use the original time
                        }

                        return $shiftedDatetime;
                    }
                } else {
                    // Handle future days
                    $fullDatetime = date('Y-m-d H:i', strtotime("next $day $time", strtotime($currentDatetime)));

                    // Shift the time
                    $shiftedDatetime = date('Y-m-d H:i', strtotime("+$nextShiftMinutes minutes", strtotime($fullDatetime)));

                    // Check if the time has shifted to the next hour
                    $originalHour = date('H', strtotime($fullDatetime));
                    $shiftedHour = date('H', strtotime($shiftedDatetime));
                    if ($shiftedHour != $originalHour) {
                        $shiftedDatetime = $fullDatetime; // if the time has shifted to the next hour, use the original time
                    }

                    return $shiftedDatetime;
                }
            }
        }

        return null;
    }

    public function getNextShiftMinutes($lastShiftMinutes) {
        if ($lastShiftMinutes === null) {
            return 0;
        }
        $shiftSequence = [0, 7, 10, 15, 3, 5];

        $currentShiftIndex = array_search($lastShiftMinutes, $shiftSequence);
        $nextShiftIndex = ($currentShiftIndex === false || $currentShiftIndex + 1 >= count($shiftSequence))
            ? 0
            : $currentShiftIndex + 1;

        return $shiftSequence[$nextShiftIndex];
    }
}