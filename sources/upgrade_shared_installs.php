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
function init__upgrade_shared_installs()
{
    require_code('upgrade_db_upgrade');
    require_lang('upgrade');
}

/**
 * Do upgrader screen: shared installs.
 *
 * @ignore
 * @return string Output messages
 */
function upgrader_sharedinstall_screen()
{
    // We typically will never use this function (called from main upgrader UI).
    // Instead we use demonstratr_upgrade.php, which steps things through better, and calls the same upgrade_sharedinstall_sites function.

    upgrade_sharedinstall_sites();

    $out = '';

    global $SITE_INFO;
    $cmd = 'mysqldump -u' . escapeshellarg_wrap($SITE_INFO['db_site_user'] . '_shareddemo') . ' -p' . escapeshellarg_wrap($SITE_INFO['db_site_password']) . ' ' . escapeshellarg_wrap($SITE_INFO['db_site']) . '_shareddemo';
    $out .= '<p>Now regenerate <kbd>template.sql</kbd>, using something like <kbd>' . escape_html($cmd) . ' > ~/public_html/uploads/website_specific/compo.sr/demonstratr/template.sql</kbd></p>';

    return $out;
}

/**
 * Upgrade shared installs.
 *
 * @param  integer $from Position to proceed from
 */
function upgrade_sharedinstall_sites($from = 0)
{
    global $CURRENT_SHARE_USER, $SITE_INFO, $TABLE_LANG_FIELDS_CACHE;

    // Find sites
    $sites = array();
    foreach (array_keys($SITE_INFO) as $key) {
        $matches = array();
        if (preg_match('#^custom_user_(.*)#', $key, $matches) != 0) {
            $sites[] = $matches[1];
        }
    }

    disable_php_memory_limit();

    $total = count($sites);

    foreach ($sites as $i => $site) {
        if (php_function_allowed('set_time_limit')) {
            @set_time_limit(0);
        }

        if (($i < $from) && ($site != 'shareddemo')) {
            continue;
        }

        // Change active site
        $CURRENT_SHARE_USER = $site;
        $TABLE_LANG_FIELDS_CACHE = array();
        _general_db_init();

        // Reset DB
        $GLOBALS['SITE_DB'] = new DatabaseConnector(get_db_site(), get_db_site_host(), get_db_site_user(), get_db_site_password(), get_table_prefix());
        $GLOBALS['FORUM_DB'] = $GLOBALS['SITE_DB'];

        // NB: File path will be ok

        // NB: Other internal caching could need changing in the future, but works at time of writing

        // Go!
        automate_upgrade();

        echo 'Upgraded ' . escape_html($site) . ' (' . escape_html(number_format($i + 1) . ' of ' . number_format($total)) . ')<br />';
        flush();
    }
}

/**
 * Automatically go through full upgrade for current site.
 */
function automate_upgrade()
{
    automate_upgrade__safe();

    // Themes
    require_code('upgrade_themes');
    require_code('themes2');
    $themes = find_all_themes();
    foreach (array_keys($themes) as $theme) {
        $from = round(cms_version_number()) - 1;
        $to = cms_version_number();
        upgrade_theme($theme, $from, $to, false);
    }
}


/**
 * Automatically go through a partial upgrade for current site.
 */
function automate_upgrade__safe()
{
    // Database
    clear_caches_1();
    clear_caches_2();
    version_specific();
    upgrade_modules();
    rebuild_zone_files();

    // Conversr
    cns_upgrade();
}
