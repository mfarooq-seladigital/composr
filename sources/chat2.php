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
 * @package    chat
 */

/**
 * Block a member.
 *
 * @param  MEMBER $blocker The member blocking
 * @param  MEMBER $blocked The member being blocked
 * @param  ?TIME $time The logged time of the block (null: now)
 */
function blocking_add($blocker, $blocked, $time = null)
{
    if ($time === null) {
        $time = time();
    }

    $GLOBALS['SITE_DB']->query_delete('chat_blocking', array(
        'member_blocker' => $blocker,
        'member_blocked' => $blocked
    ), '', 1); // Just in case page refreshed

    $GLOBALS['SITE_DB']->query_insert('chat_blocking', array(
        'member_blocker' => $blocker,
        'member_blocked' => $blocked,
        'date_and_time' => $time
    ));

    log_it('BLOCK_MEMBER', strval($blocker), strval($blocked));
}

/**
 * Unblock a member.
 *
 * @param  MEMBER $blocker The member unblocking
 * @param  MEMBER $blocked The member being unblocked
 */
function blocking_remove($blocker, $blocked)
{
    $GLOBALS['SITE_DB']->query_delete('chat_blocking', array(
        'member_blocker' => $blocker,
        'member_blocked' => $blocked
    ), '', 1); // Just in case page refreshed

    log_it('UNBLOCK_MEMBER', strval($blocker), strval($blocked));
}

/**
 * Add a friend.
 *
 * @param  MEMBER $likes The member befriending
 * @param  MEMBER $liked The member being befriended
 * @param  ?TIME $time The logged time of the friendship (null: now)
 */
function friend_add($likes, $liked, $time = null)
{
    if ($time === null) {
        $time = time();
    }

    $GLOBALS['SITE_DB']->query_delete('chat_friends', array(
        'member_likes' => $likes,
        'member_liked' => $liked
    ), '', 1); // Just in case page refreshed

    $GLOBALS['SITE_DB']->query_insert('chat_friends', array(
        'member_likes' => $likes,
        'member_liked' => $liked,
        'date_and_time' => $time
    ));

    // Send a notification
    require_code('chat');
    if (member_befriended($likes, $liked)) {
        require_lang('chat');

        require_code('notifications');
        $to_username = $GLOBALS['FORUM_DRIVER']->get_username($liked);
        $from_username = $GLOBALS['FORUM_DRIVER']->get_username($likes);
        $to_displayname = $GLOBALS['FORUM_DRIVER']->get_username($liked, true);
        $from_displayname = $GLOBALS['FORUM_DRIVER']->get_username($likes, true);
        $subject_line = do_lang('YOURE_MY_FRIEND_SUBJECT', $from_username, get_site_name(), null, get_lang($liked));
        $befriend_url = build_url(array('page' => 'chat', 'type' => 'friend_add', 'member_id' => $likes), get_module_zone('chat'), null, false, false, true);
        $message_raw = do_notification_lang('YOURE_MY_FRIEND_BODY', comcode_escape($to_username), comcode_escape(get_site_name()), array($befriend_url->evaluate(), comcode_escape($from_username), comcode_escape($to_displayname), comcode_escape($from_displayname)), get_lang($liked));
        dispatch_notification('new_friend', null, $subject_line, $message_raw, array($liked), $likes);

        // Log the action
        log_it('MAKE_FRIEND', strval($likes), strval($liked));
        require_code('activities');
        syndicate_described_activity('chat:PEOPLE_NOW_FRIENDS', $to_displayname, '', '', '_SEARCH:members:view:' . strval($liked), '_SEARCH:members:view:' . strval($likes), '', 'chat', 1, $likes);
        //syndicate_described_activity('chat:PEOPLE_NOW_FRIENDS', $to_displayname, '', '', '_SEARCH:members:view:' . strval($liked), '_SEARCH:members:view:' . strval($likes), '', 'chat', 1, $liked); Should only show if the user also does this

        decache('main_friends_list');
    }
}

/**
 * Remove ('dump') a friend.
 *
 * @param  MEMBER $likes The member befriending
 * @param  MEMBER $liked The member being dumped
 */
function friend_remove($likes, $liked)
{
    $GLOBALS['SITE_DB']->query_delete('chat_friends', array(
        'member_likes' => $likes,
        'member_liked' => $liked
    ), '', 1); // Just in case page refreshed

    log_it('DUMP_FRIEND', strval($likes), strval($liked));

    decache('main_friends_list');
}

