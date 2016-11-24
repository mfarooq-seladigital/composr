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
class Hook_secpay
{
    /**
     * Get the gateway username.
     *
     * @return string The answer.
     */
    protected function _get_username()
    {
        return ecommerce_test_mode() ? get_option('ipn_test') : get_option('ipn');
    }

    /**
     * Get the remote form URL.
     *
     * @return URLPATH The remote form URL.
     */
    protected function _get_remote_form_url()
    {
        return 'https://www.secpay.com/java-bin/ValCard';
    }

    /**
     * Generate a transaction ID.
     *
     * @return string A transaction ID.
     */
    public function generate_trans_id()
    {
        require_code('crypt');
        return get_rand_password();
    }

    /**
     * Make a transaction (payment) button.
     *
     * @param  ID_TEXT $type_code The product codename.
     * @param  SHORT_TEXT $item_name The human-readable product title.
     * @param  ID_TEXT $purchase_id The purchase ID.
     * @param  float $amount A transaction amount.
     * @param  ID_TEXT $currency The currency to use.
     * @return Tempcode The button.
     */
    public function make_transaction_button($type_code, $item_name, $purchase_id, $amount, $currency)
    {
        $username = $this->_get_username();
        $ipn_url = $this->_get_remote_form_url();
        $trans_id = $this->generate_trans_id();
        $digest = md5($trans_id . float_to_raw_string($amount) . get_option('ipn_password'));

        $GLOBALS['SITE_DB']->query_insert('trans_expecting', array(
            'id' => $trans_id,
            'e_type_code' => $type_code,
            'e_purchase_id' => $purchase_id,
            'e_item_name' => $item_name,
            'e_member_id' => get_member(),
            'e_amount' => float_to_raw_string($amount),
            'e_currency' => $currency,
            'e_ip_address' => get_ip_address(),
            'e_session_id' => get_session_id(),
            'e_time' => time(),
            'e_length' => null,
            'e_length_units' => '',
        ));

        return do_template('ECOM_BUTTON_VIA_SECPAY', array(
            '_GUID' => 'e68e80cb637f8448ef62cd7d73927722',
            'TYPE_CODE' => $type_code,
            'DIGEST' => $digest,
            'TEST' => ecommerce_test_mode(),
            'TRANS_ID' => $trans_id,
            'ITEM_NAME' => $item_name,
            'PURCHASE_ID' => strval($purchase_id),
            'AMOUNT' => float_to_raw_string($amount),
            'CURRENCY' => $currency,
            'USERNAME' => $username,
            'IPN_URL' => $ipn_url,
        ));
    }

    /**
     * Find details for a subscription in secpay format.
     *
     * @param  integer $length The subscription length in the units.
     * @param  ID_TEXT $length_units The length units.
     * @set    d w m y
     * @return array A tuple: the period in secpay units, the date of the first repeat.
     */
    protected function _translate_subscription_details($length, $length_units)
    {
        switch ($length_units) {
            case 'd':
                $length_units_2 = 'daily';
                $single_length = 60 * 60 * 24;
                break;
            case 'w':
                $length_units_2 = 'weekly';
                $single_length = 60 * 60 * 24 * 7;
                break;
            case 'm':
                $length_units_2 = 'monthly';
                $single_length = 60 * 60 * 24 * 31;
                break;
            case 'y':
                $length_units_2 = 'yearly';
                $single_length = 60 * 60 * 24 * 365;
                break;
        }
        if (($length_units == 'm') && ($length == 3)) {
            $length_units_2 = 'quarterly';
            $single_length = 60 * 60 * 24 * 92;
        }
        $first_repeat = date('Ymd', time() + $single_length);

        return array($length_units_2, $first_repeat);
    }

