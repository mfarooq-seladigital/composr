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
class Hook_config_currency
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'CURRENCY',
            'type' => 'special',
            'category' => 'ECOMMERCE',
            'group' => 'GENERAL',
            'explanation' => 'CONFIG_OPTION_currency',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 1,

            'addon' => 'ecommerce',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string The default value (null: option is disabled)
     */
    public function get_default()
    {
        return 'GBP';
    }

    /**
     * Field inputter (because the_type=special).
     *
     * @param  ID_TEXT $name The config option name
     * @param  array $myrow The config row
     * @param  Tempcode $human_name The field title
     * @param  Tempcode $explanation The field description
     * @return Tempcode The inputter
     */
    public function field_inputter($name, $myrow, $human_name, $explanation)
    {
        $list = '';
        require_code('currency');
        $currencies = array_keys(get_currency_map());
        foreach ($currencies as $currency) {
            $list .= static_evaluate_tempcode(form_input_list_entry($currency, $currency == get_option($name)));
        }
        return form_input_list($human_name, $explanation, $name, make_string_tempcode($list));
    }
}
