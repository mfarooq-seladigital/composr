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
 * @package    core
 */

/**
 * Find if a member is online.
 *
 * @param  MEMBER $member_id The member to check
 * @return boolean Whether they are online
 */
function member_is_online($member_id)
{
    $count = 0;
    $online = get_users_online(false, $member_id, $count);
    foreach ($online as $m) {
        if ($m['member_id'] == $member_id) {
            return true;
        }
    }
    return false;
}

/**
 * Get database rows of all the online members.
 *
 * @param  boolean $longer_time Whether to use a longer online-time -- the session expiry-time
 * @param  ?MEMBER $filter We really only need to make sure we get the status for this user, although at this functions discretion more may be returned and the row won't be there if the user is not online (null: no filter). May not be the guest ID
 * @param  integer $count The total online members, returned by reference
 * @return ?array Database rows (null: too many)
 */
function get_users_online($longer_time, $filter, &$count)
{
    if (get_value('no_member_tracking') === '1') {
        return array();
    }

    $users_online_time_seconds = intval($longer_time ? (60.0 * 60.0 * floatval(get_option('session_expiry_time'))) : (60.0 * floatval(get_option('users_online_time'))));
    $cutoff = time() - $users_online_time_seconds;

    if (get_option('session_prudence') != '0') {
        // If we have multiple servers this many not be accurate as we probably turned replication off for the sessions table. The site design should be updated to not show this kind of info
        $count = $GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . get_table_prefix() . 'sessions WHERE last_activity>' . strval($cutoff)); // Written in by reference
        if (!is_null($filter)) {
            return $GLOBALS['SITE_DB']->query('SELECT * FROM ' . get_table_prefix() . 'sessions WHERE last_activity>' . strval($cutoff) . ' AND member_id=' . strval($filter), 1);
        }
        return null;
    }
    $members = array();
    $guest_id = $GLOBALS['FORUM_DRIVER']->get_guest_id();
    global $SESSION_CACHE;
    $members_online = 0;
    foreach ($SESSION_CACHE as $row) {
        if (!isset($row['member_id'])) {
            continue; // Workaround to HHVM weird bug
        }

        if (($row['last_activity'] > $cutoff) && ($row['session_invisible'] == 0)) {
            if ($row['member_id'] == $guest_id) {
                $count++;
                $members[] = $row;
                $members_online++;
                if ($members_online == 200) { // This is silly, don't display any
                    if (!is_null($filter)) {// Unless we are filtering
                        return $GLOBALS['SITE_DB']->query('SELECT * FROM ' . get_table_prefix() . 'sessions WHERE last_activity>' . strval($cutoff) . ' AND member_id=' . strval($filter), 1);
                    }
                    return null;
                }
            } elseif (!member_blocked(get_member(), $row['member_id'])) {
                $count++;
                $members[-$row['member_id']] = $row; // - (minus) is just a hackerish thing to allow it to do a unique, without messing with the above
            }
        }
    }
    return $members;
}

/**
 * Find if a member is blocked by a member.
 *
 * @param  MEMBER $member_id The member being checked
 * @param  ?MEMBER $member_blocker The member who may be blocking (null: current member)
 * @return boolean Whether the member is blocked
 */
function member_blocked($member_id, $member_blocker = null)
{
    if (!addon_installed('chat')) {
        return false;
    }
    if ($member_blocker === null) {
        $member_blocker = get_member();
    }

    if ($member_blocker == $member_id) {
        return false;
    }
    if (is_guest($member_id)) {
        return false;
    }
    if (is_guest($member_blocker)) {
        return false;
    }

    if ($member_id == get_member()) {
        global $MEMBERS_BLOCKING_US_CACHE;
        if (is_null($MEMBERS_BLOCKING_US_CACHE)) {
            $rows = $GLOBALS['SITE_DB']->query_select('chat_blocking', array('member_blocker'), array('member_blocked' => get_member()), '', null, null, true);
            if (is_null($rows)) {
                $MEMBERS_BLOCKING_US_CACHE = array();
                return false;
            }
            $MEMBERS_BLOCKING_US_CACHE = collapse_1d_complexity('member_blocker', $rows);
        }
        return (in_array($member_blocker, $MEMBERS_BLOCKING_US_CACHE));
    }

    global $MEMBERS_BLOCKED_CACHE;
    if (is_null($MEMBERS_BLOCKED_CACHE)) {
        $rows = $GLOBALS['SITE_DB']->query_select('chat_blocking', array('member_blocked'), array('member_blocker' => get_member()), '', null, null, true);
        if (is_null($rows)) {
            $MEMBERS_BLOCKED_CACHE = array();
            return false;
        }
        $MEMBERS_BLOCKED_CACHE = collapse_1d_complexity('member_blocked', $rows);
    }
    return (in_array($member_id, $MEMBERS_BLOCKED_CACHE));
}

/**
 * Get template-ready details of members viewing the specified Composr location.
 *
 * @param  ?ID_TEXT $page The page they need to be viewing (null: don't care)
 * @param  ?ID_TEXT $type The page-type they need to be viewing (null: don't care)
 * @param  ?SHORT_TEXT $id The type-id they need to be viewing (null: don't care)
 * @param  boolean $forum_layer Whether this has to be done over the forum driver (multi site network)
 * @return ?array A map of member-IDs to rows about them (null: Too many)
 */
