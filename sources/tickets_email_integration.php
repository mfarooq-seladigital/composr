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
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__tickets_email_integration()
{
    require_lang('tickets');
    require_code('tickets');
    require_code('tickets2');
}

/**
 * Ticket e-mail integration class.
 *
 * @package        tickets
 */
class TicketsEmailIntegration extends EmailIntegration
{
    /**
     * Send out an e-mail message for a ticket / ticket reply.
     *
     * @param  ID_TEXT $ticket_id Ticket ID
     * @param  mixed $ticket_url URL to the ticket (URLPATH or Tempcode)
     * @param  string $ticket_type_name The ticket type's label
     * @param  string $subject Ticket subject
     * @param  string $message Ticket message
     * @param  MEMBER $to_member_id Member ID of recipient
     * @param  string $to_displayname Display name of ticket owner
     * @param  EMAIL $to_email E-mail address of ticket owner
     * @param  string $from_displayname Display name of staff poster
     * @param  boolean $new Whether this is a new ticket, just created by the ticket owner
     */
    public function outgoing_message($ticket_id, $ticket_url, $ticket_type_name, $subject, $message, $to_member_id, $to_displayname, $to_email, $from_displayname, $new = false)
    {
        if (is_object($ticket_url)) {
            $ticket_url = $ticket_url->evaluate();
        }

        $extended_subject = do_lang('TICKET_SIMPLE_SUBJECT_' . ($new ? 'new' : 'reply'), $subject, $ticket_id, array($ticket_type_name, $from_displayname, get_site_name()), get_lang($to_member_id));
        $extended_message = do_lang('TICKET_SIMPLE_MAIL_' . ($new ? 'new' : 'reply'), get_site_name(), $ticket_type_name, array($ticket_url, $from_displayname, $message), get_lang($to_member_id));
        $extended_from_displayname = do_lang('TICKET_SIMPLE_FROM', get_site_name(), $from_displayname, array(), get_lang($to_member_id));

        $this->_outgoing_message($extended_subject, $extended_message, $to_member_id, $to_displayname, $to_email, $extended_from_displayname);
    }

    /**
     * Find the e-mail address to send from (From header).
     *
     * @return EMAIL E-mail address
     */
    protected function get_sender_email()
    {
        foreach (array('website_email', 'ticket_mail_email_address', 'staff_address') as $address) {
            if (get_option($address) != '') {
                return get_option($address);
            }
        }

        warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        return '';
    }

    /**
     * Find the e-mail address for system e-mails (Reply-To header).
     *
     * @return EMAIL E-mail address
     */
    protected function get_system_email()
    {
        foreach (array('ticket_mail_email_address', 'staff_address', 'website_email') as $address) {
            if (get_option($address) != '') {
                return get_option($address);
            }
        }

        warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        return '';
    }

    /**
     * Scan for new e-mails in the support inbox.
     */
    public function incoming_scan()
    {
        if (get_option('ticket_mail_on') !== '1') {
            return;
        }

        $type = get_option('ticket_mail_server_type');
        $host = get_option('ticket_mail_server_host');
        $port = (get_option('ticket_mail_server_port') == '') ? null : intval(get_option('ticket_mail_server_port'));
        $folder = get_option('ticket_mail_folder');
        $username = get_option('ticket_mail_username');
        $password = get_option('ticket_mail_password');

        $this->_incoming_scan($type, $host, $port, $folder, $username, $password);
    }

