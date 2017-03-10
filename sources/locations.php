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
 * @package    core
 */

/**
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__locations()
{
    global $COUNTRY_LIST;
    $COUNTRY_LIST = array(
        'Africa' => array(
            'DZ' => 'Algeria',
            'AO' => 'Angola',
            'BJ' => 'Benin',
            'BW' => 'Botswana',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'CM' => 'Cameroon',
            'CV' => 'Cape Verde',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CD' => 'Congo (Dem. Rep.)',
            'CI' => 'Ivory Coast',
            'DJ' => 'Djibouti',
            'EG' => 'Egypt',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'ET' => 'Ethiopia',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GH' => 'Ghana',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'KE' => 'Kenya',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'ML' => 'Mali',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'NA' => 'Namibia',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'RW' => 'Rwanda',
            'RE' => (get_charset() == 'utf-8') ? 'Réunion' : 'Reunion',
            'SH' => 'Saint Helena',
            'SN' => 'Senegal',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'SD' => 'Sudan',
            'SZ' => 'Swaziland',
            'ST' => (get_charset() == 'utf-8') ? 'São Tomé and Príncipe' : 'Sao Tome and Principe',
            'TZ' => 'Tanzania',
            'TG' => 'Togo',
            'TN' => 'Tunisia',
            'UG' => 'Uganda',
            'EH' => 'Western Sahara',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        ),
        'Americas' => array(
            'AI' => 'Anguilla',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AW' => 'Aruba',
            'BS' => 'Bahamas',
            'BB' => 'Barbados',
            'BZ' => 'Belize',
            'BM' => 'Bermuda',
            'BO' => 'Bolivia',
            'BR' => 'Brazil',
            'VG' => 'British Virgin Islands',
            'CA' => 'Canada',
            'KY' => 'Cayman Islands',
            'CL' => 'Chile',
            'CO' => 'Colombia',
            'CR' => 'Costa Rica',
            'CU' => 'Cuba',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'SV' => 'El Salvador',
            'FK' => 'Falkland Islands',
            'GF' => 'French Guiana',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GT' => 'Guatemala',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HN' => 'Honduras',
            'JM' => 'Jamaica',
            'MQ' => 'Martinique',
            'MX' => 'Mexico',
            'MS' => 'Montserrat',
            'AN' => 'Netherlands Antilles',
            'NI' => 'Nicaragua',
            'PA' => 'Panama',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PR' => 'Puerto Rico',
            'BL' => (get_charset() == 'utf-8') ? 'Saint Barthélemy' : 'Saint Barthelemy',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'MF' => 'Saint Martin',
            'PM' => 'Saint Pierre and Miquelon',
            'VC' => 'Saint Vincent and The Grenadines',
            'SR' => 'Suriname',
            'TT' => 'Trinidad and Tobago',
            'TC' => 'Turks and Caicos Islands',
            'VI' => 'Virgin Islands of the United States',
            'US' => 'United States of America',
            'UY' => 'Uruguay',
            'VE' => 'Venezuela',
        ),
        'Asia' => array(
            'AF' => 'Afghanistan',
            'AM' => 'Armenia',
            'AZ' => 'Azerbaijan',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BT' => 'Bhutan',
            'BN' => 'Brunei',
            'KH' => 'Cambodia',
            'CN' => 'China',
            'CY' => 'Cyprus',
            'GE' => 'Georgia',
            'HK' => 'Hong Kong SAR China',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IL' => 'Israel',
            'JP' => 'Japan',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Laos',
            'LB' => 'Lebanon',
            'MO' => 'Macau SAR China',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'MN' => 'Mongolia',
            'MM' => 'Myanmar',
            'NP' => 'Nepal',
            'NT' => 'Neutral Zone',
            'KP' => 'Korea (North)',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PS' => 'Palestine',
            'YD' => 'People\'s Democratic Republic of Yemen',
            'PH' => 'Philippines',
            'QA' => 'Qatar',
            'SA' => 'Saudi Arabia',
            'SG' => 'Singapore',
            'KR' => 'Korea (South)',
            'LK' => 'Sri Lanka',
            'SY' => 'Syria',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TH' => 'Thailand',
            'TL' => 'East Timor', //,'Timor-Leste',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'AE' => 'United Arab Emirates',
            'UZ' => 'Uzbekistan',
            'VN' => 'Vietnam',
            'YE' => 'Yemen',
        ),
        'Europe' => array(
            'AL' => 'Albania',
            'AD' => 'Andorra',
            'AT' => 'Austria',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BA' => 'Bosnia and Herzegovina',
            'BG' => 'Bulgaria',
            'HR' => 'Croatia',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DD' => 'East Germany',
            'EE' => 'Estonia',
            'FO' => 'Faroe Islands',
            'FI' => 'Finland',
            'FR' => 'France',
            'DE' => 'Germany',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GG' => 'Guernsey and Alderney',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IE' => 'Ireland',
            'IM' => 'Isle of Man',
            'IT' => 'Italy',
            'JE' => 'Jersey',
            'LV' => 'Latvia',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MK' => 'Macedonia',
            'MT' => 'Malta',
            'FX' => 'Metropolitan France',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'ME' => 'Montenegro',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'RU' => 'Russia',
            'SM' => 'San Marino',
            'RS' => 'Serbia',
            'CS' => 'Serbia and Montenegro',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'ES' => 'Spain',
            'SJ' => 'Svalbard and Jan Mayen',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'UA' => 'Ukraine',
            'SU' => 'Union of Soviet Socialist Republics',
            'GB' => 'United Kingdom',
            'VA' => 'Vatican City',
            'AX' => (get_charset() == 'utf-8') ? 'Åland Islands' : 'Aland Islands',
            'XK' => 'Kosovo', // Unofficial, http://geonames.wordpress.com/2010/03/08/xk-country-code-for-kosovo/. Currently not fully UN recognised, UN-controlled ex-part of Serbia
        ),
        'Oceania' => array(
            'AS' => 'American Samoa',
            'AQ' => 'Antarctica',
            'AU' => 'Australia',
            'BV' => 'Bouvet Island',
            'IO' => 'British Indian Ocean Territory',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos [Keeling] Islands',
            'CK' => 'Cook Islands',
            'FJ' => 'Fiji Islands',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GU' => 'Guam',
            'HM' => 'Heard Island and McDonald Islands',
            'KI' => 'Kiribati',
            'MH' => 'Marshall Islands',
            'FM' => 'Micronesia',
            'NR' => 'Nauru',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'MP' => 'Northern Mariana Islands',
            'PW' => 'Palau',
            'PG' => 'Papua New Guinea',
            'PN' => 'Pitcairn Islands',
            'WS' => 'Samoa',
            'SB' => 'Solomon Islands',
            'GS' => 'South Georgia and the South Sandwich Islands',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TV' => 'Tuvalu',
            'UM' => 'United States Minor Outlying Islands',
            'VU' => 'Vanuatu',
            'WF' => 'Wallis and Futuna',
        ),
    );
}

/**
 * Find structure of continents and countries.
 *
 * @return array Structure of continents and countries
 */
