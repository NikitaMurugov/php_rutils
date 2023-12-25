<?php

namespace PhpRutils;

use PhpRutils\Struct\TimeParams;

/**
 * Russian dates without locales
 * Class Dt.
 */
class Dt
{
    public static string $PREFIX_IN = 'через'; //Prefix 'in' (i.e. B{in} three hours)

    public static string $SUFFIX_AGO = 'назад'; //Prefix 'ago' (i.e. three hours B{ago})

    /** @var array<array-key, list<string>> */
    private static array $_DAY_NAMES = [
        ['пн', 'понедельник', 'понедельник', "в\xC2\xA0"],
        ['вт', 'вторник', 'вторник', "во\xC2\xA0"],
        ['ср', 'среда', 'среду', "в\xC2\xA0"],
        ['чт', 'четверг', 'четверг', "в\xC2\xA0"],
        ['пт', 'пятница', 'пятницу', "в\xC2\xA0"],
        ['сб', 'суббота', 'субботу', "в\xC2\xA0"],
        ['вск', 'воскресенье', 'воскресенье', "в\xC2\xA0"],
    ]; //Day alternatives (i.e. one day ago -> yesterday)

    /** @var array<array-key, list<string>> */
    private static array $_MONTH_NAMES = [
        ['янв', 'январь', 'января'],
        ['фев', 'февраль', 'февраля'],
        ['мар', 'март', 'марта'],
        ['апр', 'апрель', 'апреля'],
        ['май', 'май', 'мая'],
        ['июн', 'июнь', 'июня'],
        ['июл', 'июль', 'июля'],
        ['авг', 'август', 'августа'],
        ['сен', 'сентябрь', 'сентября'],
        ['окт', 'октябрь', 'октября'],
        ['ноя', 'ноябрь', 'ноября'],
        ['дек', 'декабрь', 'декабря'],
    ]; //Forms (1, 2, 5) for noun 'day'

    /** @var list<string> */
    private static array $_PAST_ALTERNATIVES = ['вчера', 'позавчера'];

    private static $_YEAR_VARIANTS = ['год', 'года', 'лет']; //Forms (1, 2, 5) for noun 'year'

    private static $_MONTH_VARIANTS = ['месяц', 'месяца', 'месяцев'];

    private static $_DAY_VARIANTS = ['день', 'дня', 'дней'];

    private static $_HOUR_VARIANTS = ['час', 'часа', 'часов'];

    private static $_MINUTE_VARIANTS = ['минуту', 'минуты', 'минут'];

    private static $_DISTANCE_FIELDS = ['y', 'm', 'd', 'h', 'i'];

    /**
     * Russian \DateTime::format.
     * @param array|TimeParams $params Params structure
     * @return string Date/time string representation
     */
    public function ruStrFTime($params = null)
    {
        //Params handle
        if ($params === null) {
            $params = new TimeParams();
        } elseif (is_array($params)) {
            $params = TimeParams::create($params);
        } else {
            $params = clone $params;
        }

        if ($params->date === null) {
            $params->date = new \DateTime();
        } else {
            $params->date = $this->_processDateTime($params->date);
        }

        if (is_string($params->timezone)) {
            $params->timezone = new \DateTimeZone($params->timezone);
        }

        if ($params->timezone) {
            $params->date->setTimezone($params->timezone);
        }

        //Format processing
        $weekday = $params->date->format('N') - 1;
        $month = $params->date->format('n') - 1;

        $prepos = $params->preposition ? self::$_DAY_NAMES[$weekday][3] : '';

        $monthIdx = $params->monthInflected ? 2 : 1;
        $dayIdx = ($params->dayInflected || $params->preposition) ? 2 : 1;

        $search = ['D', 'l', 'M', 'F'];
        $replace = [
            $prepos . self::$_DAY_NAMES[$weekday][0],
            $prepos . self::$_DAY_NAMES[$weekday][$dayIdx],
            self::$_MONTH_NAMES[$month][0],
            self::$_MONTH_NAMES[$month][$monthIdx],
        ];

        //for russian typography standard,
        //1 April 2007, but 01.04.2007
        if (str_contains($params->format, 'F') || str_contains($params->format, 'M')) {
            $search[] = 'd';
            $replace[] = 'j';
        }

        $params->format = str_replace($search, $replace, $params->format);

        //Create date/time string
        return $params->date->format($params->format);
    }

