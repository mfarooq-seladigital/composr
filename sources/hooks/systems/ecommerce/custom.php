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
 * @package    ecommerce
 */

/**
 * Hook class.
 */
class Hook_ecommerce_custom
{
    /**
     * Standard eCommerce product configuration function.
     *
     * @return ?array A tuple: list of [fields to shown, hidden fields], title for add form, add form (null: disabled)
     */
    public function config()
    {
        $rows = $GLOBALS['SITE_DB']->query_select('ecom_prods_custom', array('*'), null, 'ORDER BY id');
        $out = array();
        foreach ($rows as $i => $row) {
            $fields = new Tempcode();
            $hidden = new Tempcode();
            $fields->attach($this->_get_fields('_' . strval($i), get_translated_text($row['c_title']), get_translated_text($row['c_description']), $row['c_enabled'], $row['c_price'], $row['c_tax'], $row['c_shipping_cost'], $row['c_price_points'], $row['c_one_per_member'], get_translated_text($row['c_mail_subject']), get_translated_text($row['c_mail_body'])));
            $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '01362c21b40d7905b76ee6134198a128', 'TITLE' => do_lang_tempcode('ACTIONS'))));
            $fields->attach(form_input_tick(do_lang_tempcode('DELETE'), do_lang_tempcode('DESCRIPTION_DELETE'), 'delete_custom_' . strval($i), false));
            $hidden->attach(form_input_hidden('custom_' . strval($i), strval($row['id'])));
            $out[] = array($fields, $hidden, do_lang_tempcode('_EDIT_CUSTOM_PRODUCT', escape_html(get_translated_text($row['c_title']))));
        }

        return array(
            array($out, do_lang_tempcode('ADD_NEW_CUSTOM_PRODUCT'), $this->_get_fields(), do_lang_tempcode('CUSTOM_PRODUCT_DESCRIPTION')),
        );
    }

    /**
     * Get fields for adding/editing one of these.
     *
     * @param  string $name_suffix What to place onto the end of the field name
     * @param  SHORT_TEXT $title Title
     * @param  LONG_TEXT $description Description
     * @param  BINARY $enabled Whether it is enabled
     * @param  ?REAL $price The price (null: not set)
     * @param  REAL $tax The tax
     * @param  REAL $shipping_cost The shipping_cost
     * @param  ?integer $price_points The price in points (null: not set)
     * @param  BINARY $one_per_member Whether it is restricted to one per member
     * @param  SHORT_TEXT $mail_subject Confirmation mail subject
     * @param  LONG_TEXT $mail_body Confirmation mail body
     * @return Tempcode The fields
     */
    protected function _get_fields($name_suffix = '', $title = '', $description = '', $enabled = 1, $price = null, $tax = 0.00, $shipping_cost = 0.00, $price_points = null, $one_per_member = 0, $mail_subject = '', $mail_body = '')
    {
        require_lang('points');

        $fields = new Tempcode();

        $fields->attach(form_input_line(do_lang_tempcode('TITLE'), do_lang_tempcode('DESCRIPTION_TITLE'), 'custom_title' . $name_suffix, $title, true));
        $fields->attach(form_input_text(do_lang_tempcode('DESCRIPTION'), do_lang_tempcode('DESCRIPTION_DESCRIPTION'), 'custom_description' . $name_suffix, $description, true));
        $fields->attach(form_input_float(do_lang_tempcode('PRICE'), do_lang_tempcode('DESCRIPTION_PRICE'), 'custom_price' . $name_suffix, $price, false));
        $fields->attach(form_input_float(do_lang_tempcode(get_option('tax_system')), do_lang_tempcode('DESCRIPTION_TAX_INCLUDING_SHIPPING_COST_TAX'), 'custom_tax' . $name_suffix, $tax, true));
        $fields->attach(form_input_float(do_lang_tempcode('SHIPPING_COST'), do_lang_tempcode('DESCRIPTION_SHIPPING_COST'), 'custom_shipping_cost' . $name_suffix, $shipping_cost, true));
        if (addon_installed('points')) {
            $fields->attach(form_input_integer(do_lang_tempcode('PRICE_POINTS'), do_lang_tempcode('DESCRIPTION_PRICE_POINTS'), 'custom_price_points' . $name_suffix, $price_points, false));
        }
        $fields->attach(form_input_tick(do_lang_tempcode('ONE_PER_MEMBER'), do_lang_tempcode('DESCRIPTION_ONE_PER_MEMBER'), 'custom_one_per_member' . $name_suffix, $one_per_member == 1));
        $fields->attach(form_input_tick(do_lang_tempcode('ENABLED'), '', 'custom_enabled' . $name_suffix, $enabled == 1));

        $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '6e4f9d4f6fc7ba05336681c5311bc42f', 'SECTION_HIDDEN' => false, 'TITLE' => do_lang_tempcode('PURCHASE_MAIL'), 'HELP' => do_lang_tempcode('DESCRIPTION_PURCHASE_MAIL'))));
        $fields->attach(form_input_line(do_lang_tempcode('PURCHASE_MAIL_SUBJECT'), '', 'custom_mail_subject' . $name_suffix, $mail_subject, false));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('PURCHASE_MAIL_BODY'), '', 'custom_mail_body' . $name_suffix, $mail_body, false));

        return $fields;
    }

    /**
     * Standard eCommerce product configuration save function.
     */
    public function save_config()
    {
        $i = 0;
        $rows = list_to_map('id', $GLOBALS['SITE_DB']->query_select('ecom_prods_custom', array('*')));
        while (array_key_exists('custom_' . strval($i), $_POST)) {
            $id = post_param_integer('custom_' . strval($i));
            $title = post_param_string('custom_title_' . strval($i));
            $description = post_param_string('custom_description_' . strval($i));
            $enabled = post_param_integer('custom_enabled_' . strval($i), 0);
            $_price = post_param_string('custom_price_' . strval($i), '');
            $price = ($_price == '') ? null : float_unformat($_price);
            $_tax = post_param_string('custom_tax_' . strval($i));
            $tax = float_unformat($_tax);
            $_shipping_cost = post_param_string('custom_shipping_cost_' . strval($i));
            $shipping_cost = float_unformat($_shipping_cost);
            if (addon_installed('points')) {
                $price_points = post_param_integer('custom_price_points_' . strval($i), null);
            } else {
                $price_points = null;
            }
            $one_per_member = post_param_integer('custom_one_per_member_' . strval($i), 0);
            $mail_subject = post_param_string('custom_mail_subject_' . strval($i));
            $mail_body = post_param_string('custom_mail_body_' . strval($i));

            $delete = post_param_integer('delete_custom_' . strval($i), 0);

            $_title = $rows[$id]['c_title'];
            $_description = $rows[$id]['c_description'];
            $_mail_subject = $rows[$id]['c_mail_subject'];
            $_mail_body = $rows[$id]['c_mail_body'];

            if ($delete == 1) {
                delete_lang($_title);
                delete_lang($_description);
                delete_lang($_mail_subject);
                delete_lang($_mail_body);
                $GLOBALS['SITE_DB']->query_delete('ecom_prods_custom', array('id' => $id), '', 1);
            } else {
                $map = array(
                    'c_enabled' => $enabled,
                    'c_price' => $price,
                    'c_tax' => $tax,
                    'c_shipping_cost' => $shipping_cost,
                    'c_price_points' => $price_points,
                    'c_one_per_member' => $one_per_member,
                );
                $map += lang_remap('c_title', $_title, $title);
                $map += lang_remap_comcode('c_description', $_description, $description);
                $map += lang_remap('c_mail_subject', $_mail_subject, $mail_subject);
                $map += lang_remap('c_mail_body', $_mail_body, $mail_body);
                $GLOBALS['SITE_DB']->query_update('ecom_prods_custom', $map, array('id' => $id), '', 1);
            }
            $i++;
        }

        $title = post_param_string('custom_title', null);
        if ($title !== null) {
            $description = post_param_string('custom_description');
            $enabled = post_param_integer('custom_enabled', 0);
            $_price = post_param_string('custom_price', '');
            $price = ($_price == '') ? null : float_unformat($_price);
            if (addon_installed('points')) {
                $price_points = post_param_integer('custom_price_points', null);
            } else {
                $price_points = null;
            }
            $one_per_member = post_param_integer('custom_one_per_member', 0);
            $mail_subject = post_param_string('custom_mail_subject');
            $mail_body = post_param_string('custom_mail_body');

            $map = array(
                'c_enabled' => $enabled,
                'c_price' => $price,
                'c_tax' => $tax,
                'c_shipping_cost' => $shipping_cost,
                'c_price_points' => $price_points,
                'c_one_per_member' => $one_per_member,
            );
            $map += insert_lang('c_title', $title, 2);
            $map += insert_lang_comcode('c_description', $description, 2);
            $map += insert_lang('c_mail_subject', $mail_subject, 2);
            $map += insert_lang('c_mail_body', $mail_body, 2);
            $GLOBALS['SITE_DB']->query_insert('ecom_prods_custom', $map);
        }

        log_it('ECOM_PRODUCTS_AMEND_CUSTOM_PRODUCTS');
    }

    /**
     * Get the products handled by this eCommerce hook.
     *
     * IMPORTANT NOTE TO PROGRAMMERS: This function may depend only on the database, and not on get_member() or any GET/POST values.
     *  Such dependencies will break IPN, which works via a Guest and no dependable environment variables. It would also break manual transactions from the Admin Zone.
     *
     * @param  ?ID_TEXT $search Product being searched for (null: none).
     * @return array A map of product name to list of product details.
     */
    public function get_products($search = null)
    {
        $products = array();

        $rows = $GLOBALS['SITE_DB']->query_select('ecom_prods_custom', array('*'), array('c_enabled' => 1));

        foreach ($rows as $i => $row) {
            $rows[$i]['_title'] = get_translated_text($row['c_title']);
        }
        sort_maps_by($rows, '_title');

        foreach ($rows as $i => $row) {
            $just_row = db_map_restrict($row, array('id', 'c_description'));
            $description = get_translated_tempcode('ecom_prods_custom', $just_row, 'c_description');
            if (strpos($description->evaluate(), '<img') === false) {
                $image_url = find_theme_image('icons/48x48/menu/_generic_spare/' . strval(($i % 8) + 1));
            } else {
                $image_url = '';
            }

            $shipping_cost = $row['shipping_cost'];

            $products['CUSTOM_' . strval($row['id'])] = automatic_discount_calculation(array(
                'item_name' => $row['_title'],
                'item_description' => $description,
                'item_image_url' => $image_url,

                'type' => PRODUCT_PURCHASE,
                'type_special_details' => array(),

                'price' => $row['c_price'],
                'currency' => get_option('currency'),
                'price_points' => addon_installed('points') ? $row['c_price_points'] : null,
                'discount_points__num_points' => null,
                'discount_points__price_reduction' => null,

                'tax' => $row['tax'],
                'shipping_cost' => $shipping_cost,
                'needs_shipping_address' => ($shipping_cost != 0.00),
            ));
        }

        return $products;
    }

    /**
     * Check whether the product codename is available for purchase by the member.
     *
     * @param  ID_TEXT $type_code The product codename.
     * @param  MEMBER $member_id The member we are checking against.
     * @param  integer $req_quantity The number required.
     * @param  boolean $must_be_listed Whether the product must be available for public listing.
     * @return integer The availability code (a ECOMMERCE_PRODUCT_* constant).
     */
    public function is_available($type_code, $member_id, $req_quantity = 1, $must_be_listed = false)
    {
        if (is_guest($member_id)) {
            return ECOMMERCE_PRODUCT_NO_GUESTS;
        }

        $custom_product_id = intval(preg_replace('#^CUSTOM\_#', '', $type_code));
        $rows = $GLOBALS['SITE_DB']->query_select('ecom_prods_custom', array('*'), array('id' => $custom_product_id, 'c_enabled' => 1));
        if (!array_key_exists(0, $rows)) {
            return ECOMMERCE_PRODUCT_MISSING;
        }
        $row = $rows[0];

        if ($row['c_one_per_member'] == 1) {
            // Test to see if it's been purchased
            $test = $GLOBALS['SITE_DB']->query_select_value_if_there('ecom_sales s JOIN ' . get_table_prefix() . 'ecom_transactions t ON t.id=s.txn_id', 'id', array('details2' => strval($rows[0]['id']), 'member_id' => $member_id, 't_type_code' => $type_code));
            if ($test !== null) {
                return ECOMMERCE_PRODUCT_ALREADY_HAS;
            }
        }

        return ECOMMERCE_PRODUCT_AVAILABLE;
    }

    /**
     * Get fields that need to be filled in in the purchasing module.
     *
     * @param  ID_TEXT $type_code The product codename.
     * @return ?array A triple: The fields (null: none), The text (null: none), The JavaScript (null: none).
     */
    public function get_needed_fields($type_code)
    {
        $fields = mixed();
        ecommerce_attach_memo_field_if_needed($fields);

        return array(null, null, null);
    }

    /**
     * Handling of a product purchase change state.
     *
     * @param  ID_TEXT $type_code The product codename.
     * @param  ID_TEXT $purchase_id The purchase ID.
     * @param  array $details Details of the product, with added keys: TXN_ID, STATUS, ORDER_STATUS.
     * @return boolean Whether the product was automatically dispatched (if not then hopefully this function sent a staff notification).
     */
    public function actualiser($type_code, $purchase_id, $details)
    {
        if ($details['STATUS'] != 'Completed') {
            return false;
        }

        $custom_product_id = intval(preg_replace('#^CUSTOM\_#', '', $type_code));

        $member_id = intval($purchase_id);

        $rows = $GLOBALS['SITE_DB']->query_select('ecom_prods_custom', array('*'), array('id' => $custom_product_id, 'c_enabled' => 1), '', 1);
        if (!array_key_exists(0, $rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $row = $rows[0];

        $c_title = get_translated_text($row['c_title']);

        $sale_id = $GLOBALS['SITE_DB']->query_insert('ecom_sales', array('date_and_time' => time(), 'member_id' => $member_id, 'details' => $c_title, 'details2' => strval($row['id']), 'txn_id' => $details['TXN_ID']), true);

        // Notification to staff
        require_code('notifications');
        $subject = do_lang('MAIL_REQUEST_CUSTOM', comcode_escape($c_title), null, null, get_site_default_lang());
        $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id);
        $body = do_notification_lang('MAIL_REQUEST_CUSTOM_BODY', comcode_escape($c_title), $username, null, get_site_default_lang());
        dispatch_notification('ecom_product_request_custom', 'custom' . strval($custom_product_id) . '_' . strval($sale_id), $subject, $body, null, null, 3, true, false, null, null, '', '', '', '', null, true);

        // E-mail member (we don't do a notification as we want to know for sure it will be received; plus avoid bloat in the notification UI)
        require_code('mail');
        $subject_line = get_translated_text($row['c_mail_subject']);
        if ($subject_line != '') {
            $message_raw = get_translated_text($row['c_mail_body']);
            $email = $GLOBALS['FORUM_DRIVER']->get_member_email_address($member_id);
            $to_name = $GLOBALS['FORUM_DRIVER']->get_username($member_id, true);
            mail_wrap($subject_line, $message_raw, array($email), $to_name, '', '', 3, null, false, null, true);
        }

        return false;
    }

    /**
     * Get the member who made the purchase.
     *
     * @param  ID_TEXT $type_code The product codename.
     * @param  ID_TEXT $purchase_id The purchase ID.
     * @return ?MEMBER The member ID (null: none).
     */
    public function member_for($type_code, $purchase_id)
    {
        return intval($purchase_id);
    }
}