    /**
     * Make a subscription (payment) button.
     *
     * @param  ID_TEXT $type_code The product codename.
     * @param  SHORT_TEXT $item_name The human-readable product title.
     * @param  ID_TEXT $purchase_id The purchase ID.
     * @param  float $amount A transaction amount.
     * @param  integer $length The subscription length in the units.
     * @param  ID_TEXT $length_units The length units.
     * @set    d w m y
     * @param  ID_TEXT $currency The currency to use.
     * @return Tempcode The button.
     */
    public function make_subscription_button($type_code, $item_name, $purchase_id, $amount, $length, $length_units, $currency)
    {
        $username = $this->_get_username();
        $ipn_url = $this->_get_remote_form_url();
        $trans_id = $this->generate_trans_id();
        $digest = md5($trans_id . float_to_raw_string($amount) . get_option('ipn_password'));
        list($length_units_2, $first_repeat) = $this->_translate_subscription_details($length, $length_units);

        $GLOBALS['SITE_DB']->query_insert('trans_expecting', array(
            'id' => $trans_id,
            'e_type_code' => $type_code,
            'e_purchase_id' => $purchase_id,
            'e_item_name' => $item_name,
            'e_member_id' => get_member(),
            'e_amount' => float_to_raw_string($amount),
            'e_currency' => $currency,
            'e_ip_address' => get_ip_address(),
            'e_session_id' => get_session_id(),
            'e_time' => time(),
            'e_length' => $length,
            'e_length_units' => $length_units,
        ));

        return do_template('ECOM_SUBSCRIPTION_BUTTON_VIA_SECPAY', array(
            '_GUID' => 'e5e6d6835ee6da1a6cf02ff8c2476aa6',
            'TYPE_CODE' => $type_code,
            'DIGEST' => $digest,
            'TEST' => ecommerce_test_mode(),
            'TRANS_ID' => $trans_id,
            'FIRST_REPEAT' => $first_repeat,
            'LENGTH' => strval($length),
            'LENGTH_UNITS_2' => $length_units_2,
            'ITEM_NAME' => $item_name,
            'PURCHASE_ID' => strval($purchase_id),
            'AMOUNT' => float_to_raw_string($amount),
            'CURRENCY' => $currency,
            'USERNAME' => $username,
            'IPN_URL' => $ipn_url,
        ));
    }

    /**
     * Make a subscription cancellation button.
     *
     * @param  ID_TEXT $purchase_id The purchase ID.
     * @return Tempcode The button.
     */
    public function make_cancel_button($purchase_id)
    {
        $cancel_url = build_url(array('page' => 'subscriptions', 'type' => 'cancel', 'id' => $purchase_id), get_module_zone('subscriptions'));
        return do_template('ECOM_CANCEL_BUTTON_VIA_SECPAY', array('_GUID' => 'bd02018c985e2345be71eed537b2f841', 'CANCEL_URL' => $cancel_url, 'PURCHASE_ID' => $purchase_id));
    }

    /**
     * Find whether the hook auto-cancels (if it does, auto cancel the given trans-ID).
     *
     * @param  string $trans_id Transaction ID to cancel.
     * @return ?boolean True: yes. False: no. (null: cancels via a user-URL-directioning)
     */
    /*function auto_cancel($trans_id)     Not currently implemented
    {
        require_lang('ecommerce');
        $username = $this->_get_username();
        $password = get_option('ipn_password');
        $password_2 = get_option('vpn_password');
        $result = xml_rpc('https://www.secpay.com:443/secxmlrpc/make_call', 'SECVPN.repeatCardFullAddr', array($username, $password_2, $trans_id, -1, $password, '', '', '', '', '', 'repeat_change=true, repeat=false'), true);
        if ($result === null) {
            return false;
        }
        return (strpos($result, '&code=A&') !== false);
    }*/

    /**
     * Find a transaction fee from a transaction amount. Regular fees aren't taken into account.
     *
     * @param  float $amount A transaction amount.
     * @return float The fee.
     */
    public function get_transaction_fee($amount)
    {
        return 0.39; // the fee for <60 transactions per month. If it's more, I'd hope Composr's simple accountancy wasn't being relied on (it shouldn't be)!
    }

    /**
     * Get a list of card types.
     *
     * @param  ?string $it The card type to select by default (null: don't care).
     * @return Tempcode The list.
     */
    public function create_selection_list_card_types($it = null)
    {
        $list = new Tempcode();
        $array = array('Visa', 'Master Card', 'Switch', 'UK Maestro', 'Maestro', 'Solo', 'Delta', 'American Express', 'Diners Card', 'JCB');
        foreach ($array as $x) {
            $list->attach(form_input_list_entry($x, $it == $x));
        }
        return $list;
    }

