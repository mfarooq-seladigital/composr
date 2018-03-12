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
 * @package    cns_forum
 */

/**
 * Block class.
 */
class Block_main_member_bar
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled)
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array();
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters
     * @return Tempcode The result of execution
     */
    public function run($map)
    {
        $error_msg = new Tempcode();
        if (!addon_installed__messaged('cns_forum', $error_msg)) {
            return $error_msg;
        }

        if (get_forum_type() != 'cns') {
            return paragraph(do_lang_tempcode('NO_CNS'), '22lg0bjjoy2j1jl25n4t1sh9xsgzj8no', 'red-alert');
        }

        require_css('cns');
        require_css('cns_header');
        require_lang('cns');

        $block_id = get_block_id($map);

        $member_id = get_member();

        if (!is_guest($member_id)) { // Logged in user
            require_code('cns_general');

            $member_info = cns_read_in_member_profile($member_id, array('avatar', 'username', 'num_points_advance', 'points', 'posts', 'primary_group_name', 'last_visit_date', 'new_topics', 'new_posts'));

            $profile_url = $GLOBALS['CNS_DRIVER']->member_profile_url($member_id, true);

            $max_avatar_height = cns_get_member_best_group_property($member_id, 'max_avatar_height');

            // Misc (shared with side_personal_stats block)
            require_code('global4');
            list($links, $links_ecommerce, $details, $num_unread_pps) = member_personal_links_and_details($member_id);

            // Any unread PT-PPs?
            $pt_extra = ($num_unread_pps == 0) ? new Tempcode() : do_lang_tempcode('NUM_UNREAD', escape_html(integer_format($num_unread_pps)));
            $private_topic_url = build_url(array('page' => 'members', 'type' => 'view', 'id' => $member_id), get_module_zone('members'), array(), true, false, false, 'tab--pts');

            $bar = do_template('CNS_MEMBER_BAR', array(
                '_GUID' => 's3kdsadf0p3wsjlcfksdj',
                'AVATAR_URL' => isset($member_info['avatar']) ? $member_info['avatar'] : '',
                'PROFILE_URL' => $profile_url,
                'USERNAME' => $member_info['username'],
                'LOGOUT_URL' => build_url(array('page' => 'login', 'type' => 'logout'), get_module_zone('login')),
                'NUM_POINTS_ADVANCE' => isset($member_info['num_points_advance']) ? integer_format($member_info['num_points_advance']) : null,
                'NUM_POINTS' => isset($member_info['points']) ? integer_format($member_info['points']) : '',
                'NUM_POSTS' => integer_format($member_info['posts']),
                'PRIMARY_GROUP' => $member_info['primary_group_name'],
                'LAST_VISIT_DATE_RAW' => strval($member_info['last_visit_time']),
                'LAST_VISIT_DATE' => $member_info['last_visit_date'],
                'PRIVATE_TOPIC_URL' => $private_topic_url,
                'NEW_POSTS_URL' => build_url(array('page' => 'vforums', 'type' => 'browse'), get_module_zone('vforums')),
                'UNREAD_TOPICS_URL' => build_url(array('page' => 'vforums', 'type' => 'unread'), get_module_zone('vforums')),
                'RECENTLY_READ_URL' => build_url(array('page' => 'vforums', 'type' => 'recently_read'), get_module_zone('vforums')),
                'INLINE_PERSONAL_POSTS_URL' => build_url(array('page' => 'topicview'), get_module_zone('topicview')),
                'UNANSWERED_TOPICS_URL' => build_url(array('page' => 'vforums', 'type' => 'unanswered'), get_module_zone('vforums')),
                'INVOLVED_TOPICS_URL' => build_url(array('page' => 'vforums', 'type' => 'involved'), get_module_zone('vforums')),
                'PT_EXTRA' => $pt_extra,
                'NUM_UNREAD_PTS' => strval($num_unread_pps),
                'NEW_TOPICS' => integer_format($member_info['new_topics']),
                'NEW_POSTS' => integer_format($member_info['new_posts']),
                'MAX_AVATAR_HEIGHT' => strval($max_avatar_height),
                'LINKS' => $links,
                'LINKS_ECOMMERCE' => $links_ecommerce,
                'DETAILS' => $details,
            ));
        } else { // Guest
            list($full_url, $login_url, $join_url) = get_login_url();
            $bar = do_template('CNS_GUEST_BAR', array(
                '_GUID' => '3b613deec9d4786f5b53dbd52af00d3c',
                'LOGIN_URL' => $login_url,
                'JOIN_URL' => $join_url,
                'FULL_LOGIN_URL' => $full_url,
                'NEW_POSTS_URL' => build_url(array('page' => 'vforums', 'type' => 'browse'), get_module_zone('vforums')),
                'UNANSWERED_TOPICS_URL' => build_url(array('page' => 'vforums', 'type' => 'unanswered'), get_module_zone('vforums')),
            ));
        }

        return do_template('BLOCK_MAIN_MEMBER_BAR', array(
            '_GUID' => '0ef12f7b17b7b40dca473db519e58a52',
            'BLOCK_ID' => $block_id,
            'BAR' => $bar,
        ));
    }
}