    /**
     * Process mixed format date.
     * @return \DateTime
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    private function _processDateTime(mixed$dateTime): \DateTime
    {
        if (is_numeric($dateTime)) {
            $timestamp = $dateTime;
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($timestamp);
        } elseif (empty($dateTime)) {
            throw new \InvalidArgumentException('Date/time is empty');
        } elseif (is_string($dateTime)) {
            $dateTime = new \DateTime($dateTime);
        }

        if (! ($dateTime instanceof \DateTime)) {
            throw new \InvalidArgumentException('Incorrect date/time type');
        }

        return $dateTime;
    }

    /**
     * Represents distance of time in words.
     * @param string|int|\DateTime $toTime Source time
     * @param string|int|\DateTime $fromTime Target time
     * @param int $accuracy Level of accuracy (year, month, day, hour, minute), default=year
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return string Distance of time in words
     */
    public function distanceOfTimeInWords($toTime, $fromTime = null, $accuracy = RUtils::ACCURACY_YEAR)
    {
        $accuracy = (int) $accuracy;

        if ($accuracy < 1 || $accuracy > 5) {
            throw new \InvalidArgumentException('Wrong accuracy value (must be 1..5)');
        }

        /* @var $toTime \DateTime */
        /* @var $fromTime \DateTime */
        /* @var $timeZone \DateTimeZone */
        /* @var $fromCurrent bool */
        [$toTime, $fromTime, $timeZone, $fromCurrent] = $this->_processFunctionParams($toTime, $fromTime);
        $interval = $toTime->diff($fromTime);

        //if diff less than one minute
        if ($interval->days == 0 && $interval->h == 0 && $interval->i == 0) {
            if ($interval->invert) {
                $result = 'менее чем через минуту';
            } else {
                $result = 'менее минуты назад';
            }

            return $result;
        }

        //create distance table
        $distanceData = $this->_createDistanceData($interval);
        $words = $this->_getResultWords($accuracy, $distanceData);

        //check short result
        if ($fromCurrent && min($accuracy, count($words)) == 1) {
            //if diff expressed in one word
            $result = $this->_getOneWordResult($interval);

            if ($result) {
                return $result;
            }

            if ($interval->days < 3) {
                //if diff 1 or 2 days
                $result = $this->_getTwoDaysResult($interval, $toTime, $timeZone);

                if ($result) {
                    return $result;
                }
            }
        }

        //general case
        $result = implode(', ', $words);

        return $this->_addResultSuffix($interval, $result);
    }

    private function _processFunctionParams($toTime, $fromTime)
    {
        $toTime = $this->_processDateTime($toTime);
        $timeZone = $toTime->getTimezone();

        $fromCurrent = false;

        if ($fromTime === null) {
            $fromTime = new \DateTime('now', $timeZone);
            $fromCurrent = true;
        } else {
            $fromTime = $this->_processDateTime($fromTime);
        }

        return [$toTime, $fromTime, $timeZone, $fromCurrent];
    }


    /**
     * @return array<string, ?string>
     */
    private function _createDistanceData(\DateInterval $interval): array
    {
        $distanceData = []; //table of word representations
        $numeral = RUtils::numeral();

        $years = $interval->y;

        if ($years) {
            $distanceData['y'] = $numeral->getPlural($years, self::$_YEAR_VARIANTS);
        }

        $months = $interval->m;

        if ($months) {
            $distanceData['m'] = $numeral->getPlural($months, self::$_MONTH_VARIANTS);
        }

        $days = $interval->d;

        if ($days) {
            $distanceData['d'] = $numeral->getPlural($days, self::$_DAY_VARIANTS);
        }

        $hours = $interval->h;

        if ($hours) {
            $distanceData['h'] = $numeral->getPlural($hours, self::$_HOUR_VARIANTS);
        }

        $minutes = $interval->i;

        if ($minutes) {
            $distanceData['i'] = $numeral->getPlural($minutes, self::$_MINUTE_VARIANTS);
        }

        return $distanceData;
    }

    /**
     * @return list<array|int>
     */
    private function _getYearResult(array $distanceData): array
    {
        return $this->_getLevelResult('y', $distanceData);
    }

    /**
     * @return list<array|int>
     */
    private function _getMonthResult(array $distanceData): array
    {
        [$words, $borderField] = $this->_getYearResult($distanceData);

        return $this->_getLevelResult('m', $distanceData, $words, $borderField);
    }

