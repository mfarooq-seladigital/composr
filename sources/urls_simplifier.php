<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.


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
 * Shorten a filename so it will fit in the database.
 * Also see cms_rawurlrecode.
 *
 * @param  string $filename The filename
 * @param  integer $length The length
 * @return string The shortened filename
 */
function shorten_urlencoded_filename($filename, $length = 226)
{
    if ((stripos(PHP_OS, 'WIN') === 0) && (version_compare(PHP_VERSION, '7.2', '<'))) {
        // Older versions of PHP on Windows cannot handle utf-8 filenames
        require_code('character_sets');
        $filename = transliterate_string($filename);
    }

    // Default length is... maxDBFieldSize - maxUploadDirSize - suffixingLeeWay = 255 - (7 + 1 + 23 + 1) - 6 = 230
    // (maxUploadDirSize is LEN('uploads') + LEN('/') + LEN(maxUploadSubdirSize) + LEN('/')
    // Suffixing leeway is so we can have up to ~99999 different files with the same base filename, varying by auto-generated suffixes

    $matches = array();
    if (preg_match('#^(.*)\.(.*)$#', $filename, $matches) != 0) {
        $filename_suffix = $matches[2];
        $_filename_stem = $matches[1];

        $i = 0;
        $mb_len = cms_mb_strlen($_filename_stem);
        $filename_stem = '';
        do {
            $next_mb_char = cms_mb_substr($_filename_stem, $i, 1);
            if (cms_mb_strlen(cms_rawurlrecode(urlencode($filename_stem . $next_mb_char . '.' . $filename_suffix), false, true)) > $length) {
                break;
            }
            $filename_stem .= $next_mb_char;
            $i++;
        }
        while ($i < $mb_len);

        $filename = $filename_stem . '.' . $filename_suffix;
    }
    return $filename;
}

/**
 * Remove unnecessarily paranoid URL-encoding if needed, so the given URL will fit in the database.
 *
 * @param  URLPATH $url The URL
 * @param  boolean $tolerate_errors If this is set to false then an error message will be shown if the URL is still too long after we do what we can; set to true if we have someway of further shortening the URL after this function is called
 * @return URLPATH The shortened URL
 */
function _cms_rawurlrecode($url, $tolerate_errors)
{
    $recoded = '';

    $parts = preg_split('#(%[\dA-F]{1,2})#i', $url, null, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $i => $part) {
        if ($i % 2 == 0) {
            $recoded .= $parts[$i];
        } else {
            if (hexdec(substr($parts[$i], 1)) < 128) {
                $recoded .= $parts[$i];
            } else {
                $recoded .= rawurldecode($parts[$i]);
            }
        }
    }

    if (!$tolerate_errors) {
        if (cms_mb_strlen($recoded) > 255) {
            warn_exit(do_lang_tempcode('FILENAME_TOO_LONG'));
        }
    }

    return $recoded;
}

/**
 * Class to encode/decode URLs to make them valid/readable. It is a safe operation in each direction, no amount of random conversions back/forth can corrupt.
 *
 * @package        core
 */
class HarmlessURLCoder
{
    private $protected_chars = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        foreach (array(
            chr(0),
            chr(1),
            chr(2),
            chr(3),
            chr(4),
            chr(5),
            chr(6),
            chr(7),
            chr(8),
            chr(9),
            chr(10),
            chr(11),
            chr(12),
            '%',
            '/',
            '?',
            ':',
            '&',
            '=',
            '@',
            '+',
            '$',
            ',',
            ';',
            '#',
        ) as $char) {
            $this->protected_chars[rawurlencode($char)] = $char;
        }
    }

    /**
     * URL-decode a string (whole or partial URL) to be readable.
     *
     * @param  string $str The input string
     * @return string The decoded string
     */
    public function decode($str)
    {
        // TODO: Document this new behaviour in v11 codebook
        if ((function_exists('idn_to_utf8')) && (strpos($str, '://') !== false) && (get_charset() == 'utf-8')) {
            $domain = parse_url($str,  PHP_URL_HOST);
            $_domain = idn_to_utf8($domain);
            if ($_domain !== false) {
                $str = preg_replace('#(^.*://)' . preg_quote($domain, '#') . '(.*$)#U', '$1' . $_domain . '$2', $str);
            }
        }

        if (get_value('urls_simplifier') !== '1') { // TODO: Make a proper option in v11
            return $str;
        }

        $decoded = '';

        $str = str_replace('+', ' ', $str);
        $parts = preg_split('#(%[\dA-F]{1,2})#i', $str, null, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($i % 2 == 0) {
                $decoded .= $parts[$i];
            } else {
                if (isset($this->protected_chars[$parts[$i]])) {
                    $decoded .= $parts[$i];
                } else {
                    $decoded .= rawurldecode($parts[$i]);
                }
            }
        }

        return $decoded;
    }

    /**
     * URL-encode a string (whole or partial URL) to be valid.
     *
     * @param  string $str The input string
     * @return string The encoded string
     */
    public function encode($str)
    {
        // TODO: Document this new behaviour in v11 codebook
        if ((function_exists('idn_to_ascii')) && (strpos($str, '://') !== false) && (get_charset() == 'utf-8')) {
            $domain = preg_replace('#(^.*://)([^:/]*)(.*$)#', '$2', $str);
            $_domain = @/*LEGACY @ to remove awkward temporary INTL_IDNA_VARIANT_2003 deprecation message that exists until PHP4*/idn_to_ascii($domain);
            if ($_domain !== false) {
                $str = preg_replace('#(^.*://)' . preg_quote($domain, '#') . '(.*$)#U', '${1}' . $_domain . '${2}', $str);
            }
        }

        if ((!function_exists('get_value')) || (get_value('urls_simplifier') !== '1')) { // TODO: Make a proper option in v11
            return $str;
        }

        $encoded = '';

        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = $str[$i];

            if (in_array($c, $this->protected_chars)) {
                $encoded .= $c;
            } else {
                $encoded .= rawurlencode($c);
            }
        }

        return $encoded;
    }
}
