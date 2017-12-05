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
 * @package    points
 */

/**
 * Module page class.
 */
class Module_points
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled)
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 9;
        $info['locked'] = true;
        $info['update_require_upgrade'] = true;
        return $info;
    }

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('chargelog');
        $GLOBALS['SITE_DB']->drop_table_if_exists('gifts');

        delete_privilege('give_points_self');
        delete_privilege('have_negative_gift_points');
        delete_privilege('give_negative_points');
        delete_privilege('view_charge_log');
        delete_privilege('use_points');
        delete_privilege('trace_anonymous_gifts');

        $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('points_used');
        $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('gift_points_used');
        $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('points_gained_given');
        $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('points_gained_rating');
    }

    /**
     * Install the module.
     *
     * @param  ?integer $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        if ($upgrade_from === null) {
            add_privilege('POINTS', 'use_points', true);

            $GLOBALS['SITE_DB']->create_table('chargelog', array(
                'id' => '*AUTO',
                'member_id' => 'MEMBER',
                'amount' => 'INTEGER',
                'reason' => 'SHORT_TRANS__COMCODE',
                'date_and_time' => 'TIME',
            ));

            $GLOBALS['SITE_DB']->create_table('gifts', array(
                'id' => '*AUTO',
                'date_and_time' => 'TIME',
                'amount' => 'INTEGER',
                'gift_from' => 'MEMBER',
                'gift_to' => 'MEMBER',
                'reason' => 'SHORT_TRANS__COMCODE',
                'anonymous' => 'BINARY',
            ));
            $GLOBALS['SITE_DB']->create_index('gifts', 'giftsgiven', array('gift_from'));
            $GLOBALS['SITE_DB']->create_index('gifts', 'giftsreceived', array('gift_to'));

            add_privilege('POINTS', 'trace_anonymous_gifts', false);
            add_privilege('POINTS', 'give_points_self', false);
            add_privilege('POINTS', 'have_negative_gift_points', false);
            add_privilege('POINTS', 'give_negative_points', false);
            add_privilege('POINTS', 'view_charge_log', false);

            $GLOBALS['FORUM_DRIVER']->install_create_custom_field('points_used', 20, 1, 0, 0, 0, '', 'integer');
            $GLOBALS['FORUM_DRIVER']->install_create_custom_field('gift_points_used', 20, 1, 0, 0, 0, '', 'integer');
            $GLOBALS['FORUM_DRIVER']->install_create_custom_field('points_gained_given', 20, 1, 0, 0, 0, '', 'integer');
            $GLOBALS['FORUM_DRIVER']->install_create_custom_field('points_gained_rating', 20, 1, 0, 0, 0, '', 'integer');
        }

        if (($upgrade_from !== null) && ($upgrade_from < 8)) { // LEGACY
            $GLOBALS['SITE_DB']->alter_table_field('chargelog', 'user_id', 'MEMBER', 'member_id');

            rename_config_option('leaderboard_start_date', 'leader_board_start_date');
        }

        if (($upgrade_from === null) || ($upgrade_from < 8)) {
            $GLOBALS['FORUM_DRIVER']->install_create_custom_field('points_gained_visiting', 20, 1, 0, 0, 0, '', 'integer');
        }

        if (($upgrade_from === null) || ($upgrade_from < 9)) {
            $GLOBALS['SITE_DB']->create_index('chargelog', 'member_id', array('member_id'));
        }
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user)
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name)
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return null to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled)
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        if (get_forum_type() == 'cns' || get_forum_type() == 'none') {
            return array();
        }
        $ret = array(
            'browse' => array('MEMBER_POINT_FIND', 'buttons/search'),
        );
        if (!$check_perms || !is_guest($member_id)) {
            $ret['member'] = array('POINTS', 'menu/social/points');
        }
        return $ret;
    }

    public $title;
    public $member_id_of;

    /**
     * Module pre-run function. Allows us to know metadata for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none)
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        require_lang('points');

        if ($type == 'browse' || $type == '_search') {
            set_feed_url('?mode=points&select=');
        }

        if ($type == 'browse') {
            $this->member_id_of = db_get_first_id() + 1;
            set_feed_url('?mode=points&select=' . strval($this->member_id_of));

            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('MEMBER_POINT_FIND'))));

            $this->title = get_screen_title('MEMBER_POINT_FIND');
        }

        if ($type == '_search') {
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('MEMBER_POINT_FIND'))));

            $this->title = get_screen_title('MEMBER_POINT_FIND');

            breadcrumb_set_self(do_lang_tempcode('RESULTS'));
        }

        if ($type == 'give') {
            $member_id_of = get_param_integer('id');

            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('MEMBER_POINT_FIND')), array('_SELF:_SELF:member:' . strval($member_id_of), do_lang_tempcode('_POINTS', escape_html($GLOBALS['FORUM_DRIVER']->get_username($member_id_of, true))))));

            $this->title = get_screen_title('POINTS');
        }

        if ($type == 'member') {
            $this->member_id_of = get_param_integer('id', get_member());
            set_feed_url('?mode=points&select=' . strval($this->member_id_of));

            $username = $GLOBALS['FORUM_DRIVER']->get_username($this->member_id_of, true, USERNAME_DEFAULT_ERROR | USERNAME_GUEST_AS_DEFAULT);
            $this->title = get_screen_title('_POINTS', true, array(escape_html($username)));
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution
     */
    public function run()
    {
        if (get_forum_type() == 'none') {
            warn_exit(do_lang_tempcode('NO_FORUM_INSTALLED'));
        }

        require_code('points');
        require_css('points');

        // Work out what we're doing here
        $type = get_param_string('type', 'browse');

        if ($type == 'browse') {
            return $this->points_search_form();
        }
        if ($type == '_search') {
            return $this->points_search_results();
        }
        if ($type == 'give') {
            return $this->do_give();
        }
        if ($type == 'member') {
            return $this->points_profile();
        }

        return new Tempcode();
    }

    /**
     * The UI to search for a member (with regard to viewing their point profile).
     *
     * @return Tempcode The UI
     */
    public function points_search_form()
    {
        $post_url = build_url(array('page' => '_SELF', 'type' => '_search'), '_SELF', array(), false, true);
        require_code('form_templates');
        if (!is_guest()) {
            $username = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
        } else {
            $username = '';
        }
        $fields = form_input_username(do_lang_tempcode('USERNAME'), '', 'username', $username, true, false);
        $submit_name = do_lang_tempcode('SEARCH');
        $text = new Tempcode();
        $text->attach(paragraph(do_lang_tempcode('POINTS_SEARCH_FORM')));
        $text->attach(paragraph(do_lang_tempcode('WILDCARD')));

        return do_template('FORM_SCREEN', array(
            '_GUID' => 'e5ab8d5d599093d1a550cb3b3e56d2bf',
            'GET' => true,
            'SKIP_WEBSTANDARDS' => true,
            'HIDDEN' => '',
            'TITLE' => $this->title,
            'URL' => $post_url,
            'FIELDS' => $fields,
            'SUBMIT_ICON' => 'buttons--search',
            'SUBMIT_NAME' => $submit_name,
            'TEXT' => $text,
        ));
    }

    /**
     * The actualiser for a points profile search.
     *
     * @return Tempcode The UI
     */
    public function points_search_results()
    {
        $username = str_replace('*', '%', get_param_string('username'));
        if ((substr($username, 0, 1) == '%') && ($GLOBALS['FORUM_DRIVER']->get_num_members() > 3000)) {
            warn_exit(do_lang_tempcode('CANNOT_WILDCARD_START'));
        }
        if ((strpos($username, '%') !== false) && (strpos($username, '%') < 6) && ($GLOBALS['FORUM_DRIVER']->get_num_members() > 30000)) {
            warn_exit(do_lang_tempcode('CANNOT_WILDCARD_START'));
        }
        if ((strpos($username, '%') !== false) && (strpos($username, '%') < 12) && ($GLOBALS['FORUM_DRIVER']->get_num_members() > 300000)) {
            warn_exit(do_lang_tempcode('CANNOT_WILDCARD_START'));
        }
        $rows = $GLOBALS['FORUM_DRIVER']->get_matching_members($username, 100);
        if (!array_key_exists(0, $rows)) {
            return warn_screen($this->title, do_lang_tempcode('NO_RESULTS'));
        }

        $results = new Tempcode();
        foreach ($rows as $myrow) {
            $id = $GLOBALS['FORUM_DRIVER']->mrow_id($myrow);
            if (!is_guest($id)) {
                $url = build_url(array('page' => '_SELF', 'type' => 'member', 'id' => $id), '_SELF');
                $username = $GLOBALS['FORUM_DRIVER']->mrow_username($myrow);

                $results->attach(do_template('POINTS_SEARCH_RESULT', array('_GUID' => 'df240255b2981dcaee38e126622be388', 'URL' => $url, 'ID' => strval($id), 'USERNAME' => $username)));
            }
        }

        return do_template('POINTS_SEARCH_SCREEN', array('_GUID' => '659af8a012d459db09dad0325a75ac70', 'TITLE' => $this->title, 'RESULTS' => $results));
    }

    /**
     * The UI for a points profile.
     *
     * @return Tempcode The UI
     */
    public function points_profile()
    {
        $member_id_of = $this->member_id_of;

        if (get_forum_type() == 'cns') {
            $url = $GLOBALS['FORUM_DRIVER']->member_profile_url($member_id_of, true);
            if (is_object($url)) {
                $url = $url->evaluate();
            }
            return redirect_screen($this->title, $url . '#tab__points', '');
        }

        require_code('points3');
        $content = points_profile($member_id_of, get_member());

        return do_template('POINTS_SCREEN', array('_GUID' => '7fadfc2886ba063008f6333fb3f19e75', 'TITLE' => $this->title, 'CONTENT' => $content));
    }

    /**
     * The actualiser for a gift point transaction.
     *
     * @return Tempcode The UI
     */
    public function do_give()
    {
        $member_id_of = get_param_integer('id');

        $trans_type = post_param_string('trans_type', 'gift');

        $amount = post_param_integer('amount');
        $reason = post_param_string('reason');

        $worked = false;

        $member_id_viewing = get_member();
        if (($member_id_of == $member_id_viewing) && (!has_privilege($member_id_viewing, 'give_points_self'))) { // No cheating
            $message = do_lang_tempcode('PE_SELF');
        } elseif (is_guest($member_id_viewing)) { // No cheating
            $message = do_lang_tempcode('MUST_LOGIN');
        } else {
            if ($trans_type == 'gift') {
                $anonymous = post_param_integer('anonymous', 0);
                $viewer_gift_points_available = get_gift_points_to_give($member_id_viewing);
                //$viewer_gift_points_used = get_gift_points_used($member_id_viewing);

                if (($viewer_gift_points_available < $amount) && (!has_privilege($member_id_viewing, 'have_negative_gift_points'))) { // Validate we have enough for this, and add to usage
                    $message = do_lang_tempcode('PE_LACKING_GIFT_POINTS');
                } elseif (($amount < 0) && (!has_privilege($member_id_viewing, 'give_negative_points'))) { // Trying to be negative
                    $message = do_lang_tempcode('PE_NEGATIVE_GIFT');
                } elseif ($reason == '') { // Must give a reason
                    $message = do_lang_tempcode('IMPROPERLY_FILLED_IN');
                } else {
                    // Write transfer
                    require_code('points2');
                    give_points($amount, $member_id_of, $member_id_viewing, $reason, $anonymous == 1);

                    // Randomised gifts
                    $gift_reward_chance = intval(get_option('gift_reward_chance'));
                    $gift_reward_amount = intval(get_option('gift_reward_amount'));
                    if (mt_rand(0, 100) < $gift_reward_chance && floatval($gift_reward_chance) / 100.0 * $gift_reward_amount >= floatval($amount)) {
                        system_gift_transfer(do_lang('_PR_LUCKY'), $gift_reward_amount, $member_id_viewing, $anonymous == 0/*if original transaction anonymous we can't log this, otherwise could be worked out via some cross-checking*/);

                        $message = do_lang_tempcode('PR_LUCKY', escape_html(integer_format($gift_reward_amount)));
                    } else {
                        $message = do_lang_tempcode('PR_NORMAL');
                    }

                    $worked = true;
                }
            }

            if ($trans_type == 'refund') {
                $trans_type = 'charge';
                $amount = -$amount;
            }
            if ($trans_type == 'charge') {
                if (has_actual_page_access($member_id_viewing, 'admin_points')) {
                    require_code('points2');
                    charge_member($member_id_of, $amount, $reason);
                    $left = available_points($member_id_of);

                    $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id_of);
                    $message = do_lang_tempcode('MEMBER_HAS_BEEN_CHARGED', escape_html($username), escape_html(integer_format($amount)), escape_html(integer_format($left)));

                    $worked = true;
                } else {
                    access_denied('I_ERROR');
                }
            }
        }

        if ($worked) {
            // Show it worked / Refresh
            $url = build_url(array('page' => '_SELF', 'type' => 'member', 'id' => $member_id_of), '_SELF');
            return redirect_screen($this->title, $url, $message);
        }
        return warn_screen($this->title, $message);
    }
}
