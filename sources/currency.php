<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    ecommerce
 */

/**
 * Convert a country code to a currency code.
 *
 * @param  ID_TEXT $country The country code.
 * @return ID_TEXT The currency code.
 */
function country_to_currency($country)
{
    $map = get_currency_map();
    $currency = null;
    foreach ($map as $tmp_currency => $countries) {
        if (in_array($country, $countries)) {
            $currency = $tmp_currency;
            break;
        }
    }
    return $currency;
}

/**
 * Find the active ISO currency for the current user.
 *
 * @return string The active currency
 */
function get_currency()
{
    // Perform a preferential guessing sequence
    // ========================================

    // keep_currency
    $currency = get_param_string('keep_currency', null);
    if (is_null($currency)) {
        // a specially named custom profile field for the currency.
        $currency = get_cms_cpf('currency');
        if ($currency === '') {
            $currency = null;
        }
        if (is_null($currency)) {
            require_code('locations');

            $country = get_country();
            if (is_null($country)) {
                $currency = get_option('currency');
            } else {
                $currency = country_to_currency($country);
            }
        }
    }

    return $currency;
}

/**
 * Perform a currency conversion.
 *
 * @param  mixed $amount The starting amount (integer or float).
 * @param  ID_TEXT $from_currency The start currency code.
 * @param  ?ID_TEXT $to_currency The end currency code (null: unknown, guess it).
 * @param  boolean $string Whether to get as a string.
 * @return ?mixed The new amount as float, or if $string then as a string (null: failed to do it).
 */
function currency_convert($amount, $from_currency, $to_currency = null, $string = false)
{
    // Check data
    $from_currency = strtoupper($from_currency);
    $map = get_currency_map();
    if (!array_key_exists($from_currency, $map)) {
        return null;
    }

    if (is_null($to_currency)) {
        $to_currency = get_currency();
    }

    // (We now know $to_currency)

    // We'll use Google as a simple web service
    if ($from_currency == $to_currency) {
        $new_amount = is_integer($amount) ? floatval($amount) : $amount;
    } else {
        $cache_key = 'currency_' . $from_currency . '_' . $to_currency . (is_float($amount) ? float_to_raw_string($amount) : strval($amount));
        $_new_amount = get_value_newer_than($cache_key, time() - 60 * 60 * 24 * 2, true);
        $new_amount = is_null($_new_amount) ? null : floatval($_new_amount);
        if (is_null($new_amount)) {
            $GLOBALS['SITE_DB']->query('DELETE FROM ' . get_table_prefix() . 'values_elective WHERE the_name LIKE \'' . db_encode_like('currency\_%') . '\' AND date_and_time<' . strval(time() - 60 * 60 * 24 * 2)); // Cleanup

            $google_url = 'http://www.google.com/finance/converter?a=' . (is_float($amount) ? float_to_raw_string($amount) : strval($amount)) . '&from=' . urlencode($from_currency) . '&to=' . urlencode(strtoupper($to_currency));
            $result = http_download_file($google_url, null, false);
            if (is_string($result)) {
                $matches = array();

                for ($i = 0; $i < strlen($result); $i++) { // bizarre unicode characters coming back from Google
                    if (ord($result[$i]) > 127) {
                        $result[$i] = ' ';
                    }
                }
                if (preg_match('#<span class=bld>([\d\., ]+) [A-Z]+</span>#U', $result, $matches) != 0) { // e.g. <b>1400 British pounds = 2 024.4 U.S. dollars</b>
                    $new_amount = floatval(str_replace(',', '', str_replace(' ', '', $matches[1])));

                    set_value($cache_key, float_to_raw_string($new_amount), true);
                } else {
                    return null;
                }
            } else { // no-can-do
                $new_amount = is_integer($amount) ? floatval($amount) : $amount;
                $to_currency = $from_currency;
            }
        }
    }

    if ($string) {
        list($symbol, $has_primacy) = get_currency_symbol($to_currency);
        $ret = $symbol;
        $ret .= escape_html(float_format($new_amount)) . '&nbsp;';
        if (!$has_primacy) {
            $ret .= escape_html($to_currency);
        }
        return $ret;
    }

    return $new_amount;
}

