<?php

namespace App\Services\B2b;

/**
 * Dialling codes and national-number lengths for the B2B phone field.
 *
 * Single source of truth: the registration form renders the picker from this
 * list, the browser validates against it, and B2bAuth re-validates server-side.
 *
 * `min`/`max` count the digits AFTER the dialling code. Ranges are deliberately
 * a little loose where a country has several formats; the goal is to catch a
 * mistyped number, not to enforce a numbering plan.
 */
class B2bCountries
{
    public const DEFAULT_ISO = 'md';

    /** @return array<int, array{iso:string, dial:string, name:string, min:int, max:int}> */
    public static function all(): array
    {
        return [
            ['iso' => 'md', 'dial' => '+373', 'name' => 'Moldova',        'min' => 8,  'max' => 8],

            ['iso' => 'at', 'dial' => '+43',  'name' => 'Austria',        'min' => 10, 'max' => 13],
            ['iso' => 'by', 'dial' => '+375', 'name' => 'Belarus',        'min' => 9,  'max' => 9],
            ['iso' => 'be', 'dial' => '+32',  'name' => 'Belgia',         'min' => 8,  'max' => 9],
            ['iso' => 'bg', 'dial' => '+359', 'name' => 'Bulgaria',       'min' => 8,  'max' => 9],
            ['iso' => 'cz', 'dial' => '+420', 'name' => 'Cehia',          'min' => 9,  'max' => 9],
            ['iso' => 'cy', 'dial' => '+357', 'name' => 'Cipru',          'min' => 8,  'max' => 8],
            ['iso' => 'hr', 'dial' => '+385', 'name' => 'Croația',        'min' => 8,  'max' => 9],
            ['iso' => 'dk', 'dial' => '+45',  'name' => 'Danemarca',      'min' => 8,  'max' => 8],
            ['iso' => 'ch', 'dial' => '+41',  'name' => 'Elveția',        'min' => 9,  'max' => 9],
            ['iso' => 'ee', 'dial' => '+372', 'name' => 'Estonia',        'min' => 7,  'max' => 8],
            ['iso' => 'fi', 'dial' => '+358', 'name' => 'Finlanda',       'min' => 6,  'max' => 10],
            ['iso' => 'fr', 'dial' => '+33',  'name' => 'Franța',         'min' => 9,  'max' => 9],
            ['iso' => 'ge', 'dial' => '+995', 'name' => 'Georgia',        'min' => 9,  'max' => 9],
            ['iso' => 'de', 'dial' => '+49',  'name' => 'Germania',       'min' => 10, 'max' => 11],
            ['iso' => 'gr', 'dial' => '+30',  'name' => 'Grecia',         'min' => 10, 'max' => 10],
            ['iso' => 'ie', 'dial' => '+353', 'name' => 'Irlanda',        'min' => 9,  'max' => 9],
            ['iso' => 'is', 'dial' => '+354', 'name' => 'Islanda',        'min' => 7,  'max' => 7],
            ['iso' => 'it', 'dial' => '+39',  'name' => 'Italia',         'min' => 9,  'max' => 10],
            ['iso' => 'lv', 'dial' => '+371', 'name' => 'Letonia',        'min' => 8,  'max' => 8],
            ['iso' => 'lt', 'dial' => '+370', 'name' => 'Lituania',       'min' => 8,  'max' => 8],
            ['iso' => 'lu', 'dial' => '+352', 'name' => 'Luxemburg',      'min' => 9,  'max' => 9],
            ['iso' => 'gb', 'dial' => '+44',  'name' => 'Marea Britanie', 'min' => 10, 'max' => 10],
            ['iso' => 'mt', 'dial' => '+356', 'name' => 'Malta',          'min' => 8,  'max' => 8],
            ['iso' => 'no', 'dial' => '+47',  'name' => 'Norvegia',       'min' => 8,  'max' => 8],
            ['iso' => 'nl', 'dial' => '+31',  'name' => 'Olanda',         'min' => 9,  'max' => 9],
            ['iso' => 'pl', 'dial' => '+48',  'name' => 'Polonia',        'min' => 9,  'max' => 9],
            ['iso' => 'pt', 'dial' => '+351', 'name' => 'Portugalia',     'min' => 9,  'max' => 9],
            ['iso' => 'ro', 'dial' => '+40',  'name' => 'România',        'min' => 9,  'max' => 9],
            ['iso' => 'ru', 'dial' => '+7',   'name' => 'Rusia',          'min' => 10, 'max' => 10],
            ['iso' => 'rs', 'dial' => '+381', 'name' => 'Serbia',         'min' => 8,  'max' => 9],
            ['iso' => 'sk', 'dial' => '+421', 'name' => 'Slovacia',       'min' => 9,  'max' => 9],
            ['iso' => 'si', 'dial' => '+386', 'name' => 'Slovenia',       'min' => 8,  'max' => 8],
            ['iso' => 'es', 'dial' => '+34',  'name' => 'Spania',         'min' => 9,  'max' => 9],
            ['iso' => 'us', 'dial' => '+1',   'name' => 'SUA',            'min' => 10, 'max' => 10],
            ['iso' => 'se', 'dial' => '+46',  'name' => 'Suedia',         'min' => 7,  'max' => 9],
            ['iso' => 'tr', 'dial' => '+90',  'name' => 'Turcia',         'min' => 10, 'max' => 10],
            ['iso' => 'ua', 'dial' => '+380', 'name' => 'Ucraina',        'min' => 9,  'max' => 9],
            ['iso' => 'hu', 'dial' => '+36',  'name' => 'Ungaria',        'min' => 9,  'max' => 9],
        ];
    }