function get_members_viewing_wrap($page = null, $type = null, $id = null, $forum_layer = false)
{
    $members = is_null($id) ? array() : get_members_viewing($page, $type, $id, $forum_layer);
    $num_guests = 0;
    $num_members = 0;
    if (is_null($members)) {
        $members_viewing = new Tempcode();
    } else {
        $members_viewing = new Tempcode();
        if (!isset($members[get_member()])) {
            if (is_guest()) {
                $members[get_member()] = 1;
            } else {
                $members[get_member()] = array('mt_cache_username' => $GLOBALS['FORUM_DRIVER']->get_username(get_member()));
            }
        }
        foreach ($members as $member_id => $at_details) {
            $username = $at_details['mt_cache_username'];

            if (is_guest($member_id)) {
                $num_guests += $at_details;/*is integer for guest*/
            } else {
                $num_members++;
                $profile_url = $GLOBALS['FORUM_DRIVER']->member_profile_url($member_id, false, true);
                $map = array('FIRST' => $num_members == 1, 'PROFILE_URL' => $profile_url, 'USERNAME' => $username, 'MEMBER_ID' => strval($member_id));
                if (isset($at_details['the_title'])) {
                    if ((has_privilege(get_member(), 'show_user_browsing')) || ((in_array($at_details['the_page'], array('topics', 'topicview'))) && ($at_details['the_id'] == $id))) {
                        $map['AT'] = escape_html($at_details['the_title']);
                    }
                }
                $map['COLOUR'] = get_group_colour(cns_get_member_primary_group($member_id));
                $members_viewing->attach(do_template('CNS_USER_MEMBER', $map));
            }
        }
        if ($members_viewing->is_empty()) {
            $members_viewing = do_lang_tempcode('NONE_EM');
        }
    }

    return array($num_guests, $num_members, $members_viewing);
}

/**
 * Get a map of members viewing the specified Composr location.
 *
 * @param  ?ID_TEXT $page The page they need to be viewing (null: environment current) (blank: blank't care)
 * @param  ?ID_TEXT $type The page-type they need to be viewing (null: environment current) (blank: don't care)
 * @param  ?SHORT_TEXT $id The type-id they need to be viewing (null: environment current) (blank: don't care)
 * @param  boolean $forum_layer Whether this has to be done over the forum driver (multi site network)
 * @return ?array A map of member-IDs to rows about them (except for guest, which is a count) (null: Too many / disabled)
 */
function get_members_viewing($page = null, $type = null, $id = null, $forum_layer = false)
{
    if (get_value('no_member_tracking') === '1') {
        return null;
    }

    global $ZONE;
    if ($page === null) {
        $page = get_param_string('page', $ZONE['zone_default_page']);
    }
    if ($type === null) {
        $type = get_param_string('type', '/');
    }
    if ($id === null) {
        $id = get_param_string('id', '/', true);
    }
    if ($type == '/') {
        $type = '';
    }
    if ($id == '/') {
        $id = '';
    }

    // Update the member tracking
    member_tracking_update($page, $type, $id);

    $map = array();
    if (($page !== null) && ($page != '')) {
        $map['mt_page'] = $page;
    }
    if (($type !== null) && ($type != '')) {
        $map['mt_type'] = $type;
    }
    if (($id !== null) && ($id != '')) {
        $map['mt_id'] = $id;
    }
    $map['session_invisible'] = 0;
    $db = ($forum_layer ? $GLOBALS['FORUM_DB'] : $GLOBALS['SITE_DB']);
    $results = $db->query_select('member_tracking t LEFT JOIN ' . $db->get_table_prefix() . 'sessions s ON t.mt_member_id=s.member_id', array('*'), $map, ' AND mt_member_id<>' . strval($GLOBALS['FORUM_DRIVER']->get_guest_id()) . ' ORDER BY mt_member_id', 200);
    if (count($results) == 200) {
        return null;
    }

    unset($map['session_invisible']);
    $num_guests = $db->query_select_value('member_tracking t', 'COUNT(*)', $map, ' AND mt_member_id=' . strval($GLOBALS['FORUM_DRIVER']->get_guest_id()));

    $results = remove_duplicate_rows($results, 'mt_member_id');

    $out = array(
        $GLOBALS['FORUM_DRIVER']->get_guest_id() => $num_guests,
    );
    foreach ($results as $row) {
        if (!member_blocked(get_member(), $row['mt_member_id'])) {
            $out[$row['mt_member_id']] = $row;
        }
    }
    return $out;
}

/**
 * Find a user to test access against, if we're planning on making presence of something public.
 *
 * @return MEMBER The modal member
 */
function get_modal_user()
{
    $modal_user = get_option('modal_user');
    if ($modal_user != '') {
        if ($modal_user == '<self>') {
            return get_member();
        }
        $member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($modal_user);
        if (!is_null($member_id)) {
            return $member_id;
        }
    }
    return $GLOBALS['FORUM_DRIVER']->get_guest_id();
}
