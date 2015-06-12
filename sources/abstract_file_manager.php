<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

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

/*
The abstract file manager allows easy and transparent file system maintenance, even when it has to be piped through FTP.
*/

/**
 * Standard code module initialisation function.
 */
function init__abstract_file_manager()
{
    require_lang('abstract_file_manager');
    require_lang('installer');
    require_code('files');

    global $AFM_FTP_CONN;
    $AFM_FTP_CONN = null;
}

/**
 * Make sure that the AFM connection details have been posted. If not, get them and loop back.
 */
function force_have_afm_details()
{
    if (is_suexec_like()) {
        set_value('uses_ftp', '0');
        return; // No need for FTP
    }

    if (get_file_base() != get_custom_file_base()) { // Shared installs are assumed to have the necessary AFM permissions where needed
        set_value('uses_ftp', '0');
        return;
    }

    if ((!function_exists('ftp_ssl_connect')) && (!function_exists('ftp_connect'))) {
        set_value('uses_ftp', '0');
        return;
    }

    $got_ftp_details = post_param_integer('got_ftp_details', 0);
    $ftp_password = get_value('ftp_password');
    if (is_null($ftp_password)) {
        $ftp_password = '';
    }
    //$uses_ftp=get_value('uses_ftp');    We can't use this because there's no reliable way to trust this is always going to be right (permissions change/differ, and we can't accurately run a test and trust the result going forward for everything)
    if (/*($uses_ftp==='0') || */
    (strlen($ftp_password) > 0)
    ) { // Permanently stored
        return;
    }
    if ($got_ftp_details == 0) { // Get FTP details
        get_afm_form();
    } else {
        // Store them as values
        $uses_ftp = post_param_integer('uses_ftp', 0);
        set_value('uses_ftp', strval($uses_ftp));
        if ($uses_ftp == 1) {
            set_value('ftp_username', post_param_string('ftp_username'));
            $ftp_directory = post_param_string('ftp_directory');
            if (substr($ftp_directory, 0, 1) != '/') {
                $ftp_directory = '/' . $ftp_directory;
            }
            set_value('ftp_directory', $ftp_directory);
            set_value('ftp_domain', post_param_string('ftp_domain'));
            if (post_param_integer('remember_password', 0) == 1) {
                set_value('ftp_password', post_param_string('ftp_password'));
            }
        }
    }
}

/**
 * Force an AFM login.
 */
function get_afm_form()
{
    $fields = get_afm_form_fields();

    $title = get_screen_title('ABSTRACT_FILE_MANAGEMENT');

    $post_url = get_self_url(true);
    $submit_name = do_lang_tempcode('PROCEED');
    $hidden = build_keep_post_fields();
    $hidden->attach(form_input_hidden('got_ftp_details', '1'));
    if (str_replace(array('on', 'true', 'yes'), array('1', '1', '1'), strtolower(ini_get('safe_mode'))) == '1') {
        $hidden->attach(form_input_hidden('uses_ftp', '1'));
    }
    $javascript = "var ftp_ticker=function() { var uses_ftp=document.getElementById('uses_ftp'); if (!uses_ftp) return; var form=uses_ftp.form; form.elements['ftp_domain'].disabled=!uses_ftp.checked; form.elements['ftp_directory'].disabled=!uses_ftp.checked; form.elements['ftp_username'].disabled=!uses_ftp.checked; form.elements['ftp_password'].disabled=!uses_ftp.checked; form.elements['remember_password'].disabled=!uses_ftp.checked; }; ftp_ticker(); document.getElementById('uses_ftp').onclick=ftp_ticker;";

    $middle = do_template('FORM_SCREEN', array('_GUID' => 'c47a31fca47a7b22eeef3a6269cc2407', 'JAVASCRIPT' => $javascript, 'SKIP_WEBSTANDARDS' => true, 'HIDDEN' => $hidden, 'SUBMIT_ICON' => 'buttons__proceed', 'SUBMIT_NAME' => $submit_name, 'TITLE' => $title, 'FIELDS' => $fields, 'URL' => $post_url, 'TEXT' => paragraph(do_lang_tempcode('TEXT_ABSTRACT_FILE_MANAGEMENT'))));
    $echo = globalise($middle, null, '', true);
    $echo->evaluate_echo();
    exit();
}

