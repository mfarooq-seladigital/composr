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
 * Find whether some content is validated.
 *
 * @param  ID_TEXT $content_type Content type
 * @param  ID_TEXT $content_id Content ID
 * @return boolean Whether it is validated
 */
function content_validated($content_type, $content_id)
{
    if (!addon_installed('unvalidated')) {
        return true;
    }

    require_code('content');
    list(, , $cma_info, $content_row,) = content_get_details($content_type, $content_id);
    if (is_null($content_row)) {
        return false;
    }
    return ($content_row[$cma_info['validated_field']] == 1);
}

/**
 * Send a "your content has been validated" notification out to the submitter of some content. Only call if this is true ;).
 *
 * @param  ID_TEXT $content_type Content type
 * @param  ID_TEXT $content_id Content ID
 */
function send_content_validated_notification($content_type, $content_id)
{
    require_code('content');
    list($content_title, $submitter_id, , , , $content_url_safe) = content_get_details($content_type, $content_id);

    if (!is_null($content_url_safe)) {
        require_code('notifications');
        require_lang('unvalidated');
        $subject = do_lang('CONTENT_VALIDATED_NOTIFICATION_MAIL_SUBJECT', $content_title, get_site_name());
        $mail = do_lang('CONTENT_VALIDATED_NOTIFICATION_MAIL', comcode_escape(get_site_name()), comcode_escape($content_title), array($content_url_safe->evaluate()));
        dispatch_notification('content_validated', null, $subject, $mail, array($submitter_id));
    }
}

/**
 * Send (by e-mail) a validation request for a submitted item to the admin.
 *
 * @param  ID_TEXT $type The validation request will say one of this type has been submitted. By convention it is the language code of what was done, e.g. ADD_DOWNLOAD
 * @param  ?ID_TEXT $table The table saved into (null: unknown)
 * @param  boolean $non_integer_id Whether the ID field is not an integer
 * @param  ID_TEXT $id The validation request will say this ID has been submitted
 * @param  Tempcode $url The validation request will link to this URL
 * @param  ?MEMBER $member_id Member doing the submitting (null: current member)
 */
function send_validation_request($type, $table, $non_integer_id, $id, $url, $member_id = null)
{
    $good = null;
    if (!is_null($table)) {
        $_hooks = find_all_hooks('modules', 'admin_unvalidated');
        foreach (array_keys($_hooks) as $hook) {
            require_code('hooks/modules/admin_unvalidated/' . filter_naughty_harsh($hook));
            $object = object_factory('Hook_unvalidated_' . filter_naughty_harsh($hook), true);
            if (is_null($object)) {
                continue;
            }
            $info = $object->info();
            if (is_null($info)) {
                continue;
            }
            if ($info['db_table'] == $table) {
                $good = $info;
                break;
            }
        }
    }

    $title = mixed();
    $title = '';
    if ((!is_null($good)) && (!is_array($good['db_identifier']))) {
        $db = array_key_exists('db', $good) ? $good['db'] : $GLOBALS['SITE_DB'];
        $where = $good['db_identifier'] . '=' . $id;
        if ($non_integer_id) {
            $where = db_string_equal_to($good['db_identifier'], $id);
        }
        $rows = $db->query('SELECT ' . $good['db_identifier'] . (array_key_exists('db_title', $good) ? (',' . $good['db_title']) : '') . ' FROM ' . $db->get_table_prefix() . $good['db_table'] . ' WHERE ' . $where, 100, null, false, true);

        if (array_key_exists('db_title', $good)) {
            $title = $rows[0][$good['db_title']];
            if ($good['db_title_dereference']) {
                $title = get_translated_text($title, $db); // May actually be comcode (can't be certain), but in which case it will be shown as source
            }
        } else {
            $title = '#' . (is_integer($id) ? strval($id) : $id);
        }
    }
    if ($title == '') {
        $title = '#' . (is_integer($id) ? strval($id) : $id);
    }

    if (is_null($member_id)) {
        $member_id = get_member();
    }

    require_lang('unvalidated');

    $_type = do_lang($type, null, null, null, null, false);
    if (!is_null($_type)) {
        $type = $_type;
    }

    $comcode = do_template('VALIDATION_REQUEST_MAIL', array('_GUID' => '1885be371b2ff7810287715ef2f7b948', 'USERNAME' => $GLOBALS['FORUM_DRIVER']->get_username($member_id), 'TYPE' => $type, 'ID' => $id, 'URL' => $url), get_site_default_lang(), false, null, '.txt', 'text');

    require_code('notifications');
    $subject = do_lang('UNVALIDATED_TITLE', $title, '', '', get_site_default_lang());
    $message = $comcode->evaluate(get_site_default_lang());
    dispatch_notification('needs_validation', null, $subject, $message, null, $member_id, 3, false, false, null, null, '', '', '', '', null, true);
}