/**
 * Get form fields for adding/editing a chatroom.
 *
 * @param  ?AUTO_LINK $id The chatroom ID (null: new)
 * @param  boolean $is_made_by_me Whether the room is being made as a private room by the current member
 * @param  SHORT_TEXT $room_name The room name
 * @param  LONG_TEXT $welcome The welcome message
 * @param  SHORT_TEXT $username The owner username
 * @param  LONG_TEXT $allow2 The comma-separated list of users that may access it (blank: no restriction)
 * @param  LONG_TEXT $allow2_groups The comma-separated list of usergroups that may access it (blank: no restriction)
 * @param  LONG_TEXT $disallow2 The comma-separated list of users that may NOT access it (blank: no restriction)
 * @param  LONG_TEXT $disallow2_groups The comma-separated list of usergroups that may NOT access it (blank: no restriction)
 * @return array A pair: The input fields, Hidden fields
 */
function get_chatroom_fields($id = null, $is_made_by_me = false, $room_name = '', $welcome = '', $username = '', $allow2 = '', $allow2_groups = '', $disallow2 = '', $disallow2_groups = '')
{
    require_code('form_templates');

    $fields = new Tempcode();

    $fields->attach(form_input_line(do_lang_tempcode('CHATROOM_NAME'), do_lang_tempcode('DESCRIPTION_CHATROOM_NAME'), 'room_name', $room_name, true));
    $fields->attach(form_input_line_comcode(do_lang_tempcode('WELCOME_MESSAGE'), do_lang_tempcode('DESCRIPTION_WELCOME_MESSAGE'), 'c_welcome', $welcome, false));
    if (!$is_made_by_me) {
        $fields->attach(form_input_username(do_lang_tempcode('CHATROOM_OWNER'), do_lang_tempcode('DESCRIPTION_CHATROOM_OWNER'), 'room_owner', $username, false));
    }
    $langs = find_all_langs();
    if (count($langs) > 1) {
        $fields->attach(form_input_list(do_lang_tempcode('CHATROOM_LANG'), do_lang_tempcode('DESCRIPTION_CHATROOM_LANG'), 'room_lang', create_selection_list_langs()));
    }
    require_lang('permissions');
    $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '4381fe8487426cc3ae8afa090c2d4a44', 'SECTION_HIDDEN' => $allow2 == '' && $allow2_groups == '' && !$is_made_by_me, 'TITLE' => do_lang_tempcode($is_made_by_me ? 'PERMISSIONS' : 'LOWLEVEL_PERMISSIONS'))));
    $fields->attach(form_input_username_multi(do_lang_tempcode('ALLOW_LIST'), do_lang_tempcode('DESCRIPTION_ALLOW_LIST'), 'allow_list', array_map(array($GLOBALS['FORUM_DRIVER'], 'get_username'), ($allow2 == '') ? array() : array_map('intval', explode(',', $allow2))), 0, true));
    if ((!$is_made_by_me) || (get_option('group_private_chatrooms') == '1')) {
        $usergroup_list = new Tempcode();
        $groups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list(true);
        foreach ($groups as $key => $val) {
            if ($key != db_get_first_id()) {
                if (get_forum_type() == 'cns') {
                    require_code('cns_groups2');
                    $num_members = cns_get_group_members_raw_count($key);
                    if (($num_members >= 1) && ($num_members <= 6)) {
                        $group_members = cns_get_group_members_raw($key);
                        $group_member_usernames = '';
                        foreach ($group_members as $group_member) {
                            if ($group_member_usernames != '') {
                                $group_member_usernames = do_lang('LIST_SEP');
                            }
                            $group_member_usernames .= $GLOBALS['FORUM_DRIVER']->get_username($group_member);
                        }
                        $val = do_lang('GROUP_MEMBERS_SPECIFIC', $val, $group_member_usernames);
                    } else {
                        $val = do_lang('GROUP_MEMBERS', $val, number_format($num_members));
                    }
                }
                $usergroup_list->attach(form_input_list_entry(strval($key), ($allow2_groups == '*') || count(array_intersect(array($key), ($allow2_groups == '') ? array() : explode(',', $allow2_groups))) != 0, $val));
            }
        }

        $fields->attach(form_input_multi_list(do_lang_tempcode('ALLOW_LIST_GROUPS'), do_lang_tempcode($is_made_by_me ? 'DESCRIPTION_ALLOW_LIST_GROUPS_SIMPLE' : 'DESCRIPTION_ALLOW_LIST_GROUPS'), 'allow_list_groups', $usergroup_list));
    }
    $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '605aae34cbc2c168c8e77a62ab9b8a47', 'SECTION_HIDDEN' => $disallow2 == '' && $disallow2_groups == '', 'TITLE' => do_lang_tempcode('ADVANCED'))));
    $fields->attach(form_input_username_multi(do_lang_tempcode('DISALLOW_LIST'), do_lang_tempcode('DESCRIPTION_DISALLOW_LIST'), 'disallow_list', array_map(array($GLOBALS['FORUM_DRIVER'], 'get_username'), ($disallow2 == '') ? array() : array_map('intval', explode(',', $disallow2))), 0, true));
    if ((!$is_made_by_me) || (get_option('group_private_chatrooms') == '1')) {
        $usergroup_list = new Tempcode();
        $groups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list(true);
        foreach ($groups as $key => $val) {
            if ($key != db_get_first_id()) {
                if (get_forum_type() == 'cns') {
                    require_code('cns_groups2');
                    $num_members = cns_get_group_members_raw_count($key);
                    if (($num_members >= 1) && ($num_members <= 6)) {
                        $group_members = cns_get_group_members_raw($key);
                        $group_member_usernames = '';
                        foreach ($group_members as $group_member) {
                            if ($group_member_usernames != '') {
                                $group_member_usernames = do_lang('LIST_SEP');
                            }
                            $group_member_usernames .= $GLOBALS['FORUM_DRIVER']->get_username($group_member);
                        }
                        $val = do_lang('GROUP_MEMBERS_SPECIFIC', $val, $group_member_usernames);
                    } else {
                        $val = do_lang('GROUP_MEMBERS', $val, number_format($num_members));
                    }
                }
                $usergroup_list->attach(form_input_list_entry(strval($key), ($disallow2_groups == '*') || count(array_intersect(array($key), ($disallow2_groups == '') ? array() : explode(',', $disallow2_groups))) != 0, $val));
            }
        }

        $fields->attach(form_input_multi_list(do_lang_tempcode('DISALLOW_LIST_GROUPS'), do_lang_tempcode('DESCRIPTION_DISALLOW_LIST_GROUPS'), 'disallow_list_groups', $usergroup_list));
    }

    require_code('content2');
    $fields->attach(metadata_get_fields('chat', ($id === null) ? null : strval($id)));

    if (addon_installed('content_reviews')) {
        require_code('content_reviews2');
        $fields->attach(content_review_get_fields('chat', ($id === null) ? null : strval($id)));
    }

    return array($fields, new Tempcode());
}

