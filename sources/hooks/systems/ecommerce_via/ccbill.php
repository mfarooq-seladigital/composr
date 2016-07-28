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
class Hook_ecommerce_via_ccbill
{
    // Requires:
    //  you have to contact support to enable dynamic pricing and generate the encryption key for your account
    //  the "Account ID" (a number given to you) is the Composr "Gateway username" and also "Testing mode gateway username" (it's all the same installation ID)
    //  the "Subaccount ID" is the Composr "Gateway VPN username". You can optionally enter two subaccount IDs separated by a comma, the first one will be used for single transactions and the second for recurring transactions.
    //  your encryption key is the Composr "Gateway VPN password".
    //  create a form with dynamic pricing from the form admin and enter its code name as the "Gateway digest code". You can optionally enter two values separated by a comma; the first one will be used for simple transactions and the second for subscriptions.

    private $length_unit_to_days = array(
        'd' => 1,
        'w' => 7,
        'm' => 30,
        'y' => 365
    );

    private $currency_numeric_to_alphabetic_code = array(
        // Currencies supported by CCBill
        840 => 'USD',
        978 => 'EUR',
        826 => 'GBP',
        124 => 'CAD',
        36 => 'AUD',
        392 => 'JPY',
    );

    private $currency_alphabetic_to_numeric_code = array(
        // Currencies supported by CCBill
        'USD' => 840,
        'EUR' => 978,
        'GBP' => 826,
        'CAD' => 124,
        'AUD' => 36,
        'JPY' => 392,
    );

    /**
     * Get the CCBill account ID
     *
     * @return string The answer.
     */
    private function get_account_id()
    {
        return ecommerce_test_mode() ? get_option('ipn_test') : get_option('ipn');
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
        if (!isset($this->currency_alphabetic_to_numeric_code[$currency])) {
            warn_exit(do_lang_tempcode('UNRECOGNISED_CURRENCY', 'ccbill', escape_html($currency)));
        }
        $currency = strval($this->currency_alphabetic_to_numeric_code[$currency]);

        $payment_address = strval($this->get_account_id());
        $ipn_url = 'https://bill.ccbill.com/jpost/signup.cgi';

        $trans_id = $this->generate_trans_id();

        $user_details = array();
        if (!is_guest()) {
            $user_details['customer_fname'] = get_cms_cpf('firstname');
            $user_details['customer_lname'] = get_cms_cpf('lastname');
            $user_details['address1'] = get_cms_cpf('street_address');
            $user_details['email'] = $GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member());
            $user_details['city'] = get_cms_cpf('city');
            $user_details['state'] = get_cms_cpf('state');
            $user_details['zipcode'] = get_cms_cpf('post_code');
            $user_details['country'] = get_cms_cpf('country');
            $user_details['username'] = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
        }

        $account_num = $this->get_account_id();
        $subaccount_nums = explode(',', get_option('vpn_username'));
        $subaccount_num = sprintf('%04d', $subaccount_nums[0]); // First value is for simple transactions, has to be exactly 4 digits
        $form_name = explode(',', get_option('ipn_digest'));
        $form_name = $form_name[0]; // First value is for simple transactions
        // CCBill oddly requires us to pass this parameter for single transactions,
        // this will show up as a confusing "$X.XX for 99 days" message to customers on the CCBill form.
        // To fix this - you need to set up a "custom dynamic description" which removes that message, by contacting CCBill support.
        $form_period = '99';
        $digest = md5(float_to_raw_string($amount) . $form_period . $currency . get_option('vpn_password'));

        $GLOBALS['SITE_DB']->query_insert('trans_expecting', array(
            'id' => $trans_id,
            'e_purchase_id' => $purchase_id,
            'e_item_name' => $item_name,
            'e_member_id' => get_member(),
            'e_amount' => float_to_raw_string($amount),
            'e_ip_address' => get_ip_address(),
            'e_session_id' => get_session_id(),
            'e_time' => time(),
            'e_length' => null,
            'e_length_units' => '',
        ));

