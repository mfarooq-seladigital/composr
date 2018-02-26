<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    testing_platform
 */

/**
 * Composr test case class (unit testing).
 */
class ecommerce_custom_test_set extends cms_test_case
{
    protected $custom_product_id;
    protected $map;

    public function setUp()
    {
        parent::setUp();

        require_code('ecommerce');
        require_code('ecommerce2');

        // Add custom product
        $map = array(
            'c_enabled' => 1,
            'c_price' => 10.00,
            'c_tax_code' => '0.00',
            'c_shipping_cost' => 0.00,
            'c_price_points' => 0,
            'c_one_per_member' => 0,
        );
        $map += insert_lang('c_title', 'TestCustomItem', 2);
        $map += insert_lang_comcode('c_description', '', 2);
        $map += insert_lang('c_mail_subject', '', 2);
        $map += insert_lang('c_mail_body', '', 2);
        $this->custom_product_id = $GLOBALS['SITE_DB']->query_insert('ecom_prods_custom', $map, true);
        $this->map = $map;
    }

    public function testCustomProductPurchaseWorks()
    {
        $this->establish_admin_session();

        set_option('payment_gateway', 'paypal');
        set_option('ecommerce_test_mode', '1');
        set_option('payment_gateway_test_username', 'test@example.com');
        set_option('currency', 'USD');

        if (get_forum_type() == 'cns') {
            $test_username = 'test';
        } else {
            $test_username = 'admin';
        }

        $member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($test_username);

        // Test custom product is there
        $url = build_url(array('page' => 'purchase', 'type' => 'browse', 'keep_su' => $test_username));
        $purchase_screen = http_get_contents($url->evaluate(), array('cookies' => array(get_session_cookie() => get_session_id())));
        $this->assertTrue(strpos($purchase_screen, 'TestCustomItem') !== false);

        // Test button generates
        $button = make_transaction_button('CUSTOM_' . strval($this->custom_product_id), 'test', strval($member_id), 10.00, array(), 0.00, array(), 0.00, 0.00, 'USD', 0, 'paypal');

        // Find custom ID for transaction
        $matches = array();
        preg_match('#<input type="hidden" name="custom" value="([^"]*)" />#', $button->evaluate(), $matches);
        $trans_expecting_id = $matches[1];

        // Clear out sales
        $GLOBALS['SITE_DB']->query_delete('ecom_sales', array('member_id' => $member_id));

        // Put through fake IPN response
        $ipn_data = array(
            'cmd' => '_notify-validate',
            'mc_gross' => '10.00',
            'payer_id' => uniqid('', true),
            'tax' => '0.00',
            'payment_date' => '20%3A12%3A59+Jan+13%2C+2009+PST',
            'charset' => 'windows-1252',
            'mc_fee' => '0.88',
            'notify_version' => '2.6',
            'custom' => $trans_expecting_id,
            'payer_status' => 'verified',
            'quantity' => '1',
            'verify_sign' => uniqid('', true),
            'payer_email' => 'gpmac_1231902590_per%40paypal.com',
            'txn_id' => uniqid('', true),
            'payment_type' => 'instant',
            'business' => 'test@example.com',
            'receiver_email' => 'test@example.com',
            'payment_fee' => '0.88',
            'receiver_id' => uniqid('', true),
            'txn_type' => 'web_accept',
            'item_name' => 'TestCustomItem',
            'mc_currency' => 'USD',
            'item_number' => '',
            'residence_country' => 'US',
            'test_ipn' => '1',
            'handling_amount' => '0.00',
            'transaction_subject' => '',
            'payment_gross' => '10.88',
            'shipping' => '0.00',
        );
        $_POST = array(
            'payment_status' => 'Completed',
        ) + $ipn_data;
        handle_ipn_transaction_script(true, false);

        // Test was actioned
        $test = $GLOBALS['SITE_DB']->query_select_value_if_there('ecom_sales', 'member_id', array('member_id' => $member_id));
        $this->assertTrue($test !== null);
    }

    public function tearDown()
    {
        // Delete custom item
        $map = $this->map;
        delete_lang($map['c_title']);
        delete_lang($map['c_description']);
        delete_lang($map['c_mail_subject']);
        delete_lang($map['c_mail_body']);
        $GLOBALS['SITE_DB']->query_delete('ecom_prods_custom', array('id' => $this->custom_product_id));

        parent::tearDown();
    }
}