function find_continents_and_countries()
{
    global $COUNTRY_LIST;
    return $COUNTRY_LIST;
}

/**
 * Find list of continents.
 *
 * @return array List of continents
 */
function find_continents()
{
    global $COUNTRY_LIST;
    return array_keys($COUNTRY_LIST);
}

/**
 * Find list of countries.
 *
 * @return array List of countries
 */
function find_countries()
{
    global $COUNTRY_LIST;
    $countries = array();
    foreach ($COUNTRY_LIST as $continent => $_countries) {
        $countries += $_countries;
    }
    asort($countries);
    return $countries;
}

/**
 * Find continent of a country.
 *
 * @param  string $country ISO country code
 * @return ?string Continent (null: not found)
 */
function find_continent($country)
{
    static $cache = array();

    if (isset($cache[$country])) {
        return $cache[$country];
    }

    global $COUNTRY_LIST;
    foreach ($COUNTRY_LIST as $continent => $countries) {
        if (in_array($country, $countries)) {
            $cache[$country] = $continent;
            return $continent;
        }
    }

    return null;
}

/**
 * Find the ISO country code from a country name.
 *
 * @param  string $country Country name
 * @return ?string ISO country code (null: not found)
 */
function find_iso_country_from_name($country)
{
    static $cache = array();

    if (isset($cache[$country])) {
        return $cache[$country];
    }

    global $COUNTRY_LIST;
    foreach ($COUNTRY_LIST as $countries) {
        $code = array_search($country, $countries);
        if ($code !== false) {
            $cache[$country] = $code;
            return $code;
        }
    }

    return null;
}

/**
 * Find the country name of an ISO country code.
 *
 * @param  string $iso ISO country code
 * @return ?string Country name (null: not found)
 */
function find_country_name_from_iso($iso)
{
    static $cache = array();

    if (isset($cache[$iso])) {
        return $cache[$iso];
    }

    global $COUNTRY_LIST;
    foreach ($COUNTRY_LIST as $continent) {
        if (isset($continent[$iso])) {
            $cache[$iso] = $continent[$iso];
            return $continent[$iso];
        }
    }

    return null;
}

/**
 * Get a nice, formatted XHTML list of countries
 *
 * @param  ?array $selected_countries The currently selected countries (null: none selected)
 * @return Tempcode The list of countries
 */
function create_country_selection_list($selected_countries = null) // TODO: In v11 make it default to array()
{
    $continents_and_countries = find_continents_and_countries();

    $list_groups = new Tempcode();
    foreach ($continents_and_countries as $continent => $countries) {
        $list = new Tempcode();
        foreach ($countries as $country_code => $country_name) {
            $list->attach(form_input_list_entry($country_code, ($selected_countries !== null) && in_array($country_code, $selected_countries), $country_name));
        }
        $list_groups->attach(form_input_list_group($continent, $list));
    }
    return $list_groups;
}

/**
 * Get a nice, formatted XHTML list of regions
 *
 * @param  ?array $selected_regions The currently selected regions (null: none selected)
 * @return Tempcode The list of regions
 */
