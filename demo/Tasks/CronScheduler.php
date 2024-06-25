<?php

class CronScheduler {
    public function getNextCronTime($cronExpression) {
        $cronParts = explode(' ', $cronExpression);
        if (count($cronParts) !== 5) {
            throw new Exception('Invalid cron expression');
        }

        $now = new DateTime();
        while (true) {
            $now->modify('+1 minute');
            if ($this->matchesCron($cronParts, $now)) {
                return $now->format('Y-m-d H:i:s');
            }
        }
    }

    private function matchesCron($cronParts, DateTime $date) {
        return $this->matchesCronPart($cronParts[0], $date->format('i')) &&
               $this->matchesCronPart($cronParts[1], $date->format('H')) &&
               $this->matchesCronPart($cronParts[2], $date->format('d')) &&
               $this->matchesCronPart($cronParts[3], $date->format('m')) &&
               $this->matchesCronPart($cronParts[4], $date->format('w'));
    }

    private function matchesCronPart($cronPart, $value) {
        if ($cronPart === '*') {
            return true;
        }

        foreach (explode(',', $cronPart) as $part) {
            if (strpos($part, '/') !== false) {
                [$range, $step] = explode('/', $part);
                if ($range === '*') {
                    if ($value % $step === 0) {
                        return true;
                    }
                } else {
                    [$start, $end] = explode('-', $range);
                    if ($value >= $start && $value <= $end && ($value - $start) % $step === 0) {
                        return true;
                    }
                }
            } elseif (strpos($part, '-') !== false) {
                [$start, $end] = explode('-', $part);
                if ($value >= $start && $value <= $end) {
                    return true;
                }
            } elseif ($part == $value) {
                return true;
            }
        }

        return false;
    }
}

?>

