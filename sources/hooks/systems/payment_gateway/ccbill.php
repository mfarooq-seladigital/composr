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
class Hook_payment_gateway_ccbill
{
    // https://www.ccbill.com/cs/manuals/CCBill_Background_Post_Users_Guide.pdf
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
     * Get a standardised config map.
     *
     * @return array The config
     */
    public function get_config()
    {
        return array(
            'supports_remote_memo' => false,
        );
    }

    /**
     * Find a transaction fee from a transaction amount. Regular fees aren't taken into account.
     *
     * @param  REAL $amount A transaction amount.
     * @return REAL The fee
     */
    public function get_transaction_fee($amount)
    {
        return 0.12 * $amount; // A wild guess for now
    }

    /**
     * Get the CCBill account ID
     *
     * @return string The answer.
     */
    private function get_account_id()
    {
        return ecommerce_test_mode() ? get_option('payment_gateway_test_username') : get_option('payment_gateway_username');
    }

    /**
     * Generate a transaction ID / trans-expecting ID.
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
     * @param  ID_TEXT $trans_expecting_id Our internal temporary transaction ID.
     * @param  ID_TEXT $type_code The product codename.
     * @param  SHORT_TEXT $item_name The human-readable product title.
     * @param  ID_TEXT $purchase_id The purchase ID.
     * @param  REAL $price Transaction price in money.
     * @param  REAL $tax Transaction tax in money.
     * @param  REAL $shipping_cost Shipping cost.
     * @param  ID_TEXT $currency The currency to use.
     * @return Tempcode The button.
     */
    public function make_transaction_button($trans_expecting_id, $type_code, $item_name, $purchase_id, $price, $tax, $shipping_cost, $currency)
    {
        if (!isset($this->currency_alphabetic_to_numeric_code[$currency])) {
            warn_exit(do_lang_tempcode('UNRECOGNISED_CURRENCY', 'ccbill', escape_html($currency)));
        }
        $currency = strval($this->currency_alphabetic_to_numeric_code[$currency]);

        $payment_address = $this->get_account_id();
        $form_url = 'https://bill.ccbill.com/jpost/signup.cgi';

        $account_num = $this->get_account_id();
        $subaccount_nums = explode(',', get_option('payment_gateway_vpn_username'));
        $subaccount_num = sprintf('%04d', $subaccount_nums[0]); // First value is for simple transactions, has to be exactly 4 digits
        $form_name = explode(',', get_option('payment_gateway_digest'));
        $form_name = $form_name[0]; // First value is for simple transactions
        // CCBill oddly requires us to pass this parameter for single transactions,
        // this will show up as a confusing "$X.XX for 99 days" message to customers on the CCBill form.
        // To fix this - you need to set up a "custom dynamic description" which removes that message, by contacting CCBill support.
        $form_period = '99';
        $digest = md5(float_to_raw_string($price + $tax + $shipping_cost) . $form_period . $currency . get_option('payment_gateway_vpn_password'));

        return do_template('ECOM_TRANSACTION_BUTTON_VIA_CCBILL', array(
            '_GUID' => '24a0560541cedd4c45898f4d19e99249',
            'TYPE_CODE' => $type_code,
            'ITEM_NAME' => $item_name,
            'PURCHASE_ID' => $purchase_id,
            'TRANS_EXPECTING_ID' => $trans_expecting_id,
            'PRICE' => float_to_raw_string($price),
            'TAX' => float_to_raw_string($tax),
            'SHIPPING_COST' => float_to_raw_string($shipping_cost),
            'AMOUNT' => float_to_raw_string($price + $tax + $shipping_cost),
            'CURRENCY' => $currency,
            'PAYMENT_ADDRESS' => $payment_address,
            'FORM_URL' => $form_url,
            'MEMBER_ADDRESS' => $this->_build_member_address(),
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
     * @param  ID_TEXT $trans_expecting_id Our internal temporary transaction ID.
     * @param  ID_TEXT $type_code The product codename.
     * @param  SHORT_TEXT $item_name The human-readable product title.
     * @param  ID_TEXT $purchase_id The purchase ID.
     * @param  REAL $price Transaction price in money.
     * @param  REAL $tax Transaction tax in money.
     * @param  ID_TEXT $currency The currency to use.
     * @param  integer $length The subscription length in the units.
     * @param  ID_TEXT $length_units The length units.
     * @set    d w m y
     * @return Tempcode The button.
     */
    public function make_subscription_button($trans_expecting_id, $type_code, $item_name, $purchase_id, $price, $tax, $currency, $length, $length_units)
    {
        if (!isset($this->currency_alphabetic_to_numeric_code[$currency])) {
            warn_exit(do_lang_tempcode('UNRECOGNISED_CURRENCY', 'ccbill', escape_html($currency)));
        }
        $currency = strval($this->currency_alphabetic_to_numeric_code[$currency]);

        $payment_address = $this->get_account_id();
        $form_url = 'https://bill.ccbill.com/jpost/signup.cgi';

        $account_num = $this->get_account_id();
        $subaccount_nums = explode(',', get_option('payment_gateway_vpn_username'));
        $subaccount_num = sprintf('%04d', count($subaccount_nums) === 1 ? $subaccount_nums[0] : $subaccount_nums[1]); // Second value is for subscriptions, has to be exactly 4 digits
        $form_name = explode(',', get_option('ipn_digest'));
        $form_name = count($form_name) === 1 ? $form_name[0] : $form_name[1]; // Second value is for subscriptions
        $form_period = strval($length * $this->length_unit_to_days[$length_units]);
        $digest = md5(float_to_raw_string($price + $tax) . $form_period . float_to_raw_string($price + $tax) . $form_period . '99' . $currency . get_option('payment_gateway_vpn_password')); // formPrice.formPeriod.formRecurringPrice.formRecurringPeriod.formRebills.currencyCode.salt

        return do_template('ECOM_SUBSCRIPTION_BUTTON_VIA_CCBILL', array(
            '_GUID' => 'f8c174f38ae06536833f1510027ba233',
            'TYPE_CODE' => $type_code,
            'ITEM_NAME' => $item_name,
            'PURCHASE_ID' => $purchase_id,
            'TRANS_EXPECTING_ID' => $trans_expecting_id,
            'LENGTH' => strval($length),
            'LENGTH_UNITS' => $length_units,
            'PRICE' => float_to_raw_string($price),
            'TAX' => float_to_raw_string($tax),
            'AMOUNT' => float_to_raw_string($price + $tax),
            'CURRENCY' => $currency,
            'PAYMENT_ADDRESS' => $payment_address,
            'FORM_URL' => $form_url,
            'MEMBER_ADDRESS' => $this->_build_member_address(),
            'ACCOUNT_NUM' => $account_num,
            'SUBACCOUNT_NUM' => $subaccount_num,
            'FORM_NAME' => $form_name,
            'FORM_PERIOD' => $form_period,
            'DIGEST' => $digest,
        ));
    }

    /**
     * Get a member address/etc for use in payment buttons.
     *
     * @return array A map of member address details (form field name => address value).
     */
    protected function _build_member_address()
    {
        $shipping_email = '';
        $shipping_phone = '';
        $shipping_firstname = '';
        $shipping_lastname = '';
        $shipping_street_address = '';
        $shipping_city = '';
        $shipping_county = '';
        $shipping_state = '';
        $shipping_post_code = '';
        $shipping_country = '';
        $shipping_email = '';
        $shipping_phone = '';
        $cardholder_name = '';
        $card_type = '';
        $card_number = null;
        $card_start_date_year = null;
        $card_start_date_month = null;
        $card_expiry_date_year = null;
        $card_expiry_date_month = null;
        $card_issue_number = null;
        $card_cv2 = null;
        $billing_street_address = '';
        $billing_city = '';
        $billing_county = '';
        $billing_state = '';
        $billing_post_code = '';
        $billing_country = '';
        get_default_ecommerce_fields(null, $shipping_email, $shipping_phone, $shipping_firstname, $shipping_lastname, $shipping_street_address, $shipping_city, $shipping_county, $shipping_state, $shipping_post_code, $shipping_country, $cardholder_name, $card_type, $card_number, $card_start_date_year, $card_start_date_month, $card_expiry_date_year, $card_expiry_date_month, $card_issue_number, $card_cv2, $billing_street_address, $billing_city, $billing_county, $billing_state, $billing_post_code, $billing_country, false, false);

        if ($shipping_street_address == '') {
            $street_address = $billing_street_address;
            $city = $billing_city;
            $county = $billing_county;
            $state = $billing_state;
            $post_code = $billing_post_code;
            $country = $billing_country;
        } else {
            $street_address = $shipping_street_address;
            $city = $shipping_city;
            $county = $shipping_county;
            $state = $shipping_state;
            $post_code = $shipping_post_code;
            $country = $shipping_country;
        }

        $member_address = array();
        $member_address['customer_fname'] = $shipping_firstname;
        $member_address['customer_lname'] = $shipping_lastname;
        $member_address['address1'] = $street_address;
        $member_address['city'] = $city;
        $member_address['state'] = $state;
        $member_address['zipcode'] = $post_code;
        $member_address['country'] = $country;
        $member_address['email'] = $shipping_email;
        $member_address['username'] = is_guest() ? '' : $GLOBALS['FORUM_DRIVER']->get_username(get_member());

        return $member_address;
    }

    /**
     * Make a subscription cancellation button.
     *
     * @param  ID_TEXT $purchase_id The purchase ID.
     * @return Tempcode The button
     */
    public function make_cancel_button($purchase_id)
    {
        return do_template('ECOM_SUBSCRIPTION_CANCEL_BUTTON_VIA_CCBILL', array('_GUID' => 'f1aaed809380c3fdca22728393eaef75', 'PURCHASE_ID' => $purchase_id));
    }

    /**
     * Handle IPN's. The function may produce output, which would be returned to the Payment Gateway. The function may do transaction verification.
     *
     * @return ?array A long tuple of collected data (null: no transaction; will only return null when not running the 'ecommerce' script).
     */
    public function handle_ipn_transaction()
    {
        $trans_expecting_id = post_param_integer('customPurchaseId');

        $transaction_rows = $GLOBALS['SITE_DB']->query_select('ecom_trans_expecting', array('*'), array('id' => $trans_expecting_id), '', 1);
        if (!array_key_exists(0, $transaction_rows)) {
            if (!running_script('ecommerce')) {
                return null;
            }
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $transaction_row = $transaction_rows[0];

        $member_id = $transaction_row['e_member_id'];
        $type_code = $transaction_row['e_type_code'];
        $item_name = $transaction_row['e_item_name'];
        $purchase_id = $transaction_row['e_purchase_id'];

        $subscription_id = post_param_string('subscription_id', '');
        $denial_id = post_param_string('denialId', '');
        $response_digest = post_param_string('responseDigest');
        $success_response_digest = md5($subscription_id . '1' . get_option('payment_gateway_vpn_password')); // responseDigest must have this value on success
        $denial_response_digest = md5($denial_id . '0' . get_option('payment_gateway_vpn_password')); // responseDigest must have this value on failure
        $success = ($success_response_digest === $response_digest);
        $is_subscription = (post_param_integer('customIsSubscription') == 1);
        $status = $success ? 'Completed' : 'Failed';
        $reason = post_param_integer('reasonForDeclineCode', 0);
        $pending_reason = '';
        $memo = '';
        $_amount = post_param_string('initialPrice');
        $amount = ($_amount == '') ? null : floatval($_amount);
        $_currency = post_param_integer('baseCurrency', 0);
        $currency = ($_currency === 0) ? get_option('currency') : $this->currency_numeric_to_alphabetic_code[$_currency];
        $txn_id = post_param_string('consumerUniqueId');
        $parent_txn_id = '';
        $period = '';

        // SECURITY
        if (($response_digest !== $success_response_digest) && ($response_digest !== $denial_response_digest)) {
            if (!running_script('ecommerce')) {
                return null;
            }
            fatal_ipn_exit(do_lang('IPN_UNVERIFIED')); // Hacker?!!!
        }

        $this->store_shipping_address($trans_expecting_id, $txn_id);

        $tax = null;

        return array($trans_expecting_id, $txn_id, $type_code, $item_name, $purchase_id, $is_subscription, $status, $reason, $amount, $tax, $currency, $parent_txn_id, $pending_reason, $memo, $period, $member_id);
    }

    /**
     * Store shipping address for a transaction.
     *
     * @param  ID_TEXT $trans_expecting_id Expected transaction ID.
     * @param  ID_TEXT $txn_id Transaction ID.
     * @return AUTO_LINK Address ID.
     */
    public function store_shipping_address($trans_expecting_id, $txn_id)
    {
        $shipping_address = array(
            'a_firstname' => post_param_string('customer_fname', ''),
            'a_lastname' => post_param_string('customer_lname', ''),
            'a_street_address' => trim(post_param_string('address1', '') . "\n" . post_param_string('address2', '')),
            'a_city' => post_param_string('city', ''),
            'a_county' => '',
            'a_state' => post_param_string('state', ''),
            'a_post_code' => post_param_string('zipcode', ''),
            'a_country' => post_param_string('country', ''),
            'a_email' => post_param_string('email', ''),
            'a_phone' => post_param_string('phone_number', ''),
        );
        return store_shipping_address($trans_expecting_id, $txn_id, $shipping_address);
    }

    /**
     * Find whether the hook auto-cancels (if it does, auto cancel the given subscription).
     *
     * @param  AUTO_LINK $subscription_id ID of the subscription to cancel.
     * @return ?boolean True: yes. False: no. (null: cancels via a user-URL-directioning)
     */
    public function auto_cancel($subscription_id)
    {
        // https://www.ccbill.com/cs/manuals/Custom_Cancellation_Software.pdf
        // Can't do it because we don't have customer's username and password ("login_id", "password")

        return false;
    }
}