    /**
     * Process an e-mail found.
     *
     * @param  EMAIL $from_email From e-mail
     * @param  EMAIL $email_bounce_to E-mail address of sender (usually the same as $email, but not if it was a forwarded e-mail)
     * @param  string $from_name From name
     * @param  string $subject E-mail subject
     * @param  string $body E-mail body
     * @param  array $attachments Map of attachments (name to file data); only populated if $mime_type is appropriate for an attachment
     */
    protected function _process_incoming_message($from_email, $email_bounce_to, $from_name, $subject, $body, $attachments)
    {
        // Try to bind to an existing ticket
        $existing_ticket = null;
        $matches = array();
        $strings = array();
        foreach (array_keys(find_all_langs()) as $lang) {
            if (preg_match('#' . do_lang('TICKET_SIMPLE_SUBJECT_regexp', null, null, null, $lang) . '#', $subject, $matches) != 0) {
                if (strpos($matches[2], '_') !== false) {
                    $existing_ticket = $matches[2];

                    // Validate
                    $topic_id = $GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier(get_option('ticket_forum_name'), $existing_ticket, do_lang('SUPPORT_TICKET', null, null, null, $lang));
                    if ($topic_id === null) {
                        $existing_ticket = null; // Invalid
                    }
                }
            }
        }

        // Remove any tags from the subject line
        $num_matches = preg_match_all('# \[([^\[\]]+)\]#', $subject, $matches);
        $tags = array();
        for ($i = 0; $i < $num_matches; $i++) {
            $tags[] = $matches[1][$i];
            $subject = str_replace($matches[0][$i], '', $subject);
        }

        // Try to bind to a from member
        $member_id = $this->find_member_id($from_email, $tags, $existing_ticket);
        if ($member_id === null) {
            $member_id = $this->handle_missing_member($from_email, $email_bounce_to, get_option('ticket_mail_nonmatch_policy'), $subject, $body);
        }
        if ($member_id === null) {
            return;
        }

        // Remember the e-mail address to member ID mapping
        $GLOBALS['SITE_DB']->query_delete('ticket_known_emailers', array(
            'email_address' => $from_email,
        ));
        $GLOBALS['SITE_DB']->query_insert('ticket_known_emailers', array(
            'email_address' => $from_email,
            'member_id' => $member_id,
        ));

        // Check there can be no forgery vulnerability
        $member_id_comcode = $this->degrade_member_id_for_comcode($member_id);

        global $LAX_COMCODE, $OVERRIDE_MEMBER_ID_COMCODE;
        $OVERRIDE_MEMBER_ID_COMCODE = $member_id_comcode;
        $LAX_COMCODE = true;

        // Add in attachments
        $attachment_errors = $this->save_attachments($attachments, $member_id, $member_id_comcode, $body);

        // Mark that this was e-mailed in
        $body .= "\n\n" . do_lang('TICKET_EMAILED_IN', null, null, null, get_lang($member_id));

        // Post
        if ($existing_ticket === null) {
            $new_ticket_id = strval($member_id) . '_' . uniqid('', false);

            $_home_url = build_url(array('page' => 'tickets', 'type' => 'ticket', 'id' => $new_ticket_id, 'redirect' => null), get_module_zone('tickets'), null, false, true, true);
            $home_url = $_home_url->evaluate();

            // Pick up ticket type, a other/general ticket type if it exists
            $ticket_type_id = null;
            $tags[] = do_lang('OTHER');
            $tags[] = do_lang('GENERAL');
            foreach ($tags as $tag) {
                $ticket_type_id = $GLOBALS['SITE_DB']->query_select_value_if_there('ticket_types', 'id', array($GLOBALS['SITE_DB']->translate_field_ref('ticket_type_name') => $tag));
                if ($ticket_type_id !== null) {
                    break;
                }
            }
            if ($ticket_type_id === null) {
                $ticket_type_id = $GLOBALS['SITE_DB']->query_select_value('ticket_types', 'MIN(id)');
            }

            // Create the ticket...

            ticket_add_post($member_id, $new_ticket_id, $ticket_type_id, $subject, $body, $home_url);

            // Send email (to staff)
            send_ticket_email($new_ticket_id, $subject, $body, $home_url, $from_email, $ticket_type_id, $member_id, true);
        } else {
            $_home_url = build_url(array('page' => 'tickets', 'type' => 'ticket', 'id' => $existing_ticket, 'redirect' => null), get_module_zone('tickets'), null, false, true, true);
            $home_url = $_home_url->evaluate();

            // Reply to the ticket...

            $ticket_type_id = $GLOBALS['SITE_DB']->query_select_value_if_there('tickets', 'ticket_type', array(
                'ticket_id' => $existing_ticket,
            ));

            ticket_add_post($member_id, $existing_ticket, $ticket_type_id, $subject, $body, $home_url);

            // Find true ticket title
            $_forum = 1;
            $_topic_id = 1;
            $_ticket_type_id = 1; // These will be returned by reference
            $posts = get_ticket_posts($existing_ticket, $_forum, $_topic_id, $_ticket_type_id);
            if (!is_array($posts)) {
                warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'ticket'));
            }
            $__title = do_lang('UNKNOWN');
            foreach ($posts as $ticket_post) {
                $__title = $ticket_post['title'];
                if ($__title != '') {
                    break;
                }
            }