/**
 * Get the fields that need to be filled in to know how to do an AFM connection.
 *
 * @return tempcode The form fields.
 */
function get_afm_form_fields()
{
    require_code('form_templates');
    $fields = new Tempcode();

    $ftp_username = get_value('ftp_username');
    $ftp_directory = get_value('ftp_directory');
    $ftp_domain = get_value('ftp_domain');
    $_uses_ftp = running_script('upgrader') ? '0' : get_value('uses_ftp');
    if (is_null($_uses_ftp)) {
        $uses_ftp = !is_writable_wrap(get_file_base() . '/adminzone/index.php');
    } else {
        $uses_ftp = ($_uses_ftp == '1');
    }

    // Domain
    if (is_null($ftp_domain)) {
        if (array_key_exists('ftp_domain', $GLOBALS['SITE_INFO'])) {
            $ftp_domain = $GLOBALS['SITE_INFO']['ftp_domain'];
        } else {
            $ftp_domain = get_domain();
        }
    }

    // Username
    if (is_null($ftp_username)) {
        if (array_key_exists('ftp_username', $GLOBALS['SITE_INFO'])) {
            $ftp_username = $GLOBALS['SITE_INFO']['ftp_username'];
        } else {
            if ((function_exists('posix_getpwuid')) && (strpos(@ini_get('disable_functions'), 'posix_getpwuid') === false)) {
                $u_info = posix_getpwuid(fileowner(get_file_base() . '/index.php'));
                if ($u_info !== false) {
                    $ftp_username = $u_info['name'];
                } else {
                    $ftp_username = '';
                }
            } else {
                $ftp_username = '';
            }
            if (is_null($ftp_username)) {
                $ftp_username = '';
            }
        }
    }

    // Directory
    if (is_null($ftp_directory)) {
        if (array_key_exists('ftp_directory', $GLOBALS['SITE_INFO'])) {
            $ftp_directory = $GLOBALS['SITE_INFO']['ftp_directory'];
        } else {
            $pos = strpos(cms_srv('SCRIPT_NAME'), 'adminzone/index.php');
            if (($pos === false) && (get_zone_name() != '')) {
                $pos = strpos(cms_srv('SCRIPT_NAME'), get_zone_name() . '/index.php');
            }
            if ($pos === false) {
                $pos = strpos(cms_srv('SCRIPT_NAME'), 'data/');
            }
            if ($pos === false) {
                $pos = strpos(cms_srv('SCRIPT_NAME'), 'data_custom/');
            }
            if ($pos === false) {
                $pos = strpos(cms_srv('SCRIPT_NAME'), 'cms/index.php');
            }
            if ($pos === false) {
                $pos = strpos(cms_srv('SCRIPT_NAME'), 'site/index.php');
            }
            $dr = cms_srv('DOCUMENT_ROOT');
            if (strpos($dr, '/') !== false) {
                $dr_parts = explode('/', $dr);
            } else {
                $dr_parts = explode('\\', $dr);
            }
            $webdir_stub = $dr_parts[count($dr_parts) - 1];
            $ftp_directory = '/' . $webdir_stub . substr(cms_srv('SCRIPT_NAME'), 0, $pos);
        }
    }

    $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '671ec3d1ffd376766450b36d718f1c60', 'TITLE' => do_lang_tempcode('SETTINGS'))));
    if (str_replace(array('on', 'true', 'yes'), array('1', '1', '1'), strtolower(ini_get('safe_mode'))) != '1') {
        $fields->attach(form_input_tick(do_lang_tempcode('NEED_FTP'), do_lang_tempcode('DESCRIPTION_NEED_FTP'), 'uses_ftp', $uses_ftp));
    }
    $fields->attach(form_input_line(do_lang_tempcode('FTP_DOMAIN'), '', 'ftp_domain', $ftp_domain, false));
    $fields->attach(form_input_line(do_lang_tempcode('FTP_DIRECTORY'), do_lang_tempcode('FTP_FOLDER'), 'ftp_directory', $ftp_directory, false));
    $fields->attach(form_input_line(do_lang_tempcode('FTP_USERNAME'), '', 'ftp_username', $ftp_username, false));
    $fields->attach(form_input_password(do_lang_tempcode('FTP_PASSWORD'), '', 'ftp_password', false));
    $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '7b2ed7bd1b2869a02e3b3bf40b3f99cd', 'TITLE' => do_lang_tempcode('ACTIONS'))));
    $fields->attach(form_input_tick(do_lang_tempcode('REMEMBER_PASSWORD'), do_lang_tempcode('DESCRIPTION_REMEMBER_PASSWORD'), 'remember_password', false));

    return $fields;
}

