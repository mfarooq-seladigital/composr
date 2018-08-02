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
 * @package    filedump
 */

/**
 * Hook class.
 */
class Hook_actionlog_filedump extends Hook_actionlog
{
    /**
     * Get details of action log entry types handled by this hook.
     *
     * @return array Map of handler data in standard format
     */
    public function get_handlers()
    {
        if (!addon_installed('filedump')) {
            return array();
        }

        require_lang('filedump');

        return array(
            'FILEDUMP_CREATE_FOLDER' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'FOLDER' => '_SEARCH:filedump:place={1}',
                    '_FILEDUMP' => '_SEARCH:filedump',
                ),
            ),
            'FILEDUMP_DELETE_FOLDER' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'FOLDER' => '_SEARCH:filedump:place={1}',
                    '_FILEDUMP' => '_SEARCH:filedump',
                ),
            ),
            'FILEDUMP_UPLOAD' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'FOLDER' => '_SEARCH:filedump:place={1}',
                    '_FILEDUMP' => '_SEARCH:filedump',
                ),
            ),
            'FILEDUMP_MOVE' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'FOLDER' => '_SEARCH:filedump:place={DIR}',
                    '_FILEDUMP' => '_SEARCH:filedump',
                ),
            ),
            'FILEDUMP_DELETE_FILE' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'FOLDER' => '_SEARCH:filedump:place={1}',
                    '_FILEDUMP' => '_SEARCH:filedump',
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
            case 'FILEDUMP_CREATE_FOLDER':
            case 'FILEDUMP_DELETE_FOLDER':
            case 'FILEDUMP_UPLOAD':
            case 'FILEDUMP_DELETE_FILE':
                $path = trim($actionlog_row['param_b'], '/');
                $written_context = ($path == '') ? '' : ($path . '/') . $actionlog_row['param_a'];
                return $written_context;

            case 'FILEDUMP_MOVE':
                $path = trim($actionlog_row['param_b'], '/');
                $written_context = do_lang('SOMETHING_TO', $actionlog_row['param_a'], ($path == '') ? do_lang('ROOT') : $path);
                return $written_context;
        }

        return parent::get_written_context($actionlog_row, $handler_data, $identifier);
    }

    /**
     * Get details of action log entry types handled by this hook.
     *
     * @param  array $actionlog_row Action log row
     * @param  ?string $identifier The identifier associated with this action log entry (null: unknown / none)
     * @param  ?string $written_context The written context associated with this action log entry (null: unknown / none)
     * @param  array $bindings Default bindings
     */
    protected function get_extended_actionlog_bindings($actionlog_row, $identifier, $written_context, &$bindings)
    {
        switch ($actionlog_row['the_type']) {
            case 'FILEDUMP_MOVE':
                $path = trim($actionlog_row['param_b'], '/');
                $bindings += array(
                    'DIR' => (strpos($path, '/') !== false) ? dirname($path) : '',
                );
                break;
        }
    }
}
