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
 * @package    core_upgrader
 */

/**
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__upgrade_integrity_scan()
{
    require_lang('upgrade');
}

/**
 * Do upgrader screen: integrity scan.
 *
 * @ignore
 * @return string Output messages
 */
function upgrader_integrity_scan_screen()
{
    $allow_merging = either_param_integer('allow_merging', 0);
    return run_integrity_check(false, $allow_merging == 1);
}

/**
 * Do upgrader screen: move/delete certain selected things, in follow up to an integrity scan.
 *
 * @ignore
 * @return string Output messages
 */
function upgrader__integrity_scan_screen()
{
    foreach (array_keys($_POST) as $key) {
        $val = post_param_string($key);
        if (strpos($val, ':') !== false) {
            $bits = explode(':', $val);
            if ($bits[0] == 'delete') {
                afm_delete_file($bits[1]);
            } elseif ($bits[0] == 'move') {
                afm_delete_file($bits[2]);
                afm_move($bits[1], $bits[2]);
            }

            // Now delete empty directories
            $_subdirs = explode('/', dirname($bits[1]));
            $subdirs = array();
            $buildup = '';
            foreach ($_subdirs as $subdir) {
                if ($buildup != '') {
                    $buildup .= '/';
                }
                $buildup .= $subdir;

                $subdirs[] = $buildup;
            }
            foreach (array_reverse($subdirs) as $subdir) {
                $files = @scandir(get_file_base() . '/' . $subdir);
                if (($files !== false) && (count(array_diff($files, array('..', '.', '.DS_Store'))) == 0)) {
                    @unlink(get_file_base() . '/' . $subdir . '/.DS_Store');
                    @rmdir(get_file_base() . '/' . $subdir);
                }
            }

            unset($_POST[$key]); // We don't want it propagating with buttons, annoying and confusing
        }
    }

    return '<p>' . do_lang('SUCCESS') . '</p>';
}

/**
 * Load up manifest of file checksums.
 *
 * @param  boolean $previous Whether to use data for the previous version
 * @return array Master data
 */
function load_integrity_manifest($previous = false)
{
    $path = get_file_base() . '/data/' . ($previous ? 'files_previous.dat' : 'files.dat');
    $manifest = @unserialize(file_get_contents($path));
    if ($manifest === false) {
        $manifest = array();
    }
    return $manifest;
}

/**
 * Load up a list of files for the addons we have installed (addon_registry based ones only).
 *
 * @param  array $manifest Manifest of file checksums
 * @return array A pair: List of hook files, List of files
 */
function load_files_list_of_installed_addons($manifest)
{
    // We'll need to know about stuff in our addon registry, and file manifest
    if (function_exists('find_all_hooks')) {
        $hooks = find_all_hooks('systems', 'addon_registry');
    } else {
        // In case failing with a critical error
        $hooks = array();
        $dir = get_file_base() . '/sources/hooks/systems/addon_registry';
        $dh = @opendir($dir);
        if ($dh !== false) {
            while (($file = readdir($dh)) !== false) {
                if ((substr($file, -4) == '.php') && (preg_match('#^[\w\-]*$#', substr($file, 0, strlen($file) - 4)) != 0)) {
                    $hooks[substr($file, 0, strlen($file) - 4)] = 'sources';
                }
            }
            closedir($dh);
        }
    }
    $hook_files = array();
    foreach ($hooks as $hook => $hook_type) {
        if ($hook_type != 'sources_custom') {
            if (!isset($manifest['sources/hooks/systems/addon_registry/' . filter_naughty_harsh($hook) . '.php'])) {
                continue; // Old addon
            }
        }

        $path = get_file_base() . '/' . $hook_type . '/hooks/systems/addon_registry/' . filter_naughty_harsh($hook) . '.php';
        $hook_files[$hook] = $path;
    }
    $files_to_check = array();
    foreach ($hook_files as $addon_name => $hook_path) {
        $hook_file = file_get_contents($path);
        $matches = array();
        if (preg_match('#function get_file_list\(\)\s*\{([^\}]*)\}#', $hook_file, $matches) != 0) {
            $files_to_check = array_merge($files_to_check, cms_eval($matches[1], $hook_path)); // A bit of a hack, but saves a lot of RAM
        }
    }
    sort($files_to_check);

    return array($files_to_check, $hook_files);
}

