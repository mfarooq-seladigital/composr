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
 * @package    cns_forum
 */

/**
 * Hook class.
 */
class Hook_profiles_tabs_pts
{
    /**
     * Find whether this hook is active.
     *
     * @param  MEMBER $member_id_of The ID of the member who is being viewed
     * @param  MEMBER $member_id_viewing The ID of the member who is doing the viewing
     * @return boolean Whether this hook is active
     */
    public function is_active($member_id_of, $member_id_viewing)
    {
        return (($member_id_of == $member_id_viewing) || (has_privilege($member_id_viewing, 'view_other_pt')));
    }

    /**
     * Render function for profile tab hooks.
     *
     * @param  MEMBER $member_id_of The ID of the member who is being viewed
     * @param  MEMBER $member_id_viewing The ID of the member who is doing the viewing
     * @param  boolean $leave_to_ajax_if_possible Whether to leave the tab contents NULL, if tis hook supports it, so that AJAX can load it later
     * @return array A tuple: The tab title, the tab contents, the suggested tab order, the icon
     */
    public function render_tab($member_id_of, $member_id_viewing, $leave_to_ajax_if_possible = false)
    {
        $title = do_lang_tempcode('PRIVATE_TOPICS_INBOX');

        $order = 80;

        if ($leave_to_ajax_if_possible) {
            return array($title, null, $order, 'tool_buttons/inbox2');
        }

        require_code('cns_forumview');
        require_code('cns_topics');
        require_code('cns_general');
        require_lang('cns');

        $id = null;
        $current_filter_cat = get_param_string('category', '');

        $root = get_param_integer('keep_forum_root', db_get_first_id());

        $max = get_param_integer('forum_max', intval(get_option('private_topics_per_page')));
        $start = get_param_integer('forum_start', get_param_integer('kfs', 0));

        $root = db_get_first_id();

        list($content) = cns_render_forumview($id, null, $current_filter_cat, $max, $start, $root, $member_id_of, new Tempcode());

        $content = do_template('CNS_MEMBER_PROFILE_PTS', array('_GUID' => '5d0cae3320634a1e4eb345154c853c35', 'CONTENT' => $content));

        return array($title, $content, $order, 'tool_buttons/inbox2');
    }
}
