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
 * Get the poster name a Guest may have specified, after sanitising it.
 *
 * @param  boolean $is_required_field If it is a required field (null: typically no, but look at hidden option for it).
 * @return string Poster name.
 */
function cns_get_safe_specified_poster_name($is_required_field = null)
{
    if ($is_required_field === null) {
        $is_required_field = (get_value('force_guest_names') === '1');
    }

    if ((get_value('force_guest_names') === '1') && (is_guest())) {
        $poster_name_if_guest = post_param_string('poster_name_if_guest');
    } else {
        $poster_name_if_guest = post_param_string('poster_name_if_guest', null);
    }
    if ($poster_name_if_guest == '') {
        $poster_name_if_guest = null;
    }
    if (!is_null($poster_name_if_guest)) {
        $poster_name_if_guest = trim($poster_name_if_guest);

        if ($is_required_field) {
            if ($poster_name_if_guest == do_lang('GUEST')) {
                warn_exit(do_lang_tempcode('NO_PARAMETER_SENT', escape_html(post_param_string('label_for__poster_name_if_guest', 'poster_name_if_guest'))));
            }
        }

        $restricted_usernames = explode(',', get_option('restricted_usernames'));
        $restricted_usernames[] = do_lang('UNKNOWN');
        $restricted_usernames[] = do_lang('SYSTEM');
        if (!is_null($GLOBALS['FORUM_DRIVER']->get_member_from_username($poster_name_if_guest))) {
            $restricted_usernames[] = $poster_name_if_guest;
        }
        foreach ($restricted_usernames as $_restricted_username) {
            $restricted_username = trim($_restricted_username);
            if ($restricted_username == '') {
                continue;
            }
            if ($poster_name_if_guest == $restricted_username) {
                $poster_name_if_guest = $poster_name_if_guest . ' (' . do_lang('GUEST') . ')';
                break;
            }
        }
    } else {
        $poster_name_if_guest = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
    }
    return $poster_name_if_guest;
}

/**
 * Check to see if a member deserves promotion, and handle it.
 *
 * @param  ?MEMBER $member_id The member (null: current member).
 */
function cns_member_handle_promotion($member_id = null)
{
    if (!addon_installed('points')) {
        return;
    }
    if (get_mass_import_mode()) {
        return;
    }

    if (is_null($member_id)) {
        $member_id = get_member();
    }

    require_code('cns_members');
    if (cns_is_ldap_member($member_id)) {
        return;
    }

    require_code('points');
    $total_points = total_points($member_id);
    $groups = $GLOBALS['CNS_DRIVER']->get_members_groups($member_id, false, true);
    $or_list = '';
    foreach ($groups as $id) {
        if ($or_list != '') {
            $or_list .= ' OR ';
        }
        $or_list .= 'id=' . strval($id);
    }
    $promotions = $GLOBALS['FORUM_DB']->query('SELECT id,g_promotion_target FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_groups WHERE (' . $or_list . ') AND g_promotion_target IS NOT NULL AND g_promotion_threshold<=' . strval($total_points) . ' ORDER BY g_promotion_threshold');
    $promotes_today = array();
    foreach ($promotions as $promotion) {
        $_p = $promotion['g_promotion_target'];
        if ((!array_key_exists($_p, $groups)) && (!array_key_exists($_p, $promotes_today))) { // If we're not already in the group
            // If it is our primary
            if ($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id, 'm_primary_group') == $promotion['id']) {
                $GLOBALS['FORUM_DB']->query_update('f_members', array('m_primary_group' => $_p), array('id' => $member_id), '', 1);
            } else {
                $GLOBALS['FORUM_DB']->query_delete('f_group_members', array('gm_member_id' => $member_id, 'gm_group_id' => $_p), '', 1);
                $GLOBALS['FORUM_DB']->query_insert('f_group_members', array('gm_validated' => 1, 'gm_member_id' => $member_id, 'gm_group_id' => $_p), false, true);
                $GLOBALS['FORUM_DB']->query_delete('f_group_members', array('gm_member_id' => $member_id, 'gm_group_id' => $promotion['id']), '', 1); // It's a transition, so remove old membership
            }
            $GLOBALS['FORUM_DB']->query_insert('f_group_join_log', array(
                'member_id' => $member_id,
                'usergroup_id' => $_p,
                'join_time' => time()
            ));

            // Notify the member
            $subject = do_lang('RANK_PROMOTED_MAIL_SUBJECT', cns_get_group_name($_p), null, null, get_lang($member_id));
            $mail = do_lang('RANK_PROMOTED_MAIL', comcode_escape(cns_get_group_name($_p)), null, null, get_lang($member_id));
            require_code('notifications');
            dispatch_notification('cns_rank_promoted', null, $subject, $mail, array($member_id));

            // Carefully update run-time caching
            global $USERS_GROUPS_CACHE;
            foreach (array(true, false) as $a) {
                foreach (array(true, false) as $b) {
                    if (isset($USERS_GROUPS_CACHE[$member_id][$a][$b])) {
                        $groups = $USERS_GROUPS_CACHE[$member_id][$a][$b];
                        $pos = array_search($_p, $groups);
                        if ($pos !== false) {
                            unset($groups[$pos]);
                        }
                        $groups[] = $promotion['id'];
                        $USERS_GROUPS_CACHE[$member_id][$a][$b] = $groups;
                    }
                }
            }

            $promotes_today[$_p] = 1;
        }
    }

    if (count($promotes_today) != 0) {
        $name = $GLOBALS['CNS_DRIVER']->get_member_row_field($member_id, 'm_username');
        log_it('MEMBER_PROMOTED_AUTOMATICALLY', strval($member_id), $name);
    }
}