/**
 * Do an integrity check. This does not include an alien check in basic mode; otherwise check_alien() is called within this function.
 *
 * @param  boolean $basic Whether to just do the minimum basic scan
 * @param  boolean $allow_merging Whether merging of CSS changes is allowed
 * @param  boolean $unix_help Whether to give some help to unix people
 * @return string Results
 */
function run_integrity_check($basic = false, $allow_merging = true, $unix_help = false)
{
    $ret_str = '';
    $found_something = false;

    require_code('files');

    disable_php_memory_limit();

    // Moved module handling
    if ($basic) {
        $not_missing = array();
    } else {
        $hidden = post_fields_relay();
        $ret_str .= '<form title="' . do_lang('PROCEED') . '" action="upgrader.php?type=_integrity_scan" method="post">' . $hidden;
        list($moved, $not_missing) = move_modules_ui();
        if ($moved != '') {
            $ret_str .= do_lang('WARNING_MOVED_MODULES', $moved);
            $found_something = true;
        }
    }

    $manifest = load_integrity_manifest();
    list($files_to_check, $hook_files) = load_files_list_of_installed_addons($manifest);

    // Override handling
    $check_outdated__handle_overrides_result = check_outdated__handle_overrides(get_file_base() . '/', '', $manifest, $hook_files, $allow_merging);
    list($outdated__outdated_original_and_override, $outdated__possibly_outdated_override, $outdated__missing_original_but_has_override, $outdated__uninstalled_addon_but_has_override) = $check_outdated__handle_overrides_result;

    // Look for missing or outdated files files
    $outdated__outdated_original = '';
    $outdated__missing_file_entirely = '';
    $outdated__future_files = '';
    $files_determined_to_upload = array();
    foreach ($files_to_check as $file) {
        if (($basic) && (time() - $_SERVER['REQUEST_TIME'] > 5)) {
            return ''; // Taking too long
        }

        // What to skip
        if (should_ignore_file($file, IGNORE_SHIPPED_VOLATILE | IGNORE_UNSHIPPED_VOLATILE | IGNORE_NONBUNDLED | IGNORE_FLOATING | IGNORE_CUSTOM_DIRS | IGNORE_UPLOADS)) {
            continue;
        }
        if ($file == 'data/files.dat') {
            continue; // Can't check integrity against self!
        }
        if ($file == 'data/files_previous.dat') {
            continue; // Comes in outside scope of files.dat
        }
        if ($file == 'recommended.htaccess') {
            continue; // May be renamed
        }

        if (!file_exists(get_file_base() . '/' . $file)) {
            if (!in_array(get_file_base() . '/' . $file, $not_missing)) {
                $outdated__missing_file_entirely .= '<li><kbd>' . escape_html($file) . '</kbd></li>';
                $files_determined_to_upload[] = $file;
            }
        } elseif (isset($manifest[$file])) {
            // Hash checking...

            if (@filesize(get_file_base() . '/' . $file) > 1024 * 1024) {
                continue; // Too big, so special exception
            }

            $file_contents = @file_get_contents(get_file_base() . '/' . $file);
            if ($file_contents === false) {
                continue;
            }
            if (strpos($file, '/version.php') !== false) {
                $file_contents = preg_replace('/\d{10}/', '', $file_contents); // Strip timestamp, too volatile
            }
            $true_hash = sprintf('%u', crc32(preg_replace('#[\r\n\t ]#', '', $file_contents)));
            if ($true_hash != $manifest[$file][0]) {
                if (filemtime(get_file_base() . '/' . $file) < cms_version_time()) {
                    $outdated__outdated_original .= '<li><kbd>' . escape_html($file) . '</kbd></li>'; //  [disk-hash: ' . $true_hash . ', required-hash: ' . $manifest[$file][0].']
                    $files_determined_to_upload[] = $file;
                } else {
                    $outdated__future_files .= '<li><kbd>' . escape_html($file) . '</kbd></li>';
                }
            }
        }
    }

    // Output integrity check results
    if ($outdated__possibly_outdated_override != '') {
        if ($basic) {
            $ret_str .= '<p>The following files have been superseded by new versions, but you have overrides/customisations blocking the new versions. Look into this and consider reincorporating your changes into our new version. If this is not done, bugs (potentially security holes) may occur, or be left unfixed. If you edited using an inbuilt editor, the file on which you based it will be saved as <kbd>file.editfrom</kbd>: you may use a tool such as <a href="http://winmerge.sourceforge.net/" target="_blank">WinMerge</a> to compare the <kbd>editfrom</kbd> file to your own, and then apply those same changes to the latest version of the file.</p><ul>' . $outdated__possibly_outdated_override . '</ul>';
        } else {
            $ret_str .= do_lang('WARNING_FILE_OUTDATED_OVERRIDE', $outdated__possibly_outdated_override);
        }
        $found_something = true;
    }
    if ($outdated__outdated_original_and_override != '') {
        if ($basic) {
            $ret_str .= '<p>The following non-overridden files are outdated, as are the corresponding overridden files (you can find the correct versions for the original in the manual installer ZIP for the version you\'re running, but the overrides may still cause problems and might need removing/replacing):</p><ul>' . $outdated__outdated_original_and_override . '</ul>';
        } else {
            $ret_str .= do_lang('WARNING_FILE_OUTDATED_ORIGINAL_AND_OVERRIDE', $outdated__outdated_original_and_override);
        }
        $found_something = true;
    }
    if ($outdated__missing_original_but_has_override != '') {
        if ($basic) {
            $ret_str .= '<p>The following original files to these overridden files are actually missing (you can find them in the manual installer ZIP for the version you\'re running):</p><ul>' . $outdated__missing_original_but_has_override . '</ul>';
        } else {
            $ret_str .= do_lang('WARNING_FILE_MISSING_ORIGINAL_BUT_HAS_OVERRIDE', $outdated__missing_original_but_has_override);
        }
        $found_something = true;
    }
    if (($outdated__uninstalled_addon_but_has_override != '') && (!$basic)) {
        $ret_str .= do_lang('WARNING_FILE_OVERRIDE_FROM_UNINSTALLED_ADDON', $outdated__uninstalled_addon_but_has_override);
        $found_something = true;
    }
    if ($outdated__missing_file_entirely != '') {
        if ($basic) {
            $ret_str .= '<p>These files are actually missing and need uploading (you can find them in the manual installer ZIP for the version you\'re running):</p><ul>' . $outdated__missing_file_entirely . '</ul>';
        } else {
            $ret_str .= do_lang('WARNING_FILE_MISSING_FILE_ENTIRELY', $outdated__missing_file_entirely);
        }
        $found_something = true;
    }
    if ($outdated__outdated_original != '') {
        if ($basic) {
            $ret_str .= '<p>These files are outdated (you can find the correct versions in the manual installer ZIP for the version you\'re running):</p><ul>' . $outdated__outdated_original . '</ul>';
        } else {
            $ret_str .= do_lang('WARNING_FILE_OUTDATED_ORIGINAL', $outdated__outdated_original);
        }
        $found_something = true;
    }
    if ($outdated__future_files != '') {
        if ($basic) {
            $ret_str .= '<p>These files do not match the ones bundled with your version, but claim to be newer (so these might be bug fixes someone has put here):</p><ul>' . $outdated__future_files . '</ul>';
        } else {
            $ret_str .= do_lang('WARNING_FILE_FUTURE_FILES', $outdated__future_files);
        }
        $found_something = true;
    }

    // And some special help for unix geeks (copying missing files is a little hard when directories may be missing)
    if (($unix_help) && (php_function_allowed('escapeshellcmd'))) {
        $unix_out = 'CMS_EXTRACTED_AT="<manual-extracted-at-dir>";' . "\n" . 'cd "<temp-dir-to-upload-from>";' . "\n";
        $directories_to_make = array();
        foreach ($files_determined_to_upload as $file) {
            $dirname = dirname($file);
            if ($dirname == '.') {
                $dirname = '';
            }
            $directories_to_make[$dirname] = true;
        }
        foreach (array_keys($directories_to_make) as $directory) {
            $unix_out .= 'mkdir -p ' . escapeshellcmd($directory) . ';' . "\n";
        }
        foreach ($files_determined_to_upload as $file) {
            $dirname = dirname($file);
            if ($dirname == '.') {
                $dirname = '';
            }
            $unix_out .= 'cp "$CMS_EXTRACTED_AT/' . escapeshellcmd($file) . '" "' . escapeshellcmd($dirname) . '"/;' . "\n";
        }
        $ret_str .= do_lang('SH_COMMAND', nl2br(escape_html($unix_out)));
        $found_something = true;
    }

    // Alien files
    if (!$basic) {
        list($alien, $addon) = check_alien(get_file_base() . '/', '', false, null, null, array_flip($files_to_check));
        if (($alien != '') || ($addon != '')) {
            $ret_str .= '<div>';
            if ($alien != '') {
                $ret_str .= do_lang('WARNING_FILE_ALIEN', $alien);
            }
            if ($addon != '') {
                $ret_str .= do_lang('WARNING_FILE_ADDON', $addon);
            }
            $ret_str .= '<p class="associated-details"><a href="#!" onclick="var checkmarks=this.parentNode.parentNode.getElementsByTagName(\'input\'); for (var i=0;i&lt;checkmarks.length;i++) { checkmarks[i].checked=true; } return false;">' . do_lang('UPGRADER_CHECK_ALL') . '</a></p>';
            $proceed_icon = do_template('ICON', array('NAME' => 'buttons/proceed'));
            $ret_str .= '<button class="btn btn-primary btn-scr buttons--proceed" accesskey="c" type="submit">' . $proceed_icon . ' ' . do_lang('UPGRADER_AUTO_HANDLE') . '</button>';
            $ret_str .= '</div>';

            $found_something = true;
        }
        $ret_str .= '</form>';
    }

    if (!$found_something) {
        $ret_str = do_lang('NO_ISSUES_FOUND');
    }

    return $ret_str;
}

