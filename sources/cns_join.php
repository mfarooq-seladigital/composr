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
 * Give error if Conversr-joining is not possible on this site.
 */
function check_joining_allowed()
{
    if (get_forum_type() != 'cns') {
        warn_exit(do_lang_tempcode('NO_CNS'));
    }

    // Check RBL's/stopforumspam
    $spam_check_level = get_option('spam_check_level');
    if (($spam_check_level == 'EVERYTHING') || ($spam_check_level == 'ACTIONS') || ($spam_check_level == 'GUESTACTIONS') || ($spam_check_level == 'JOINING')) {
        require_code('antispam');
        check_rbls();
        check_stopforumspam();
    }

    global $LDAP_CONNECTION;
    if ((!is_null($LDAP_CONNECTION)) && (get_option('ldap_allow_joining', true) === '0')) {
        warn_exit(do_lang_tempcode('JOIN_DISALLOW'));
    }
}

/**
 * Get the join form.
 *
 * @param  Tempcode $url URL to direct to
 * @param  boolean $captcha_if_enabled Whether to handle CAPTCHA (if enabled at all)
 * @param  boolean $intro_message_if_enabled Whether to ask for intro messages (if enabled at all)
 * @param  boolean $invites_if_enabled Whether to check for invites (if enabled at all)
 * @param  boolean $one_per_email_address_if_enabled Whether to check email-address restrictions (if enabled at all)
 * @return array A tuple: Necessary JavaScript code, the form
 */
function cns_join_form($url, $captcha_if_enabled = true, $intro_message_if_enabled = true, $invites_if_enabled = true, $one_per_email_address_if_enabled = true)
{
    cns_require_all_forum_stuff();

    require_css('cns');
    require_code('cns_members_action');
    require_code('cns_members_action2');
    require_code('form_templates');

    $hidden = new Tempcode();
    $hidden->attach(build_keep_post_fields());

    $groups = cns_get_all_default_groups(true);
    $primary_group = either_param_integer('primary_group', null);
    if (($primary_group !== null) && (!in_array($primary_group, $groups))) {
        // Check security
        $test = $GLOBALS['FORUM_DB']->query_select_value('f_groups', 'g_is_presented_at_install', array('id' => $primary_group));
        if ($test == 1) {
            $groups = cns_get_all_default_groups(false);
            $hidden->attach(form_input_hidden('primary_group', strval($primary_group)));
            $groups[] = $primary_group;
        }
    }

    url_default_parameters__enable();
    list($fields, $_hidden) = cns_get_member_fields(true, null, $groups);
    url_default_parameters__disable();
    $hidden->attach($_hidden);

    if ($intro_message_if_enabled) {
        $forum_id = get_option('intro_forum_id');
        if ($forum_id != '') {
            $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => 'b8197832e4467b08e953535202235501', 'TITLE' => do_lang_tempcode('INTRODUCE_YOURSELF'))));
            $fields->attach(form_input_line(do_lang_tempcode('TITLE'), '', 'intro_title', do_lang('INTRO_POST_DEFAULT', '___'), false));
            $fields->attach(form_input_text_comcode(do_lang_tempcode('POST_COMMENT'), do_lang_tempcode('DESCRIPTION_INTRO_POST'), 'intro_post', '', false));
        }
    }

    $text = do_lang_tempcode('ENTER_PROFILE_DETAILS');

    if ($captcha_if_enabled) {
        if (addon_installed('captcha')) {
            require_code('captcha');
            if (use_captcha()) {
                $fields->attach(form_input_captcha());
                $text->attach(' ');
                $text->attach(do_lang_tempcode('FORM_TIME_SECURITY'));
            }
        }
    }

    $submit_name = do_lang_tempcode('PROCEED');

    require_javascript('ajax');

    $script = find_script('username_check');
    $javascript = "
        var form=document.getElementById('username').form;
        form.elements['username'].onchange=function()
        {
            if (form.elements['intro_title'])
                    form.elements['intro_title'].value='" . addslashes(do_lang('INTRO_POST_DEFAULT')) . "'.replace(/\{1\}/g,form.elements['username'].value);
        }
        form.old_submit=form.onsubmit;
        form.onsubmit=function() {
            if ((typeof form.elements['confirm']!='undefined') && (form.elements['confirm'].type=='checkbox') && (!form.elements['confirm'].checked))
            {
                window.fauxmodal_alert('" . php_addslashes(do_lang('DESCRIPTION_I_AGREE_RULES')) . "');
                return false;
            }
            if ((typeof form.elements['email_address_confirm']!='undefined') && (form.elements['email_address_confirm'].value!=form.elements['email_address'].value))
            {
                window.fauxmodal_alert('" . php_addslashes(do_lang('EMAIL_ADDRESS_MISMATCH')) . "');
                return false;
            }
            if ((typeof form.elements['password_confirm']!='undefined') && (form.elements['password_confirm'].value!=form.elements['password'].value))
            {
                window.fauxmodal_alert('" . php_addslashes(do_lang('PASSWORD_MISMATCH')) . "');
                return false;
            }
            document.getElementById('submit_button').disabled=true;
            var url='" . addslashes($script) . "?username='+window.encodeURIComponent(form.elements['username'].value);
            if (!do_ajax_field_test(url,'password='+window.encodeURIComponent(form.elements['password'].value)))
            {
                document.getElementById('submit_button').disabled=false;
                return false;
            }
    ";
    $script = find_script('snippet');
    if ($invites_if_enabled) {
        if (get_option('is_on_invites') == '1') {
            $javascript .= "
            url='" . addslashes($script) . "?snippet=invite_missing&name='+window.encodeURIComponent(form.elements['email_address'].value);
            if (!do_ajax_field_test(url))
            {
                document.getElementById('submit_button').disabled=false;
                return false;
            }
            ";
        }
    }
    if ($one_per_email_address_if_enabled) {
        if (get_option('one_per_email_address') == '1') {
            $javascript .= "
            url='" . addslashes($script) . "?snippet=email_exists&name='+window.encodeURIComponent(form.elements['email_address'].value);
            if (!do_ajax_field_test(url))
            {
                document.getElementById('submit_button').disabled=false;
                return false;
            }
            ";
        }
    }
    if ($captcha_if_enabled) {
        if (addon_installed('captcha')) {
            require_code('captcha');
            if (use_captcha()) {
                $javascript .= "
                    url='" . addslashes($script) . "?snippet=captcha_wrong&name='+window.encodeURIComponent(form.elements['captcha'].value);
                    if (!do_ajax_field_test(url))
                    {
                        document.getElementById('submit_button').disabled=false;
                        return false;
                    }
                    ";
            }
        }
    }
    $javascript .= "
            document.getElementById('submit_button').disabled=false;
            if (typeof form.old_submit!='undefined' && form.old_submit) return form.old_submit();
            return true;
        };
    ";

    $form = do_template('FORM', array('_GUID' => 'f6dba5638ae50a04562df50b1f217311', 'TEXT' => '', 'HIDDEN' => $hidden, 'FIELDS' => $fields, 'SUBMIT_ICON' => 'menu__site_meta__user_actions__join', 'SUBMIT_NAME' => $submit_name, 'URL' => $url));

    return array($javascript, $form);
}