        return do_template('ECOM_BUTTON_VIA_CCBILL', array(
            '_GUID' => '24a0560541cedd4c45898f4d19e99249',
            'TYPE_CODE' => strval($type_code),
            'ITEM_NAME' => strval($item_name),
            'PURCHASE_ID' => strval($purchase_id),
            'AMOUNT' => float_to_raw_string($amount),
            'CURRENCY' => $currency,
            'PAYMENT_ADDRESS' => $payment_address,
            'IPN_URL' => $ipn_url,
            'TRANS_ID' => $trans_id,
            'MEMBER_ADDRESS' => $user_details,
            'ACCOUNT_NUM' => $account_num,
            'SUBACCOUNT_NUM' => $subaccount_num,
            'FORM_NAME' => $form_name,
            'FORM_PERIOD' => $form_period,
            'DIGEST' => $digest,
        ));
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
        if (!isset($this->currency_alphabetic_to_numeric_code[$currency])) {
            warn_exit(do_lang_tempcode('UNRECOGNISED_CURRENCY', 'ccbill', escape_html($currency)));
        }
        $currency = strval($this->currency_alphabetic_to_numeric_code[$currency]);

        $payment_address = strval($this->get_account_id());
        $ipn_url = 'https://bill.ccbill.com/jpost/signup.cgi';

        $trans_id = $this->generate_trans_id();

