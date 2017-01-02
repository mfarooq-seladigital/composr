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
function init__files()
{
    if (!defined('IGNORE_DEFAULTS')) {
        global $DOWNLOAD_LEVEL;
        $DOWNLOAD_LEVEL = 0;

        define('IGNORE_DEFAULTS', 0);
        // -
        define('IGNORE_NON_EN_SCATTERED_LANGS', 1);
        define('IGNORE_ACCESS_CONTROLLERS', 2);
        define('IGNORE_HIDDEN_FILES', 4);
        define('IGNORE_EDITFROM_FILES', 8);
        define('IGNORE_REVISION_FILES', 16);
        define('IGNORE_CUSTOM_ZONES', 32);
        define('IGNORE_CUSTOM_THEMES', 64);
        define('IGNORE_USER_CUSTOMISE', 256); // This is more specific than IGNORE_CUSTOM_DIR_SUPPLIED_CONTENTS | IGNORE_CUSTOM_DIR_GROWN_CONTENTS
        define('IGNORE_NONBUNDLED_SCATTERED', 512); // This is fairly specific stuff that we know we need to skip, not a broad skip pattern and not listing all non-bundled addon files
        define('IGNORE_BUNDLED_VOLATILE', 1024);
        define('IGNORE_BUNDLED_UNSHIPPED_VOLATILE', 2048);
        define('IGNORE_UPLOADS', 8192); // More specific than IGNORE_CUSTOM_DIR_GROWN_CONTENTS, except it does skip .htaccess/index.html under uploads too
        define('IGNORE_CUSTOM_DIR_SUPPLIED_CONTENTS', 16384);
        define('IGNORE_CUSTOM_DIR_GROWN_CONTENTS', 32768);
        define('IGNORE_NONBUNDLED_VERY_SCATTERED', 65536);
        define('IGNORE_NONBUNDLED_EXTREMELY_SCATTERED', 131072);

        define('FILE_WRITE_FAILURE_SILENT', 0);
        define('FILE_WRITE_FAILURE_SOFT', 1);
        define('FILE_WRITE_FAILURE_HARD', 2);
        define('FILE_WRITE_FIX_PERMISSIONS', 4);
        define('FILE_WRITE_SYNC_FILE', 8);
    }
}

/**
 * Write out to a file, with lots of error checking and locking.
 *
 * @param  PATH $path File path.
 * @param  string $contents File contents.
 * @param  integer $flags FILE_WRITE_* flags.
 * @param  integer $retry_depth How deep it is into retrying if somehow the data did not get written.
 * @return boolean Success status.
 */
function cms_file_put_contents_safe($path, $contents, $flags = 2, $retry_depth = 0)
{
    $num_bytes_to_save = strlen($contents);

    // If the directory is missing
    if (!is_dir(dirname($path))) {
        require_code('files2');
        make_missing_directory(dirname($path));
    }

    // Error condition: If there's a lack of disk space
    if (function_exists('disk_free_space')) {
        $num_bytes_to_write = $num_bytes_to_save;
        if (is_file($path)) {
            $num_bytes_to_write -= filesize($path);
        }
        $disk_space = disk_free_space(dirname($path));
        if ($disk_space < $num_bytes_to_write) {
            $error_message = do_lang_tempcode('COULD_NOT_SAVE_FILE', escape_html($path));
            return _cms_file_put_contents_safe_failed($error_message, $path, $flags);
        }
    }

    // Save
    $num_bytes_written = @file_put_contents($path, $contents, LOCK_EX);

    // Error condition: If it failed to save
    if ($num_bytes_written === false) {
        $error_message = intelligent_write_error_inline($path);
        return _cms_file_put_contents_safe_failed($error_message, $path, $flags);
    }

    // Error condition: If it did not save all bytes
    if ($num_bytes_written < $num_bytes_to_save) {
        $error_message = do_lang_tempcode('COULD_NOT_SAVE_FILE', escape_html($path));
        return _cms_file_put_contents_safe_failed($error_message, $path, $flags);
    }

    // Error condition: If somehow it said it saved but didn't actually (maybe a race condition on servers with buggy locking)
    if (filesize($path) != $num_bytes_to_save) {
        if ($retry_depth < 5) {
            return cms_file_put_contents_safe($path, $contents, $flags, $retry_depth + 1);
        }

        $error_message = do_lang_tempcode('COULD_NOT_SAVE_FILE', escape_html($path));
        return _cms_file_put_contents_safe_failed($error_message, $path, $flags);
    }

    // Extra requested operations
    if (($flags & FILE_WRITE_FIX_PERMISSIONS) != 0) {
        fix_permissions($path);
    }
    if (($flags & FILE_WRITE_SYNC_FILE) != 0) {
        sync_file($path);
    }

    return true;
}