/**
 * Return the FTP connection, from stored/posted details.
 *
 * @param  boolean $light_fail Whether to simply echo-out errors.
 * @return ~resource The FTP connection (false: not connecting via FTP).
 */
function _ftp_info($light_fail = false)
{
    global $AFM_FTP_CONN;
    if (!is_null($AFM_FTP_CONN)) {
        return $AFM_FTP_CONN;
    }

    if (((get_value('uses_ftp') == '1') && (!running_script('upgrader'))) || (post_param_integer('uses_ftp', 0) == 1)) {
        require_lang('installer');

        $conn = false;
        $domain = post_param_string('ftp_domain', get_value('ftp_domain'));
        $port = 21;
        if (strpos($domain, ':') !== false) {
            list($domain, $_port) = explode(':', $domain, 2);
            $port = intval($_port);
        }
        if (function_exists('ftp_ssl_connect')) {
            $conn = @ftp_ssl_connect($domain, $port);
        }
        $ssl = ($conn !== false);

        $username = post_param_string('ftp_username', get_value('ftp_username'));
        $password = post_param_string('ftp_password', get_value('ftp_password'));

        if (($ssl) && (!@ftp_login($conn, $username, $password))) {
            $conn = false;
            $ssl = false;
        }
        if (($conn === false) && (function_exists('ftp_connect'))) {
            $conn = @ftp_connect($domain, $port);
        }
        if ($conn === false) {
            set_value('ftp_password', '');
            if ($light_fail) {
                $temp = do_lang_tempcode('NO_FTP_CONNECT');
                echo '<strong>';
                $temp->evaluate_echo();
                echo '</strong>';
                return null;
            } else {
                set_value('ftp_password', ''); // Wipe out password, because we need the user to see FTP login screen again
                attach_message(do_lang_tempcode('NO_FTP_CONNECT'), 'warn');
                get_afm_form();
            }
        }

        $username = post_param_string('ftp_username', get_value('ftp_username'));
        $password = post_param_string('ftp_password', get_value('ftp_password'));

        if ((!$ssl) && (@ftp_login($conn, $username, $password) === false)) {
            set_value('ftp_password', '');
            if ($light_fail) {
                $temp = do_lang_tempcode('NO_FTP_LOGIN', @strval($php_errormsg));
                $temp->evaluate_echo();
                return null;
            } else {
                set_value('ftp_password', ''); // Wipe out password, because we need the user to see FTP login screen again
                attach_message(do_lang_tempcode('NO_FTP_LOGIN', @strval($php_errormsg)), 'warn');
                get_afm_form();
            }
        }

        $ftp_folder = post_param_string('ftp_folder', get_value('ftp_directory'));
        if (substr($ftp_folder, -1) != '/') {
            $ftp_folder .= '/';
        }
        if (@ftp_chdir($conn, $ftp_folder) === false) {
            set_value('ftp_password', '');
            if ($light_fail) {
                $temp = do_lang_tempcode('NO_FTP_DIR', @strval($php_errormsg), '1');
                $temp->evaluate_echo();
                return null;
            } else {
                set_value('ftp_password', ''); // Wipe out password, because we need the user to see FTP login screen again
                attach_message(do_lang_tempcode('NO_FTP_DIR', @strval($php_errormsg), '1'), 'warn');
                get_afm_form();
            }
        }
        $files = @ftp_nlist($conn, '.');
        if ($files === false) { // :(. Weird bug on some systems
            $files = array();
            if (@ftp_rename($conn, '_config.php', '_config.php')) {
                $files = array('_config.php');
            }
        }
        if (!in_array('_config.php', $files)) {
            set_value('ftp_password', '');
            if ($light_fail) {
                $temp = do_lang_tempcode('NO_FTP_DIR', @strval($php_errormsg), '2');
                $temp->evaluate_echo();
                return null;
            } else {
                set_value('ftp_password', ''); // Wipe out password, because we need the user to see FTP login screen again
                attach_message(do_lang_tempcode('NO_FTP_DIR', @strval($php_errormsg), '2'), 'warn');
                get_afm_form();
            }
        }

        $AFM_FTP_CONN = $conn;
        return $AFM_FTP_CONN;
    }

    return false;
}

