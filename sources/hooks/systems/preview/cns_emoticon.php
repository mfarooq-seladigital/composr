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
 * @package    core_cns
 */

/**
 * Hook class.
 */
class Hook_preview_cns_emoticon
{
    /**
     * Find whether this preview hook applies.
     *
     * @return array Triplet: Whether it applies, the attachment ID type, whether the forum DB is used [optional]
     */
    public function applies()
    {
        $applies = (get_param_string('page', '') == 'admin_cns_emoticons');
        return array($applies, null, false);
    }

    /**
     * Run function for preview hooks.
     *
     * @return array A pair: The preview, the updated post Comcode
     */
    public function run()
    {
        require_code('uploads');

        $urls = get_url('', 'file', 'safe_mode_temp', 0, CMS_UPLOAD_IMAGE, false);
        if ($urls[0] == '') {
            if (!is_null(post_param_integer('id', null))) {
                $rows = $GLOBALS['SITE_DB']->query_select('cns_emoticons', array('e_theme_img_code'), array('id' => post_param_integer('id')), '', 1);
                $urls = $rows[0];

                $url = find_theme_image($urls['e_theme_img_code']);
            } elseif (!is_null(post_param_string('theme_img_code', null))) {
                $url = find_theme_image(post_param_string('theme_img_code'));
            } else {
                warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN_UPLOAD'));
            }
        } else {
            $url = $urls[0];
        }

        require_code('images');
        $preview = do_image_thumb(url_is_local($url) ? (get_custom_base_url() . '/' . $url) : $url, post_param_string('code'), true);

        return array($preview, null);
    }
}
