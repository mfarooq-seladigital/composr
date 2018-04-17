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
 * @package    tickets
 */

/**
 * Find the active support user. Supports the "support_operator" option, for anonymising support.
 *
 * @return MEMBER Member ID
 */
function get_active_support_user()
{
    $member_id = get_member();

    if (has_privilege($member_id, 'support_operator')) {
        $support_operator = get_option('support_operator');
        if (!empty($support_operator)) {
            $_member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($support_operator);
            if (!is_null($_member_id)) {
                $member_id = $_member_id;
            }
        }
    }

    return $member_id;
}

/**
 * Find who a ticket is assigned to.
 *
 * @param  ID_TEXT $ticket_id Ticket ID
 * @return array Map of assigned members (member ID to display name)
 */
function find_ticket_assigned_to($ticket_id)
{
    $assigned = array();
    $where = array('l_notification_code' => 'ticket_assigned_staff', 'l_code_category' => $ticket_id);
    $_assigned = $GLOBALS['SITE_DB']->query_select('notifications_enabled', array('l_member_id'), $where, 'ORDER BY id DESC', 200/*reasonable limit*/);
    foreach ($_assigned as $__assigned) {
        $username = $GLOBALS['FORUM_DRIVER']->get_username($__assigned['l_member_id'], true);
        if ($username !== null) {
            $assigned[$__assigned['l_member_id']] = $username;
        }
    }
    return $assigned;
}

/**
 * Build a list of ticket types.
 *
 * @param  ?AUTO_LINK $selected_ticket_type_id The current selected ticket type (null: none)
 * @param  ?array $ticket_types_to_let_through List of ticket types to show regardless of access permissions (null: none)
 * @return array A map between ticket types, and template-ready details about them
 */
function build_types_list($selected_ticket_type_id, $ticket_types_to_let_through = null)
{
    if (is_null($ticket_types_to_let_through)) {
        $ticket_types_to_let_through = array();
    }

    $_types = $GLOBALS['SITE_DB']->query_select('ticket_types', array('id', 'ticket_type_name', 'cache_lead_time'), null, 'ORDER BY ' . $GLOBALS['SITE_DB']->translate_field_ref('ticket_type_name'));
    $types = array();
    foreach ($_types as $type) {
        if ((!has_category_access(get_member(), 'tickets', strval($type['id']))) && (!in_array($type['id'], $ticket_types_to_let_through))) {
            continue;
        }

        if (is_null($type['cache_lead_time'])) {
            $lead_time = do_lang('UNKNOWN');
        } else {
            $lead_time = display_time_period($type['cache_lead_time']);
        }
        $types[$type['id']] = array('TICKET_TYPE_ID' => strval($type['id']), 'SELECTED' => ($type['id'] === $selected_ticket_type_id), 'NAME' => get_translated_text($type['ticket_type_name']), 'LEAD_TIME' => $lead_time);
    }
    return $types;
}

/**
 * Checks the ticket ID is valid, and there is access for the current member to view it. Bombs out if there's a problem.
 *
 * @param  string $id The ticket ID to check
 * @return MEMBER The ticket owner
 */
function check_ticket_access($id)
{
    // Never for a guest
    if (is_guest()) {
        access_denied('NOT_AS_GUEST');
    }

    // Check we are allowed using normal checks
    $_temp = explode('_', $id);
    $ticket_owner = intval($_temp[0]);
    if (array_key_exists(2, $_temp)) {
        log_hack_attack_and_exit('TICKET_SYSTEM_WEIRD');
    }
    if (has_privilege(get_member(), 'view_others_tickets')) {
        return $ticket_owner;
    }
    if ($ticket_owner == get_member()) {
        return $ticket_owner;
    }

    // Check we're allowed using extra access
    $test = $GLOBALS['SITE_DB']->query_select_value_if_there('ticket_extra_access', 'ticket_id', array('ticket_id' => $id, 'member_id' => get_member()));
    if (!is_null($test)) {
        return $ticket_owner;
    }

    // No access :(
    if (is_guest(intval($_temp[0]))) {
        access_denied(do_lang('TICKET_OTHERS_HACK'));
    }
    log_hack_attack_and_exit('TICKET_OTHERS_HACK');

    return $ticket_owner; // Will never get here
}

