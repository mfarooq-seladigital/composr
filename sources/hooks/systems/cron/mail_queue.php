<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

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
 * Hook class.
 */
class Hook_cron_mail_queue
{
    /**
     * Run function for CRON hooks. Searches for tasks to perform.
     */
    public function run()
    {
        if (get_option('mail_queue_debug') == '0') {
            $mails = $GLOBALS['SITE_DB']->query_select(
                'logged_mail_messages',
                array('*'),
                array('m_queued' => 1),
                '',
                100
            );

            if (count($mails) != 0) {
                require_code('mail');

                foreach ($mails as $row) {
                    $subject = $row['m_subject'];
                    $message = $row['m_message'];
                    $to_email = @unserialize($row['m_to_email']);
                    $extra_cc_addresses = ($row['m_extra_cc_addresses'] == '') ? array() : @unserialize($row['m_extra_cc_addresses']);
                    $extra_bcc_addresses = ($row['m_extra_bcc_addresses'] == '') ? array() : @unserialize($row['m_extra_bcc_addresses']);
                    $to_name = @unserialize($row['m_to_name']);
                    $from_email = $row['m_from_email'];
                    $from_name = $row['m_from_name'];
                    $join_time = $row['m_join_time'];

                    if ((!is_array($to_email)) && ($to_email !== null)) {
                        continue;
                    }

                    $result_ob = dispatch_mail(
                        $subject,
                        $message,
                        $to_email,
                        $to_name,
                        $from_email,
                        $from_name,
                        array(
                            'priority' => $row['m_priority'],
                            'attachments' => unserialize($row['m_attachments']),
                            'no_cc' => ($row['m_no_cc'] == 1),
                            'as' => $row['m_as'],
                            'as_admin' => ($row['m_as_admin'] == 1),
                            'in_html' => ($row['m_in_html'] == 1),
                            'coming_out_of_queue' => true,
                            'mail_template' => $row['m_template'],
                            'extra_cc_addresses' => $extra_cc_addresses,
                            'extra_bcc_addresses' => $extra_bcc_addresses,
                            'require_recipient_valid_since' => $join_time,
                        )
                    );
                    $success = $result_ob->worked;

                    if ($success) {
                        $GLOBALS['SITE_DB']->query_update('logged_mail_messages', array('m_queued' => 0), array('id' => $row['id']), '', 1);
                    }
                }

                delete_cache_entry('main_staff_checklist');
            }
        }
    }
}
