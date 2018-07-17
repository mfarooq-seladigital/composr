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
 * @package    pointstore
 */

/**
 * Hook class.
 */
class Hook_actionlog_pointstore
{
    /**
     * Get details of actionlog entry types handled by this hook.
     *
     * @return array Map of handler data in standard format
     */
    public function get_handlers()
    {
        if (!addon_installed('pointstore')) {
            return array();
        }

        require_lang('pointstore');

        return array(
            'POINTSTORE_ADD_MAIL_FORWARDER' => array(
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'POINTSTORE_MANAGE_INVENTORY' => 'TODO',
                ),
            ),
            'POINTSTORE_ADD_MAIL_POP3' => array(
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'POINTSTORE_MANAGE_INVENTORY' => 'TODO',
                ),
            ),
            'POINTSTORE_AMEND_CUSTOM_PERMISSIONS' => array(
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'POINTSTORE_MANAGE_INVENTORY' => 'TODO',
                ),
            ),
            'POINTSTORE_AMEND_CUSTOM_PRODUCTS' => array(
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'POINTSTORE_MANAGE_INVENTORY' => 'TODO',
                ),
            ),
            'POINTSTORE_CHANGED_PRICES' => array(
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'POINTSTORE_MANAGE_INVENTORY' => 'TODO',
                ),
            ),
        );
    }
}
