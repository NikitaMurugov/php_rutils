<?php

declare(strict_types=1);

namespace PhpRutils;

final class RUtils
{
    //gender constants
    const MALE = 1;
    const FEMALE = 2;
    const NEUTER = 3;

    //accuracy for Dt::distanceOfTimeInWords function
    const ACCURACY_YEAR = 1;
    const ACCURACY_MONTH = 2;
    const ACCURACY_DAY = 3;
    const ACCURACY_HOUR = 4;
    const ACCURACY_MINUTE = 5;

    private static ?Numeral $_numeral;

    private static ?Dt $_dt;

    private static ?Translit $_translit;

    private static ?Typo $_typo;

    /**
     * Plural forms and in-word representation for numerals.
     */
    public static function numeral(): Numeral
    {
        if (self::$_numeral === null) {
            self::$_numeral = new Numeral();
        }

        return self::$_numeral;
    }

    /**
     * Russian dates without locales.
     */
    public static function dt(): Dt
    {
        if (self::$_dt === null) {
            self::$_dt = new Dt();
        }

        return self::$_dt;
    }

    /**
     * Simple transliteration.
     */
    public static function translit(): Translit
    {
        if (self::$_translit === null) {
            self::$_translit = new Translit();
        }

        return self::$_translit;
    }

    /**
     * Russian typography.
     */
    public static function typo(): Typo
    {
        if (self::$_typo === null) {
            self::$_typo = new Typo();
        }

        return self::$_typo;
    }

    /**
     * Format number with russian locale.
     */
    public static function formatNumber(float $number, int $decimals = 0): string
    {
        $number = number_format($number, $decimals, ',', ' ');

        return str_replace(' ', "\xE2\x80\x89", $number);
    }
}