/**
 * Read in chat permission fields, from the complex posted data.
 *
 * @return array A tuple of permission fields
 */
function read_in_chat_perm_fields()
{
    $allow2 = '';
    $_x = post_param_string('allow_list_0', '');
    $x = $GLOBALS['FORUM_DRIVER']->get_member_from_username($_x);
    if ($x !== null) {
        $allow2 .= strval($x);
    }
    foreach ($_POST as $key => $_x) {
        if (substr($key, 0, strlen('allow_list')) != 'allow_list') {
            continue;
        }
        if ($key == 'allow_list_0') {
            continue;
        }
        if ($key == 'allow_list_groups') {
            continue;
        }
        if ($_x == '') {
            continue;
        }
        $x = $GLOBALS['FORUM_DRIVER']->get_member_from_username($_x);
        if ($x !== null) {
            if ($allow2 != '') {
                $allow2 .= ',';
            }
            $allow2 .= strval($x);
        }
    }
    $allow2_groups = array_key_exists('allow_list_groups', $_POST) ? implode(',', $_POST['allow_list_groups']) : '';
    $disallow2 = '';
    $_x = post_param_string('disallow_list_0', '');
    $x = $GLOBALS['FORUM_DRIVER']->get_member_from_username($_x);
    if ($x !== null) {
        $disallow2 .= strval($x);
    }
    foreach ($_POST as $key => $_x) {
        if (substr($key, 0, strlen('disallow_list')) != 'disallow_list') {
            continue;
        }
        if ($key == 'disallow_list_0') {
            continue;
        }
        if ($key == 'disallow_list_groups') {
            continue;
        }
        if ($_x == '') {
            continue;
        }
        $x = $GLOBALS['FORUM_DRIVER']->get_member_from_username($_x);
        if ($x !== null) {
            if ($disallow2 != '') {
                $disallow2 .= ',';
            }
            $disallow2 .= strval($x);
        }
    }
    $disallow2_groups = array_key_exists('disallow_list_groups', $_POST) ? implode(',', $_POST['disallow_list_groups']) : '';

    return array($allow2, $allow2_groups, $disallow2, $disallow2_groups);
}