    /**
     * @return list<array|int>
     */
    private function _getDaysResult(array $distanceData): array
    {
        list($words, $borderField) = $this->_getMonthResult($distanceData);

        return $this->_getLevelResult('d', $distanceData, $words, $borderField);
    }

    /**
     * @return list<array|int>
     */
    private function _getHoursResult(array $distanceData): array
    {
        [$words, $borderField] = $this->_getDaysResult($distanceData);

        return $this->_getLevelResult('h', $distanceData, $words, $borderField);
    }

    /**
     * @return list<array|int>
     */
    private function _getMinutesResult(array $distanceData): array
    {
        [$words, $borderField] = $this->_getHoursResult($distanceData);

        return $this->_getLevelResult('i', $distanceData, $words, $borderField);
    }

    /**
     * @return list<array|int>
     */
    private function _getLevelResult($fieldCode, array $distanceData, array $words = [], $borderField = -1): array
    {
        $curPos = array_search($fieldCode, self::$_DISTANCE_FIELDS);

        if ($borderField >= $curPos) {
            return [$words, $borderField];
        }

        $nextField = $borderField + 1;
        $length = count(self::$_DISTANCE_FIELDS);

        for ($i = $nextField; $i < $length; $i++) {
            $field = self::$_DISTANCE_FIELDS[$i];

            if ($borderField != -1 && $i > $curPos) {
                break;
            }

            if (isset($distanceData[$field])) {
                $words[] = $distanceData[$field];
                $borderField = $i;

                break;
            }
        }

        return [$words, $borderField];
    }

    private function _getOneWordResult(\DateInterval $interval): ?string
    {
        $result = null;

        if ($interval->days == 0 && $interval->h == 0 && $interval->i == 1) {
            $result = 'минуту';
        } elseif ($interval->days == 0 && $interval->h == 1) {
            $result = 'час';
        } elseif ($interval->y == 0 && $interval->m == 1) {
            $result = 'месяц';
        } elseif ($interval->y == 1) {
            $result = 'год';
        }

        if ($result) {
            $result = $this->_addResultSuffix($interval, $result);
        }

        return $result;
    }

    /**
     * Add suffix or Postfix to string.
     */
    private function _addResultSuffix(\DateInterval $interval, string $result): string
    {
        return $interval->invert ? self::$PREFIX_IN . "\xC2\xA0" . $result : $result . "\xC2\xA0" . self::$SUFFIX_AGO;
    }

    /**
     * @throws \Exception
     */
    private function _getTwoDaysResult(\DateInterval $interval, \DateTime $toTime, \DateTimeZone $timeZone = null): string
    {
        $result = null;
        $days = $interval->days;

        if ($interval->invert == 0 && ($days == 1 || $days == 2)) {
            $variant = $days - 1;
            $result = self::$_PAST_ALTERNATIVES[$variant];
        } elseif ($interval->invert && ($days == 0 || $days == 1)) {
            $tomorrow = new \DateTime('today', $timeZone);
            $tomorrow->add(new \DateInterval('P1D'));
            $afterTomorrow = new \DateTime('today', $timeZone);
            $afterTomorrow->add(new \DateInterval('P2D'));

            if ($toTime >= $tomorrow && $toTime < $afterTomorrow) {
                $result = 'завтра';
            } elseif ($days == 1 && $toTime >= $afterTomorrow) {
                $result = 'послезавтра';
            }
        }

        return $result;
    }

    /** @return array<> */
    private function _getResultWords($accuracy, $distanceData): array
    {
        /** @var array $words */
        [$words] = match ($accuracy) {
            RUtils::ACCURACY_YEAR => $this->_getYearResult($distanceData),
            RUtils::ACCURACY_MONTH => $this->_getMonthResult($distanceData),
            RUtils::ACCURACY_DAY => $this->_getDaysResult($distanceData),
            RUtils::ACCURACY_HOUR => $this->_getHoursResult($distanceData),
            RUtils::ACCURACY_MINUTE => $this->_getMinutesResult($distanceData),
            default => throw new \RuntimeException("Unexpected accuracy level: $accuracy"),
        };

        return $words;
    }

    /**
     * Calculates age.
     * @param string|int|\DateTime $birthDate Date of birth
     * @throws \InvalidArgumentException
     * @return int Full years age
     */
    public function getAge($birthDate)
    {
        $birthDate = $this->_processDateTime($birthDate);
        $interval = $birthDate->diff(new \DateTime());

        if ($interval->invert) {
            throw new \InvalidArgumentException('Birth date is in future');
        }

        return $interval->y;
    }
}