    /**
     * Perform a transaction.
     *
     * @param  ?ID_TEXT $trans_id The transaction ID (null: generate one).
     * @param  SHORT_TEXT $cardholder_name Cardholder name.
     * @param  SHORT_TEXT $card_type Card Type.
     * @set    "Visa" "Master Card" "Switch" "UK Maestro" "Maestro" "Solo" "Delta" "American Express" "Diners Card" "JCB"
     * @param  SHORT_TEXT $card_number Card number.
     * @param  SHORT_TEXT $card_start_date Card Start date.
     * @param  SHORT_TEXT $card_expiry_date Card Expiry date.
     * @param  integer $card_issue_number Card Issue number.
     * @param  SHORT_TEXT $card_cv2 Card CV2 number (security number).
     * @param  SHORT_TEXT $amount Transaction amount.
     * @param  ID_TEXT $currency The currency
     * @param  LONG_TEXT $billing_street_address Street address (billing, i.e. AVS)
     * @param  SHORT_TEXT $billing_city Town/City (billing, i.e. AVS)
     * @param  SHORT_TEXT $billing_county County (billing, i.e. AVS)
     * @param  SHORT_TEXT $billing_state State (billing, i.e. AVS)
     * @param  SHORT_TEXT $billing_post_code Postcode/Zip (billing, i.e. AVS)
     * @param  SHORT_TEXT $billing_country Country (billing, i.e. AVS)
     * @param  SHORT_TEXT $shipping_firstname First name (shipping)
     * @param  SHORT_TEXT $shipping_lastname Last name (shipping)
     * @param  LONG_TEXT $shipping_street_address Street address (shipping)
     * @param  SHORT_TEXT $shipping_city Town/City (shipping)
     * @param  SHORT_TEXT $shipping_county County (shipping)
     * @param  SHORT_TEXT $shipping_state State (shipping)
     * @param  SHORT_TEXT $shipping_post_code Postcode/Zip (shipping)
     * @param  SHORT_TEXT $shipping_country Country (shipping)
     * @param  SHORT_TEXT $shipping_email E-mail address (shipping)
     * @param  SHORT_TEXT $shipping_phone Phone number (shipping)
     * @param  ?integer $length The subscription length in the units. (null: not a subscription)
     * @param  ?ID_TEXT $length_units The length units. (null: not a subscription)
     * @set    d w m y
     * @return array A tuple: success (boolean), trans-ID (string), message (string), raw message (string).
     */
    public function do_local_transaction($trans_id, $cardholder_name, $card_type, $card_number, $card_start_date, $card_expiry_date, $card_issue_number, $card_cv2, $amount, $currency, $billing_street_address, $billing_city, $billing_county, $billing_state, $billing_post_code, $billing_country, $shipping_firstname = '', $shipping_lastname = '', $shipping_street_address = '', $shipping_city = '', $shipping_county = '', $shipping_state = '', $shipping_post_code = '', $shipping_country = '', $shipping_email = '', $shipping_phone = '', $length = null, $length_units = null)
    {
        if ($trans_id === null) {
            $trans_id = $this->generate_trans_id();
        }
        $username = $this->_get_username();
        $password_2 = get_option('vpn_password');
        $digest = md5($trans_id . strval($amount) . get_option('ipn_password'));
        $options = 'currency=' . $currency . ',card_type=' . str_replace(',', '', $card_type) . ',digest=' . $digest . ',cv2=' . strval(intval($card_cv2)) . ',mand_cv2=true';
        if (ecommerce_test_mode()) {
            $options .= ',test_status=true';
        }
        if ($length !== null) {
            list($length_units_2, $first_repeat) = $this->_translate_subscription_details($length, $length_units);
            $options .= ',repeat=' . $first_repeat . '/' . $length_units_2 . '/0/' . $amount;
        }

        $item_name = $GLOBALS['SITE_DB']->query_select_value('trans_expecting', 'e_item_name', array('id' => $trans_id));

        $shipping_street_address_lines = explode("\n", $shipping_street_address, 2);
        $shipping_address = 'ship_name=' . $shipping_firstname . ' ' . $shipping_lastname . ',';
        $shipping_address .= 'ship_addr_1=' . $shipping_street_address_lines[0] . ',';
        $shipping_address .= 'ship_addr_2=' . $shipping_street_address_lines[1] . ',';
        $shipping_address .= 'ship_city=' . $shipping_city . ',';
        $shipping_address .= 'ship_state=' . $shipping_state . ',';
        $shipping_address .= 'ship_country=' . $shipping_country . ',';
        $shipping_address .= 'ship_post_code=' . $shipping_post_code . ',';
        $shipping_address .= 'ship_tel=' . $shipping_phone . ',';
        $shipping_address .= 'ship_email=' . $shipping_email;

        $billing_street_address_lines = explode("\n", $billing_street_address, 2);
        $billing_address = 'bill_addr_1=' . $billing_street_address_lines[0] . ',';
        $billing_address .= 'bill_addr_2=' . $billing_street_address_lines[1] . ',';
        $billing_address .= 'bill_city=' . $billing_city . ',';
        $billing_address .= 'bill_state=' . $billing_state . ',';
        $billing_address .= 'bill_country=' . $billing_country . ',';
        $billing_address .= 'bill_post_code=' . $billing_post_code;

        require_lang('ecommerce');

        require_code('xmlrpc');
        $result = xml_rpc('https://www.secpay.com:443/secxmlrpc/make_call', 'SECVPN.validateCardFull', array($username, $password_2, $trans_id, get_ip_address(), $cardholder_name, $card_number, $amount, $card_expiry_date, $card_issue_number, $card_start_date, $currency, '', '', $options, $item_name, $shipping_address, $billing_address));
        $pos_1 = strpos($result, '<value>');
        if ($pos_1 === false) {
            fatal_exit(do_lang('INTERNAL_ERROR'));
        }
        $pos_2 = strpos($result, '</value>');
        $value = @html_entity_decode(trim(substr($result, $pos_1 + 7, $pos_2 - $pos_1 - 7)), ENT_QUOTES, get_charset());
        if (substr($value, 0, 1) == '?') {
            $value = substr($value, 1);
        }
        $_map = explode('&', $value);
        $map = array();
        foreach ($_map as $x) {
            $explode = explode('=', $x);
            if (count($explode) == 2) {
                $map[$explode[0]] = $explode[1];
            }
        }

        $success = ((array_key_exists('code', $map)) && (($map['code'] == 'A') || ($map['code'] == 'P:P')));
        $message_raw = array_key_exists('message', $map) ? $map['message'] : '';
        $message = $success ? do_lang('ACCEPTED_MESSAGE', $message_raw) : do_lang('DECLINED_MESSAGE', $message_raw);

        return array($success, $trans_id, $message, $message_raw);
    }