/**
 * Get the forum ID for a given ticket type and member, taking the ticket_member_forums and ticket_type_forums options
 * into account.
 *
 * @param  ?AUTO_LINK $member_id The member ID (null: no member)
 * @param  ?integer $ticket_type_id The ticket type (null: all ticket types)
 * @param  boolean $create Create the forum if it's missing
 * @param  boolean $silent_error_handling Whether to skip showing errors, returning null instead
 * @return ?AUTO_LINK Forum ID (null: not found)
 */
function get_ticket_forum_id($member_id = null, $ticket_type_id = null, $create = false, $silent_error_handling = false)
{
    static $fid_cache = array();
    if (isset($fid_cache[$member_id][$ticket_type_id])) {
        return $fid_cache[$member_id][$ticket_type_id];
    }

    $root_forum = get_option('ticket_forum_name');

    // Check the root ticket forum is valid
    $fid = $GLOBALS['FORUM_DRIVER']->forum_id_from_name($root_forum);
    if (is_null($fid)) {
        if ($silent_error_handling) {
            return null;
        }
        warn_exit(do_lang_tempcode('NO_FORUM'));
    }

    // Only the root ticket forum is supported for non-Conversr installations
    if (get_forum_type() != 'cns') {
        return $fid;
    }

    require_code('cns_forums_action');
    require_code('cns_forums_action2');

    $category_id = $GLOBALS['FORUM_DB']->query_select_value('f_forums', 'f_forum_grouping_id', array('id' => $fid));

    if ((!is_null($member_id)) && (get_option('ticket_member_forums') == '1')) {
        $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id);
        $rows = $GLOBALS['FORUM_DB']->query_select('f_forums', array('id'), array('f_parent_forum' => $fid, 'f_name' => $username), '', 1);
        if (count($rows) == 0) {
            $fid = cns_make_forum($username, do_lang('SUPPORT_TICKETS_FOR_MEMBER', $username), $category_id, null, $fid);
        } else {
            $fid = $rows[0]['id'];
        }
    }

    if ((!is_null($ticket_type_id)) && (get_option('ticket_type_forums') == '1')) {
        $_ticket_type_name = $GLOBALS['SITE_DB']->query_select_value_if_there('ticket_types', 'ticket_type_name', array('id' => $ticket_type_id));
        if (!is_null($_ticket_type_name)) {
            $ticket_type_name = get_translated_text($_ticket_type_name);
            $rows = $GLOBALS['FORUM_DB']->query_select('f_forums', array('id'), array('f_parent_forum' => $fid, 'f_name' => $ticket_type_name), '', 1);
            if (count($rows) == 0) {
                $fid = cns_make_forum($ticket_type_name, do_lang('SUPPORT_TICKETS_FOR_TYPE', $ticket_type_name), $category_id, null, $fid);
            } else {
                $fid = $rows[0]['id'];
            }
        }
    }

    $fid_cache[$member_id][$ticket_type_id] = $fid;

    return $fid;
}

/**
 * Returns whether the given forum ID is for a ticket forum (subforum of the root ticket forum).
 *
 * @param  ?AUTO_LINK $forum_id The forum ID (null: private topics)
 * @return boolean Whether the given forum is a ticket forum
 */
function is_ticket_forum($forum_id)
{
    static $cache = array();
    if (isset($cache[$forum_id])) {
        return $cache[$forum_id];
    }

    if (is_null($forum_id)) {
        $cache[$forum_id] = false;
        return false;
    }

    $root_ticket_forum_id = get_ticket_forum_id(null, null, false, true);
    if (is_null($root_ticket_forum_id)) {
        $cache[$forum_id] = false;
        return false;
    }
    if (($root_ticket_forum_id == db_get_first_id()) && ($forum_id != db_get_first_id())) {
        $cache[$forum_id] = false;
        return false; // If ticket forum (oddly) set as root, don't cascade it through all!
    }
    if ($forum_id === $root_ticket_forum_id) {
        $cache[$forum_id] = true;
        return true;
    }

    $query = 'SELECT COUNT(*) AS cnt FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id=' . strval($forum_id) . ' AND f_parent_forum IN (SELECT id FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id=' . strval($root_ticket_forum_id) . ' OR f_parent_forum IN (SELECT id FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id=' . strval($root_ticket_forum_id) . '))';

    $rows = $GLOBALS['FORUM_DB']->query($query);
    $ret = ($rows[0]['cnt'] != 0);
    $cache[$forum_id] = $ret;
    return $ret;
}
