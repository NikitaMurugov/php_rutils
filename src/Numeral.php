<?php

declare(strict_types=1);

namespace PhpRutils;

/**
 * Plural forms and in-word representation for numerals
 * Class Numeral.
 */
class Numeral
{
    private static $_FRACTIONS = [
        ['десятая', 'десятых', 'десятых'],
        ['сотая', 'сотых', 'сотых'],
        ['тысячная', 'тысячных', 'тысячных'],
        ['десятитысячная', 'десятитысячных', 'десятитысячных'],
        ['стотысячная', 'стотысячных', 'стотысячных'],
        ['миллионная', 'милллионных', 'милллионных'],
        ['десятимиллионная', 'десятимилллионных', 'десятимиллионных'],
        ['стомиллионная', 'стомилллионных', 'стомиллионных'],
        ['миллиардная', 'миллиардных', 'миллиардных'],
    ]; //Forms (1, 2, 5) for fractions

    private static $_ONES = [
        ['', '', ''],
        ['один', 'одна', 'одно'],
        ['два', 'две', 'два'],
        ['три', 'три', 'три'],
        ['четыре', 'четыре', 'четыре'],
        ['пять', 'пять', 'пять'],
        ['шесть', 'шесть', 'шесть'],
        ['семь', 'семь', 'семь'],
        ['восемь', 'восемь', 'восемь'],
        ['девять', 'девять', 'девять'],
    ]; //Forms (MALE, FEMALE, NEUTER) for ones

    private static $_TENS = [
        0 => '',
        //1 - special variant
        10 => 'десять',
        11 => 'одиннадцать',
        12 => 'двенадцать',
        13 => 'тринадцать',
        14 => 'четырнадцать',
        15 => 'пятнадцать',
        16 => 'шестнадцать',
        17 => 'семнадцать',
        18 => 'восемнадцать',
        19 => 'девятнадцать',
        2 => 'двадцать',
        3 => 'тридцать',
        4 => 'сорок',
        5 => 'пятьдесят',
        6 => 'шестьдесят',
        7 => 'семьдесят',
        8 => 'восемьдесят',
        9 => 'девяносто',
    ]; //Tens

    private static $_HUNDREDS = [
        0 => '',
        1 => 'сто',
        2 => 'двести',
        3 => 'триста',
        4 => 'четыреста',
        5 => 'пятьсот',
        6 => 'шестьсот',
        7 => 'семьсот',
        8 => 'восемьсот',
        9 => 'девятьсот',
    ]; //Hundreds

    /**
     * Get proper case with value.
     * @param int $amount Amount of objects
     * @param array $variants Variants (forms) of object in such form: array('1 object', '2 objects', '5 objects')
     * @param string|null $absence If amount is zero will return it
     * @return string|null
     */
    public function getPlural($amount, array $variants, $absence = null)
    {
        if ($amount || $absence === null) {
            $result = RUtils::formatNumber($amount) . ' Numeral.php' . $this->choosePlural($amount, $variants);
        } else {
            $result = $absence;
        }

        return $result;
    }

    /**
     * Choose proper case depending on amount.
     * @param int $amount Amount of objects
     * @param string[] $variants Variants (forms) of object in such form: array('1 object', '2 objects', '5 objects')
     * @return string Proper variant
     * @throws \InvalidArgumentException Variants' length lesser than 3
     */
    public function choosePlural($amount, array $variants)
    {
        if (count($variants) < 3) {
            throw new \InvalidArgumentException('Incorrect values length (must be 3)');
        }

        $amount = abs($amount);
        $mod10 = $amount % 10;
        $mod100 = $amount % 100;

        if ($mod10 == 1 && $mod100 != 11) {
            $variant = 0;
        } elseif ($mod10 >= 2 && $mod10 <= 4 && ! ($mod100 > 10 && $mod100 < 20)) {
            $variant = 1;
        } else {
            $variant = 2;
        }

        return $variants[$variant];
    }

    /**
     * Get sum in words.
     * @param int $amount Amount of objects (0 <= amount <= PHP_INT_MAX)
     * @param int $gender Gender of object (MALE, FEMALE or NEUTER)
     * @param array $variants Variants (forms) of object in such form: array('1 object', '2 objects', '5 objects')
     * @return string In-words representation objects' amount
     * @throws \RangeException
     * @throws \InvalidArgumentException
     */
    public function sumString($amount, $gender, array $variants = null)
    {
        if ($variants === null) {
            $variants = array_fill(0, 3, '');
        }

        if (count($variants) < 3) {
            throw new \InvalidArgumentException('Incorrect items length (must be 3)');
        }

        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be positive or 0');
        }

        if ($amount == 0) {
            return trim('ноль ' . $variants[2]);
        }

        $result = '';
        $tmpVal = $amount;

        //ones
        list($result, $tmpVal) = $this->_sumStringOneOrder($result, $tmpVal, $gender, $variants);
        //thousands
        list($result, $tmpVal) = $this->_sumStringOneOrder(
            $result,
            $tmpVal,
            RUtils::FEMALE,
            ['тысяча', 'тысячи', 'тысяч']
        );
        //millions
        list($result, $tmpVal) = $this->_sumStringOneOrder(
            $result,
            $tmpVal,
            RUtils::MALE,
            ['миллион', 'миллиона', 'миллионов']
        );
        //billions
        list($result) = $this->_sumStringOneOrder(
            $result,
            $tmpVal,
            RUtils::MALE,
            ['миллиард', 'миллиарда', 'миллиардов']
        );

