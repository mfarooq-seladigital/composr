<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

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
These are special functions used by the installer and upgrader.
*/

/**
 * Get the list of files that need CHmodding for write access.
 *
 * @param  ID_TEXT $lang Language to use
 * @param  boolean $runtime Whether to include wildcards represented runtime-created chmoddable files
 * @return array The list of files
 */
function get_chmod_array($lang, $runtime = false)
{
    $extra_files = array();

    if (function_exists('find_all_hooks')) {
        $hooks = find_all_hooks('systems', 'addon_registry');
        $hook_keys = array_keys($hooks);
        foreach ($hook_keys as $hook) {
            /*require_code('hooks/systems/addon_registry/' . filter_naughty_harsh($hook));
            $object = object_factory('Hook_addon_registry_' . filter_naughty_harsh($hook));
            $extra_files = array_merge($extra_files, $object->get_chmod_array());*/

            // Save memory compared to above commented code...

            $path = get_custom_file_base() . '/sources_custom/hooks/systems/addon_registry/' . filter_naughty_harsh($hook) . '.php';
            if (!file_exists($path)) {
                $path = get_file_base() . '/sources/hooks/systems/addon_registry/' . filter_naughty_harsh($hook) . '.php';
            }
            $matches = array();
            if (preg_match('#function get_chmod_array\(\)\s*\{([^\}]*)\}#', file_get_contents($path), $matches) != 0) {
                $extra_files = array_merge($extra_files, eval($matches[1]));
            }
        }
    }

    if ($runtime) {
        $extra_files = array_merge($extra_files, array(
            'adminzone/pages/comcode_custom/*/*.txt',
            'adminzone/pages/html_custom/*/*.htm',
            'cms/pages/comcode_custom/*/*.txt',
            'cms/pages/html_custom/*/*.htm',
            'data_custom/modules/admin_backup/*',
            'data_custom/modules/admin_stats/*',
            'data_custom/modules/chat/*',
            'data_custom/modules/web_notifications/*',
            'data_custom/sitemaps/*',
            'data_custom/spelling/personal_dicts/*',
            'data_custom/xml_config/*.xml',
            'exports/*/*',
            'forum/pages/comcode_custom/*/*.txt',
            'forum/pages/html_custom/*/*.htm',
            'imports/*/*',
            'lang_custom/*/*.ini',
            'pages/comcode_custom/*/*.txt',
            'pages/html_custom/*/*.htm',
            'site/pages/comcode_custom/*/*.txt',
            'site/pages/html_custom/*/*.htm',
            'text_custom/*.txt',
            'text_custom/*/*.txt',
            'themes/*/css_custom/*',
            'themes/*/images_custom/*',
            'themes/*/javascript_custom/*',
            'themes/*/templates_custom/*',
            'themes/*/text_custom/*',
            'themes/*/xml_custom/*',
            'uploads/attachments/*',
            'uploads/attachments_thumbs/*',
            'uploads/auto_thumbs/*',
            'uploads/banners/*',
            'uploads/catalogues/*',
            'uploads/cns_avatars/*',
            'uploads/cns_cpf_upload/*',
            'uploads/cns_photos/*',
            'uploads/cns_photos_thumbs/*',
            'uploads/downloads/*',
            'uploads/filedump/*',
            'uploads/galleries/*',
            'uploads/galleries_thumbs/*',
            'uploads/personal_sound_effects/*',
            'uploads/repimages/*',
            'uploads/watermarks/*',
            'uploads/website_specific/*',
        ));
    }

    return array_merge(
        $extra_files,
        array(
            'adminzone/pages/comcode_custom/' . $lang,
            'adminzone/pages/html_custom/' . $lang,
            'caches/guest_pages',
            'caches/lang',
            'caches/lang/' . $lang,
            'caches/persistent',
            'caches/self_learning',
            'caches/http',
            'cms/pages/comcode_custom/' . $lang,
            'cms/pages/html_custom/' . $lang,
            'data_custom/errorlog.php',
            'data_custom/firewall_rules.txt',
            'data_custom/modules/admin_backup',
            'data_custom/modules/admin_stats',
            'data_custom/modules/chat',
            'data_custom/modules/web_notifications',
            'data_custom/sitemaps',
            'data_custom/spelling/personal_dicts',
            'data_custom/xml_config',
            'exports/addons',
            'exports/backups',
            'exports/file_backups',
            'forum/pages/comcode_custom/' . $lang,
            'forum/pages/html_custom/' . $lang,
            'imports/addons',
            'lang_custom',
            'lang_custom/' . $lang,
            'pages/comcode_custom/' . $lang,
            'pages/html_custom/' . $lang,
            'temp',
            'site/pages/comcode_custom/' . $lang,
            'site/pages/html_custom/' . $lang,
            'text_custom',
            'text_custom/' . $lang,
            'themes',
            'themes/admin/css_custom',
            'themes/admin/images_custom',
            'themes/admin/javascript_custom',
            'themes/admin/templates_cached/' . $lang,
            'themes/admin/templates_custom',
            'themes/admin/text_custom',
            'themes/admin/xml_custom',
            'themes/default/css_custom',
            'themes/default/images_custom',
            'themes/default/javascript_custom',
            'themes/default/templates_cached/' . $lang,
            'themes/default/templates_custom',
            'themes/default/text_custom',
            'themes/default/theme.ini',
            'themes/default/xml_custom',
            'themes/map.ini',
            'uploads/attachments',
            'uploads/attachments_thumbs',
            'uploads/auto_thumbs',
            'uploads/banners',
            'uploads/catalogues',
            'uploads/cns_avatars',
            'uploads/cns_cpf_upload',
            'uploads/cns_photos',
            'uploads/cns_photos_thumbs',
            'uploads/downloads',
            'uploads/filedump',
            'uploads/galleries',
            'uploads/galleries_thumbs',
            'uploads/incoming',
            'uploads/personal_sound_effects',
            'uploads/repimages',
            'uploads/watermarks',
            'uploads/website_specific',
            '_config.php',
        )
    );
}
