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
 * @package    ecommerce
 */

require_code('crud_module');
require_javascript('ecommerce');

/**
 * Module page class.
 */
class Module_admin_ecommerce extends Standard_crud_module
{
    protected $lang_type = 'USERGROUP_SUBSCRIPTION';
    protected $select_name = 'TITLE';
    protected $select_name_description = 'DESCRIPTION_TITLE';
    protected $menu_label = 'USERGROUP_SUBSCRIPTIONS';
    protected $table = 'f_usergroup_subs';
    protected $orderer = 's_title';
    protected $title_is_multi_lang = true;
    protected $donext_entry_content_type = 'usergroup_subscription';
    protected $donext_category_content_type = null;

    protected $functions = 'moduleAdminEcommerce';

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
        $info['version'] = 2;
        $info['locked'] = false;
        return $info;
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
        if (!addon_installed('ecommerce')) {
            return null;
        }

        if (get_value('unofficial_ecommerce') !== '1') {
            if (get_forum_type() != 'cns') {
                return null;
            }
        }

        $ret = array(
            'browse' => array('CUSTOM_PRODUCT_USERGROUP', 'menu/adminzone/audit/ecommerce/subscriptions'),
        );
        if (!$be_deferential) {
            $ret += array(
                'prices' => array('ECOM_PRODUCTS_MANAGE_INVENTORY', 'menu/adminzone/setup/ecommerce_products'),
            );
        }
        $ret += parent::get_entry_points();
        return $ret;
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know metadata for <head> before we start streaming output.
     *
     * @param  boolean $top_level Whether this is running at the top level, prior to having sub-objects called
     * @param  ?ID_TEXT $type The screen type to consider for metadata purposes (null: read from environment)
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none)
     */
    public function pre_run($top_level = true, $type = null)
    {
        if (!addon_installed('TODO')) {
            return null;
        }

        $error_msg = new Tempcode();
        if (!addon_installed__messaged('ecommerce', $error_msg)) {
            return $error_msg;
        }

        $type = get_param_string('type', 'browse');

        require_code('ecommerce');

        set_helper_panel_tutorial('tut_ecommerce');

        if ($type == 'browse') {
            $this->title = get_screen_title('ECOMMERCE');
        }

        if (($type == 'browse') || ($type == 'add') || ($type == '_add') || ($type == 'edit') || ($type == '_edit') || ($type == '__edit')) {
            set_helper_panel_text(comcode_lang_string('DOC_USERGROUP_SUBSCRIPTION'));

            if (get_forum_type() == 'cns') {
                breadcrumb_set_parents(array(array('_SEARCH:admin:setup', do_lang_tempcode('SETUP')), array('_SEARCH:admin_cns_members:browse', do_lang_tempcode('MEMBERS'))));
            }
        }

        if ($type == 'prices' || $type == '_prices') {
            $this->title = get_screen_title('ECOM_PRODUCTS_MANAGE_INVENTORY');
        }

        return parent::pre_run($top_level);
    }

    /**
     * Standard crud_module run_start.
     *
     * @param  ID_TEXT $type The type of module execution
     * @return Tempcode The output of the run
     */
    public function run_start($type)
    {
        require_code('ecommerce2');

        if (get_value('unofficial_ecommerce') !== '1') {
            if (get_forum_type() != 'cns') {
                warn_exit(do_lang_tempcode('NO_CNS'));
            }
        }

        if (get_forum_type() == 'cns') {
            cns_require_all_forum_stuff();
        }

        $this->add_one_label = do_lang_tempcode('ADD_USERGROUP_SUBSCRIPTION');
        $this->edit_this_label = do_lang_tempcode('EDIT_THIS_USERGROUP_SUBSCRIPTION');
        $this->edit_one_label = do_lang_tempcode('EDIT_USERGROUP_SUBSCRIPTION');

        if ($type == 'browse') {
            return $this->browse();
        }
        if ($type == 'prices') {
            return $this->prices();
        }
        if ($type == '_prices') {
            return $this->_prices();
        }

        return new Tempcode();
    }

    /**
     * The do-next manager for before setup management.
     *
     * @return Tempcode The UI
     */
    public function browse()
    {
        require_code('templates_donext');
        return do_next_manager(
            $this->title,
            comcode_lang_string('DOC_ECOMMERCE'),
            array(
                ((get_forum_type() != 'cns') && (get_value('unofficial_ecommerce') !== '1')) ? null : array('admin/add', array('_SELF', array('type' => 'add'), '_SELF'), do_lang('ADD_USERGROUP_SUBSCRIPTION')),
                ((get_forum_type() != 'cns') && (get_value('unofficial_ecommerce') !== '1')) ? null : array('admin/edit', array('_SELF', array('type' => 'edit'), '_SELF'), do_lang('EDIT_USERGROUP_SUBSCRIPTION')),
                array('menu/adminzone/setup/ecommerce_products', array('_SELF', array('type' => 'prices'), '_SELF'), do_lang('ECOM_PRODUCTS_MANAGE_INVENTORY')),
            ),
            do_lang('CUSTOM_PRODUCT_USERGROUP')
        );
    }

    /**
     * Get Tempcode for adding/editing form.
     *
     * @param  SHORT_TEXT $title The title
     * @param  LONG_TEXT $description The description
     * @param  REAL $price The price
     * @param  ID_TEXT $tax_code The tax code
     * @param  integer $length The length
     * @param  SHORT_TEXT $length_units The units for the length
     * @set    y m d w
     * @param  BINARY $auto_recur Auto-recur
     * @param  ?GROUP $group_id The usergroup that purchasing gains membership to (null: not set)
     * @param  BINARY $uses_primary Whether this is applied to primary usergroup membership
     * @param  BINARY $enabled Whether this is currently enabled
     * @param  ?LONG_TEXT $mail_start The text of the e-mail to send out when a subscription is start (null: default)
     * @param  ?LONG_TEXT $mail_end The text of the e-mail to send out when a subscription is ended (null: default)
     * @param  ?LONG_TEXT $mail_uhoh The text of the e-mail to send out when a subscription cannot be renewed because the subproduct is gone (null: default)
     * @param  array $mails Other e-mails to send
     * @param  ?AUTO_LINK $id ID of existing subscription (null: new)
     * @return array Tuple: The input fields, The hidden fields, The delete fields
     */
    public function get_form_fields($title = '', $description = '', $price = 9.99, $tax_code = '0%', $length = 12, $length_units = 'm', $auto_recur = 1, $group_id = null, $uses_primary = 0, $enabled = 1, $mail_start = null, $mail_end = null, $mail_uhoh = null, $mails = array(), $id = null)
    {
        if (($title == '') && (get_forum_type() == 'cns')) {
            $add_usergroup_url = build_url(array('page' => 'admin_cns_groups', 'type' => 'add'), get_module_zone('admin_cns_groups'));
            attach_message(do_lang_tempcode('ADD_USER_GROUP_FIRST', escape_html($add_usergroup_url->evaluate())), 'inform', true);
        }

        $hidden = new Tempcode();

        if ($group_id === null) {
            $group_id = get_param_integer('group_id', db_get_first_id() + 3);
        }
        if ($mail_start === null) {
            $mail_start = do_lang('_PAID_SUBSCRIPTION_STARTED', get_option('site_name'));
        }
        if ($mail_end === null) {
            $_purchase_url = build_url(array('page' => 'purchase'), get_module_zone('purchase'), array(), false, false, true);
            $purchase_url = $_purchase_url->evaluate();
            $mail_end = do_lang('_PAID_SUBSCRIPTION_ENDED', get_option('site_name'), $purchase_url);
        }
        if ($mail_uhoh === null) {
            $mail_uhoh = do_lang('_PAID_SUBSCRIPTION_UHOH', get_option('site_name'));
        }

        $fields = new Tempcode();
        $fields->attach(form_input_line(do_lang_tempcode('TITLE'), do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_TITLE'), 'title', $title, true));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('DESCRIPTION'), do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_DESCRIPTION'), 'description', $description, true));
        $fields->attach(form_input_float(do_lang_tempcode('PRICE'), do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_PRICE'), 'price', $price, true));
        $fields->attach(form_input_tax_code(do_lang_tempcode(get_option('tax_system')), do_lang_tempcode('DESCRIPTION_TAX_CODE'), 'tax_code', $tax_code, true));

        $list = new Tempcode();
        foreach (array('d', 'w', 'm', 'y') as $unit) {
            $list->attach(form_input_list_entry($unit, $unit == $length_units, do_lang_tempcode('LENGTH_UNIT_' . $unit)));
        }
        $fields->attach(form_input_list(do_lang_tempcode('LENGTH_UNITS'), do_lang_tempcode('DESCRIPTION_LENGTH_UNITS'), 'length_units', $list));
        $fields->attach(form_input_integer(do_lang_tempcode('SUBSCRIPTION_LENGTH'), do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_LENGTH'), 'length', $length, true));
        if (cron_installed()) {
            $fields->attach(form_input_tick(do_lang_tempcode('AUTO_RECUR'), do_lang_tempcode('DESCRIPTION_AUTO_RECUR'), 'auto_recur', $auto_recur == 1));
        } else {
            $hidden->attach(form_input_hidden('auto_recur', '1'));
        }

        $list = new Tempcode();
        $groups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list();
        if (get_forum_type() == 'cns') {
            require_code('cns_groups');
            $default_groups = cns_get_all_default_groups(true, true);
        }
        foreach ($groups as $id => $group) {
            if (get_forum_type() == 'cns') {
                if ((in_array($id, $default_groups)) && ($id !== $group_id)) {
                    continue;
                }
            }

            if ($id != $GLOBALS['FORUM_DRIVER']->get_guest_id()) {
                $list->attach(form_input_list_entry(strval($id), $id == $group_id, $group));
            }
        }
        $fields->attach(form_input_list(do_lang_tempcode('USERGROUP'), do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_GROUP'), 'group_id', $list));

        $fields->attach(form_input_tick(do_lang_tempcode('USES_PRIMARY'), do_lang_tempcode('DESCRIPTION_USES_PRIMARY'), 'uses_primary', $uses_primary == 1));

        $fields->attach(form_input_tick(do_lang_tempcode('ENABLED'), do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_ENABLED'), 'enabled', $enabled == 1));

        $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => 'a03ec5b2afe5be764bd10694fc401fex', 'TITLE' => do_lang_tempcode('SUBSCRIPTION_EVENT_EMAILS'))));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('MAIL_START'), do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_MAIL_START'), 'mail_start', $mail_start, true, null, true));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('MAIL_END'), do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_MAIL_END'), 'mail_end', $mail_end, true, null, true));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('MAIL_UHOH'), do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_MAIL_UHOH'), 'mail_uhoh', $mail_uhoh, false, null, true));

        // Extra mails
        if (get_forum_type() == 'cns') {
            for ($i = 0; $i < count($mails) + 3/*Allow adding 3 on each edit*/; $i++) {
                $subject = isset($mails[$i]) ? $mails[$i]['subject'] : '';
                $body = isset($mails[$i]) ? $mails[$i]['body'] : '';
                $ref_point = isset($mails[$i]) ? $mails[$i]['ref_point'] : 'start';
                $ref_point_offset = isset($mails[$i]) ? $mails[$i]['ref_point_offset'] : 0;

                $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '18f5d62292d76cc0364463fb6de1faa3', 'TITLE' => do_lang_tempcode('EXTRA_SUBSCRIPTION_MAIL', escape_html(integer_format($i + 1))), 'SECTION_HIDDEN' => ($subject == ''))));
                $fields->attach(form_input_line_comcode(do_lang_tempcode('SUBJECT'), do_lang_tempcode('DESCRIPTION_SUBSCRIPTION_SUBJECT'), 'subject_' . strval($i), $subject, false));
                $fields->attach(form_input_text_comcode(do_lang_tempcode('BODY'), do_lang_tempcode('DESCRIPTION_SUBSCRIPTION_BODY'), 'body_' . strval($i), $body, false, null, true));
                $radios = new Tempcode();
                foreach (array('start', 'term_start', 'term_end', 'expiry') as $ref_point_type) {
                    $radios->attach(form_input_radio_entry('ref_point_' . strval($i), $ref_point_type, $ref_point == $ref_point_type, do_lang_tempcode('_SUBSCRIPTION_' . strtoupper($ref_point_type) . '_TIME')));
                }
                $fields->attach(form_input_radio(do_lang_tempcode('SUBSCRIPTION_REF_POINT'), do_lang_tempcode('DESCRIPTION_SUBSCRIPTION_REF_POINT'), 'ref_point_' . strval($i), $radios, true));
                $fields->attach(form_input_integer(do_lang_tempcode('SUBSCRIPTION_REF_POINT_OFFSET'), do_lang_tempcode('DESCRIPTION_SUBSCRIPTION_REF_POINT_OFFSET'), 'ref_point_offset_' . strval($i), $ref_point_offset, true));
            }
        }

        $delete_fields = null;
        if ($GLOBALS['SITE_DB']->query_select_value('ecom_subscriptions', 'COUNT(*)', array('s_type_code' => 'USERGROUP' . strval($id))) > 0) {
            $delete_fields = new Tempcode();
            $delete_fields->attach(form_input_tick(do_lang_tempcode('DELETE'), do_lang_tempcode('DESCRIPTION_DELETE_USERGROUP_SUB_DANGER'), 'delete', false));
        }

        return array($fields, $hidden, $delete_fields, null, $delete_fields !== null);
    }

    /**
     * Standard crud_module table function.
     *
     * @param  array $url_map Details to go to build_url for link to the next screen
     * @return array A pair: The choose table, Whether re-ordering is supported from this screen
     */
    public function create_selection_list_choose_table($url_map)
    {
        require_code('templates_results_table');

        $db = get_db_for('f_usergroup_subs');

        $current_ordering = get_param_string('sort', 's_title ASC', INPUT_FILTER_GET_COMPLEX);
        if (strpos($current_ordering, ' ') === false) {
            warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }
        list($sortable, $sort_order) = explode(' ', $current_ordering, 2);
        $sortables = array(
            's_title' => do_lang_tempcode('TITLE'),
            's_price' => do_lang_tempcode('PRICE'),
            's_length' => do_lang_tempcode('SUBSCRIPTION_LENGTH'),
            's_group_id' => do_lang_tempcode('USERGROUP'),
            's_enabled' => do_lang('ENABLED'),
        );
        if (((strtoupper($sort_order) != 'ASC') && (strtoupper($sort_order) != 'DESC')) || (!array_key_exists($sortable, $sortables))) {
            log_hack_attack_and_exit('ORDERBY_HACK');
        }

        $header_row = results_header_row(array(
            do_lang_tempcode('TITLE'),
            do_lang_tempcode('PRICE'),
            do_lang_tempcode('SUBSCRIPTION_LENGTH'),
            do_lang_tempcode('USERGROUP'),
            do_lang('ENABLED'),
            do_lang_tempcode('ACTIONS'),
        ), $sortables, 'sort', $sortable . ' ' . $sort_order);

        $result_entries = new Tempcode();

        list($rows, $max_rows) = $this->get_entry_rows(false, $current_ordering, null, get_forum_type() != 'cns');
        foreach ($rows as $r) {
            $edit_url = build_url($url_map + array('id' => $r['id']), '_SELF');

            $result_entries->attach(results_entry(array(get_translated_text($r['s_title'], $db), $r['s_price'], do_lang_tempcode('_LENGTH_UNIT_' . $r['s_length_units'], integer_format($r['s_length'])), cns_get_group_name($r['s_group_id']), ($r['s_enabled'] == 1) ? do_lang_tempcode('YES') : do_lang_tempcode('NO'), protect_from_escaping(hyperlink($edit_url, do_lang_tempcode('EDIT'), false, false, '#' . strval($r['id'])))), true));
        }

        return array(results_table(do_lang($this->menu_label), get_param_integer('start', 0), 'start', either_param_integer('max', 20), 'max', $max_rows, $header_row, $result_entries, $sortables, $sortable, $sort_order), false);
    }

    /**
     * Standard crud_module list function.
     *
     * @return Tempcode The selection list
     */
    public function create_selection_list_entries()
    {
        $db = get_db_for('f_usergroup_subs');

        $_m = $db->query_select('f_usergroup_subs', array('*'));
        $entries = new Tempcode();
        foreach ($_m as $m) {
            $entries->attach(form_input_list_entry(strval($m['id']), false, get_translated_text($m['s_title'], $db)));
        }

        return $entries;
    }

    /**
     * Standard crud_module edit form filler.
     *
     * @param  ID_TEXT $id The entry being edited
     * @return array Tuple: The input fields, The hidden fields, The delete fields
     */
    public function fill_in_edit_form($id)
    {
        $db = get_db_for('f_usergroup_subs');

        $m = $db->query_select('f_usergroup_subs', array('*'), array('id' => intval($id)), '', 1);
        if (!array_key_exists(0, $m)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $r = $m[0];

        $_mails = $db->query_select('f_usergroup_sub_mails', array('*'), array('m_usergroup_sub_id' => intval($id)), 'ORDER BY ' . $GLOBALS['SITE_DB']->translate_field_ref('m_subject'));
        $mails = array();
        foreach ($_mails as $_mail) {
            $mails[] = array(
                'subject' => get_translated_text($_mail['m_subject'], $db),
                'body' => get_translated_text($_mail['m_body'], $db),
                'ref_point' => $_mail['m_ref_point'],
                'ref_point_offset' => $_mail['m_ref_point_offset'],
            );
        }

        $fields = $this->get_form_fields(
            get_translated_text($r['s_title'], $db),
            get_translated_text($r['s_description'], $db),
            $r['s_price'],
            $r['s_tax_code'],
            $r['s_length'],
            $r['s_length_units'],
            $r['s_auto_recur'],
            $r['s_group_id'],
            $r['s_uses_primary'],
            $r['s_enabled'],
            get_translated_text($r['s_mail_start'], $db),
            get_translated_text($r['s_mail_end'], $db),
            get_translated_text($r['s_mail_uhoh'], $db),
            $mails,
            $id
        );

        return $fields;
    }

    /**
     * Get a mapping of extra mails for the usergroup subscription.
     *
     * @return array Extra mails
     */
    public function _mails()
    {
        $mails = array();
        foreach (array_keys($_POST) as $key) {
            $matches = array();
            if (preg_match('#^subject_(\d+)$#', $key, $matches) != 0) {
                $subject = post_param_string('subject_' . $matches[1], '');
                $body = post_param_string('body_' . $matches[1], '');
                $ref_point = post_param_string('ref_point_' . $matches[1]);
                $ref_point_offset = post_param_integer('ref_point_offset_' . $matches[1]);
                if (($ref_point_offset < 0) && ($ref_point != 'expiry')) {
                    $ref_point_offset = 0;
                    attach_message(do_lang_tempcode('SUBSCRIPTION_REF_POINT_OFFSET_NEGATIVE_ERROR'), 'warn');
                }
                if ($subject != '' && $body != '') {
                    $mails[] = array(
                        'subject' => $subject,
                        'body' => $body,
                        'ref_point' => $ref_point,
                        'ref_point_offset' => $ref_point_offset,
                    );
                }
            }
        }
        return $mails;
    }

    /**
     * Standard crud_module add actualiser.
     *
     * @return array A pair: The entry added, Description about usage
     */
    public function add_actualisation()
    {
        if (has_actual_page_access(get_member(), 'admin_config')) {
            $_config_url = build_url(array('page' => 'admin_config', 'type' => 'category', 'id' => 'ECOMMERCE'), get_module_zone('admin_config'));
            $config_url = $_config_url->evaluate();
            $config_url .= '#group_ECOMMERCE';

            $text = paragraph(do_lang_tempcode('ECOM_ADDED_SUBSCRIP', escape_html($config_url)));
        } else {
            $text = null;
        }

        $title = post_param_string('title');

        $mails = $this->_mails();

        $id = add_usergroup_subscription($title, post_param_string('description'), float_unformat(post_param_string('price')), post_param_tax_code('tax_code'), post_param_integer('length'), post_param_string('length_units'), post_param_integer('auto_recur', 0), post_param_integer('group_id'), post_param_integer('uses_primary', 0), post_param_integer('enabled', 0), post_param_string('mail_start'), post_param_string('mail_end'), post_param_string('mail_uhoh'), $mails);
        return array(strval($id), $text);
    }

    /**
     * Standard crud_module edit actualiser.
     *
     * @param  ID_TEXT $id The entry being edited
     */
    public function edit_actualisation($id)
    {
        $title = post_param_string('title');

        $mails = $this->_mails();

        edit_usergroup_subscription(intval($id), $title, post_param_string('description'), float_unformat(post_param_string('price')), post_param_tax_code('tax_code'), post_param_integer('length'), post_param_string('length_units'), post_param_integer('auto_recur', 0), post_param_integer('group_id'), post_param_integer('uses_primary', 0), post_param_integer('enabled', 0), post_param_string('mail_start'), post_param_string('mail_end'), post_param_string('mail_uhoh'), $mails);
    }

    /**
     * Standard crud_module delete actualiser.
     *
     * @param  ID_TEXT $id The entry being deleted
     */
    public function delete_actualisation($id)
    {
        $uhoh_mail = post_param_string('mail_uhoh');

        delete_usergroup_subscription(intval($id), $uhoh_mail);
    }

    /**
     * The UI to set eCommerce product prices.
     *
     * @return Tempcode The UI
     */
    public function prices()
    {
        require_code('input_filter_2');
        rescue_shortened_post_request();
        if (get_value('disable_modsecurity_workaround') !== '1') {
            modsecurity_workaround_enable();
        }

        $field_groups = new Tempcode();
        $add_forms = new Tempcode();

        // Load up configuration from hooks
        $_hooks = find_all_hook_obs('systems', 'ecommerce', 'Hook_ecommerce_');
        foreach ($_hooks as $hook => $object) {
            if (method_exists($object, 'config')) {
                $fgs = $object->config();
                foreach ($fgs as $fg) {
                    foreach ($fg[0] as $__fg) {
                        $_fg = do_template('FORM_GROUP', array('_GUID' => '58a0948313f0e8e69c06ee01fb7ee48a', 'FIELDS' => $__fg[0], 'HIDDEN' => $__fg[1]));
                        $field_groups->attach(do_template('ECOM_PRODUCTS_PRICES_FORM_WRAP', array('_GUID' => '938143162b418de982cdb6ce8d8a92ee', 'TITLE' => $__fg[2], 'FORM' => $_fg)));
                    }
                    if (!$fg[2]->is_empty()) {
                        $submit_name = do_lang_tempcode('ADD');

                        $post_url = build_url(array('page' => '_SELF', 'type' => '_prices'), '_SELF');

                        $fg[2] = do_template('FORM', array(
                            '_GUID' => 'e98141bc0a2a54abcca59a5c947a6738',
                            'SECONDARY_FORM' => true,
                            'TABINDEX' => strval(get_form_field_tabindex(null)),
                            'HIDDEN' => '',
                            'TEXT' => $fg[3],
                            'FIELDS' => $fg[2],
                            'SUBMIT_BUTTON_CLASS' => 'proceed-button-left',
                            'SUBMIT_ICON' => 'admin--add',
                            'SUBMIT_NAME' => $submit_name,
                            'URL' => $post_url,
                            'SUPPORT_AUTOSAVE' => true,
                        ));
                        $add_forms->attach(do_template('ECOM_PRODUCTS_PRICES_FORM_WRAP', array('_GUID' => '3956550ebff14bbb923b57c8341b0862', 'TITLE' => $fg[1], 'FORM' => $fg[2])));
                    }
                }
            }
        }

        $submit_name = do_lang_tempcode('SAVE_ALL');

        $post_url = build_url(array('page' => '_SELF', 'type' => '_prices'), '_SELF');

        if ($field_groups->is_empty()) {
            $edit_form = new Tempcode();
        } else {
            $edit_form = do_template('FORM_GROUPED', array(
                '_GUID' => 'bf025026dcfc86cfd0a8ef3728bbf6d8',
                'TEXT' => '',
                'FIELD_GROUPS' => $field_groups,
                'SUBMIT_ICON' => 'buttons--save',
                'SUBMIT_NAME' => $submit_name,
                'SUBMIT_BUTTON_CLASS' => 'proceed-button-left-2',
                'URL' => $post_url,
                'SUPPORT_AUTOSAVE' => true,
                'MODSECURITY_WORKAROUND' => true,
            ));
        }

        list($warning_details, $ping_url) = handle_conflict_resolution();

        return do_template('ECOM_PRODUCT_PRICE_SCREEN', array(
            '_GUID' => '278c8244c7f1743370198dfc437b7bbf',
            'PING_URL' => $ping_url,
            'WARNING_DETAILS' => $warning_details,
            'TITLE' => $this->title,
            'EDIT_FORM' => $edit_form,
            'ADD_FORMS' => $add_forms,
        ));
    }

    /**
     * The actualiser to set eCommerce product prices.
     *
     * @return Tempcode The UI
     */
    public function _prices()
    {
        require_code('input_filter_2');
        if (get_value('disable_modsecurity_workaround') !== '1') {
            modsecurity_workaround_enable();
        }

        // Save configuration for hooks
        $_hooks = find_all_hook_obs('systems', 'ecommerce', 'Hook_ecommerce_');
        foreach ($_hooks as $hook => $object) {
            if (method_exists($object, 'save_config')) {
                $object->save_config();
            }
        }

        log_it('ECOM_PRODUCT_CHANGED_PRICES');

        // Show it worked / Refresh
        $url = build_url(array('page' => '_SELF', 'type' => 'prices'), '_SELF');
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }
}