/**
 * Translate truth about needing world write access to a directory to absolute permissions.
 *
 * @param  boolean $world_access Whether world directory access is required.
 * @return integer The absolute permission.
 */
function _translate_dir_access($world_access)
{
    if (is_suexec_like()) {
        return 0755;
    }

    if (_ftp_info() === false) {
        return 0777; // We want the FTP user to be able to delete.. otherwise it gets awkward for them
    }

    return $world_access ? 0777 : 0755;
}

/**
 * Translate truth about needing world write access to a file to absolute permissions.
 *
 * @param  boolean $world_access Whether world file access is required.
 * @param  ID_TEXT $file_type The file type (blank: don't care).
 * @return integer The absolute permission.
 */
function _translate_file_access($world_access, $file_type = '')
{
    $mask = 0;

    if ($file_type == 'php') {
        $php_perms = fileperms(get_file_base() . '/index.php');
        if (($php_perms & 0100) == 0100) { // If PHP files need to be marked user executable
            $mask = $mask | 0100;
        }
        if (($php_perms & 0010) == 0010) { // If PHP files need to be marked group executable
            $mask = $mask | 0010;
        }
        if (($php_perms & 0001) == 0001) { // If PHP files need to be marked other executable
            $mask = $mask | 0001;
        }
    }

    if (is_suexec_like()) {
        return 0644 | $mask;
    }

    if (_ftp_info() === false) {
        return 0666 | $mask; // We want the FTP user to be able to delete.. otherwise it gets awkward for them
    }

    return ($world_access ? 0666 : 0644) | $mask;
}

/**
 * Convert an integer permission to the string version.
 *
 * @param  integer $access_int The integer permission.
 * @return string The string version.
 */
function _access_string($access_int)
{
    return sprintf('%o', $access_int);
}

/**
 * Rescope a Composr path to a path suitable for the AFM connection.
 *
 * @param  PATH $path Original path.
 * @return PATH Rescoped path.
 */
function _rescope_path($path)
{
    if (post_param_string('uses_ftp', running_script('upgrader') ? '0' : get_value('uses_ftp')) == '1') {
        $ftp_folder = post_param_string('ftp_folder', get_value('ftp_directory'));
        if (substr($ftp_folder, -1) != '/') {
            $ftp_folder .= '/';
        }
        return $ftp_folder . $path;
    }
    return get_custom_file_base() . '/' . $path;
}

/**
 * Sets permissions over the open AFM connection.
 *
 * @param  PATH $basic_path The path of the file/directory we are setting permissions of.
 * @param  boolean $world_access Whether world access is required.
 */
function afm_set_perms($basic_path, $world_access)
{
    $access = is_dir(get_file_base() . '/' . $basic_path) ? _translate_dir_access($world_access) : _translate_file_access($world_access);
    $path = _rescope_path($basic_path);

    $conn = _ftp_info();
    if ($conn !== false) {
        @ftp_chmod($conn, $access, $path);
    } else {
        @chmod($path, $access);
    }
}