/**
 * If cms_file_put_contents_safe has failed, process the error messaging.
 *
 * @param  Tempcode $error_message Error message.
 * @param  PATH $path File path.
 * @param  integer $flags FILE_WRITE_* flags.
 * @return boolean Success status (always false).
 */
function _cms_file_put_contents_safe_failed($error_message, $path, $flags = 2)
{
    static $looping = false;
    if ($looping) {
        critical_error('PASSON', do_lang('WRITE_ERROR', escape_html($path))); // Bail out hard if would cause a loop
    }
    $looping = true;

    if (($flags & FILE_WRITE_FAILURE_SOFT) != 0) {
        attach_message($error_message, 'warn');
    }

    if (($flags & FILE_WRITE_FAILURE_HARD) != 0) {
        warn_exit($error_message);
    }

    $looping = false;

    return false;
}

/**
 * Get the number of bytes for a PHP config option. Code taken from the PHP manual.
 *
 * @param  string $val PHP config option value.
 * @return integer Number of bytes.
 */
function php_return_bytes($val)
{
    $val = trim($val);
    if ($val == '') {
        return 0;
    }
    $last = strtolower($val[strlen($val) - 1]);
    $_val = intval($val);
    switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $_val *= 1024;
        case 'm':
            $_val *= 1024;
        case 'k':
            $_val *= 1024;
    }

    return $_val;
}

/**
 * Get a formatted-string filesize for the specified file. It is formatted as such: x MB/KB/Bytes (or unknown). It is assumed that the file exists.
 *
 * @param  URLPATH $url The URL that the file size of is being worked out for. Should be local.
 * @return string The formatted-string file size
 */
function get_file_size($url)
{
    if (substr($url, 0, strlen(get_base_url())) == get_base_url()) {
        $url = substr($url, strlen(get_base_url()));
    }

    if (!url_is_local($url)) {
        return do_lang('UNKNOWN');
    }

    $_full = rawurldecode($url);
    $_full = get_file_base() . '/' . $_full;
    $file_size_bytes = filesize($_full);

    return clean_file_size($file_size_bytes);
}

/**
 * Format the specified filesize.
 *
 * @param  integer $bytes The number of bytes the file has
 * @return string The formatted-string file size
 */
function clean_file_size($bytes)
{
    if ($bytes < 0) {
        return '-' . clean_file_size(-$bytes);
    }

    if (is_null($bytes)) {
        return do_lang('UNKNOWN') . ' bytes';
    }
    if (floatval($bytes) > 2.0 * 1024.0 * 1024.0 * 1024.0) {
        return strval(intval(round(floatval($bytes) / 1024.0 / 1024.0 / 1024.0))) . ' GB';
    }
    if (floatval($bytes) > 1024.0 * 1024.0 * 1024.0) {
        return float_format(floatval($bytes) / 1024.0 / 1024.0 / 1024.0, 2) . ' GB';
    }
    if (floatval($bytes) > 2.0 * 1024.0 * 1024.0) {
        return strval(intval(round(floatval($bytes) / 1024.0 / 1024.0))) . ' MB';
    }
    if (floatval($bytes) > 1024.0 * 1024.0) {
        return float_format(floatval($bytes) / 1024.0 / 1024.0, 2) . ' MB';
    }
    if (floatval($bytes) > 2.0 * 1024.0) {
        return strval(intval(round(floatval($bytes) / 1024.0))) . ' KB';
    }
    if (floatval($bytes) > 1024.0) {
        return float_format(floatval($bytes) / 1024.0, 2) . ' KB';
    }
    return strval($bytes) . ' Bytes';
}