/**
 * Get the symbol for a currency.
 *
 * @param  ID_TEXT $currency The currency.
 * @return array A pair: The symbol, and whether the symbol is okay to use on its own (as it is the accepted default for the symbol).
 */
function get_currency_symbol($currency)
{
    $ret = '';
    if (in_array($currency, array('USD', 'AUD', 'CAD', 'SRD', 'SBD', 'SGD', 'NZD', 'NAD', 'MXN', 'LRD', 'GYD', 'FJD', 'SVC', 'XCD', 'COP', 'CLP', 'KYD', 'BND', 'BMD', 'BBD', 'BSD', 'ARS'))) {
        $ret .= '$';
    } elseif (in_array($currency, array('GBP', 'SHP', 'LBP', 'JEP', 'GGP', 'GIP', 'FKP', 'EGP'))) {
        $ret .= '&pound;';
    } elseif (in_array($currency, array('JPY'))) {
        $ret .= '&yen;';
    } elseif (in_array($currency, array('EUR'))) {
        $ret .= '&euro;';
    }

    $has_primacy = ($currency == 'USD' || $currency == 'GBP' || $currency == 'JPY' || $currency == 'EUR');

    return array($ret, $has_primacy);
}

/**
 * Get the currency map.
 *
 * @return array The currency map, currency code, to an array of country codes.
 */
