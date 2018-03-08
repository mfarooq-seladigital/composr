<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    bankr
 */

/**
 * Hook class.
 */
class Hook_ecommerce_bank
{
    /**
     * Get the overall categorisation for the products handled by this eCommerce hook.
     *
     * @return ?array A map of product categorisation details (null: disabled)
     */
    public function get_product_category()
    {
        require_lang('bank');

        $bank_dividend = intval(get_option('bank_dividend'));
        // IDEA: Make 30 days a config option too, or even have multiple products?
        // IDEA: Send e-mail saying bank returned money?
        // IDEA: Have the bank do marketing to people?

        return array(
            'category_name' => do_lang('BANKING', integer_format($bank_dividend)),
            'category_description' => do_lang_tempcode('BANK_DESCRIPTION', escape_html(integer_format($bank_dividend))),
            'category_image_url' => find_theme_image('icons/menu/adminzone/audit/ecommerce/cash_flow'),
        );
    }

    /**
     * Get the products handled by this eCommerce hook.
     *
     * IMPORTANT NOTE TO PROGRAMMERS: This function may depend only on the database, and not on get_member() or any GET/POST values.
     *  Such dependencies will break IPN, which works via a Guest and no dependable environment variables. It would also break manual transactions from the Admin Zone.
     *
     * @param  ?ID_TEXT $search Product being searched for (null: none)
     * @return array A map of product name to list of product details
     */
    public function get_products($search = null)
    {
        if (!$GLOBALS['SITE_DB']->table_exists('bank')) {
            return array();
        }

        require_lang('bank');

        $bank_dividend = intval(get_option('bank_dividend'));

        $products = array();

        foreach (array(10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000) as $amount) {
            $products['BANK_' . strval($amount)] = array(
                'item_name' => do_lang('BANK', integer_format($amount)),
                'item_description' => new Tempcode(),
                'item_image_url' => '',

                'type' => PRODUCT_PURCHASE,
                'type_special_details' => array(),

                'price' => null,
                'currency' => get_option('currency'),
                'price_points' => $amount,
                'discount_points__num_points' => null,
                'discount_points__price_reduction' => null,

                'tax_code' => '0.0',
                'shipping_cost' => 0.00,
                'product_weight' => null,
                'product_length' => null,
                'product_width' => null,
                'product_height' => null,
                'needs_shipping_address' => false,
            );
        }

        return $products;
    }

    /**
     * Check whether the product codename is available for purchase by the member.
     *
     * @param  ID_TEXT $type_code The product codename
     * @param  MEMBER $member_id The member we are checking against
     * @param  integer $req_quantity The number required
     * @param  boolean $must_be_listed Whether the product must be available for public listing
     * @return integer The availability code (a ECOMMERCE_PRODUCT_* constant)
     */
    public function is_available($type_code, $member_id, $req_quantity = 1, $must_be_listed = false)
    {
        if (is_guest($member_id)) {
            return ECOMMERCE_PRODUCT_NO_GUESTS;
        }

        return ECOMMERCE_PRODUCT_AVAILABLE;
    }

    /**
     * Get fields that need to be filled in in the purchasing module.
     *
     * @param  ID_TEXT $type_code The product codename
     * @param  boolean $from_admin Whether this is being called from the Admin Zone. If so, optionally different fields may be used, including a purchase_id field for direct purchase ID input.
     * @return ?array A triple: The fields (null: none), The text (null: none), The JavaScript (null: none)
     */
    public function get_needed_fields($type_code, $from_admin = false)
    {
        return null;
    }

    /**
     * Handling of a product purchase change state.
     *
     * @param  ID_TEXT $type_code The product codename
     * @param  ID_TEXT $purchase_id The purchase ID
     * @param  array $details Details of the product, with added keys: TXN_ID, STATUS, ORDER_STATUS
     * @return boolean Whether the product was automatically dispatched (if not then hopefully this function sent a staff notification)
     */
    public function actualiser($type_code, $purchase_id, $details)
    {
        if ($details['STATUS'] != 'Completed') {
            return false;
        }

        require_lang('bank');

        $amount = intval(preg_replace('#^BANK_#', '', $type_code));

        $member_id = intval($purchase_id);

        $bank_dividend = intval(get_option('bank_dividend'));
        $GLOBALS['SITE_DB']->query_insert('bank', array('add_time' => time(), 'member_id' => $member_id, 'amount' => $amount, 'dividend' => $bank_dividend));

        $GLOBALS['SITE_DB']->query_insert('ecom_sales', array('date_and_time' => time(), 'member_id' => $member_id, 'details' => do_lang('BANKING', null, null, null, get_site_default_lang()), 'details2' => strval($amount), 'txn_id' => $details['TXN_ID']));

        // Show an instant message (plus buying via points, so will definitely be seen)
        $result = do_lang_tempcode('BANKING_CONGRATULATIONS', escape_html(integer_format($amount)), escape_html(integer_format($bank_dividend)));
        global $ECOMMERCE_SPECIAL_SUCCESS_MESSAGE;
        $ECOMMERCE_SPECIAL_SUCCESS_MESSAGE = $result;

        return true;
    }

    /**
     * Get the member who made the purchase.
     *
     * @param  ID_TEXT $type_code The product codename
     * @param  ID_TEXT $purchase_id The purchase ID
     * @return ?MEMBER The member ID (null: none)
     */
    public function member_for($type_code, $purchase_id)
    {
        return intval($purchase_id);
    }
}