/**
 * Tell the user about any modules that need moving again (because the cms ones haven't moved).
 *
 * @return array Pair: HTML list of moved files, raw list
 */
function move_modules_ui()
{
    $out = '';
    $outr = array();

    $zones = find_all_zones();
    foreach ($zones as $zone) {
        $pages = find_all_pages($zone, 'modules');
        foreach (array_keys($pages) as $page) {
            // See if this isn't the true home of the module
            foreach ($zones as $zone2) {
                $_path_a = $zone2 . '/pages/modules/' . $page . '.php'; // potential true home
                $_path_b = $zone . '/pages/modules/' . $page . '.php'; // where it is now
                $path_a = zone_black_magic_filterer(get_file_base() . '/' . $_path_a);
                $path_b = zone_black_magic_filterer(get_file_base() . '/' . $_path_b);
                if (($zone2 != $zone) && (file_exists($path_a)) && (filemtime($path_a) >= filemtime($path_b))) {
                    if (($page == 'filedump') && ($zone2 == 'cms')) {
                        continue; // This has moved between versions
                    }

                    $out .= '<li><input type="checkbox" name="' . uniqid('', true) . '" value="move:' . escape_html($_path_a . ':' . $_path_b) . '" /> ' . do_lang('FILE_MOVED', '<kbd>' . escape_html($page) . '</kbd>', '<kbd>' . escape_html($zone2) . '</kbd>', '<kbd>' . escape_html($zone) . '</kbd>') . '</li>';
                    $outr[] = $path_b;
                }
            }
        }
    }

    return array($out, $outr);
}

