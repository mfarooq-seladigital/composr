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
 * @package    breadcrumbs
 */

/**
 * Hook class.
 */
class Hook_actionlog_breadcrumbs
{
    /**
     * Get details of actionlog entry types handled by this hook.
     *
     * @return array Map of handler data in standard format
     */
    public function get_handlers()
    {
        if (!addon_installed('breadcrumbs')) {
            return array();
        }

        require_lang('config');

        return array(
            'BREADCRUMB_OVERRIDES' => array(
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'BREADCRUMB_OVERRIDES' => 'TODO',
                ),
            ),
        );
    }
}