/**
 * Send out a notification, as a topic just got a new post.
 *
 * @param  URLPATH $url The URL to view the new post.
 * @param  AUTO_LINK $topic_id The ID of the topic that got posted in.
 * @param  ?AUTO_LINK $forum_id The forum that the topic is in (null: find out from the DB).
 * @param  MEMBER $sender_member_id The member that made the post triggering this tracking notification.
 * @param  boolean $is_starter Whether the post started a new topic.
 * @param  LONG_TEXT $post The post, in Comcode format.
 * @param  SHORT_TEXT $topic_title The topic title (blank: look it up from the $topic_id). If non-blank we must use it as it is implying the database might not have the correct value yet.
 * @param  ?MEMBER $_limit_to Only send the notification to this member (null: no such limit).
 * @param  boolean $is_pt Whether this is for a Private Topic.
 * @param  ?ID_TEXT $no_notify_for__notification_code DO NOT send notifications to: The notification code (null: no restriction)
 * @param  ?SHORT_TEXT $no_notify_for__code_category DO NOT send notifications to: The category within the notification code (null: none / no restriction)
 */
function cns_send_topic_notification($url, $topic_id, $forum_id, $sender_member_id, $is_starter, $post, $topic_title, $_limit_to = null, $is_pt = false, $no_notify_for__notification_code = null, $no_notify_for__code_category = null)
{
    if ((is_null($forum_id)) && ($is_starter)) {
        return;
    }

    if ($topic_title == '') {
        $topic_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('t_pt_to', 't_pt_from', 't_cache_first_title'), array('id' => $topic_id), '', 1);
        if (!array_key_exists(0, $topic_info)) {
            return; // Topic's gone missing somehow (e.g. race condition)
        }
        $topic_title = $topic_info[0]['t_cache_first_title'];
    }

    $sender_displayname = $GLOBALS['FORUM_DRIVER']->get_username($sender_member_id, true);
    $sender_username = $GLOBALS['FORUM_DRIVER']->get_username($sender_member_id);

    require_lang('cns');
    $subject = do_lang($is_starter ? 'TOPIC_NOTIFICATION_MAIL_SUBJECT' : 'POST_NOTIFICATION_MAIL_SUBJECT', get_site_name(), $topic_title, array($sender_displayname, $sender_username));
    $mail = do_lang($is_starter ? 'TOPIC_NOTIFICATION_MAIL' : 'POST_NOTIFICATION_MAIL', comcode_escape(get_site_name()), comcode_escape($url), array(comcode_escape($sender_displayname), $post, $topic_title, strval($sender_member_id), comcode_escape($sender_username)));

    $limit_to = is_null($_limit_to) ? array() : array($_limit_to);

    if ($is_pt) {
        $topic_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('t_pt_to', 't_pt_from', 't_cache_first_title'), array('id' => $topic_id), '', 1);
        if (!array_key_exists(0, $topic_info)) {
            return; // Topic's gone missing somehow (e.g. race condition)
        }

        $limit_to[] = $topic_info[0]['t_pt_to'];
        $limit_to[] = $topic_info[0]['t_pt_from'];
        $limit_to = array_merge($limit_to, collapse_1d_complexity('s_member_id', $GLOBALS['FORUM_DB']->query_select('f_special_pt_access', array('s_member_id'), array('s_topic_id' => $topic_id))));
    }

    require_code('notifications');
    dispatch_notification('cns_topic', strval($topic_id), $subject, $mail, (count($limit_to) == 0) ? null : $limit_to, $sender_member_id, 3, false, false, $no_notify_for__notification_code, $no_notify_for__code_category);
}