/**
 * Add a chatroom.
 *
 * @param  SHORT_TEXT $welcome The welcome message
 * @param  SHORT_TEXT $room_name The room name
 * @param  MEMBER $room_owner The room owner
 * @param  LONG_TEXT $allow2 The comma-separated list of users that may access it (blank: no restriction)
 * @param  LONG_TEXT $allow2_groups The comma-separated list of usergroups that may access it (blank: no restriction)
 * @param  LONG_TEXT $disallow2 The comma-separated list of users that may NOT access it (blank: no restriction)
 * @param  LONG_TEXT $disallow2_groups The comma-separated list of usergroups that may NOT access it (blank: no restriction)
 * @param  LANGUAGE_NAME $room_language The room language
 * @param  BINARY $is_im Whether it is an IM room
 * @return AUTO_LINK The chatroom ID
 */
function add_chatroom($welcome, $room_name, $room_owner, $allow2, $allow2_groups, $disallow2, $disallow2_groups, $room_language, $is_im = 0)
{
    require_code('global4');
    prevent_double_submit('ADD_CHATROOM', null, $room_name);

    $map = array(
        'is_im' => $is_im,
        'room_name' => $room_name,
        'room_owner' => $room_owner,
        'allow_list' => $allow2,
        'allow_list_groups' => $allow2_groups,
        'disallow_list' => $disallow2,
        'disallow_list_groups' => $disallow2_groups,
        'room_language' => $room_language,
    );
    $map += insert_lang('c_welcome', $welcome, 2);
    $id = $GLOBALS['SITE_DB']->query_insert('chat_rooms', $map, true);

    if ($is_im == 0) {
        log_it('ADD_CHATROOM', strval($id), $room_name);
    }

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resource_fs_moniker('chat', strval($id), null, null, true);
    }

    decache('side_shoutbox');

    if ($is_im == 0) {
        require_code('sitemap_xml');
        notify_sitemap_node_add('SEARCH:chat:room:' . strval($id), time(), null, SITEMAP_IMPORTANCE_MEDIUM, 'never', ($allow2 == '') && ($allow2_groups == ''));
    }

    return $id;
}

/**
 * Edit a chatroom.
 *
 * @param  AUTO_LINK $id The chatroom ID
 * @param  SHORT_TEXT $welcome The welcome message
 * @param  SHORT_TEXT $room_name The room name
 * @param  MEMBER $room_owner The room owner
 * @param  LONG_TEXT $allow2 The comma-separated list of users that may access it (blank: no restriction)
 * @param  LONG_TEXT $allow2_groups The comma-separated list of usergroups that may access it (blank: no restriction)
 * @param  LONG_TEXT $disallow2 The comma-separated list of users that may NOT access it (blank: no restriction)
 * @param  LONG_TEXT $disallow2_groups The comma-separated list of usergroups that may NOT access it (blank: no restriction)
 * @param  LANGUAGE_NAME $room_language The room language
 */
function edit_chatroom($id, $welcome, $room_name, $room_owner, $allow2, $allow2_groups, $disallow2, $disallow2_groups, $room_language)
{
    $c_welcome = $GLOBALS['SITE_DB']->query_select_value('chat_rooms', 'c_welcome', array('id' => $id));

    $map = array(
        'room_name' => $room_name,
        'room_owner' => $room_owner,
        'allow_list' => $allow2,
        'allow_list_groups' => $allow2_groups,
        'disallow_list' => $disallow2,
        'disallow_list_groups' => $disallow2_groups,
        'room_language' => $room_language,
    );
    $map += lang_remap('c_welcome', $c_welcome, $welcome);
    $GLOBALS['SITE_DB']->query_update('chat_rooms', $map, array('id' => $id), '', 1);

    decache('side_shoutbox');

    require_code('urls2');
    suggest_new_idmoniker_for('chat', 'room', strval($id), '', $room_name);

    log_it('EDIT_CHATROOM', strval($id), $room_name);

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resource_fs_moniker('chat', strval($id));
    }

    require_code('sitemap_xml');
    notify_sitemap_node_edit('SEARCH:chat:room:' . strval($id), ($allow2 == '') && ($allow2_groups == ''));
}