/**
 * Check for out-dated files.
 *
 * @param  SHORT_TEXT $dir The directory we are scanning relative to
 * @param  SHORT_TEXT $rela The directory (relative) we are scanning
 * @param  array $manifest Unserialised data/files.dat
 * @param  array $hook_files A list of the contents of our addon registry hook files
 * @param  boolean $allow_merging Whether merging of CSS changes is allowed
 * @return array Tuple of various kinds of outdated/missing files
 */
function check_outdated__handle_overrides($dir, $rela, &$manifest, &$hook_files, $allow_merging)
{
    $outdated__outdated_original_and_override = '';
    $outdated__possibly_outdated_override = '';
    $outdated__missing_original_but_has_override = '';
    $outdated__uninstalled_addon_but_has_override = '';

    require_code('diff');
    require_code('files');

    $dh = @opendir($dir);
    if ($dh !== false) {
        while (($file = readdir($dh)) !== false) {
            if (should_ignore_file($rela . $file, IGNORE_ACCESS_CONTROLLERS | IGNORE_CUSTOM_ZONES | IGNORE_CUSTOM_THEMES | IGNORE_CUSTOM_DIRS | IGNORE_UPLOADS | IGNORE_SHIPPED_VOLATILE | IGNORE_UNSHIPPED_VOLATILE | IGNORE_NONBUNDLED | IGNORE_FLOATING)) {
                continue;
            }

            $is_dir = @is_dir($dir . $file);

            if (($is_dir) && (is_readable($dir . $file))) {
                list($_outdated__outdated_original_and_override, $_outdated__possibly_outdated_override, $_outdated__missing_original_but_has_override, $_outdated__uninstalled_addon_but_has_override) = check_outdated__handle_overrides($dir . $file . '/', $rela . $file . '/', $manifest, $hook_files, $allow_merging);
                $outdated__outdated_original_and_override .= $_outdated__outdated_original_and_override;
                $outdated__possibly_outdated_override .= $_outdated__possibly_outdated_override;
                $outdated__missing_original_but_has_override .= $_outdated__missing_original_but_has_override;
                $outdated__uninstalled_addon_but_has_override .= $_outdated__uninstalled_addon_but_has_override;
            } elseif (strpos($rela, '_custom') !== false) {
                $equiv_file = get_file_base() . '/' . str_replace('_custom', '', $rela) . $file;
                if ((!file_exists($equiv_file)) && (substr($rela, 0, 7) == 'themes/') && (substr_count($rela, '/') == 3)) {
                    $equiv_file = get_file_base() . '/' . str_replace('_custom', '', preg_replace('#themes/[^/]*/#', 'themes/default/', $rela)) . $file;
                }
                if (file_exists($equiv_file)) {
                    if ($allow_merging) {
                        if (file_exists($dir . $file . '.editfrom')) { // If we edited-from, then we use that to do the compare
                            $hash_on_disk = sprintf('%u', crc32(preg_replace('#[\r\n\t ]#', '', file_get_contents($dir . $file . '.editfrom'))));
                            $only_if_noncustom = false;
                        } else {
                            $hash_on_disk = sprintf('%u', crc32(preg_replace('#[\r\n\t ]#', '', file_get_contents($dir . $file))));
                            $only_if_noncustom = true;
                        }
                        $_true_hash = sprintf('%u', crc32(preg_replace('#[\r\n\t ]#', '', file_get_contents($equiv_file))));
                        if (array_key_exists($file, $manifest)) { // Get hash from perfection table
                            $true_hash = $manifest[$rela . $file][0];
                            if ($true_hash != $_true_hash) {
                                $outdated__outdated_original_and_override .= '<li><kbd>' . escape_html($rela . $file) . '</kbd></li>';
                                unset($manifest[$rela . $file]);
                                continue;
                            }
                        } else { // Get hash from non-overridden file (equiv file)
                            if ($only_if_noncustom) {
                                $true_hash = null; // Except we can't as we're not looking at the .editfrom and thus can't expect equality
                            } else {
                                $true_hash = $_true_hash;
                            }
                        }

                        if (($true_hash !== null) && ($hash_on_disk != $true_hash)) {
                            if ((function_exists('diff_compute_new')) && (substr($file, -4) == '.css') && ($true_hash !== 2) && (file_exists($dir . $file . '.editfrom')) && (cms_is_writable($dir . $file))) {
                                $new = diff_compute_new($equiv_file, $dir . $file . '.editfrom', $dir . $file);
                                cms_file_put_contents_safe($dir . $file . '.' . strval(time()), file_get_contents($dir . $file), FILE_WRITE_FIX_PERMISSIONS | FILE_WRITE_SYNC_FILE);
                                cms_file_put_contents_safe($dir . $file, $new, FILE_WRITE_FIX_PERMISSIONS | FILE_WRITE_SYNC_FILE);
                                $outdated__possibly_outdated_override .= '<li><kbd>' . escape_html($rela . $file) . '</kbd> ' . do_lang('AUTO_MERGED') . '</li>';
                                cms_file_put_contents_safe($dir . $file . '.editfrom', file_get_contents($equiv_file), FILE_WRITE_FIX_PERMISSIONS | FILE_WRITE_SYNC_FILE);
                            } else {
                                $outdated__possibly_outdated_override .= '<li><kbd>' . escape_html($rela . $file) . '</kbd></li>';
                            }
                        }
                    } else {
                        $outdated__possibly_outdated_override .= '<li><kbd>' . escape_html($rela . $file) . '</kbd></li>';
                    }

                    unset($manifest[$rela . $file]);
                } elseif (array_key_exists(str_replace('_custom', '', preg_replace('#themes/[^/]*/#', 'themes/default/', $rela)) . $file, $manifest)) {
                    $known_in_addon = false;
                    foreach ($hook_files as $hook_file) {
                        if (strpos($hook_file, str_replace('themes/default/css/', '', str_replace('themes/default/templates/', '', str_replace('_custom', '', preg_replace('#themes/[^/]*/#', 'themes/default/', $rela)))) . $file) !== false) {
                            $known_in_addon = true;
                            break;
                        }
                    }
                    if ($known_in_addon) {
                        $outdated__missing_original_but_has_override .= '<li><kbd>' . escape_html($rela . $file) . '</kbd></li>';
                    } else {
                        $outdated__uninstalled_addon_but_has_override .= '<li><kbd>' . escape_html($rela . $file) . '</kbd></li>';
                    }
                    unset($manifest[$rela . $file]);
                }
            }
        }

        closedir($dh);
    }

    return array($outdated__outdated_original_and_override, $outdated__possibly_outdated_override, $outdated__missing_original_but_has_override, $outdated__uninstalled_addon_but_has_override);
}