/**
 * Parse the specified INI file, and get an array of what it found.
 *
 * @param  ?PATH $filename The path to the ini file to open (null: given contents in $file instead)
 * @param  ?string $file The contents of the file (null: the file needs opening)
 * @return array A map of the contents of the ini files
 */
function better_parse_ini_file($filename, $file = null)
{
    // NB: 'file()' function not used due to slowness compared to file_get_contents then explode

    if (is_null($file)) {
        global $FILE_ARRAY;
        if (@is_array($FILE_ARRAY)) {
            $file = file_array_get($filename);
        } else {
            $file = cms_file_get_contents_safe($filename);
        }
    }

    $ini_array = array();
    $lines = explode("\n", $file);
    foreach ($lines as $line) {
        $line = rtrim($line);

        if ($line == '') {
            continue;
        }
        if ($line[0] == '#') {
            continue;
        }

        $bits = explode('=', $line, 2);
        if (isset($bits[1])) {
            list($property, $value) = $bits;
            $value = trim($value, '"');
            $ini_array[$property] = str_replace('\n', "\n", $value);
        }
    }

    return $ini_array;
}

/**
 * Find whether a file is known to be something that should/could be there but isn't a Composr distribution file, or for some other reason should be ignored.
 *
 * @param  string $filepath File path (relative to Composr base directory)
 * @param  integer $bitmask Bitmask of extra stuff to ignore (see IGNORE_* constants)
 * @param  integer $bitmask_defaults Set this to 0 if you don't want the default IGNORE_* constants to carry through
 * @return boolean Whether it should be ignored
 */
