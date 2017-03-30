<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

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
class ecommerce_shipping_test_set extends cms_test_case
{
    public function setUp()
    {
        require_code('ecommerce');
        require_code('config2');

        set_option('shipping_density', '5000.0');
        set_option('shipping_weight_units', 'Kg');
        set_option('shipping_length_units', 'Cm');
        set_option('shipping_shippo', '');
        set_option('shipping_tax_code', '0%');
        set_option('shipping_cost_base', '10.00');
        set_option('shipping_cost_factor', '1.20');
    }

    public function testShippingCalculations()
    {
        // Make sure dimensions calculate properly if weight known
        $product_weight = 10.0;
        $product_length = null;
        $product_width = null;
        $product_height = null;
        calculate_shipping_cost_based_on_properties($product_weight, $product_length, $product_width, $product_height);
        //$expected_product_volume = $product_weight * intval(get_option('shipping_density')) = 10.0 * 5000.0 = 50000.0 cm3;
        //$product_length = $product_width = $product_height = pow(50000.0, 1.0 / 3.0) = 36.84 cm;
        $this->assertTrue(round($product_length, 2) == 36.84);
        $this->assertTrue($product_length == $product_width);
        $this->assertTrue($product_length == $product_height);

        // Now the reverse, Make sure weight calculates properly if dimensions known
        $product_weight = null;
        $product_length = 36.84;
        $product_width = null;
        $product_height = null;
        calculate_shipping_cost_based_on_properties($product_weight, $product_length, $product_width, $product_height);
        $this->assertTrue(round($product_weight, 2) == 10.00);

        // Test actual shipping cost
        $product_weight = 10.0;
        $product_length = 36.84;
        $product_width = 36.84;
        $product_height = 36.84;
        $cost = calculate_shipping_cost_based_on_properties($product_weight, $product_length, $product_width, $product_height);
        $this->assertTrue($cost == 10.00 + (10.0 * 1.20));
    }
}