        return trim($result);
    }

    /**
     * Make in-words representation of single order.
     * @param string $prevResult In-words representation of lower orders
     * @param int $tmpVal Temporary value without lower orders
     * @param int $gender (MALE, FEMALE or NEUTER)
     * @param string[] $variants Variants of objects
     * @throws \RangeException
     * @return array ($result, $tmpVal)
     */
    private function _sumStringOneOrder($prevResult, $tmpVal, $gender, array $variants)
    {
        if ($tmpVal == 0) {
            return [$prevResult, $tmpVal];
        }

        $words = [];
        $fiveItems = $variants[2];
        $rest = $tmpVal % 1000;

        if ($rest < 0) {
            throw new \RangeException('Int overflow');
        }

        $tmpVal = (int) ($tmpVal / 1000);

        //check last digits are 0
        if ($rest == 0) {
            if (! $prevResult) {
                $prevResult = $fiveItems . ' ';
            }

            return [$prevResult, $tmpVal];
        }

        //hundreds
        $words[] = self::$_HUNDREDS[(int) ($rest / 100)];

        //tens
        $rest %= 100;
        $rest1 = (int) ($rest / 10);
        $words[] = ($rest1 == 1) ? self::$_TENS[$rest] : self::$_TENS[$rest1];

        //ones
        if ($rest1 == 1) {
            $endWord = $fiveItems;
        } else {
            $amount = $rest % 10;
            $words[] = self::$_ONES[$amount][$gender - 1];
            $endWord = $this->choosePlural($amount, $variants);
        }
        $words[] = $endWord;

        $words[] = $prevResult;
        $words = array_filter($words, 'strlen');

        $result = trim(implode(' ', $words));

        return [$result, $tmpVal];
    }

    /**
     * Numeral in words.
     * @param float $amount Amount of objects
     * @param int|null $gender (MALE, FEMALE, NEUTER or null)
     * @return string In-words representation of numeral
     */
    public function getInWords($amount, $gender = RUtils::MALE)
    {
        if ($amount == (int) $amount) {
            return $this->getInWordsInt($amount, $gender);
        }

        return $this->getInWordsFloat($amount);
    }

    /**
     * Integer in words.
     * @param int $amount Amount of objects (0 <= amount <= PHP_INT_MAX)
     * @param int $gender (MALE, FEMALE or NEUTER)
     * @return string In-words representation of numeral
     */
    public function getInWordsInt($amount, $gender = RUtils::MALE)
    {
        $amount = round($amount);

        return $this->sumString($amount, $gender);
    }

    /**
     * Float in words.
     * @param float $amount Amount of objects
     * @return string In-words representation of float numeral
     */
    public function getInWordsFloat($amount)
    {
        $words = [];

        $intPart = (int) $amount;
        $pointVariants = ['целая', 'целых', 'целых'];
        $words[] = $this->sumString($intPart, RUtils::FEMALE, $pointVariants);

        $remainder = $this->_getFloatRemainder($amount);
        $signs = strlen($remainder) - 1;
        $words[] = $this->sumString($remainder, RUtils::FEMALE, self::$_FRACTIONS[$signs]);

        $result = trim(implode(' ', $words));

        return $result;
    }

    /**
     * Get remainder of float, i.e. 2.05 -> '05'.
     * @param float $value
     * @param int $signs
     * @return string
     */
    private function _getFloatRemainder($value, $signs = 9)
    {
        if ($value == (int) $value) {
            return '0';
        }

        $signs = min($signs, count(self::$_FRACTIONS));
        $value = number_format($value, $signs, '.', '');
        list(, $remainder) = explode('.', $value);
        $remainder = preg_replace('/0+$/', '', $remainder);

        if (! $remainder) {
            $remainder = '0';
        }

        return $remainder;
    }

    /**
     * Get string for money (RUB).
     * @param float $amount Amount of money
     * @param bool $zeroForKopeck If false, then zero kopecks ignored
     * @return string   In-words representation of money's amount
     * @throws \InvalidArgumentException
     */
    public function getRubles($amount, $zeroForKopeck = false)
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be positive or 0');
        }

        $words = [];
        $amount = round($amount, 2);

        $iAmount = (int) $amount;

        if ($iAmount) {
            $words[] = $this->sumString(
                (int) $amount,
                RUtils::MALE,
                ['рубль', 'рубля', 'рублей']
            );
        }

        $remainder = $this->_getFloatRemainder($amount, 2);

        if ($remainder || $zeroForKopeck) {
            if ($remainder < 10 && strlen($remainder) == 1) {
                $remainder *= 10;
            }
            $words[] = $this->sumString(
                $remainder,
                RUtils::FEMALE,
                ['копейка', 'копейки', 'копеек']
            );
        }

        return trim(implode(' ', $words));
    }
}