    /** @return array{iso:string, dial:string, name:string, min:int, max:int}|null */
    public static function byIso(string $iso): ?array
    {
        foreach (self::all() as $c) {
            if ($c['iso'] === $iso) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Country matching an E.164 number, longest dialling code first so +373
     * wins over +37 and +1 does not swallow +1xx.
     *
     * @return array{iso:string, dial:string, name:string, min:int, max:int}|null
     */
    public static function byNumber(string $e164): ?array
    {
        $list = self::all();
        usort($list, fn($a, $b) => strlen($b['dial']) <=> strlen($a['dial']));

        foreach ($list as $c) {
            if (strpos($e164, $c['dial']) === 0) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Brings any user input to E.164 (+373XXXXXXXX). Returns '' when the result
     * cannot be a phone number.
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return '';
        }

        if (strpos($digits, '00') === 0) {                   // 00373... -> 373...
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 9 && $digits[0] === '0') {   // 0XXXXXXXX -> 373XXXXXXXX
            $digits = '373' . substr($digits, 1);
        }
        if (strlen($digits) === 8) {                         // XXXXXXXX -> 373XXXXXXXX
            $digits = '373' . $digits;
        }

        return (strlen($digits) < 10 || strlen($digits) > 15) ? '' : '+' . $digits;
    }

    /**
     * Checks the national part against the country's expected length.
     *
     * A number whose dialling code is not in the list passes: the list covers the
     * markets we expect, and normalize() already enforces a sane overall length,
     * so an unknown country is not a reason to reject a client.
     *
     * @return array{ok: bool, error?: string}
     */
    public static function validate(string $e164): array
    {
        $country = self::byNumber($e164);
        if (!$country) {
            return ['ok' => true];
        }

        $national = strlen(substr($e164, strlen($country['dial'])));

        if ($national < $country['min'] || $national > $country['max']) {
            $expected = $country['min'] === $country['max']
                ? $country['min'] . ' cifre'
                : $country['min'] . '-' . $country['max'] . ' cifre';

            return [
                'ok'    => false,
                'error' => 'Numărul pentru ' . $country['name'] . ' trebuie să conțină ' . $expected
                         . ' după prefixul ' . $country['dial'] . '.',
            ];
        }

        return ['ok' => true];
    }
}