/**
 * Make a directory over the open AFM connection.
 *
 * @param  PATH $basic_path The path to and of the directory we are making.
 * @param  boolean $world_access Whether world access is required.
 * @param  boolean $recursive Whether we should recursively make any directories that are missing in the given path, until we can make the final directory.
 */
function afm_make_directory($basic_path, $world_access, $recursive = false)
{
    $access = _translate_dir_access($world_access);
    $path = _rescope_path($basic_path);

    if ($recursive) {
        $parts = explode('/', $basic_path);
        unset($parts[count($parts) - 1]);
    }

    $conn = _ftp_info();
    if ($conn !== false) {
        if ($recursive) {
            $build_up = post_param_string('ftp_folder', get_value('ftp_directory'));
            foreach ($parts as $part) {
                $build_up .= '/' . $part;
                @ftp_mkdir($conn, $build_up);
                @ftp_chmod($conn, $access, $build_up);
            }
        }
        if (!file_exists(get_custom_file_base() . '/' . $basic_path)) {
            $success = @ftp_mkdir($conn, $path);
            if (!is_string($success)) {
                warn_exit(protect_from_escaping(@strval($php_errormsg)));
            }
        }
        @ftp_chmod($conn, $access, $path);

        clearstatcache();

        sync_file(get_custom_file_base() . '/' . $basic_path);
    } else {
        if (!file_exists(get_custom_file_base() . '/' . $basic_path)) {
            @mkdir($path, $access, $recursive) or warn_exit(do_lang_tempcode('WRITE_ERROR_DIRECTORY', escape_html($path), escape_html(dirname($path))));
        } else {
            @chmod($path, $access);
        }

        sync_file($path);
    }
}

/**
 * Get a list of files under a directory.
 *
 * @param  PATH $base The base directory for the search.
 * @param  PATH $at The directory where we are searching under.
 * @return array An array of directories found under this recursive level.
 */
function _get_dir_tree($base, $at = '')
{
    $out = array(array('dir', $at));
    $stub = get_custom_file_base() . '/' . $base . '/' . $at;
    $dh = @opendir($stub);
    if ($dh !== false) {
        while (($file = readdir($dh)) !== false) {
            if (($file != '.') && ($file != '..')) {
                $stub2 = $stub . (($at != '') ? '/' : '') . $file;
                if (is_dir($stub2)) {
                    $out = array_merge($out, _get_dir_tree($base, $at . (($at != '') ? '/' : '') . $file));
                } else {
                    $out[] = array('file', $at . (($at != '') ? '/' : '') . $file);
                }
            }
        }
        closedir($dh);
    } else {
        warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
    }
    return $out;
}

/**
 * Delete a directory over the open AFM connection.
 *
 * @param  PATH $basic_path The path to and of the directory we are deleting.
 * @param  boolean $recursive Whether we should recursively delete any child files and directories.
 */
function afm_delete_directory($basic_path, $recursive = false)
{
    $paths = $recursive ? array_reverse(_get_dir_tree($basic_path)) : array(array('dir', ''));

    $conn = _ftp_info();

    foreach ($paths as $bits) {
        list($type, $path) = $bits;

        if ($type == 'file') {
            afm_delete_file($basic_path . '/' . $path);
        } else {
            $path = _rescope_path($basic_path . '/' . $path);

            if ($conn !== false) {
                ftp_rmdir($conn, $path);

                clearstatcache();

                sync_file(get_custom_file_base() . '/' . $basic_path);
            } else {
                @rmdir($path) or warn_exit(do_lang_tempcode('WRITE_ERROR_DIRECTORY', escape_html($path)));

                sync_file($path);
            }
        }
    }
}

/**
 * Make a new file over the open AFM connection. Will overwrite if already exists (assuming has access).
 *
 * @param  PATH $basic_path The path to the file we are making.
 * @param  string $contents The desired file contents.
 * @param  boolean $world_access Whether world access is required.
 */