/**
 * Delete a chatroom.
 *
 * @param  AUTO_LINK $id The chatroom ID
 */
function delete_chatroom($id)
{
    $rows = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('c_welcome', 'room_name', 'is_im'), array('id' => $id), '', 1);
    if (!array_key_exists(0, $rows)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'chat'));
    }

    delete_lang($rows[0]['c_welcome']);

    $GLOBALS['SITE_DB']->query_delete('chat_rooms', array('id' => $id), '', 1);

    delete_chat_messages(array('room_id' => $id));

    decache('side_shoutbox');

    if (addon_installed('catalogues')) {
        update_catalogue_content_ref('chat', strval($id), '');
    }

    if ($rows[0]['is_im'] == 0) {
        log_it('DELETE_CHATROOM', strval($id), $rows[0]['room_name']);
    }

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        expunge_resource_fs_moniker('chat', strval($id));
    }

    require_code('sitemap_xml');
    notify_sitemap_node_delete('SEARCH:chat:room:' . strval($id));
}

/**
 * Delete chat messages.
 *
 * @param  array $where Where query to specify what to delete
 */
function delete_chat_messages($where)
{
    if (php_function_allowed('set_time_limit')) {
        @set_time_limit(0);
    }
    do {
        $messages = $GLOBALS['SITE_DB']->query_select('chat_messages', array('id', 'the_message'), $where, '', 400);
        foreach ($messages as $message) {
            delete_lang($message['the_message']);
            $GLOBALS['SITE_DB']->query_delete('chat_messages', array('id' => $message['id']), '', 1);
        }
    } while ($messages != array());
}

/**
 * Delete all chatrooms.
 */
function delete_all_chatrooms()
{
    if (php_function_allowed('set_time_limit')) {
        @set_time_limit(0);
    }
    do {
        $c_welcomes = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('id', 'c_welcome'), array('is_im' => 0), '', 400);
        foreach ($c_welcomes as $c_welcome) {
            delete_lang($c_welcome['c_welcome']);
            $GLOBALS['SITE_DB']->query_delete('chat_rooms', array('id' => $c_welcome['id']));
            delete_chat_messages(array('room_id' => $c_welcome['id']));
        }
    } while ($c_welcomes != array());

    decache('side_shoutbox');

    log_it('DELETE_ALL_CHATROOMS');
}

/**
 * Ban a member from a chatroom.
 *
 * @param  MEMBER $member_id The member to ban
 * @param  AUTO_LINK $id The chatroom ID
 */
function chatroom_ban_to($member_id, $id)
{
    log_it('CHAT_BAN', strval($id), $GLOBALS['FORUM_DRIVER']->get_username($member_id));

    $disallow_list = $GLOBALS['SITE_DB']->query_select_value('chat_rooms', 'disallow_list', array('id' => $id));
    if ($disallow_list == '') {
        $disallow_list = strval($member_id);
    } else {
        $disallow_list .= ',' . strval($member_id);
    }
    $GLOBALS['SITE_DB']->query_update('chat_rooms', array('disallow_list' => $disallow_list), array('id' => $id), '', 1);
}

/**
 * Unban a member from a chatroom.
 *
 * @param  MEMBER $member_id The member to unban
 * @param  AUTO_LINK $id The chatroom ID
 */
function chatroom_unban_to($member_id, $id)
{
    log_it('CHAT_UNBAN', strval($id), $GLOBALS['FORUM_DRIVER']->get_username($member_id));

    $disallow_list = $GLOBALS['SITE_DB']->query_select_value('chat_rooms', 'disallow_list', array('id' => $id));
    $_disallow_list = explode(',', $disallow_list);
    $_disallow_list2 = array();
    $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id);
    foreach ($_disallow_list as $dis) {
        if (((strval($member_id) != $dis)) && ($dis != $username)) {
            $_disallow_list2[] = $dis;
        }
    }
    $disallow_list = implode(',', $_disallow_list2);
    $GLOBALS['SITE_DB']->query_update('chat_rooms', array('disallow_list' => $disallow_list), array('id' => $id), '', 1);
}

/**
 * Delete all messages in a chatroom.
 *
 * @param  AUTO_LINK $id The chatroom ID
 */
function delete_chatroom_messages($id)
{
    delete_chat_messages(array('room_id' => $id));

    log_it('DELETE_ALL_MESSAGES', strval($id));

    decache('side_shoutbox');
}
