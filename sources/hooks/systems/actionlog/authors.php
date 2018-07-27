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
 * @package    authors
 */

/**
 * Hook class.
 */
class Hook_actionlog_authors extends Hook_actionlog
{
    /**
     * Get details of action log entry types handled by this hook.
     *
     * @return array Map of handler data in standard format
     */
    public function get_handlers()
    {
        if (!addon_installed('authors')) {
            return array();
        }

        require_lang('authors');

        return array(
            'DEFINE_AUTHOR' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'author',
                'identifier_index' => 0,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'VIEW_AUTHOR' => '_SEARCH:authors:browse:{ID}',
                    'EDIT_THIS_AUTHOR' => '_SEARCH:cms_authors:_add:author={ID}',
                    'ADD_AUTHOR' => '_SEARCH:cms_authors:_add',
                ),
            ),
            'DELETE_AUTHOR' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'author',
                'identifier_index' => 0,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'ADD_AUTHOR' => '_SEARCH:cms_authors:_add',
                ),
            ),
            'MERGE_AUTHORS' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'author',
                'identifier_index' => 1,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'VIEW_AUTHOR' => '_SEARCH:authors:browse:{ID}',
                    'EDIT_THIS_AUTHOR' => '_SEARCH:cms_authors:_add:author={ID}',
                    'ADD_AUTHOR' => '_SEARCH:cms_authors:_add',
                ),
            ),
        );
    }

    /**
     * Get written context for an action log entry handled by this hook.
     *
     * @param  array $actionlog_row Action log row
     * @param  array $handler_data Handler data
     * @param  ?string $identifier Identifier (null: none)
     * @return string Written context
     */
    protected function get_written_context($actionlog_row, $handler_data, $identifier)
    {
        switch ($actionlog_row['the_type']) {
            case 'MERGE_AUTHORS':
                $written_context = do_lang('SOMETHING_TO', $actionlog_row['param_a'], $actionlog_row['param_b']);
                return $written_context;
        }

        return parent::get_written_context($actionlog_row, $handler_data, $identifier);
    }
}
