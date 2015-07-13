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
 * @package    pointstore
 */

/**
 * Module page class.
 */
class Module_admin_pointstore
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions.
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user).
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        $ret = array(
            'browse' => array('POINTSTORE_MANAGE_SALES', 'menu/adminzone/audit/pointstore_log'),
        );
        if (!$be_deferential) {
            $ret += array(
                'p' => array('POINTSTORE_MANAGE_INVENTORY', 'menu/social/pointstore'),
            );
        }
        return $ret;
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        require_lang('pointstore');

        set_helper_panel_tutorial('tut_points');

        if ($type == 'browse') {
            $also_url = build_url(array('page' => '_SELF', 'type' => 'p'), '_SELF');
            attach_message(do_lang_tempcode('menus:ALSO_SEE_SETUP', escape_html($also_url->evaluate())), 'inform', true);
        }

        if ($type == 'browse' || $type == '_logs') {
            $this->title = get_screen_title('POINTSTORE_MANAGE_SALES');
        }

        if ($type == 'p') {
            $also_url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
            attach_message(do_lang_tempcode('menus:ALSO_SEE_AUDIT', escape_html($also_url->evaluate())), 'inform', true);
        }

        if ($type == 'p' || $type == '_p') {
            $this->title = get_screen_title('POINTSTORE_MANAGE_INVENTORY');
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution.
     */
    public function run()
    {
        require_code('form_templates');
        require_css('points');

        $type = get_param_string('type', 'browse');

        if ($type == 'browse') {
            return $this->pointstore_log_interface();
        }
        if ($type == '_logs') {
            return $this->delete_log_entry();
        }
        if ($type == 'p') {
            return $this->interface_set_prices();
        }
        if ($type == '_p') {
            return $this->set_prices();
        }

        return new Tempcode();
    }

    /**
     * The UI to view Point Store logs.
     *
     * @return Tempcode The UI
     */
    public function pointstore_log_interface()
    {
        $max = get_param_integer('max', 50);
        $start = get_param_integer('start', 0);

        $rows = $GLOBALS['SITE_DB']->query_select('sales', array('*'), null, 'ORDER BY date_and_time DESC', $max, $start);
        $max_rows = $GLOBALS['SITE_DB']->query_select_value('sales', 'COUNT(*)');

        $out = new Tempcode();
        require_code('templates_results_table');
        require_code('templates_columned_table');
        $do_other_details = false;
        foreach ($rows as $row) {
            if ($row['details2'] != '') {
                $do_other_details = true;
            }
        }
        foreach ($rows as $row) {
            $username = $GLOBALS['FORUM_DRIVER']->get_username($row['memberid']);
            if (is_null($username)) {
                $username = do_lang('UNKNOWN');
            }
            switch ($row['purchasetype']) {
                case 'banner':
                    require_lang('banners');
                    $type = do_lang('ADD_BANNER');
                    break;
                case 'pop3':
                    $type = do_lang('POP3');
                    break;
                case 'forwarding':
                    $type = do_lang('FORWARDING');
                    break;
                default:
                    $_type = do_lang($row['purchasetype'], null, null, null, null, false);
                    if (is_null($type)) {
                        $type = make_string_tempcode($row['purchasetype']);
                    } else {
                        $type = do_lang_tempcode($row['purchasetype']);
                    }
                    break;
            }
            $details_1 = $row['details'];
            $details_2 = $row['details2'];
            $date = get_timezoned_date($row['date_and_time']);

            $url = build_url(array('page' => '_SELF', 'type' => '_logs', 'date_and_time' => $row['date_and_time'], 'memberid' => $row['memberid']), '_SELF');
            $actions = do_template('COLUMNED_TABLE_ACTION_DELETE_ENTRY', array('_GUID' => '12e3ea365f1a1ed2e7800293f3203283', 'NAME' => $username, 'URL' => $url));

            if ($do_other_details) {
                $out->attach(columned_table_row(array($username, $type, $details_1, $details_2, $date, $actions), true));
            } else {
                $out->attach(columned_table_row(array($username, $type, $details_1, $date, $actions), true));
            }
        }
        if ($out->is_empty()) {
            return inform_screen($this->title, do_lang_tempcode('NO_ENTRIES'));
        }

        if ($do_other_details) {
            $header_row = columned_table_header_row(array(do_lang_tempcode('USERNAME'), do_lang_tempcode('PURCHASE'), do_lang_tempcode('DETAILS'), do_lang_tempcode('OTHER_DETAILS'), do_lang_tempcode('DATE_TIME'), do_lang_tempcode('ACTIONS')));
        } else {
            $header_row = columned_table_header_row(array(do_lang_tempcode('USERNAME'), do_lang_tempcode('PURCHASE'), do_lang_tempcode('DETAILS'), do_lang_tempcode('DATE_TIME'), do_lang_tempcode('ACTIONS')));
        }

        $content = do_template('COLUMNED_TABLE', array('_GUID' => 'd87800ff26e9e5b8f7593fae971faa73', 'HEADER_ROW' => $header_row, 'ROWS' => $out));

        require_code('templates_pagination');
        $pagination = pagination(do_lang('POINTSTORE_MANAGE_SALES'), $start, 'start', $max, 'max', $max_rows);

        return do_template('POINTSTORE_LOG_SCREEN', array('_GUID' => '014cf9436ece951edb55f2f7b0efb597', 'TITLE' => $this->title, 'CONTENT' => $content, 'PAGINATION' => $pagination));
    }

    /**
     * The actualiser to delete a purchase.
     *
     * @return Tempcode The UI
     */
    public function delete_log_entry()
    {
        $this->_delete_log_entry(get_param_integer('date_and_time'), get_param_integer('memberid'));

        // Show it worked / Refresh
        $url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }

    /**
     * Delete a Point Store purchase.
     *
     * @param  integer $date_and_time The time of the purchase
     * @param  MEMBER $memberid The member that made the purchase
     */
    public function _delete_log_entry($date_and_time, $memberid)
    {
        $GLOBALS['SITE_DB']->query_delete('sales', array('date_and_time' => $date_and_time, 'memberid' => $memberid), '', 1);
    }

    /**
     * The UI to set Point Store prices.
     *
     * @return Tempcode The UI
     */
    public function interface_set_prices()
    {
        $field_groups = new Tempcode();
        $add_forms = new Tempcode();

        // Load up configuration from hooks
        $_hooks = find_all_hooks('modules', 'pointstore');
        foreach (array_keys($_hooks) as $hook) {
            require_code('hooks/modules/pointstore/' . filter_naughty_harsh($hook));
            $object = object_factory('Hook_pointstore_' . filter_naughty_harsh($hook), true);
            if (is_null($object)) {
                continue;
            }
            if (method_exists($object, 'config')) {
                $fg = $object->config();
                if (!is_null($fg)) {
                    foreach ($fg[0] as $__fg) {
                        $_fg = do_template('FORM_GROUP', array('_GUID' => '58a0948313f0e8e69c06ee01fb7ee48a', 'FIELDS' => $__fg[0], 'HIDDEN' => $__fg[1]));
                        $field_groups->attach(do_template('POINTSTORE_PRICES_FORM_WRAP', array('_GUID' => '938143162b418de982cdb6ce8d8a92ee', 'TITLE' => $__fg[2], 'FORM' => $_fg)));
                    }
                    if (!$fg[2]->is_empty()) {
                        $submit_name = do_lang_tempcode('ADD');

                        $post_url = build_url(array('page' => '_SELF', 'type' => '_p'), '_SELF');

                        $fg[2] = do_template('FORM', array(
                            '_GUID' => 'e98141bc0a2a54abcca59a5c947a6738',
                            'SECONDARY_FORM' => true,
                            'TABINDEX' => strval(get_form_field_tabindex(null)),
                            'HIDDEN' => '',
                            'TEXT' => $fg[3],
                            'FIELDS' => $fg[2],
                            'SUBMIT_BUTTON_CLASS' => 'proceed_button_left',
                            'SUBMIT_ICON' => 'menu___generic_admin__add_one',
                            'SUBMIT_NAME' => $submit_name,
                            'URL' => $post_url,
                            'SUPPORT_AUTOSAVE' => true,
                        ));
                        $add_forms->attach(do_template('POINTSTORE_PRICES_FORM_WRAP', array('_GUID' => '3956550ebff14bbb923b57c8341b0862', 'TITLE' => $fg[1], 'FORM' => $fg[2])));
                    }
                }
            }
        }

        $submit_name = do_lang_tempcode('SAVE_ALL');

        $post_url = build_url(array('page' => '_SELF', 'type' => '_p'), '_SELF');

        if ($field_groups->is_empty()) {
            $edit_form = new Tempcode();
        } else {
            $edit_form = do_template('FORM_GROUPED', array(
                '_GUID' => 'bf025026dcfc86cfd0a8ef3728bbf6d8',
                'TEXT' => '',
                'FIELD_GROUPS' => $field_groups,
                'SUBMIT_ICON' => 'buttons__save',
                'SUBMIT_NAME' => $submit_name,
                'SUBMIT_BUTTON_CLASS' => 'proceed_button_left_2',
                'URL' => $post_url,
                'SUPPORT_AUTOSAVE' => true,
            ));
        }

        list($warning_details, $ping_url) = handle_conflict_resolution();

        return do_template('POINTSTORE_PRICE_SCREEN', array(
            '_GUID' => '278c8244c7f1743370198dfc437b7bbf',
            'PING_URL' => $ping_url,
            'WARNING_DETAILS' => $warning_details,
            'TITLE' => $this->title,
            'EDIT_FORM' => $edit_form,
            'ADD_FORMS' => $add_forms,
        ));
    }

    /**
     * The actualiser to set Point Store prices.
     *
     * @return Tempcode The UI
     */
    public function set_prices()
    {
        // Save configuration for hooks
        $_hooks = find_all_hooks('modules', 'pointstore');
        foreach (array_keys($_hooks) as $hook) {
            require_code('hooks/modules/pointstore/' . filter_naughty_harsh($hook));
            $object = object_factory('Hook_pointstore_' . filter_naughty_harsh($hook), true);
            if (is_null($object)) {
                continue;
            }
            if (method_exists($object, 'save_config')) {
                $object->save_config();
            }
        }

        log_it('POINTSTORE_CHANGED_PRICES');

        // Show it worked / Refresh
        $url = build_url(array('page' => '_SELF', 'type' => 'p'), '_SELF');
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }
}