function get_currency_map()
{
    return array
    (
        'AED' => array
        (
            'AE'
        ),

        'AFA' => array
        (
            'AF'
        ),

        'ALL' => array
        (
            'AL'
        ),

        'AMD' => array
        (
            'AM'
        ),

        'ANG' => array
        (
            'AN'
        ),

        'AOK' => array
        (
            'AO'
        ),

        'AON' => array
        (
            'AO'
        ),

        'ARA' => array
        (
            'AR'
        ),

        'ARP' => array
        (
            'AR'
        ),

        'ARS' => array
        (
            'AR'
        ),

        'AUD' => array
        (
            'AU',
            'CX',
            'CC',
            'HM',
            'KI',
            'NR',
            'NF',
            'TV'
        ),

        'AWG' => array
        (
            'AW'
        ),

        'AZM' => array
        (
            'AZ'
        ),

        'BAM' => array
        (
            'BA'
        ),

        'BBD' => array
        (
            'BB'
        ),

        'BDT' => array
        (
            'BD'
        ),

        'BGL' => array
        (
            'BG'
        ),

        'BHD' => array
        (
            'BH'
        ),

        'BIF' => array
        (
            'BI'
        ),

        'BMD' => array
        (
            'BM'
        ),

        'BND' => array
        (
            'BN'
        ),

        'BOB' => array
        (
            'BO'
        ),

        'BOP' => array
        (
            'BO'
        ),

        'BRC' => array
        (
            'BR'
        ),

        'BRL' => array
        (
            'BR'
        ),

        'BRR' => array
        (
            'BR'
        ),

        'BSD' => array
        (
            'BS'
        ),

        'BTN' => array
        (
            'BT'
        ),

        'BWP' => array
        (
            'BW'
        ),

        'BYR' => array
        (
            'BY'
        ),

        'BZD' => array
        (
            'BZ'
        ),

        'CAD' => array
        (
            'CA'
        ),

        'CDZ' => array
        (
            'CD',
            'ZR'
        ),

        'CHF' => array
        (
            'LI',
            'CH'
        ),

        'CLF' => array
        (
            'CL'
        ),

        'CLP' => array
        (
            'CL'
        ),

        'CNY' => array
        (
            'CN'
        ),

        'COP' => array
        (
            'CO'
        ),

        'CRC' => array
        (
            'CR'
        ),

        'CSD' => array
        (
            'CS'
        ),

        'CUP' => array
        (
            'CU'
        ),

        'CVE' => array
        (
            'CV'
        ),

        'CYP' => array
        (
            'CY'
        ),

        'CZK' => array
        (
            'CZ'
        ),

        'DJF' => array
        (
            'DJ'
        ),

        'DKK' => array
        (
            'DK',
            'FO',
            'GL'
        ),

        'DOP' => array
        (
            'DO'
        ),

        'DZD' => array
        (
            'DZ'
        ),

        'EEK' => array
        (
            'EE'
        ),

        'EGP' => array
        (
            'EG'
        ),

        'ERN' => array
        (
            'ER'
        ),

        'ETB' => array
        (
            'ER',
            'ET'
        ),

        'EUR' => array
        (
            'AT',
            'BE',
            'FI',
            'FR',
            'DE',
            'GR',
            'IE',
            'IT',
            'LU',
            'NL',
            'PT',
            'ES',
            'AD',
            'MC',
            'CS',
            'VA',
            'SM'
        ),

        'FJD' => array
        (
            'FJ'
        ),

        'FKP' => array
        (
            'FK'
        ),

        'GBP' => array
        (
            'IO',
            'VG',
            'GS',
            'GB'
        ),

        'GEL' => array
        (
            'GE'
        ),

        'GHC' => array
        (
            'GH'
        ),

        'GIP' => array
        (
            'GI'
        ),

        'GMD' => array
        (
            'GM'
        ),

        'GNS' => array
        (
            'GN'
        ),

        'GQE' => array
        (
            'GQ'
        ),

        'GTQ' => array
        (
            'GT'
        ),

        'GWP' => array
        (
            'GW'
        ),

        'GYD' => array
        (
            'GY'
        ),

        'HKD' => array
        (
            'HK'
        ),

        'HNL' => array
        (
            'HN'
        ),

        'HRD' => array
        (
            'HR'
        ),

        'HRK' => array
        (
            'HR'
        ),

        'HTG' => array
        (
            'HT'
        ),

        'HUF' => array
        (
            'HU'
        ),

        'IDR' => array
        (
            'ID'
        ),

        'ILS' => array
        (
            'IL'
        ),

        'INR' => array
        (
            'BT',
            'IN'
        ),

        'IQD' => array
        (
            'IQ'
        ),

        'IRR' => array
        (
            'IR'
        ),

        'ISK' => array
        (
            'IS'
        ),

        'JMD' => array
        (
            'JM'
        ),

        'JOD' => array
        (
            'JO'
        ),

        'JPY' => array
        (
            'JP'
        ),

        'KES' => array
        (
            'KE'
        ),

        'KGS' => array
        (
            'KG'
        ),

        'KHR' => array
        (
            'KH'
        ),

        'KMF' => array
        (
            'KM'
        ),

        'KPW' => array
        (
            'KP'
        ),

        'KRW' => array
        (
            'KR'
        ),

        'KWD' => array
        (
            'KW'
        ),

        'KYD' => array
        (
            'KY'
        ),

        'KZT' => array
        (
            'KZ'
        ),

        'LAK' => array
        (
            'LA'
        ),

        'LBP' => array
        (
            'LB'
        ),

        'LKR' => array
        (
            'LK'
        ),

        'LRD' => array
        (
            'LR'
        ),

        'LSL' => array
        (
            'LS'
        ),

        'LSM' => array
        (
            'LS'
        ),

        'LTL' => array
        (
            'LT'
        ),

        'LVL' => array
        (
            'LA'
        ),

        'LYD' => array
        (
            'LY'
        ),

        'MAD' => array
        (
            'MA',
            'EH'
        ),

        'MDL' => array
        (
            'MD'
        ),

        'MGF' => array
        (
            'MG'
        ),

        'MKD' => array
        (
            'MK'
        ),

        'MLF' => array
        (
            'ML'
        ),

        'MMK' => array
        (
            'MM',
            'BU'
        ),

        'MNT' => array
        (
            'MN'
        ),

        'MOP' => array
        (
            'MO'
        ),

        'MRO' => array
        (
            'MR',
            'EH'
        ),

        'MTL' => array
        (
            'MT'
        ),

        'MUR' => array
        (
            'MU'
        ),

        'MVR' => array
        (
            'MV'
        ),

        'MWK' => array
        (
            'MW'
        ),

        'MXN' => array
        (
            'MX'
        ),

        'MYR' => array
        (
            'MY'
        ),

        'MZM' => array
        (
            'MZ'
        ),

        'NAD' => array
        (
            'NA'
        ),

        'NGN' => array
        (
            'NG'
        ),

        'NIC' => array
        (
            'NI'
        ),

        'NOK' => array
        (
            'AQ',
            'BV',
            'NO',
            'SJ'
        ),

        'NPR' => array
        (
            'NP'
        ),

        'NZD' => array
        (
            'CK',
            'NZ',
            'NU',
            'PN',
            'TK'
        ),

        'OMR' => array
        (
            'OM'
        ),

        'PAB' => array
        (
            'PA'
        ),

        'PEI' => array
        (
            'PE'
        ),

        'PEN' => array
        (
            'PE'
        ),

        'PGK' => array
        (
            'PG'
        ),

        'PHP' => array
        (
            'PH'
        ),

        'PKR' => array
        (
            'PK'
        ),

        'PLN' => array
        (
            'PL'
        ),

        'PYG' => array
        (
            'PY'
        ),

        'QAR' => array
        (
            'QA'
        ),

        'ROL' => array
        (
            'RO'
        ),

        'RUB' => array
        (
            'RU'
        ),

        'RWF' => array
        (
            'RW'
        ),

        'SAR' => array
        (
            'SA'
        ),

        'SBD' => array
        (
            'SB'
        ),

        'SCR' => array
        (
            'IO',
            'SC'
        ),

        'SDD' => array
        (
            'SD'
        ),

        'SDP' => array
        (
            'SD'
        ),

        'SEK' => array
        (
            'SE'
        ),

        'SGD' => array
        (
            'SG'
        ),

        'SHP' => array
        (
            'SH'
        ),

        'SIT' => array
        (
            'SI'
        ),

        'SKK' => array
        (
            'SK'
        ),

        'SLL' => array
        (
            'SL'
        ),

        'SOS' => array
        (
            'SO'
        ),

        'SRG' => array
        (
            'SR'
        ),

        'STD' => array
        (
            'ST'
        ),

        'SUR' => array
        (
            'SU'
        ),

        'SVC' => array
        (
            'SV'
        ),

        'SYP' => array
        (
            'SY'
        ),

        'SZL' => array
        (
            'SZ'
        ),

        'THB' => array
        (
            'TH'
        ),

        'TJR' => array
        (
            'TJ'
        ),

        'TMM' => array
        (
            'TM'
        ),

        'TND' => array
        (
            'TN'
        ),

        'TOP' => array
        (
            'TO'
        ),

        'TPE' => array
        (
            'TP'
        ),

        'TRL' => array
        (
            'TR'
        ),

        'TTD' => array
        (
            'TT'
        ),

        'TWD' => array
        (
            'TW'
        ),

        'TZS' => array
        (
            'TZ'
        ),

        'UAH' => array
        (
            'UA'
        ),

        'UAK' => array
        (
            'UA'
        ),

        'UGS' => array
        (
            'UG'
        ),

        'USD' => array
        (
            'AS',
            'VG',
            'EC',
            'FM',
            'GU',
            'MH',
            'MP',
            'PW',
            'PA',
            'PR',
            'TC',
            'US',
            'UM',
            'VI'
        ),

        'UYU' => array
        (
            'UY'
        ),

        'UZS' => array
        (
            'UZ'
        ),

        'VEB' => array
        (
            'VE'
        ),

        'VND' => array
        (
            'VN'
        ),

        'VUV' => array
        (
            'VU'
        ),

        'WST' => array
        (
            'WS'
        ),

        'XAF' => array
        (
            'BJ',
            'BF',
            'CM',
            'CF',
            'TD',
            'CG',
            'CI',
            'GQ',
            'GA',
            'GW',
            'ML',
            'NE',
            'SN',
            'TG'
        ),

        'XCD' => array
        (
            'AI',
            'AG',
            'VG',
            'DM',
            'GD',
            'MS',
            'KN',
            'LC',
            'VC'
        ),

        'XOF' => array
        (
            'NE',
            'SN'
        ),

        'XPF' => array
        (
            'PF',
            'NC',
            'WF'
        ),

        'YDD' => array
        (
            'YD'
        ),

        'YER' => array
        (
            'YE'
        ),

        'ZAL' => array
        (
            'ZA'
        ),

        'ZAR' => array
        (
            'LS',
            'NA',
            'ZA'
        ),

        'ZMK' => array
        (
            'ZM'
        ),

        'ZWD' => array
        (
            'ZW'
        ),
    );
}
