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
 * @package    galleries
 */

/**
 * Script handler for downloading a gallery, as specified by GET parameters.
 */
function download_gallery_script()
{
    if (!addon_installed('galleries')) {
        warn_exit(do_lang_tempcode('MISSING_ADDON', escape_html('galleries')));
    }

    // Closed site
    $site_closed = get_option('site_closed');
    if (($site_closed == '1') && (!has_privilege(get_member(), 'access_closed_site')) && (!$GLOBALS['IS_ACTUALLY_ADMIN'])) {
        header('Content-type: text/plain; charset=' . get_charset());
        @exit(get_option('closed'));
    }

    $cat = get_param_string('cat');

    if (!has_category_access(get_member(), 'galleries', $cat)) {
        access_denied('CATEGORY_ACCESS');
    }

    check_privilege('may_download_gallery', array('galleries', $cat));
    if ((strpos($cat, "\n") !== false) || (strpos($cat, "\r") !== false)) {
        log_hack_attack_and_exit('HEADER_SPLIT_HACK');
    }

    $num_videos = $GLOBALS['SITE_DB']->query_select_value('videos', 'COUNT(*)', array('cat' => $cat, 'validated' => 1));

    require_lang('galleries');

    require_code('tasks');
    $ret = call_user_func_array__long_task(do_lang('DOWNLOAD_GALLERY_CONTENTS'), get_screen_title('DOWNLOAD_GALLERY_CONTENTS'), 'download_gallery', array($cat), false, $num_videos == 0);

    $echo = globalise($ret, null, '', true);
    $echo->evaluate_echo(null);
}