    /**
     * Handle IPN's. The function may produce output, which would be returned to the Payment Gateway. The function may do transaction verification.
     *
     * @return array A long tuple of collected data. Emulates some of the key variables of the PayPal IPN response.
     */
    public function handle_ipn_transaction()
    {
        $txn_id = post_param_string('trans_id');
        if (substr($txn_id, 0, 7) == 'subscr_') {
            $subscription = true;
            $txn_id = substr($txn_id, 7);
        } else {
            $subscription = false;
        }

        $transaction_rows = $GLOBALS['SITE_DB']->query_select('trans_expecting', array('*'), array('id' => $txn_id), '', 1);
        if (!array_key_exists(0, $transaction_rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $transaction_row = $transaction_rows[0];

        $member_id = $transaction_row['e_member_id'];
        $item_name = $subscription ? '' : $transaction_row['e_item_name'];
        $purchase_id = $transaction_row['e_purchase_id'];

        $code = post_param_string('code');
        $success = ($code == 'A');
        $message = post_param_string('message');
        if ($message == '') {
            switch ($code) {
                case 'P:A':
                    $message = do_lang('PGE_A');
                    break;
                case 'P:X':
                    $message = do_lang('PGE_X');
                    break;
                case 'P:P':
                    $message = do_lang('PGE_P');
                    break;
                case 'P:S':
                    $message = do_lang('PGE_S');
                    break;
                case 'P:E':
                    $message = do_lang('PGE_E');
                    break;
                case 'P:I':
                    $message = do_lang('PGE_I');
                    break;
                case 'P:C':
                    $message = do_lang('PGE_C');
                    break;
                case 'P:T':
                    $message = do_lang('PGE_T');
                    break;
                case 'P:N':
                    $message = do_lang('PGE_N');
                    break;
                case 'P:M':
                    $message = do_lang('PGE_M');
                    break;
                case 'P:B':
                    $message = do_lang('PGE_B');
                    break;
                case 'P:D':
                    $message = do_lang('PGE_D');
                    break;
                case 'P:V':
                    $message = do_lang('PGE_V');
                    break;
                case 'P:R':
                    $message = do_lang('PGE_R');
                    break;
                case 'P:#':
                    $message = do_lang('PGE_HASH');
                    break;
                case 'C':
                    $message = do_lang('PGE_COMM');
                    break;
                default:
                    $message = do_lang('UNKNOWN');
            }
        }

        $payment_status = $success ? 'Completed' : 'Failed';
        $reason_code = '';
        $pending_reason = '';
        $memo = '';
        $mc_gross = post_param_string('amount');
        $mc_currency = post_param_string('currency', ''); // May be blank for subscription

        // Validate
        $hash = post_param_string('hash');
        if ($subscription) {
            $my_hash = md5('trans_id=' . $txn_id . '&' . 'req_cv2=true' . '&' . get_option('ipn_digest'));
        } else {
            $repeat = $this->_translate_subscription_details($transaction_row['e_length'], $transaction_row['e_length_units']);
            $my_hash = md5('trans_id=' . $txn_id . '&' . 'req_cv2=true' . '&' . 'repeat=' . $repeat . '&' . get_option('ipn_digest'));
        }
        if ($hash != $my_hash) {
            fatal_ipn_exit(do_lang('IPN_UNVERIFIED'));
        }

        if ($success) {
            require_code('notifications');
            dispatch_notification('payment_received', null, do_lang('PAYMENT_RECEIVED_SUBJECT', $txn_id, null, null, get_lang($member_id)), do_notification_lang('PAYMENT_RECEIVED_BODY', float_format(floatval($mc_gross)), $mc_currency, get_site_name(), get_lang($member_id)), array($member_id), A_FROM_SYSTEM_PRIVILEGED);
        }

        if (addon_installed('shopping')) {
            if ($transaction_row['e_type_code'] == 'cart_orders') {
                $this->store_shipping_address(intval($purchase_id));
            }
        }

        // Subscription stuff
        if (get_param_integer('subc', 0) == 1) {
            if (!$success) {
                $payment_status = 'SCancelled';
            }
        }

        // We need to echo the output of our finish page to SecPay's IPN caller
        if ($success) {
            $_url = build_url(array('page' => 'purchase', 'type' => 'finish', 'type_code' => get_param_string('type_code', null)), get_module_zone('purchase'));
        } else {
            $_url = build_url(array('page' => 'purchase', 'type' => 'finish', 'cancel' => 1, 'message' => do_lang_tempcode('DECLINED_MESSAGE', $message)), get_module_zone('purchase'));
        }
        $url = $_url->evaluate();
        echo http_download_file($url, null, false);

        return array($purchase_id, $item_name, $payment_status, $reason_code, $pending_reason, $memo, $mc_gross, $mc_currency, $txn_id, '', '');
    }

    /**
     * Store shipping address for orders.
     *
     * @param  AUTO_LINK $order_id Order ID.
     * @return ?mixed Address ID (null: No address record found).
     */
    public function store_shipping_address($order_id)
    {
        if (post_param_string('first_name', null) === null) {
            return null;
        }

        if ($GLOBALS['SITE_DB']->query_select_value_if_there('shopping_order_addresses', 'id', array('order_id' => $order_id)) === null) {
            $shipping_address = array(
                'order_id' => $order_id,
                'firstname' => trim(post_param_string('ship_name', '') . ', ' . post_param_string('ship_company', ''), ' ,'),
                'lastname' => '',
                'street_address' => trim(post_param_string('ship_addr_1', '') . "\n" . post_param_string('ship_addr_2', '')),
                'city' => post_param_string('ship_city', ''),
                'county' => '',
                'state' => post_param_string('ship_state', ''),
                'post_code' => post_param_string('ship_post_code', ''),
                'country' => post_param_string('ship_country', ''),
                'email' => '',
                'phone' => post_param_string('ship_tel', ''),
            );
            return $GLOBALS['SITE_DB']->query_insert('shopping_order_addresses', $shipping_address, true);
        }

        return null;
    }
}
