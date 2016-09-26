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
 * Helper function for *_text_file
 *
 * @param  string $codename The file name (without .txt)
 * @param  ?LANGUAGE_NAME $lang The language to load from (null: none) (blank: search)
 * @return string The path to the file
 *
 * @ignore
 */
function _find_text_file_path($codename, $lang)
{
    if ($lang === null) {
        $langs = array('');
    } elseif ($lang != '') {
        $langs = array($lang);
    } else {
        $langs = array(user_lang());
        if (get_site_default_lang() != user_lang()) {
            $langs[] = get_site_default_lang();
        }
        if (fallback_lang() != get_site_default_lang()) {
            $langs[] = fallback_lang();
        }
    }
    $i = 0;
    $path = '';
    do {
        $lang = $langs[$i];
        $path = get_custom_file_base() . '/text_custom/' . $lang . '/' . $codename . '.txt';
        if (!file_exists($path)) {
            $path = get_file_base() . '/text_custom/' . $lang . '/' . $codename . '.txt';
        }
        if (!file_exists($path)) {
            $path = get_file_base() . '/text/' . $lang . '/' . $codename . '.txt';
        }
        $i++;
    } while ((!file_exists($path)) && (array_key_exists($i, $langs)));
    if (!file_exists($path)) {
        $path = '';
    }

    return $path;
}

/**
 * Read a text file, using the _custom system
 *
 * @param  string $codename The file name (without .txt)
 * @param  ?LANGUAGE_NAME $lang The language to load from (null: none) (blank: search)
 * @param  boolean $missing_blank Whether to tolerate missing files
 * @return string The file contents
 */
function read_text_file($codename, $lang = null, $missing_blank = false)
{
    $path = _find_text_file_path($codename, $lang);

    $tmp = @fopen($path, 'rb');
    if ($tmp === false) {
        if ($lang !== fallback_lang()) {
            return read_text_file($codename, fallback_lang(), $missing_blank);
        }

        if ($missing_blank) {
            return '';
        }
        warn_exit(do_lang_tempcode('MISSING_TEXT_FILE', escape_html($codename), escape_html('text/' . (($lang === null) ? '' : ($lang . '/')) . $codename . '.txt')), false, true);
    }
    @flock($tmp, LOCK_SH);
    $in = @file_get_contents($path);
    @flock($tmp, LOCK_UN);
    fclose($tmp);
    $in = unixify_line_format($in);

    if (strpos($path, '_custom/') === false) {
        global $LANG_FILTER_OB;
        $in = $LANG_FILTER_OB->compile_time(null, $in, $lang);
    }

    return $in;
}

/**
 * Write a text file, using the _custom system
 *
 * @param  string $codename The file name (without .txt)
 * @param  ?LANGUAGE_NAME $lang The language to write for (null: none) (blank: search)
 * @param  string $out The data to write
 */
function write_text_file($codename, $lang, $out)
{
    $xpath = _find_text_file_path($codename, $lang);
    if ($xpath == '') {
        $xpath = get_file_base() . '/text/' . user_lang() . '/' . $codename . '.txt';
    }
    $path = str_replace(get_file_base() . '/text/', get_custom_file_base() . '/text_custom/', $xpath);

    if (!file_exists(dirname($path))) {
        require_code('files2');
        make_missing_directory(dirname($path));
    }

    $myfile = @fopen($path, GOOGLE_APPENGINE ? 'wb' : 'at');
    if ($myfile === false) {
        intelligent_write_error($path);
    }
    @flock($myfile, LOCK_EX);
    if (!GOOGLE_APPENGINE) {
        ftruncate($myfile, 0);
    }
    if (fwrite($myfile, $out) < strlen($out)) {
        warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'), false, true);
    }
    @flock($myfile, LOCK_UN);
    fclose($myfile);
    fix_permissions($path);
    sync_file($path);

    // Backup with a timestamp (useful if for example an addon update replaces changes)
    $path .= '.' . strval(time());
    $myfile = @fopen($path, GOOGLE_APPENGINE ? 'wb' : 'at');
    if ($myfile === false) {
        intelligent_write_error($path);
    }
    if (fwrite($myfile, $out) < strlen($out)) {
        warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'), false, true);
    }
    fclose($myfile);
    fix_permissions($path);
    sync_file($path);
}