function should_ignore_file($filepath, $bitmask = 0, $bitmask_defaults = 0)
{
    $bitmask = $bitmask | $bitmask_defaults;

    $is_dir = @is_dir(get_file_base() . '/' . $filepath);
    $is_file = @is_file(get_file_base() . '/' . $filepath);

    // Normalise
    if (strpos($filepath, '/') !== false) {
        $dir = dirname($filepath);
        $filename = basename($filepath);
    } else {
        $dir = '';
        $filename = $filepath;
    }

    $ignore_filenames_and_dir_names = array( // Case insensitive, define in lower case
                                             '.' => '.*',
                                             '..' => '.*',

                                             // Files other stuff makes
                                             '__macosx' => '.*',
                                             '.bash_history' => '.*',
                                             'error_log' => '.*',
                                             'thumbs.db:encryptable' => '.*',
                                             'thumbs.db' => '.*',
                                             '.ds_store' => '.*',

                                             // Source code control systems
                                             '.svn' => '.*',
                                             '.git' => '.*',
                                             'git-hooks' => '',
                                             '.gitattributes' => '',
                                             '.gitignore' => '',
                                             '.gitconfig' => '',
                                             'phpdoc.dist.xml' => '',

                                             // Web server extensions / leave-behinds
                                             'web-inf' => '.*',
                                             'www.pid' => '',
                                             '.ftaccess' => '',
                                             '.ftpquota' => '',
                                             'cgi-bin' => '',
                                             'stats' => '', // ISPConfig

                                             // Stuff from composr_homesite deployment
                                             'upgrades' => '',

                                             // Specially-recognised naming conventions
                                             '_old' => '.*',
                                             '_old_backups' => '.*',

                                             // Syntax's used during Composr testing
                                             'gibb' => '.*',
                                             'gibberish' => '.*',

                                             // Files you are sometimes expected to leave around, but outside Composr's direct remit
                                             'bingsiteauth.xml' => '',
                                             'php.ini' => '.*',
                                             '.htpasswd' => '.*',
                                             'iirf.ini' => '',
                                             'robots.txt' => '',
                                             'favicon.ico' => '', // Not used for Composr, but default path for other scripts on server
                                             '400.shtml' => '',
                                             '500.shtml' => '',
                                             '404.shtml' => '',
                                             '403.shtml' => '',
                                             'cron.yaml' => '',
                                             'dos.yaml' => '',
                                             'server_certificates.pem' => 'data_custom/modules/composr_mobile_sdk/ios',
                                             'queue.yaml' => '',
                                             '.htaccess' => '',

                                             // Installer files
                                             'install.php' => '',
                                             'data.cms' => '',
                                             'cms.sql' => '', // Temporary backup
                                             'restore.php' => '',

                                             // IDE projects
                                             'nbproject' => '', // Netbeans
                                             '.project' => '', // Eclipse
                                             '.idea' => '', // JetBrains / PhpStorm
                                             '.editorconfig' => '',

                                             // Composr control files
                                             'closed.html' => '',
                                             'closed.html.old' => '',
                                             'install_ok' => '',
                                             'install_locked' => '',

                                             // Demonstratr
                                             'text/if_hosted_service.txt' => '',
                                             'sites' => '',

                                             // Tapatalk
                                             'request_helper.dat' => 'mobiquo/include',

                                             // API docs
                                             'api' => 'docs',
                                             'composr-api-template' => 'docs',

                                             // PHP compiler temporary files
                                             'hphp-static-cache' => '',
                                             'hphp.files.list' => '',
                                             'hphp' => '',

                                             // LEGACY: Old files
                                             'info.php' => '', // Pre-v10 equivalent to _config.php
                                             'persistant_cache' => '', // Old misspelling
                                             'mods' => 'imports|exports',
    );

    $ignore_extensions = array( // Case insensitive, define in lower case
                                // Exports (effectively these are like temporary files - only intended for file transmission)
                                'tar' => '(imports|exports)/.*',
                                'txt' => '(imports|exports)/.*',

                                // Exports/Cache files
                                'gz' => '(themes/[^/]*/templates_cached|imports|exports)/.*',

                                // Cache files
                                'lcd' => '(caches|lang_cached)/.*', // LEGACY
                                'gcd' => '(caches|persistent_cache|persistant_cache)/.*', // LEGACY
                                'htm' => 'caches/guest_pages',
                                'xml' => 'caches/guest_pages',
                                'tcp' => 'themes/[^/]*/templates_cached/.*',
                                'tcd' => 'themes/[^/]*/templates_cached/.*',
                                'css' => 'themes/[^/]*/templates_cached/.*',
                                'js' => 'themes/[^/]*/templates_cached/.*',

                                // Logs
                                'log' => '.*',

                                // Temporary files
                                'tmp' => '.*',
                                'inc' => 'safe_mode_temp',
                                'dat' => 'safe_mode_temp',
                                'bak' => '.*',
                                'old' => '.*',
                                'cms' => '.*', // Installers and upgraders

                                // HHVM Hack converted files (built on-the-fly)
                                'hh' => '.*',

                                // IDE projects
                                'clpprj' => '', // Code Lobster
    );

    $ignore_filename_and_dir_name_patterns = array( // Case insensitive
                                                    array('\..*\.(png|gif|jpeg|jpg)', '.*'), // Image metadata file, e.g. ".example.png"
                                                    array('\_vti\_.*', '.*'), // Frontpage
                                                    array('google.*\.html', ''), // Google authorisation files
                                                    array('\.\_.*', '.*'), // MacOS extended attributes
                                                    array('tmpfile__.*', '.*'), // cms_tempnam produced temporarily files (unfortunately we can't specify a .tmp suffix)
                                                    array('.*\.\d+', 'exports/file_backups'), // File backups (saved as revisions)
    );
    $ignore_filename_patterns = array( // Case insensitive; we'll use this only when we *need* directories that would match to be valid
    );

    if (($bitmask & IGNORE_BUNDLED_VOLATILE) != 0) {
        $ignore_filenames_and_dir_names += array(
            // Bundled stuff that is not necessarily in a *_custom dir yet is volatile
            '_config.php' => '',
            'map.ini' => 'themes',
            'functions.dat' => 'data_custom',
            'errorlog.php' => 'data_custom',
            'execute_temp.php' => 'data_custom',
            'upgrader.cms.tmp' => 'data_custom',
            'unit_test_positive_ignore_sampler.xxx' => 'data_custom', // To help us test this function. This file won't ever exist.
        );
    }

    if ((($bitmask & IGNORE_BUNDLED_VOLATILE) != 0) || (($bitmask & IGNORE_BUNDLED_UNSHIPPED_VOLATILE) != 0)) {
        $ignore_filenames_and_dir_names += array(
            // Bundled stuff that is not necessarily in a *_custom dir yet is volatile and should not be included in shipped builds
            'chat_last_full_check.dat' => 'data_custom/modules/chat',
            'chat_last_msg.dat' => 'data_custom/modules/chat',
            'latest.dat' => 'data_custom/modules/web_notifications',
            'permissioncheckslog.php' => 'data_custom',
            'failover_rewritemap.txt' => 'data_custom',
            'failover_rewritemap__mobile.txt' => 'data_custom',
            'aggregate_types.xml' => 'data_custom/xml_config',
            'breadcrumbs.xml' => 'data_custom/xml_config',
            'fields.xml' => 'data_custom/xml_config',
            'EN.pwl' => 'data_custom/spelling/personal_dicts',
        );
    }

    if (($bitmask & IGNORE_NONBUNDLED_SCATTERED) != 0 || ($bitmask & IGNORE_NONBUNDLED_VERY_SCATTERED) != 0 || ($bitmask & IGNORE_NONBUNDLED_EXTREMELY_SCATTERED) != 0) {
        $ignore_filenames_and_dir_names += array(
            '_critical_error.html' => '',
            'critical_errors' => '',
        );
    }

    if (($bitmask & IGNORE_ACCESS_CONTROLLERS) != 0) {
        $ignore_filenames_and_dir_names = array(
            '.htaccess' => '.*',
            'index.html' => '.*',
        ) + $ignore_filenames_and_dir_names; // Done in this order as we are overriding .htaccess to block everywhere (by default blocks root only). PHP has weird array merge precedence rules.
    }

    if (($bitmask & IGNORE_USER_CUSTOMISE) != 0) { // Ignores directories that user override files go in, not code or uploads (which IGNORE_CUSTOM_DIR_SUPPLIED_CONTENTS | IGNORE_CUSTOM_DIR_GROWN_CONTENTS would cover): stuff edited through frontend to override bundled files
        $ignore_filenames_and_dir_names += array(
            'comcode_custom' => '.*',
            'html_custom' => '.*',
            'css_custom' => '.*',
            'templates_custom' => '.*',
            'javascript_custom' => '.*',
            'xml_custom' => '.*',
            'text_custom' => '.*',
            'images_custom' => '.*',
            'lang_custom' => '.*',
            'file_backups' => 'exports',
            'theme.ini' => 'themes/[^/]*',
        );
    }

    if (($bitmask & IGNORE_EDITFROM_FILES) != 0) {
        $ignore_extensions += array(
            'editfrom' => '.*',
        );
    }

    if (($bitmask & IGNORE_CUSTOM_DIR_SUPPLIED_CONTENTS) != 0) { // Ignore all override directories, for both users and addons
        if (($dir == 'data_custom') && (in_array($filename, array('errorlog.php', 'execute_temp.php', 'functions.dat')))) {
            // These are allowed, as they are volatile yet bundled. Use IGNORE_BUNDLED_VOLATILE if you don't want them.
        } else {
            $ignore_filename_patterns = array_merge($ignore_filename_and_dir_name_patterns, array(
                array('(?!index\.html$)(?!\.htaccess$).*', '.*_custom(/.*)?'), // Stuff under custom folders
            ));
            $ignore_filename_and_dir_name_patterns = array_merge($ignore_filename_and_dir_name_patterns, array(
                //'.*\_custom' => '.*', Let it find them, but work on the contents
                array('(?!index\.html$)(?!\.htaccess$).*', 'sources_custom/[^/]*'), // We don't want deep sources_custom directories either
            ));
        }
    }

    if (($bitmask & IGNORE_CUSTOM_DIR_GROWN_CONTENTS) != 0) { // Ignore all override directories, for both users and addons
        $ignore_filename_and_dir_name_patterns = array_merge($ignore_filename_and_dir_name_patterns, array(
            array('(?!index\.html$)(?!\.htaccess$).*', 'themes/default/images_custom'), // We don't want deep images_custom directories either
            array('(?!index\.html$)(?!\.htaccess$).*', 'data_custom/modules/admin_stats'), // Various temporary XML files get created under here, for SVG graphs
            array('(?!index\.html$)(?!\.htaccess$).*', 'data_custom/modules/chat'), // Various chat data files
            array('(?!index\.html$)(?!\.htaccess$).*', 'data/spelling/aspell'), // We don't supply aspell outside git, too much space taken
            array('(?!pre_transcoding$)(?!index.html$)(?!\.htaccess$).*', 'uploads/.*'), // Uploads
            array('(?!index\.html$)(?!\.htaccess$).*', '.*/(comcode|html)_custom/.*'), // Comcode pages
            array('.*', 'exports/builds/.*'),
        ));
    }

    if (($bitmask & IGNORE_UPLOADS) != 0) {
        $ignore_filename_and_dir_name_patterns = array_merge($ignore_filename_and_dir_name_patterns, array(
            array('.*', 'uploads/.*'), // Uploads
        ));
    }

    if (($bitmask & IGNORE_HIDDEN_FILES) != 0) {
        $ignore_filename_and_dir_name_patterns = array_merge($ignore_filename_and_dir_name_patterns, array(
            array('\..*', '.*'),
        ));
    }

    if (($bitmask & IGNORE_REVISION_FILES) != 0) { // E.g. global.css.<timestamp>
        $ignore_filename_and_dir_name_patterns = array_merge($ignore_filename_and_dir_name_patterns, array(
            array('.*\.\d+', '.*'),
        ));
    }

    $filename_lower = strtolower($filename);

    if (isset($ignore_filenames_and_dir_names[$filename_lower])) {
        if (preg_match('#^' . $ignore_filenames_and_dir_names[$filename_lower] . '$#i', $dir) != 0) {
            return true; // Check dir context
        }
    }

    $extension = get_file_extension($filename);
    $extension_lower = strtolower($extension);
    if (isset($ignore_extensions[$extension_lower])) {
        if (preg_match('#^' . $ignore_extensions[$extension_lower] . '$#i', $dir) != 0) {
            return true; // Check dir context
        }
    }
    foreach (array_merge($is_file ? $ignore_filename_patterns : array(), $ignore_filename_and_dir_name_patterns) as $pattern) {
        list($filename_pattern, $dir_pattern) = $pattern;
        if (preg_match('#^' . $filename_pattern . '$#i', $filename) != 0) {
            if (preg_match('#^' . $dir_pattern . '$#i', $dir) != 0) { // Check dir context
                return true;
            }
        }
    }

    if (($dir != '') && (is_dir(get_file_base() . '/' . $filepath)) && (is_dir(get_file_base() . '/' . $filepath . '/sources_custom'))) { // Composr dupe (e.g. backup) install
        return true;
    }

    if (($bitmask & IGNORE_CUSTOM_THEMES) != 0) {
        if ((preg_match('#^themes($|/)#i', $dir) != 0) && (substr($filepath, 0, strlen('themes/default/')) != 'themes/default/') && (substr($filepath, 0, strlen('themes/admin/')) != 'themes/admin/') && (!in_array(strtolower($filepath), array('themes/default', 'themes/admin', 'themes/index.html', 'themes/map.ini')))) {
            return true;
        }
    }

    if (($bitmask & IGNORE_CUSTOM_ZONES) != 0) {
        if ((is_dir(get_file_base() . '/' . $filepath)) && (is_file(get_file_base() . '/' . $filepath . '/index.php')) && (is_dir(get_file_base() . '/' . $filepath . '/pages')) && (!in_array($filename_lower, array('adminzone', 'collaboration', 'cms', 'forum', 'site')))) {
            return true;
        }
    }

    if (($bitmask & IGNORE_NONBUNDLED_SCATTERED) != 0 || ($bitmask & IGNORE_NONBUNDLED_VERY_SCATTERED) != 0 || ($bitmask & IGNORE_NONBUNDLED_EXTREMELY_SCATTERED) != 0) {
        if (preg_match('#^data_custom/images/addon_screenshots(/|$)#', strtolower($filepath)) != 0) {
            return true; // Relating to addon build, but not defined in addons
        }
        if (preg_match('#^exports/static(/|$)#', strtolower($filepath)) != 0) {
            return true; // Empty directory, so has to be a special exception
        }
        if (preg_match('#^exports/(builds|backups/test)(/|$)#', strtolower($filepath)) != 0) {
            return true; // Needed to stop build recursion
        }
        if (preg_match('#^_tests(/|$)#', strtolower($filepath)) != 0) {
            return true; // Test set may have various temporary files buried within
        }
        if (preg_match('#^data_custom/ckeditor(/|$)#', strtolower($filepath)) != 0) {
            return true; // Don't want development version of CKEditor
        }

        if (preg_match('#^data_custom/sitemaps(/|$)#', strtolower($filepath)) != 0) {
            return true; // Don't want sitemap files
        }
    }

    if (($bitmask & IGNORE_NONBUNDLED_SCATTERED) != 0 || ($bitmask & IGNORE_NONBUNDLED_VERY_SCATTERED) != 0) {
        static $addon_files = null;
        if ($addon_files === null) {
            $addon_files = array();// Old style: function_exists('collapse_1d_complexity') ? array_map('strtolower', collapse_1d_complexity('filename', $GLOBALS['SITE_DB']->query_select('addons_files', array('filename')))) : array();
            $hooks = find_all_hooks('systems', 'addon_registry');
            foreach ($hooks as $hook => $place) {
                if ($place == 'sources_custom') {
                    if (function_exists('filter_naughty_harsh')) {
                        require_code('addons');
                        $addon_info = read_addon_info($hook);
                        $addon_files = array_merge($addon_files, array_map('strtolower', $addon_info['files']));
                    } else { // Running from outside Composr
                        require_code('hooks/systems/addon_registry/' . $hook);
                        $ob = object_factory('Hook_addon_registry_' . $hook);
                        $addon_files = array_merge($addon_files, array_map('strtolower', $ob->get_file_list()));
                    }
                }
            }
            $addon_files = array_flip($addon_files);
        }
        if (isset($addon_files[strtolower($filepath)])) {
            if (($bitmask & IGNORE_NONBUNDLED_SCATTERED) != 0 || ($bitmask & IGNORE_NONBUNDLED_VERY_SCATTERED) != 0 && strpos($filepath, '_custom') === false) {
                return true;
            }
        }
        // Note that we have no support for identifying directories related to addons, only files inside. Code using this function should detect directories with no usable files in as relating to addons.
    }

    if (($bitmask & IGNORE_NON_EN_SCATTERED_LANGS) != 0) {
        // Wrong lang packs
        if (((strlen($filename) == 2) && (strtoupper($filename) == $filename) && ($filename_lower != $filename) && ($filename != 'EN')) || ($filename == 'EN_us') || ($filename == 'ZH-TW') || ($filename == 'ZH-CN')) {
            return true;
        }
    }

    return false;
}

/**
 * Delete all the contents of a directory, and any subdirectories of that specified directory (recursively).
 *
 * @param  PATH $dir The pathname to the directory to delete
 * @param  boolean $default_preserve Whether to preserve files there by default
 * @param  boolean $just_files Whether to just delete files
 */
function deldir_contents($dir, $default_preserve = false, $just_files = false)
{
    require_code('files2');
    _deldir_contents($dir, $default_preserve, $just_files);
}
