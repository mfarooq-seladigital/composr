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
 * @package    sms
 */

/**
 * Prepare a phone number for use with the SMS gateway.
 *
 * @param  string $number The number
 * @return string Cleaned number
 */
function cleanup_mobile_number($number)
{
    return str_replace('-', '', str_replace('(', '', str_replace(')', '', str_replace(' ', '', $number))));
}

/**
 * Attempt to send an SMS.
 *
 * @param  string $message The message
 * @param  array $to_sms The member IDs of those receiving messages
 * @return integer How many were sent
 */
function dispatch_sms($message, $to_sms)
{
    // 140 byte limit for single packet
    // 134*255 byte limit for multiple packets (but there's cost for each additional 134 byte segment)

    if (count($to_sms) == 0) {
        return 0;
    }

    require_lang('sms');

    $is_super_admin = $GLOBALS['FORUM_DRIVER']->is_super_admin(get_member());

    require_code('xml');
    $api_id = xmlentities(get_option('sms_api_id'));
    $username = xmlentities(get_option('sms_username'));
    $password = xmlentities(get_option('sms_password'));
    $site_name = xmlentities(substr(get_site_name(), 0, 11));
    if (get_charset() != 'utf-8') {
        $site_name = utf8_encode($site_name);
    }
    //$callback = xmlentities(find_script('sms')); --- set on clickatell's site
    $callback = '0'; /* return nothing (for the moment); TODO: change to 3 (return all message statuses)   #376 on tracker */

    $threshold = mktime(0, 0, 0, intval(date('m')), 0, intval(date('Y')));

    // TODO: $confirmed_numbers = collapse_2d_complexity('m_phone_number', 'm_member_id', $GLOBALS['SITE_DB']->query_select('confirmed_mobiles', array('m_phone_number', 'm_member_id'), array('m_confirm_code' => ''))); #376 on tracker

    // Check current user has not trigered too many
    $triggered_already = $GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . get_table_prefix() . 'sms_log WHERE s_time>' . strval(time() - 60 * 60 * 24 * 31) . ' AND s_time<=' . strval(time()) . ' AND ' . db_string_equal_to('s_trigger_ip', get_ip_address(2)));
    $trigger_limit = intval(get_option('sms_' . (has_privilege(get_member(), 'sms_higher_trigger_limit') ? 'high' : 'low') . '_trigger_limit'));
    if ($triggered_already + count($to_sms) > $trigger_limit) {
        return 0;
    }

    $num_sent = 0;

    foreach ($to_sms as $to_member) {
        if (!has_privilege($to_member, 'use_sms')) {
            continue;
        }

        // Check that not one over quota
        $sent_in_month = $GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'sms_log WHERE s_member_id=' . strval($to_member) . ' AND s_time>' . strval($threshold));
        $limit = intval(get_option('sms_' . (has_privilege($to_member, 'sms_higher_limit') ? 'high' : 'low') . '_limit'));
        if ($sent_in_month + 1 > $limit) {
            continue;
        }

        // If just gone over quota, tell them instead of sending the real notification
        $_message = ($sent_in_month + 1 == $limit) ? do_lang('OVER_SMS_LIMIT') : xmlentities($message);
        if (get_charset() != 'utf-8') {
            $_message = utf8_encode($_message);
        }

        // Let the super-admin trigger or receive longer messages
        $is_this_super_admin = $GLOBALS['FORUM_DRIVER']->is_super_admin($to_member);
        $concat = ($is_super_admin || $is_this_super_admin) ? '3' : '1';

        // Find the phone number configured
        $cpf_values = $GLOBALS['FORUM_DRIVER']->get_custom_fields($to_member);
        if (!array_key_exists('mobile_phone_number', $cpf_values)) {
            continue; // :S  -- should be there
        }
        $to = cleanup_mobile_number($cpf_values['mobile_phone_number']);
        if ($to == '') {
            continue;
        }
        // TODO: if (!array_key_exists($to, $confirmed_numbers)) continue;        #376 on tracker
        $to = xmlentities($to);

        $xml = <<<END
<clickAPI>
    <sendMsg>
        <api_id>{$api_id}</api_id>
        <user>{$username}</user>
        <password>{$password}</password>
        <to>{$to}</to>
        <text>{$_message}</text>
        <from>{$site_name}</from>
        <callback>{$callback}</callback>
        <max_credits>2.5</max_credits>
        <concat>{$concat}</concat>
    </sendMsg>
</clickAPI>
END;

        $result = http_get_contents('http://api.clickatell.com/xml/xml', array('trigger_error' => false, 'post_params' => array('data' => $xml)));
        if (strpos($result, 'fault') !== false) {
            attach_message($result, 'warn', false, true);
            continue;
        }

        $num_sent++;

        $GLOBALS['SITE_DB']->query_insert('sms_log', array('s_trigger_ip' => get_ip_address(2), 's_member_id' => $to_member, 's_time' => time()));
    }

    return $num_sent;
}

/**
 * Handle maintenance of SMS numbers (block numbers if they prove unreliable).
 */
function sms_callback_script()
{
    if (!addon_installed('sms')) {
        warn_exit(do_lang_tempcode('MISSING_ADDON', escape_html('sms')));
    }

    // Currently does nothing. Would receive messages in the form below, via the "data" GET parameter
    /*
    < ?xml version="1.0"? >
    <callback>
        <apiMsgId>996411ad91fa211e7d17bc873aa4a41d</apiMsgId>
        <cliMsgId></cliMsgId>
        <timestamp>1218008129</timestamp>
        <to>279995631564</to>
        <from>27833001171</from>
        <charge>0.300000</charge>
        <status>004</status>
    </callback>
    */
}
