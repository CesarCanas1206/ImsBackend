<?php
namespace App\Traits;

trait DateFunctions
{
    /**
     * Takes the given pattern and date and returns the date based on the pattern.
     */
    public function addBasedOnPattern($pattern, $date)
    {
        switch ($pattern) {
            case 'dayweekly':
                return $date->modify('+1 day');
            case 'weekly':
                return $date->modify('+1 week');
            case 'fortnightly':
                return $date->modify('+2 week');
            case 'fourweekly':
                return $date->modify('+4 week');
            case 'monthly':
                return $date->modify('+1 month');
            case 'quarterly':
                return $date->modify('+3 month');
            case 'yearly':
                return $date->modify('+1 year');
            default:
                return $date->modify('+1 day');
        }
    }

    /**
     * Takes in a provided patter (e.g. weekly, fortnightly), start date, end date, day, number of times to repeat, and an array of dates to exclude.
     * Returns an array of dates based on those props.
     */
    public function datesFromPattern($pattern, $date, $end, $day, $times, $exclude = [])
    {
        $dates = [];
        $current = new \DateTime($date);
        $endDate = new \DateTime($end);
        $excludeDates = array_map(function ($date) {
            return (new \DateTime($date))->format('Y-m-d');
        }, $exclude);
        if (isset($times) && $times !== 0 && $times != '') {
            for ($i = 0; $i < intval($times); $i++) {
                $formatted = $current->format('Y-m-d');
                $dateAllowed = !in_array($formatted, $excludeDates);

                if ($dateAllowed && $pattern == 'dayweekly' && !empty($day)) {
                    $dateAllowed = $current->format('l') == $day;
                }

                if ($dateAllowed) {
                    $dates[] = $formatted;
                }
                $current = $this->addBasedOnPattern($pattern, $current);
            }
            return $dates;
        }
        while ($current <= $endDate) {
            $formatted = $current->format('Y-m-d');
            $dateAllowed = !in_array($formatted, $excludeDates);

            if ($dateAllowed && $pattern == 'dayweekly' && !empty($day)) {
                $dateAllowed = $current->format('l') == $day;
            }

            if ($dateAllowed) {
                $dates[] = $formatted;
            }
            $current = $this->addBasedOnPattern($pattern, $current);
        }
        return $dates;
    }
}
