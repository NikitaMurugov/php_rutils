<?php

namespace PhpRutils;

/**
 * Rules for Typo::typography
 * Class TypoRules.
 */
class TypoRules
{
    //Clean double spaces, trailing spaces, heading spaces,
    const CLEAN_SPACES = 'CleanSpaces';

    //Replace three dots to ellipsis
    const ELLIPSIS = 'Ellipsis';

    //Replace space between initials and surname by thin space
    const INITIALS = 'Initials';

    //Replace dash to long/medium dashes
    const DASHES = 'Dashes';

    //Glue (set nonbreakable space) short words with word before/after
    const WORD_GLUE = 'WordGlue';

    //Replace +-, (c), (tm), (r), (p), etc by its typographic equivalents
    const MARKS = 'Marks';

    //Replace quotes by typographic quotes
    const QUOTES = 'Quotes';

    //Standard rules: quotes, marks, dashes, clean spaces
    public static array $STANDARD_RULES = [self::QUOTES, self::MARKS, self::DASHES, self::CLEAN_SPACES];

    //Standard rules: quotes, marks, word glue, dashes, initials, ellipsis, clean spaces
    public static array $EXTENDED_RULES = [
        self::DASHES,
        self::QUOTES,
        self::MARKS,
        self::ELLIPSIS,
        self::CLEAN_SPACES,
        self::INITIALS,
        self::WORD_GLUE,
    ];
}