/**
 * Give points to a member for submitting something, then returns the XHTML page to say so.
 *
 * @param  ID_TEXT $type One of this type has been submitted. By convention it is the language code of what was done, e.g. ADD_DOWNLOAD
 * @param  ?MEMBER $member The member to give the points to (null: give to current member)
 * @return ?string A message about the member being given these submit points (null: no message)
 */
function give_submit_points($type, $member = null)
{
    if (is_null($member)) {
        $member = get_member();
    }
    if ((!is_guest($member)) && (addon_installed('points'))) {
        $points = get_option('points_' . $type, true);
        if (is_null($points)) {
            return '';
        }
        require_code('points2');
        system_gift_transfer(do_lang($type), intval($points), get_member());
        return do_lang('SUBMIT_AWARD', integer_format(intval($points)));
    }
    return null;
}

/**
 * Find a member from their IP address. Unlike plain $GLOBALS['FORUM_DRIVER']->probe_ip, it has the benefit of looking in the adminlogs table also.
 *
 * @param  IP $ip The IP address to probe
 * @return array The members found
 */
function wrap_probe_ip($ip)
{
    if (strpos($ip, '*') !== false) {
        $a = $GLOBALS['SITE_DB']->query('SELECT DISTINCT member_id AS id FROM ' . get_table_prefix() . 'adminlogs WHERE ip LIKE \'' . db_encode_like(str_replace('*', '%', $ip)) . '\'');
    } else {
        $a = $GLOBALS['SITE_DB']->query_select('adminlogs', array('DISTINCT member_id AS id'), array('ip' => $ip));
    }
    $b = $GLOBALS['FORUM_DRIVER']->probe_ip($ip);
    $r = array();
    $guest_id = $GLOBALS['FORUM_DRIVER']->get_guest_id();
    foreach ($a as $x) {
        if ((!in_array($x, $r)) && ($x['id'] != $guest_id)) {
            $r[] = $x;
        }
    }
    foreach ($b as $x) {
        if ((!in_array($x, $r)) && ($x['id'] != $guest_id)) {
            $r[] = $x;
        }
    }
    return $r;
}

/**
 * Ban the specified IP address.
 *
 * @param  IP $ip The IP address to ban
 * @param  LONG_TEXT $descrip Explanation for ban
 */
function ban_ip($ip, $descrip = '')
{
    $ban = trim($ip);
    if (($ban != '') && (!compare_ip_address($ban, get_ip_address()))) {
        require_code('failure');
        add_ip_ban($ban, $descrip);
    } elseif (compare_ip_address($ban, get_ip_address())) {
        attach_message(do_lang_tempcode('AVOIDING_BANNING_SELF'), 'warn');
    }
}

/**
 * Unban the specified IP address.
 *
 * @param  IP $ip The IP address to unban
 */
function unban_ip($ip)
{
    require_code('failure');

    $unban = trim($ip);
    remove_ip_ban($unban);
}