/**
 * Check for alien files.
 *
 * @param  SHORT_TEXT $dir The directory we are scanning relative to
 * @param  SHORT_TEXT $rela The directory (relative) we are scanning
 * @param  boolean $raw Whether to give raw output (no UI)
 * @param  ?array $addon_files List of files from non-bundled addons (a map: relative file paths as keys of map) (null: unknown, load them from addons_files table)
 * @param  ?array $old_files List of files from old version (a map: relative file paths as keys of map) (null: unknown, load them from files_previous.dat manifest)
 * @param  ?array $files List of verbatim files (a map: relative file paths as keys of map) (null: unknown, load them from files.day manifest)
 * @return array A pair: HTML list of alien files, HTML list of addon files
 */
function check_alien($dir, $rela = '', $raw = false, $addon_files = null, $old_files = null, $files = null)
{
    if ($addon_files === null) {
        $addon_files = collapse_2d_complexity('filename', 'addon_name', $GLOBALS['SITE_DB']->query_select('addons_files', array('filename', 'addon_name')));
    }
    if ($old_files === null) {
        $old_files = load_integrity_manifest(true);
    }
    if ($files === null) {
        $manifest = load_integrity_manifest();
        list($files_to_check, $hook_files) = load_files_list_of_installed_addons($manifest);
        $files = array_flip($files_to_check);
    }

    $alien = '';
    $alien_count = 0;
    $addon = '';

    require_code('files');

    $dh = @opendir($dir);
    if ($dh !== false) {
        if ($rela == '') {
            $old_addons_now_gone = array(
                'sources/hooks/systems/addon_registry/core_installation_uninstallation.php', // LEGACY
            );
            $modules_moved_intentionally = array(
            );
            foreach (array_merge($old_addons_now_gone, $modules_moved_intentionally) as $x) {
                if (file_exists(get_file_base() . '/' . $x)) {
                    $alien .= '<li>';
                    if (!$raw) {
                        $alien .= '<input checked="checked" type="checkbox" name="' . uniqid('', true) . '" value="delete:' . escape_html($x) . '" /> ';
                    }
                    $alien .= '<kbd>' . escape_html($x) . '</kbd></li>';
                }
            }
        }
        $dir_files = array();
        while (($file = readdir($dh)) !== false) {
            $dir_files[] = $file;
        }
        sort($dir_files);
        foreach ($dir_files as $file) {
            if (should_ignore_file($rela . $file, IGNORE_CUSTOM_DIRS | IGNORE_UPLOADS | IGNORE_CUSTOM_THEMES | IGNORE_CUSTOM_ZONES | IGNORE_NONBUNDLED | IGNORE_FLOATING | IGNORE_UNSHIPPED_VOLATILE | IGNORE_REVISION_FILES | IGNORE_EDITFROM_FILES)) {
                continue;
            }

            $is_dir = @is_dir($dir . $file);
            if (!is_readable($dir . $file)) {
                continue;
            }

            if ($is_dir) {
                if (!file_exists($dir . $file . '/_config.php')) {
                    if (($rela == '') && (!file_exists($dir . $file . '/pages'))) { // Scan to make sure it's not some other system placed under the webroot
                        $ok = false;
                        foreach (array_keys($files) as $f) {
                            if (substr($f, 0, strlen($rela . $file . '/')) == $rela . $file . '/') {
                                $ok = true;
                                break;
                            }
                        }
                        if (!$ok) {
                            continue;
                        }
                    }

                    list($_alien, $_addon) = check_alien($dir . $file . '/', $rela . $file . '/', $raw, $addon_files, $old_files, $files);
                    $alien .= $_alien;
                    $addon .= $_addon;
                }
            } else {
                if (!array_key_exists($rela . $file, $files)) {
                    if (strpos($rela, 'pages/modules') !== false) { // Check it isn't a moved module
                        $zones = find_all_zones();
                        $matches = array();
                        preg_match('#(.*)pages/modules#', $rela, $matches);
                        $current_zone = str_replace('/', '', $matches[1]);
                        foreach ($zones as $zone) {
                            if (array_key_exists(str_replace($current_zone . '/', $zone . (($zone == '') ? '' : '/'), $rela . $file), $files)) {
                                continue 2;
                            }
                        }
                    }
                    $disabled = '';
                    //if ((is_dir($dir . '/' . $file == '')) && ()) Not needed as this is only for files
                    $checked = '';

                    if (array_key_exists($rela . $file, $old_files)) {
                        $checked = 'checked="checked" ';
                    }
                    $file_html = '';
                    $file_html .= '<li>';
                    if (!$raw) {
                        $file_html .= '<input ' . $disabled . $checked . 'type="checkbox" name="' . uniqid('', true) . '" value="delete:' . escape_html($rela . $file) . '" /> ';
                    }
                    $file_html .= '<kbd>' . escape_html($rela . $file) . '</kbd></li>' . "\n";
                    if (array_key_exists($rela . $file, $addon_files)) {
                        $addon .= $file_html;
                    } else {
                        if ($alien_count <= 10000) { // Reasonable limit
                            $alien .= $file_html;
                            $alien_count++;
                        }
                    }
                }
            }
        }

        closedir($dh);
    }

    if ($alien_count > 10000) { // Reasonable limit
        $alien = '';
    }

    return array($alien, $addon);
}