            // Send email (to staff & to confirm receipt to $member_id)
            send_ticket_email($existing_ticket, $__title, $body, $home_url, $from_email, null, $member_id, true);
        }

        if (count($attachment_errors) != 0) {
            $this->send_bounce_email__attachment_errors($subject, $body, $from_email, $email_bounce_to, $attachment_errors, $home_url);
        }
    }

    /**
     * Find member ID behind an e-mail.
     *
     * @param  EMAIL $from_email From e-mail
     * @param  ?array $tags List of extra tags (null: none)
     * @param  ?string $existing_ticket ID of existing ticket (null: unknown)
     * @return ?MEMBER The member ID (null: not found)
     */
    protected function find_member_id($from_email, $tags = null, $existing_ticket = null)
    {
        $member_id = null;
        if ($tags !== null) { // TODO: Change to pass empty array() by default in v11
            foreach ($tags as $tag) {
                $member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($tag);
                if ($member_id !== null) {
                    break;
                }
            }
        }
        if ($member_id === null) {
            $member_id = $GLOBALS['SITE_DB']->query_select_value_if_there('ticket_known_emailers', 'member_id', array(
                'email_address' => $from_email,
            ));
        }
        if ($member_id === null) {
            $member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_email_address($from_email);
        }
        if ($member_id === null) {
            if ($existing_ticket === null) {
                $_temp = explode('_', $existing_ticket);
                $member_id = intval($_temp[0]);
            }
        }

        return $member_id;
    }

    /**
     * Strip system code from an e-mail component.
     *
     * @param  string $body E-mail component
     * @param  integer $format A STRIP_* constant
     */
    public/*TODO: protected*/ function strip_system_code(&$body, $format)
    {
        switch ($format) {
            case self::STRIP_SUBJECT:
                // We don't need to bind replies by subject, so no need to strip down
                break;

            case self::STRIP_HTML:
                $strings = array();
                foreach (array_keys(find_all_langs()) as $lang) {
                    $strings[] = do_lang('TICKET_SIMPLE_MAIL_new_regexp', null, null, null, $lang);
                    $strings[] = do_lang('TICKET_SIMPLE_MAIL_reply_regexp', null, null, null, $lang);
                }
                foreach ($strings as $s) {
                    $body = preg_replace('#' . str_replace("\n", "(\n|<br[^<>]*>)*", $s) . '#i', '', $body);
                }
                break;

            case self::STRIP_TEXT:
                $strings = array();
                foreach (array_keys(find_all_langs()) as $lang) {
                    $strings[] = do_lang('TICKET_SIMPLE_MAIL_new_regexp', null, null, null, $lang);
                    $strings[] = do_lang('TICKET_SIMPLE_MAIL_reply_regexp', null, null, null, $lang);
                }
                foreach ($strings as $s) {
                    $body = preg_replace('#' . $s . '#i', '', $body);
                }
                break;
        }
    }

    /**
     * Send out an e-mail about us not recognising an e-mail address for an incoming e-mail.
     *
     * @param  string $subject Subject line of original message
     * @param  string $body Body of original message
     * @param  EMAIL $email E-mail address we tried to bind to
     * @param  EMAIL $email_bounce_to E-mail address of sender (usually the same as $email, but not if it was a forwarded e-mail)
     */
    protected function send_bounce_email__cannot_bind($subject, $body, $email, $email_bounce_to)
    {
        $extended_subject = do_lang('TICKET_CANNOT_BIND_SUBJECT', $subject, $email, array(get_site_name()), get_site_default_lang());
        $extended_message = do_lang('TICKET_CANNOT_BIND_MAIL', comcode_to_clean_text($body), $email, array($subject, get_site_name()), get_site_default_lang());

        $this->send_system_email($extended_subject, $extended_message, $email, $email_bounce_to);
    }
}