/**
 * Update a topic's caching.
 *
 * @param  AUTO_LINK $topic_id The ID of the topic to update caching of.
 * @param  ?integer $post_count_dif The post count difference we know the topic has undergone (null: we'll need to work out from scratch how many posts are in the topic)
 * @param  boolean $last Whether this is the latest post in the topic.
 * @param  boolean $first Whether this is the first post in the topic.
 * @param  ?AUTO_LINK $last_post_id The ID of the last post in the topic (null: unknown).
 * @param  ?TIME $last_time The time of the last post in the topic (null: unknown).
 * @param  ?string $last_title The title of the last post in the topic (null: unknown).
 * @param  ?AUTO_LINK $last_post The ID of the last posts language string for the topic (null: unknown).
 * @param  ?string $last_username The last username to post in the topic (null: unknown).
 * @param  ?MEMBER $last_member_id The ID of the last member to post in the topic (null: unknown).
 */
function cns_force_update_topic_caching($topic_id, $post_count_dif = null, $last = true, $first = false, $last_post_id = null, $last_time = null, $last_title = null, $last_post = null, $last_username = null, $last_member_id = null)
{
    $first_title = '';
    if (is_null($last_post_id)) {
        if ($first) { // We're updating caching of the first
            $posts = $GLOBALS['FORUM_DB']->query_select('f_posts', array('*'), array('p_topic_id' => $topic_id), 'ORDER BY p_time ASC,id ASC', 1);
            if (!array_key_exists(0, $posts)) {
                $first_post_id = null;
                $first_time = null;
                $first_post = null;
                $first_title = '';
                $first_username = '';
                $first_member_id = null;
            } else {
                $first_post_id = $posts[0]['id'];
                $first_post = $posts[0]['p_post'];
                $first_time = $posts[0]['p_time'];
                $first_title = $posts[0]['p_title'];
                $first_username = $posts[0]['p_poster_name_if_guest'];
                $first_member_id = $posts[0]['p_poster'];
            }
        }
        if ($last) { // We're updating caching of the last
            $posts = $GLOBALS['FORUM_DB']->query_select('f_posts', array('*'), array('p_intended_solely_for' => null, 'p_topic_id' => $topic_id), 'ORDER BY p_time DESC,id DESC', 1);
            if (!array_key_exists(0, $posts)) {
                $last_post_id = null;
                $last_time = null;
                $last_title = '';
                $last_username = '';
                $last_member_id = null;
            } else {
                $last_post_id = $posts[0]['id'];
                $last_time = $posts[0]['p_time'];
                $last_title = $posts[0]['p_title'];
                $last_username = $posts[0]['p_poster_name_if_guest'];
                $last_member_id = $posts[0]['p_poster'];
            }
        }
    } else {
        $first_post_id = $last_post_id;
        $first_time = $last_time;
        $first_post = $last_post;
        $first_title = $last_title;
        $first_username = $last_username;
        $first_member_id = $last_member_id;
    }

    if ($first_title == '') {
        $first_title = do_lang('NO_TOPIC_TITLE', strval($topic_id));
    }

    if ($first) {
        $update_first =
            't_cache_first_post_id=' . (is_null($first_post_id) ? 'NULL' : strval($first_post_id)) . ',
        ' . (($first_title == '') ? '' : ('t_cache_first_title=\'' . db_escape_string($first_title) . '\'') . ',') . '
        t_cache_first_time=' . (is_null($first_time) ? 'NULL' : strval($first_time)) . ',
        t_cache_first_post=' . (multi_lang_content() ? ((is_null($first_post) ? '\'NULL\'' : strval($first_post))) : '\'\'') . ',
        t_cache_first_username=\'' . db_escape_string($first_username) . '\',
        t_cache_first_member_id=' . (is_null($first_member_id) ? 'NULL' : strval($first_member_id)) . ',';
    }

    if ($last) {
        $update_last =
            't_cache_last_post_id=' . (is_null($last_post_id) ? 'NULL' : strval($last_post_id)) . ',
        t_cache_last_title=\'' . db_escape_string($last_title) . '\',
        t_cache_last_time=' . (is_null($last_time) ? 'NULL' : strval($last_time)) . ',
        t_cache_last_username=\'' . db_escape_string(substr($last_username, 0, 255)) . '\',
        t_cache_last_member_id=' . (is_null($last_member_id) ? 'NULL' : strval($last_member_id)) . ',';
    }

    $GLOBALS['FORUM_DB']->query('UPDATE ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics SET ' .
                                ($first ? $update_first : '') .
                                ($last ? $update_last : '') .
                                (
                                !is_null($post_count_dif)
                                    ?
                                    ('t_cache_num_posts=(t_cache_num_posts+' . strval($post_count_dif) . ')')
                                    :
                                    ('t_cache_num_posts=' . strval($GLOBALS['FORUM_DB']->query_select_value_if_there('f_posts', 'COUNT(*)', array('p_topic_id' => $topic_id, 'p_intended_solely_for' => null))))
                                ) .
                                ' WHERE id=' . strval($topic_id),
        null,
        null,
        false,
        true
    );
}