function create_region_selection_list($selected_regions = null) // TODO: In v11 make it default to array()
{
    return create_country_selection_list($regions); // The standard implementation is for a region to just be a country
}

/**
 * Get a nice, formatted XHTML list of USA states
 *
 * @param  ?array $selected_states The currently selected states (null: none selected)
 * @return Tempcode The list of states
 */
function create_usa_state_selection_list($selected_states = null) // TODO: In v11 make it default to array()
{
    $state_list = new Tempcode();

    $state_choices = array(
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AS' => 'American Samoa',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'DC' => 'District of Columbia',
        'FM' => 'Federated States of Micronesia',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'GU' => 'Guam',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MH' => 'Marshall Islands',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'MP' => 'Northern Mariana Islands',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PW' => 'Palau',
        'PA' => 'Pennsylvania',
        'PR' => 'Puerto Rico',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VI' => 'Virgin Islands',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    );
    foreach ($state_choices as $state_code => $state_title) {
        $state_list->attach(form_input_list_entry($state_code, ($selected_states !== null) && in_array($state_code, $selected_states), $state_title));
    }

    return $state_list;
}

/**
 * Find the active region for the current user.
 * Function likely to be overridden if a region scheme more complex than ISO countries is in use. E.g. to detect via considering state CPF also.
 *
 * @return ?string The active region (null: none found, unfiltered)
 */
function get_region()
{
    $region = get_param_string('keep_region', null);
    if ($region !== null) {
        if ($region == '') {
            return null;
        }
        return $region;
    }

    return get_country();
}

/**
 * Find the active ISO country for the current user.
 *
 * @return ?string The active region (null: none found, unfiltered)
 */
function get_country()
{
    $country = get_param_string('keep_country', null);
    if ($country !== null) {
        if ($country == '') {
            return null;
        }
        return $country;
    }

    if (!is_guest()) {
        $country = get_cms_cpf('country');
        if (!empty($country)) {
            return $country;
        }
    }

    if (addon_installed('stats')) {
        $country = geolocate_ip();
        if ($country !== null) {
            return $country;
        }
    }

    return null;
}

/**
 * Find the country an IP address long is located in
 *
 * @param  ?IP $ip The IP to geolocate (null: current user's IP)
 * @return ?string The country initials (null: unknown)
 */
function geolocate_ip($ip = null)
{
    static $result = array();

    if (is_null($ip)) {
        $ip = get_ip_address();
    }

    if (array_key_exists($ip, $result)) {
        return $result[$ip];
    }

    if (!addon_installed('stats')) {
        $result[$ip] = null;
        return null;
    }

    $long_ip = ip2long($ip);
    if ($long_ip === false) {
        $result[$ip] = null;
        return null; // No IP6 support
    }

    if (running_script('install')) {
        $result[$ip] = null;
        return null;
    }

    $query = 'SELECT * FROM ' . get_table_prefix() . 'ip_country WHERE begin_num<=' . sprintf('%u', $long_ip) . ' AND end_num>=' . sprintf('%u', $long_ip);
    $results = $GLOBALS['SITE_DB']->query($query);

    if (!array_key_exists(0, $results)) {
        $result[$ip] = null;
    } elseif (!is_null($results[0]['country'])) {
        $result[$ip] = $results[0]['country'];
    } else {
        $result[$ip] = null;
    }

    return $result[$ip];
}

/**
 * Get a region inputter
 *
 * @param  ?array $regions The currently selected regions (null: none selected)
 * @return Tempcode The region inputter
 */
function form_input_regions($regions = null)
{
    require_code('form_templates');
    $list_groups = create_region_selection_list($regions);
    return form_input_multi_list(do_lang_tempcode('FILTER_REGIONS'), do_lang_tempcode('DESCRIPTION_FILTER_REGIONS'), 'regions', $list_groups, null, 10);
}

/**
 * Get SQL to add to wider SQL query, for region filtering
 *
 * @param  string $content_type The content type
 * @param  string $field_name_to_join Field name for content ID in table being connected to
 * @param  ?string $region Region to show for (null: auto-detect)
 * @return string SQL
 */
function sql_region_filter($content_type, $field_name_to_join, $region = null)
{
    if (is_null($region)) {
        $region = get_region();
    }
    if (is_null($region)) {
        return '';
    }
    $ret = ' AND (';
    $ret .= 'NOT EXISTS(SELECT * FROM ' . get_table_prefix() . 'content_regions cr WHERE ' . db_cast($field_name_to_join, 'CHAR') . '=cr.content_id AND ' . db_string_equal_to('content_type', $content_type) . ')';
    $ret .= ' OR ';
    $ret .= 'EXISTS(SELECT * FROM ' . get_table_prefix() . 'content_regions cr WHERE ' . db_cast($field_name_to_join, 'CHAR') . '=cr.content_id AND ' . db_string_equal_to('cr.region', $region) . ' AND ' . db_string_equal_to('content_type', $content_type) . ')';
    $ret .= ')';
    return $ret;
}
