<?php

namespace Simplydigital\SchengenCalculator\Services;

use InvalidArgumentException;

class CountryCodeValidator
{
    /**
     * ISO 3166-1 alpha-2 country and territory codes.
     *
     * This is a validation list, not a list of Schengen countries.
     */
    private const ISO_CODES = '
        AD AE AF AG AI AL AM AO AQ AR AS AT AU AW AX AZ
        BA BB BD BE BF BG BH BI BJ BL BM BN BO BQ BR BS BT BV BW BY BZ
        CA CC CD CF CG CH CI CK CL CM CN CO CR CU CV CW CX CY CZ
        DE DJ DK DM DO DZ
        EC EE EG EH ER ES ET
        FI FJ FK FM FO FR
        GA GB GD GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY
        HK HM HN HR HT HU
        ID IE IL IM IN IO IQ IR IS IT JE JM JO JP
        KE KG KH KI KM KN KP KR KW KY KZ
        LA LB LC LI LK LR LS LT LU LV LY
        MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT MU MV MW MX MY MZ
        NA NC NE NF NG NI NL NO NP NR NU NZ
        OM
        PA PE PF PG PH PK PL PM PN PR PS PT PW PY
        QA
        RE RO RS RU RW
        SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR SS ST SV SX SY SZ
        TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ
        UA UG UM US UY UZ
        VA VC VE VG VI VN VU
        WF WS
        YE YT
        ZA ZM ZW
    ';

    /**
     * Validate and normalise an ISO alpha-2 country code.
     */
    public function normalize(string $countryCode): string
    {
        $countryCode = strtoupper(trim($countryCode));

        if (! preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw new InvalidArgumentException(
                'Country code must be a two-letter ISO 3166-1 alpha-2 code.'
            );
        }

        if (! \in_array($countryCode, $this->all(), true)) {
            throw new InvalidArgumentException(
                "Unknown ISO 3166-1 alpha-2 country code: {$countryCode}."
            );
        }

        return $countryCode;
    }

    /**
     * Determine whether a code is recognised.
     */
    public function isValid(string $countryCode): bool
    {
        try {
            $this->normalize($countryCode);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Return all supported ISO alpha-2 codes.
     */
    public function all(): array
    {
        return preg_split(
            '/\s+/',
            trim(self::ISO_CODES),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
    }
}