/**
 * Update a forums cached details.
 *
 * @param  AUTO_LINK $forum_id The ID of the forum to update the cached details of.
 * @param  ?integer $num_topics_increment How much to increment the topic count by (null: It has to be completely recalculated).
 * @param  ?integer $num_posts_increment How much to increment the post count by (null: It has to be completely recalculated).
 * @param  ?AUTO_LINK $last_topic_id The ID of the last topic (null: Unknown, it will have to be looked up).
 * @param  ?string $last_title The title of the last topic (null: Unknown, it will have to be looked up).
 * @param  ?TIME $last_time The last post time of the last topic (null: Unknown, it will have to be looked up).
 * @param  ?string $last_username The last post username of the last topic (null: Unknown, it will have to be looked up).
 * @param  ?MEMBER $last_member_id The last post member of the last topic (null: Unknown, it will have to be looked up).
 * @param  ?AUTO_LINK $last_forum_id The forum the last post was in (note this makes sense, because there may be subforums under this forum that we have to take into account). (null: Unknown, it will have to be looked up).
 */
function cns_force_update_forum_caching($forum_id, $num_topics_increment = null, $num_posts_increment = null, $last_topic_id = null, $last_title = null, $last_time = null, $last_username = null, $last_member_id = null, $last_forum_id = null)
{
    if ((is_null($num_topics_increment)) && (!is_null($num_posts_increment))) {
        $num_topics_increment = 0;
    }
    if ((!is_null($num_topics_increment)) && (is_null($num_posts_increment))) {
        $num_posts_increment = 0;
    }

    if (is_null($last_topic_id)) { // We don't know what was last, so we'll have to work it out
        require_code('cns_forums');
        $or_list = cns_get_all_subordinate_forums($forum_id, 't_forum_id', null, true);
        $last_topic = $GLOBALS['FORUM_DB']->query('SELECT * FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics WHERE (' . $or_list . ') AND t_validated=1 ORDER BY t_cache_last_time DESC', 1, null, false, true);
        if (!array_key_exists(0, $last_topic)) { // No topics left apparently
            $last_topic_id = null;
            $last_title = '';
            $last_time = null;
            $last_username = '';
            $last_member_id = null;
            $last_forum_id = null;
        } else {
            $last_topic_id = $last_topic[0]['id'];
            $last_title = $last_topic[0]['t_cache_first_title']; // Actually, the first title of the last topic
            $last_time = $last_topic[0]['t_cache_last_time'];
            $last_username = $last_topic[0]['t_cache_last_username'];
            $last_member_id = $last_topic[0]['t_cache_last_member_id'];
            $last_forum_id = $last_topic[0]['t_forum_id'];
        }
    } else {
        if (is_null($num_topics_increment)) {
            $or_list = cns_get_all_subordinate_forums($forum_id, 't_forum_id', null, true);
        }
    }
    if (is_null($num_topics_increment)) { // Apparently we're doing a recount
        $num_topics = $GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) AS topic_count FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics WHERE ' . $or_list, false, true);
        $or_list_2 = str_replace('t_forum_id', 'p_cache_forum_id', $or_list);
        $num_posts = $GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) AS post_count FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE p_intended_solely_for IS NULL AND (' . $or_list_2 . ')', false, true);
    }

    $GLOBALS['FORUM_DB']->query('UPDATE ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums SET ' .
                                (!is_null($num_posts_increment) ? ('
        f_cache_num_topics=(f_cache_num_topics+' . strval($num_topics_increment) . '),
        f_cache_num_posts=(f_cache_num_posts+' . strval($num_posts_increment) . '),')
                                    :
                                    ('
        f_cache_num_topics=' . strval($num_topics) . ',
        f_cache_num_posts=' . strval($num_posts) . ',
        ')) .
                                'f_cache_last_topic_id=' . (!is_null($last_topic_id) ? strval($last_topic_id) : 'NULL') . ',
        f_cache_last_title=\'' . db_escape_string($last_title) . '\',
        f_cache_last_time=' . (!is_null($last_time) ? strval($last_time) : 'NULL') . ',
        f_cache_last_username=\'' . db_escape_string(substr($last_username, 0, 255)) . '\',
        f_cache_last_member_id=' . (!is_null($last_member_id) ? strval($last_member_id) : 'NULL') . ',
        f_cache_last_forum_id=' . (!is_null($last_forum_id) ? strval($last_forum_id) : 'NULL') . '
            WHERE id=' . strval($forum_id), 1, null, false, true);

    // Now, are there any parents who need updating?
    if (!is_null($forum_id)) {
        $parent_forum = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums', 'f_parent_forum', array('id' => $forum_id));
        if ((!is_null($parent_forum)) && ($parent_forum != db_get_first_id())) {
            cns_force_update_forum_caching($parent_forum, $num_topics_increment, $num_posts_increment);
        }
    }
}