        $user_details = array();
        if (!is_guest()) {
            $user_details['customer_fname'] = get_cms_cpf('firstname');
            $user_details['customer_lname'] = get_cms_cpf('lastname');
            $user_details['address1'] = get_cms_cpf('street_address');
            $user_details['email'] = $GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member());
            $user_details['city'] = get_cms_cpf('city');
            $user_details['state'] = get_cms_cpf('state');
            $user_details['zipcode'] = get_cms_cpf('post_code');
            $user_details['country'] = get_cms_cpf('country');
            $user_details['username'] = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
        }

        $account_num = $this->get_account_id();
        $subaccount_nums = explode(',', get_option('vpn_username'));
        $subaccount_num = sprintf('%04d', count($subaccount_nums) === 1 ? $subaccount_nums[0] : $subaccount_nums[1]); // Second value is for subscriptions, has to be exactly 4 digits
        $form_name = explode(',', get_option('ccbill_form_names'));
        $form_name = count($form_name) === 1 ? $form_name[0] : $form_name[1]; // Second value is for subscriptions
        $form_period = strval($length * $this->length_unit_to_days[$length_units]);
        $digest = md5(float_to_raw_string($amount) . $form_period . float_to_raw_string($amount) . $form_period . '99' . $currency . get_option('vpn_password')); // formPrice.formPeriod.formRecurringPrice.formRecurringPeriod.formRebills.currencyCode.salt

        $GLOBALS['SITE_DB']->query_insert('trans_expecting', array(
            'id' => $trans_id,
            'e_purchase_id' => $purchase_id,
            'e_item_name' => $item_name,
            'e_member_id' => get_member(),
            'e_amount' => float_to_raw_string($amount),
            'e_ip_address' => get_ip_address(),
            'e_session_id' => get_session_id(),
            'e_time' => time(),
            'e_length' => $length,
            'e_length_units' => $length_units,
        ));

        return do_template('ECOM_SUBSCRIPTION_BUTTON_VIA_CCBILL', array(
            '_GUID' => 'f8c174f38ae06536833f1510027ba233',
            'TYPE_CODE' => strval($type_code),
            'ITEM_NAME' => strval($item_name),
            'LENGTH' => strval($length),
            'LENGTH_UNITS' => $length_units,
            'PURCHASE_ID' => strval($purchase_id),
            'AMOUNT' => float_to_raw_string($amount),
            'CURRENCY' => $currency,
            'PAYMENT_ADDRESS' => $payment_address,
            'IPN_URL' => $ipn_url,
            'TRANS_ID' => $trans_id,
            'MEMBER_ADDRESS' => $user_details,
            'ACCOUNT_NUM' => $account_num,
            'SUBACCOUNT_NUM' => $subaccount_num,
            'FORM_NAME' => $form_name,
            'FORM_PERIOD' => $form_period,
            'DIGEST' => $digest,
        ));
    }

    /**
     * Make a subscription cancellation button.
     *
     * @param  ID_TEXT $purchase_id The purchase ID.
     * @return Tempcode The button
     */
    public function make_cancel_button($purchase_id)
    {
        return do_template('ECOM_CANCEL_BUTTON_VIA_CCBILL', array('_GUID' => 'f1aaed809380c3fdca22728393eaef75', 'PURCHASE_ID' => $purchase_id));
    }

    /**
     * Find whether the hook auto-cancels (if it does, auto cancel the given trans-ID).
     *
     * @param  string $trans_id Transaction ID to cancel.
     * @return ?boolean True: yes. False: no. (null: cancels via a user-URL-directioning)
     */
    public function auto_cancel($trans_id)
    {
        return false;
    }

    /**
     * Find a transaction fee from a transaction amount. Regular fees aren't taken into account.
     *
     * @param  float $amount A transaction amount.
     * @return float The fee
     */
    public function get_transaction_fee($amount)
    {
        return 0.12 * $amount; // A wild guess for now
    }

    /**
     * Handle IPN's. The function may produce output, which would be returned to the Payment Gateway. The function may do transaction verification.
     *
     * @return array A long tuple of collected data.
     */
    public function handle_transaction()
    {
        // assign posted variables to local variables
        $trans_id = post_param_string('customTransId');
        $purchase_id = post_param_integer('customPurchaseId');

        $transaction_rows = $GLOBALS['SITE_DB']->query_select('trans_expecting', array('*'), array('id' => $trans_id, 'e_purchase_id' => $purchase_id), '', 1);
        if (!array_key_exists(0, $transaction_rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $transaction_row = $transaction_rows[0];

        $subscription_id = post_param_string('subscription_id', '');
        $denial_id = post_param_string('denialId', '');
        $response_digest = post_param_string('responseDigest');
        $success_response_digest = md5($subscription_id . '1' . get_option('vpn_password')); // responseDigest must have this value on success
        $denial_response_digest = md5($denial_id . '0' . get_option('vpn_password')); // responseDigest must have this value on failure

        if (($response_digest !== $success_response_digest) && ($response_digest !== $denial_response_digest)) {
            fatal_ipn_exit(do_lang('IPN_UNVERIFIED')); // Hacker?!!!
        }

        $success = ($success_response_digest === $response_digest);
        $is_subscription = (bool)post_param_integer('customIsSubscription');
        $item_name = $is_subscription ? '' : $transaction_row['e_item_name'];
        $payment_status = $success ? 'Completed' : 'Failed';
        $reason_code = post_param_integer('reasonForDeclineCode', 0);
        $pending_reason = '';
        $memo = '';
        $mc_gross = post_param_string('initialPrice');
        $_mc_currency = post_param_integer('baseCurrency', 0);
        $mc_currency = ($_mc_currency === 0) ? get_option('currency') : $this->currency_numeric_to_alphabetic_code[$_mc_currency];

        if (addon_installed('shopping')) {
            $this->store_shipping_address($purchase_id);
        }

        return array($purchase_id, $item_name, $payment_status, $reason_code, $pending_reason, $memo, $mc_gross, $mc_currency, $trans_id, '');
    }

    /**
     * Store shipping address for orders.
     *
     * @param  AUTO_LINK $order_id Order ID.
     * @return ?mixed Address ID (null: No address record found).
     */
    public function store_shipping_address($order_id)
    {
        if (post_param_string('address1', null) === null) {
            return null;
        }

        if ($GLOBALS['SITE_DB']->query_select_value_if_there('shopping_order_addresses', 'id', array('order_id' => $order_id)) === null) {
            $shipping_address = array();
            $shipping_address['order_id'] = $order_id;
            $shipping_address['address_name'] = post_param_string('customer_fname', '') . ' ' . post_param_string('customer_lname', '');
            $shipping_address['address_street'] = post_param_string('address1', '');
            $shipping_address['address_zip'] = post_param_string('zipcode', '');
            $shipping_address['address_city'] = post_param_string('city', '');
            $shipping_address['address_city'] = post_param_string('state', '');
            $shipping_address['address_country'] = post_param_string('country', '');
            $shipping_address['receiver_email'] = post_param_string('email', '');
            $shipping_address['contact_phone'] = post_param_string('phone_number', '');
            $shipping_address['first_name'] = post_param_string('customer_fname', '');
            $shipping_address['last_name'] = post_param_string('customer_lname', '');

            return $GLOBALS['SITE_DB']->query_insert('shopping_order_addresses', $shipping_address, true);
        }

        return null;
    }
}
