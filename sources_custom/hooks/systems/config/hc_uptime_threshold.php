<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    health_check
 */

/**
 * Hook class.
 */
class Hook_config_hc_uptime_threshold
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'HC_UPTIME_THRESHOLD',
            'type' => 'integer',
            'category' => 'HEALTH_CHECK',
            'group' => 'PERFORMANCE',
            'explanation' => 'CONFIG_OPTION_hc_uptime_threshold',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 5,
            'required' => true,

            'addon' => 'health_check',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string The default value (null: option is disabled)
     */
    public function get_default()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            return null;
        }

        return '20';
    }
}
