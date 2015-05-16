<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: TornUserinfoClass|SoapClient*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/*
This code is not in failure.php due to the dynamic class declaration, triggering: https://bugs.php.net/bug.php?id=35634
*/

/**
 * Syndicate a spammer report out to wherever we can.
 *
 * @param  IP $ip_addr IP address to report
 * @param  ID_TEXT $username Username address to report
 * @param  EMAIL $email Email address to report
 * @param  string $reason The reason for the report (blank: none)
 * @param  boolean $trigger_error Whether to throw a Composr error, on error. Should not be 'true' for automatic spammer reports, as the spammer should not see the submission process in action!
 */
function syndicate_spammer_report($ip_addr, $username, $email, $reason, $trigger_error = false)
{
    $did_something = false;

    // Syndicate to dnsbl.tornevall.org
    // ================================

    $can_do_torn = (class_exists('SoapClient')) && (get_option('tornevall_api_username') != '');

    if ($can_do_torn) {
        $torn_url = 'http://dnsbl.tornevall.org/soap/soapsubmit.php';

        if (!class_exists('TornUserinfoClass')) {
            /**
             * Tornevall interfacing class (antispam).
             *
             * @package    core_database_drivers
             */
            class TornUserinfoClass
            {
                public $Username;
                public $Password;
            }
        }

        $soapconf = array(
            'location' => $torn_url,
            'uri' => $torn_url,
            'trace' => 0,
            'exceptions' => 0,
            'connection_timeout' => 0
        );

        $userinfo = new TornUserinfoClass();
        $userinfo->Username = get_option('tornevall_api_username');
        $userinfo->Password = get_option('tornevall_api_password');

        $add = array();
        $add['ip'] = $ip_addr;
        if ($username != '') {
            $add['username'] = $username;
        }
        if ($email != '') {
            $add['mail'] = $email;
        }

        $client = new SoapClient(null, $soapconf);
        $udata = array('userinfo' => $userinfo);
        $result = $client->submit($udata, array('add' => $add));
        if ($trigger_error) {
            if (isset($result['error'])) {
                attach_message('dnsbl.tornevall.org: ' . $result['error']['message'], 'warn');
            }
        }

        $did_something = true;
    }

    // Syndicate to Stop Forum Spam
    // ============================

    $stopforumspam_key = get_option('stopforumspam_api_key');
    $can_do_stopforumspam = ($stopforumspam_key != '') && ($username != '') && ($email != '');

    if ($can_do_stopforumspam) {
        require_code('files');
        require_code('character_sets');
        $url = 'http://www.stopforumspam.com/add.php?api_key=' . urlencode($stopforumspam_key) . '&ip_addr=' . urlencode($ip_addr);
        if ($username != '') {
            $url .= '&username=' . urlencode(convert_to_internal_encoding($username, get_charset(), 'utf-8'));
        }
        if ($email != '') {
            $url .= '&email=' . urlencode(convert_to_internal_encoding($email, get_charset(), 'utf-8'));
        }
        if ($reason != '') {
            $url .= '&evidence=' . urlencode(convert_to_internal_encoding($reason, get_charset(), 'utf-8'));
        }
        $result = http_download_file($url, null, $trigger_error);
        if (($trigger_error) && ($result != '')) {
            attach_message($result . ' [ ' . $url . ' ]', 'warn');
        }

        $did_something = true;
    }

    // ---

    // Did we get anything done?
    if (($trigger_error) && (!$did_something)) {
        attach_message(do_lang('SPAM_REPORT_NO_EMAIL_OR_USERNAME'), 'warn');
    }
}