function afm_make_file($basic_path, $contents, $world_access)
{
    $path = _rescope_path($basic_path);
    $access = _translate_file_access($world_access, get_file_extension($basic_path));

    $conn = _ftp_info();
    if ($conn !== false) {
        $path2 = cms_tempnam('cmsafm');

        $h = fopen($path2, 'wb');
        if (fwrite($h, $contents) < strlen($contents)) {
            warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE_TMP', escape_html($path2)));
        }
        fclose($h);

        $h = fopen($path2, 'rb');
        $success = @ftp_fput($conn, $path, $h, FTP_BINARY);
        if (!$success) {
            if (running_script('upgrader')) {
                echo @strval($php_errormsg);
                return;
            }
            warn_exit(protect_from_escaping(@strval($php_errormsg)));
        }
        fclose($h);

        @unlink($path2);

        @ftp_chmod($conn, $access, $path);

        clearstatcache();

        sync_file(get_custom_file_base() . '/' . $basic_path);
    } else {
        $h = @fopen($path, 'wb');
        if ($h === false) {
            intelligent_write_error($path);
        }
        if (fwrite($h, $contents) < strlen($contents)) {
            warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE_TMP'));
        }
        fclose($h);
        @chmod($path, $access);
        fix_permissions($path);

        sync_file($path);
    }
}

/**
 * Read a file (not actually over the open AFM connection, but same result: we can do this directly).
 *
 * @param  PATH $path The path to the file we are reading.
 * @return string The contents of the file.
 */
function afm_read_file($path)
{
    return file_get_contents(get_custom_file_base() . '/' . $path);
}

/**
 * Copies a file (NOT a directory) on the open AFM connection.
 *
 * @param  PATH $old_path The path to the file we are copying.
 * @param  PATH $new_path The target path.
 * @param  boolean $world_access Whether world access is required for the copy.
 */
function afm_copy($old_path, $new_path, $world_access)
{
    $a = get_custom_file_base() . '/' . $old_path;
    if (!file_exists($a)) {
        $a = get_file_base() . '/' . $old_path;
    }
    $contents = file_get_contents($a);
    afm_make_file($new_path, $contents, $world_access);
}

/**
 * Moves a file on the open AFM connection.
 *
 * @param  PATH $basic_old_path The path to the file we are moving from.
 * @param  PATH $basic_new_path The target path.
 */
function afm_move($basic_old_path, $basic_new_path)
{
    if (is_dir(get_custom_file_base() . '/' . $basic_new_path)) {
        $basic_new_path .= substr($basic_old_path, strrpos($basic_old_path, '/')); // If we are moving to a path, add on the filename to that path
    }

    $old_path = _rescope_path($basic_old_path);
    $new_path = _rescope_path($basic_new_path);

    $conn = _ftp_info();
    if ($conn !== false) {
        $success = @ftp_rename($conn, $old_path, $new_path);
        if (!$success) {
            if (running_script('upgrader')) {
                echo @strval($php_errormsg);
                return;
            }
            warn_exit(protect_from_escaping(@strval($php_errormsg)));
        }

        clearstatcache();

        sync_file_move(get_custom_file_base() . '/' . $basic_old_path, get_custom_file_base() . '/' . $basic_new_path);
    } else {
        @rename($old_path, $new_path) or intelligent_write_error($old_path);

        sync_file_move($old_path, $new_path);
    }
}

/**
 * Deletes a file (NOT a directory) on the open AFM connection.
 *
 * @param  PATH $basic_path The path to the file we are deleting.
 */
function afm_delete_file($basic_path)
{
    $path = _rescope_path($basic_path);

    $conn = _ftp_info();
    if ($conn !== false) {
        $success = @ftp_delete($conn, $path);
        if (!$success) {
            if (running_script('upgrader')) {
                echo @strval($php_errormsg);
                return;
            }
            warn_exit(protect_from_escaping(@strval($php_errormsg)));
        }

        clearstatcache();

        sync_file(get_custom_file_base() . '/' . $basic_path);
    } else {
        if (!file_exists($path)) {
            return;
        }
        @unlink($path) or intelligent_write_error($path);

        sync_file($path);
    }
}
