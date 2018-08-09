<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_configuration
 */

/**
 * Hook class.
 */
class Hook_config_noindex_comcode_pages
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'NOINDEX_COMCODE_PAGES',
            'type' => 'text',
            'category' => 'SITE',
            'group' => 'SEO',
            'explanation' => 'CONFIG_OPTION_noindex_comcode_pages',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 10,
            'required' => false,

            'public' => false,

            'addon' => 'core_configuration',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string The default value (null: option is disabled)
     */
    public function get_default()
    {
        return ":recommend_help\n:popup_blockers\n:help\n:userguide_chatcode\n:userguide_comcode\nsite:popup_blockers\nsite:help\nsite:userguide_chatcode\nsite:userguide_comcode\n:rules\nsite:rules\nforum:rules\n:keymap";
    }
}