/**
 * Actualise the join form.
 *
 * @param  boolean $captcha_if_enabled Whether to handle CAPTCHA (if enabled at all)
 * @param  boolean $intro_message_if_enabled Whether to ask for intro messages (if enabled at all)
 * @param  boolean $invites_if_enabled Whether to check for invites (if enabled at all)
 * @param  boolean $one_per_email_address_if_enabled Whether to check email-address restrictions (if enabled at all)
 * @param  boolean $confirm_if_enabled Whether to require staff confirmation (if enabled at all)
 * @param  boolean $validate_if_enabled Whether to force email address validation (if enabled at all)
 * @param  boolean $coppa_if_enabled Whether to do COPPA checks (if enabled at all)
 * @param  boolean $instant_login Whether to instantly log the user in
 * @param  ?ID_TEXT $username Username (null: read from environment)
 * @param  ?EMAIL $email_address E-mail address (null: read from environment)
 * @param  ?string $password Password (null: read from environment)
 * @param  ?array $actual_custom_fields Custom fields to save (null: read from environment)
 * @return array A tuple: Messages to show, member ID of new member
 */
function cns_join_actual($captcha_if_enabled = true, $intro_message_if_enabled = true, $invites_if_enabled = true, $one_per_email_address_if_enabled = true, $confirm_if_enabled = true, $validate_if_enabled = true, $coppa_if_enabled = true, $instant_login = true, $username = null, $email_address = null, $password = null, $actual_custom_fields = null)
{
    cns_require_all_forum_stuff();

    require_css('cns');
    require_code('cns_members_action');
    require_code('cns_members_action2');

    // Read in data

    if (is_null($username))	{
        $username = trim(post_param_string('username'));
    }
    cns_check_name_valid($username, null, null, true); // Adjusts username if needed

    if (is_null($password))	{
        $password = trim(post_param_string('password'));
        $password_confirm = trim(post_param_string('password_confirm'));
        if ($password != $password_confirm) {
            warn_exit(make_string_tempcode(escape_html(do_lang('PASSWORD_MISMATCH'))));
        }
    }

    if (is_null($email_address))	{
        $confirm_email_address = post_param_string('email_address_confirm', null);
        $email_address = trim(post_param_string('email_address', member_field_is_required(null, 'email_address') ? false : ''));
        if (!is_null($confirm_email_address)) {
            if (trim($confirm_email_address) != $email_address) {
                warn_exit(make_string_tempcode(escape_html(do_lang('EMAIL_ADDRESS_MISMATCH'))));
            }
        }
        require_code('type_sanitisation');
        if (!is_email_address($email_address)) {
            warn_exit(do_lang_tempcode('INVALID_EMAIL_ADDRESS'));
        }
    }

    if ($invites_if_enabled) { // code branch also triggers general tracking of referrals
        if (get_option('is_on_invites') == '1') {
            $test = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_invites', 'i_inviter', array('i_email_address' => $email_address, 'i_taken' => 0));
            if (is_null($test)) {
                warn_exit(do_lang_tempcode('NO_INVITE'));
            }
        }

        $GLOBALS['FORUM_DB']->query_update('f_invites', array('i_taken' => 1), array('i_email_address' => $email_address, 'i_taken' => 0), '', 1);
    }

    require_code('temporal2');
    list($dob_year, $dob_month, $dob_day) = get_input_date_components('dob');
    if ((is_null($dob_year)) || (is_null($dob_month)) || (is_null($dob_day))) {
        if (member_field_is_required(null, 'dob', null, null)) {
            warn_exit(do_lang_tempcode('NO_PARAMETER_SENT', escape_html('dob')));
        }

        $dob_day = -1;
        $dob_month = -1;
        $dob_year = -1;
    }
    $reveal_age = post_param_integer('reveal_age', 0);

    $timezone = post_param_string('timezone', get_users_timezone());

    $language = post_param_string('language', get_site_default_lang());

    $allow_emails = post_param_integer('allow_emails', 0);
    $allow_emails_from_staff = post_param_integer('allow_emails_from_staff', 0);

    $groups = cns_get_all_default_groups(true); // $groups will contain the built in default primary group too (it is not $secondary_groups)
    $primary_group = post_param_integer('primary_group', null);
    if (($primary_group !== null) && (!in_array($primary_group, $groups)/*= not built in default, which is automatically ok to join without extra security*/)) {
        // Check security
        $test = $GLOBALS['FORUM_DB']->query_select_value('f_groups', 'g_is_presented_at_install', array('id' => $primary_group));
        if ($test == 1) {
            $groups = cns_get_all_default_groups(false); // Get it so it does not include the built in default primary group
            $groups[] = $primary_group; // And add in the *chosen* primary group
        } else {
            $primary_group = null;
        }
    } else {
        $primary_group = null;
    }
    if ($primary_group === null) { // Security error, or built in default (which will already be in $groups)
        $primary_group = get_first_default_group();
    }

    $custom_fields = cns_get_all_custom_fields_match($groups, null, null, null, null, null, null, 0, true);
    if (is_null($actual_custom_fields)) {
        $actual_custom_fields = cns_read_in_custom_fields($custom_fields);
    }

    // Check that the given address isn't already used (if one_per_email_address on)
    $member_id = null;
    if ($one_per_email_address_if_enabled) {
        if (get_option('one_per_email_address') == '1') {
            $test = $GLOBALS['FORUM_DB']->query_select('f_members', array('id', 'm_username'), array('m_email_address' => $email_address), '', 1);
            if (array_key_exists(0, $test)) {
                if ($test[0]['m_username'] != $username) {
                    $reset_url = build_url(array('page' => 'lost_password', 'email_address' => $email_address), get_module_zone('lost_password'));
                    warn_exit(do_lang_tempcode('EMAIL_ADDRESS_IN_USE', escape_html(get_site_name()), escape_html($reset_url->evaluate())));
                }
                $member_id = $test[0]['id'];
            }
        }
    }

    // Check RBL's/stopforumspam
    $spam_check_level = get_option('spam_check_level');
    if (($spam_check_level == 'EVERYTHING') || ($spam_check_level == 'ACTIONS') || ($spam_check_level == 'GUESTACTIONS') || ($spam_check_level == 'JOINING')) {
        require_code('antispam');
        check_rbls();
        check_stopforumspam($username, $email_address);
    }

    if ($captcha_if_enabled) {
        if (addon_installed('captcha')) {
            require_code('captcha');
            enforce_captcha();
        }
    }
    if (addon_installed('ldap')) {
        require_code('cns_ldap');
        if (cns_is_ldap_member_potential($username)) {
            warn_exit(do_lang_tempcode('DUPLICATE_JOIN_AUTH'));
        }
    }

    // Add member
    $skip_confirm = (get_option('email_confirm_join') == '0');
    if (!$confirm_if_enabled) {
        $skip_confirm = true;
    }
    $validated_email_confirm_code = $skip_confirm ? '' : strval(mt_rand(1, 32000));
    $require_new_member_validation = get_option('require_new_member_validation') == '1';
    if (!$validate_if_enabled) {
        $require_new_member_validation = false;
    }
    $coppa = (get_option('is_on_coppa') == '1') && (utctime_to_usertime(time() - mktime(0, 0, 0, $dob_month, $dob_day, $dob_year)) / 31536000.0 < 13.0);
    if (!$coppa_if_enabled) {
        $coppa = false;
    }
    $validated = ($require_new_member_validation || $coppa) ? 0 : 1;
    if (is_null($member_id)) {
        $member_id = cns_make_member($username, $password, $email_address, $groups, $dob_day, $dob_month, $dob_year, $actual_custom_fields, $timezone, $primary_group, $validated, time(), time(), '', null, '', 0, (get_option('default_preview_guests') == '1') ? 1 : 0, $reveal_age, '', '', '', 1, (get_option('allow_auto_notifications') == '0') ? 0 : 1, $language, $allow_emails, $allow_emails_from_staff, get_ip_address(), $validated_email_confirm_code, true, '', '');
    } else {
        attach_message(do_lang_tempcode('ALREADY_EXISTS', escape_html($username)), 'notice');
    }

    // Send confirm mail
    if (!$skip_confirm) {
        $zone = get_module_zone('join');
        $_url = build_url(array('page' => 'join', 'type' => 'step4', 'email' => $email_address, 'code' => $validated_email_confirm_code), $zone, null, false, false, true);
        $url = $_url->evaluate();
        $_url_simple = build_url(array('page' => 'join', 'type' => 'step4'), $zone, null, false, false, true);
        $url_simple = $_url_simple->evaluate();
        $redirect = get_param_string('redirect', '');
        if ($redirect != '') {
            $url .= '&redirect=' . cms_url_encode($redirect);
        }
        $message = do_lang('CNS_SIGNUP_TEXT', comcode_escape(get_site_name()), comcode_escape($url), array($url_simple, $email_address, $validated_email_confirm_code), $language);
        require_code('mail');
        if (!$coppa) {
            mail_wrap(do_lang('CONFIRM_EMAIL_SUBJECT', get_site_name(), null, null, $language), $message, array($email_address), $username, '', '', 3, null, false, null, false, false, false, 'MAIL', true);
        }
    }

    // Send COPPA mail
    if ($coppa) {
        $fields_done = do_lang('THIS_WITH_COMCODE', do_lang('USERNAME'), $username) . "\n\n";
        foreach ($custom_fields as $custom_field) {
            if ($custom_field['cf_type'] != 'upload') {
                $fields_done .= do_lang('THIS_WITH_COMCODE', $custom_field['trans_name'], post_param_string('field_' . $custom_field['id'])) . "\n";
            }
        }
        $_privacy_url = build_url(array('page' => 'privacy'), '_SEARCH', null, false, false, true);
        $privacy_url = $_privacy_url->evaluate();
        $message = do_lang('COPPA_MAIL', comcode_escape(get_option('site_name')), comcode_escape(get_option('privacy_fax')), array(comcode_escape(get_option('privacy_postal_address')), comcode_escape($fields_done), comcode_escape($privacy_url)), $language);
        require_code('mail');
        mail_wrap(do_lang('COPPA_JOIN_SUBJECT', $username, get_site_name(), null, $language), $message, array($email_address), $username);
    }

    // Send 'validate this member' notification
    if ($require_new_member_validation) {
        require_code('notifications');
        $_validation_url = build_url(array('page' => 'members', 'type' => 'view', 'id' => $member_id), get_module_zone('members'), null, false, false, true, 'tab__edit');
        $validation_url = $_validation_url->evaluate();
        $message = do_lang('VALIDATE_NEW_MEMBER_MAIL', comcode_escape($username), comcode_escape($validation_url), comcode_escape(strval($member_id)), get_site_default_lang());
        dispatch_notification('cns_member_needs_validation', null, do_lang('VALIDATE_NEW_MEMBER_SUBJECT', $username, null, null, get_site_default_lang()), $message, null, A_FROM_SYSTEM_PRIVILEGED);
    }

    // Send new member notification
    require_code('notifications');
    $_member_url = build_url(array('page' => 'members', 'type' => 'view', 'id' => $member_id), get_module_zone('members'), null, false, false, true);
    $member_url = $_member_url->evaluate();
    $message = do_lang('NEW_MEMBER_NOTIFICATION_MAIL', comcode_escape($username), comcode_escape(get_site_name()), array(comcode_escape($member_url), comcode_escape(strval($member_id))), get_site_default_lang());
    dispatch_notification('cns_new_member', null, do_lang('NEW_MEMBER_NOTIFICATION_MAIL_SUBJECT', $username, get_site_name(), null, get_site_default_lang()), $message, null, A_FROM_SYSTEM_PRIVILEGED);

    // Intro post
    if ($intro_message_if_enabled) {
        $forum_id = get_option('intro_forum_id');
        if ($forum_id != '') {
            if (!is_numeric($forum_id)) {
                $_forum_id = $GLOBALS['FORUM_DB']->query_select_value('f_forums', 'id', array('f_name' => $forum_id));
                if (is_null($_forum_id)) {
                    $forum_id = strval(db_get_first_id());
                } else {
                    $forum_id = strval($_forum_id);
                }
            }

            $intro_title = post_param_string('intro_title', '');
            $intro_post = post_param_string('intro_post', '');
            if ($intro_post != '') {
                require_code('cns_topics_action');
                $initial_validated = 1;
                if ($intro_title == '') {
                    $intro_title = do_lang('INTRO_POST_DEFAULT', $username);
                }
                $topic_id = cns_make_topic(intval($forum_id), '', '', $initial_validated, 1, 0, 0, 0, null, null, false);
                require_code('cns_posts_action');
                cns_make_post($topic_id, $intro_title, $intro_post, 0, true, $initial_validated, 0, null, null, null, $member_id, null, null, null, false);
            }
        }
    }

    // Alert user to situation
    $message = new Tempcode();
    if ($coppa) {
        if (!$skip_confirm) {
            $message->attach(do_lang_tempcode('CNS_WAITING_CONFIRM_MAIL'));
        }
        $message->attach(do_lang_tempcode('CNS_WAITING_CONFIRM_MAIL_COPPA'));
    } elseif ($require_new_member_validation) {
        if (!$skip_confirm) {
            $message->attach(do_lang_tempcode('CNS_WAITING_CONFIRM_MAIL'));
        }
        $message->attach(do_lang_tempcode('CNS_WAITING_CONFIRM_MAIL_VALIDATED', escape_html(get_custom_base_url())));
    } elseif ($skip_confirm) {
        if (($instant_login) && (!$GLOBALS['IS_ACTUALLY_ADMIN'])) { // Automatic instant log in
            require_code('users_active_actions');
            handle_active_login($username); // The auto-login simulates a real login, i.e. actually checks the password from the form against the real account. So no security hole when "re-registering" a real user
            $message->attach(do_lang_tempcode('CNS_LOGIN_AUTO'));
        } else { // Invite them to explicitly instant log in
            $_login_url = build_url(array('page' => 'login', 'redirect' => get_param_string('redirect', null)), get_module_zone('login'));
            $login_url = $_login_url->evaluate();
            $message->attach(do_lang_tempcode('CNS_LOGIN_INSTANT', escape_html($login_url)));
        }
    } else {
        if (!$skip_confirm) {
            $message->attach(do_lang_tempcode('CNS_WAITING_CONFIRM_MAIL'));
        }
        $message->attach(do_lang_tempcode('CNS_WAITING_CONFIRM_MAIL_INSTANT'));
    }
    $message = protect_from_escaping($message);

    return array($message, $member_id);
}