/**
 * Do upgrader screen: remove addons UI.
 *
 * @ignore
 * @return string Output messages
 */
function upgrader_addon_remove_screen()
{
    $out = '';

    $out .= '<p>This addon removal tool remove all files from a given list of addons. It should only be used if you have placed files from addons (non-bundled or bundled) that are not actually installed/were uninstalled. Do not use it on addons that are installed (i.e. have tables and settings for them in the database already).</p>';
    $out .= '<p>';
    $out .= 'For example, it is useful if you have extracted the contents of the full manual installer package as part of an upgrade and need to now remove the files from addons you were not actually using (not a supported upgrade practice, but sometimes useful for developers to do).<br />';
    $out .= 'If you backed up the original website files under <kbd>old</kbd> and were upgrading to <kbd>new</kbd>, this shell command would find the list of addon names to remove:<br />';
    $out .= '<kbd>diff -rcw old/sources/hooks/systems/addon_registry new/sources/hooks/systems/addon_registry | sed -e "s/Only in new\/sources\/hooks\/systems\/addon_registry: \(.*\)\.php/\1/" -e \'tx\' -e \'d\' -e \':x\'</kbd>';
    $out .= '</p>';
    $out .= '<form action="upgrader.php?type=_addon_remove" method="post">';
    $out .= '<p><label for="addons">Addons to remove:</label><br /><textarea name="addons" id="addons" class="form-control" rows="10"></textarea>';
    $icon = do_template('ICON', array('NAME' => 'admin/delete3'));
    $out .= '<button class="btn btn-danger btn-scr" type="submit">' . $icon->evaluate() . ' Remove addon files</button>';
    $out .= post_fields_relay();
    $out .= '</form>';

    return $out;
}

/**
 * Do upgrader screen: remove addons actualiser.
 *
 * @ignore
 * @return string Output messages
 */
function upgrader__addon_remove_screen()
{
    $out = '';

    $_addons = post_param_string('addons');
    $addons = explode("\n", $_addons);
    $addon_files = array();
    require_code('addons');
    foreach ($addons as $addon) {
        $addon = trim($addon);
        if ($addon == '') {
            continue;
        }
        $details = read_addon_info($addon);
        $addon_files = array_merge($addon_files, $details['files']);
    }
    foreach ($addon_files as $addon_file) {
        afm_delete_file($addon_file);
    }
    $out .= '<p>The files have been deleted. Now, you may want install TARs for any bundled addons that were removed, in <kbd>exports/addons</kbd>, so you can reinstall them when you want. To generate these from a development copy of Composr, go to <kbd>adminzone/index.php?page=build_addons&export_bundled_addons=1&export_addons=0</kbd> and then copy them over.</p>';

    return $out;
}
