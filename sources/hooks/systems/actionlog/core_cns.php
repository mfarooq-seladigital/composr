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
 * @package    core_cns
 */

/**
 * Hook class.
 */
class Hook_actionlog_core_cns extends Hook_actionlog
{
    /**
     * Get details of action log entry types handled by this hook.
     *
     * @return array Map of handler data in standard format
     */
    public function get_handlers()
    {
        if (get_forum_type() != 'cns') {
            return array();
        }

        require_lang('cns');

        return array(
            'ADD_EMOTICON' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'emoticon',
                'identifier_index' => 0,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'EDIT_THIS_EMOTICON' => '_SEARCH:admin_cns_emoticons:_edit:{ID}',
                    'ADD_EMOTICON' => '_SEARCH:admin_cns_emoticons:add',
                ),
            ),
            'EDIT_EMOTICON' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'emoticon',
                'identifier_index' => 0,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'EDIT_THIS_EMOTICON' => '_SEARCH:admin_cns_emoticons:_edit:{ID}',
                    'ADD_EMOTICON' => '_SEARCH:admin_cns_emoticons:add',
                ),
            ),
            'DELETE_EMOTICON' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'emoticon',
                'identifier_index' => 0,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'ADD_EMOTICON' => '_SEARCH:admin_cns_emoticons:add',
                ),
            ),
            'IMPORT_EMOTICONS' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'EMOTICONS' => '_SEARCH:admin_cns_emoticons',
                ),
            ),
            'ADD_GROUP' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'group',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW' => '_SEARCH:groups:view:{ID}',
                    'EDIT_THIS_GROUP' => '_SEARCH:admin_cns_groups:_edit:{ID}',
                    'ADD_GROUP' => '_SEARCH:admin_cns_groups:add',
                ),
            ),
            'EDIT_GROUP' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'group',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW' => '_SEARCH:groups:view:{ID}',
                    'EDIT_THIS_GROUP' => '_SEARCH:admin_cns_groups:_edit:{ID}',
                    'ADD_GROUP' => '_SEARCH:admin_cns_groups:add',
                ),
            ),
            'DELETE_GROUP' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'group',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'ADD_GROUP' => '_SEARCH:admin_cns_groups:add',
                ),
            ),
            'MEMBER_PROMOTED_AUTOMATICALLY' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                ),
            ),
            'MEMBER_ADDED_TO_GROUP' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                    'USERGROUP' => '_SEARCH:groups:view:{1}',
                ),
            ),
            'MEMBER_PRIMARY_GROUP_CHANGED' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                    'USERGROUP' => '_SEARCH:groups:view:{1}',
                ),
            ),
            'MEMBER_REMOVED_FROM_GROUP' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                    'USERGROUP' => '_SEARCH:groups:view:{1}',
                ),
            ),
            'IMPORT_MEMBER_CSV' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'MEMBERS' => '_SEARCH:admin_cns_members',
                ),
            ),
            'DOWNLOAD_MEMBER_CSV' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'MEMBERS' => '_SEARCH:admin_cns_members',
                ),
            ),
            'ADD_MEMBER' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                    'ADD_MEMBER' => '_SEARCH:admin_cns_members:step1',
                ),
            ),
            'EDIT_MEMBER_PROFILE' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                ),
            ),
            'DELETE_MEMBER' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'MEMBER_DIRECTORY' => '_SEARCH:members',
                ),
            ),
            'DELETE_LURKERS' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'DELETE_LURKERS' => '_SEARCH:admin_cns_members:delurk',
                ),
            ),
            'MERGE_MEMBERS' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 1,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'MERGE_MEMBERS' => '_SEARCH:admin_cns_merge_members',
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                ),
            ),
            'BAN_MEMBER' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                ),
            ),
            'UNBAN_MEMBER' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                ),
            ),
            'LOST_PASSWORD' => array(
                'flags' => ACTIONLOG_FLAGS_NONE | ACTIONLOG_FLAG__USER_ACTION,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => null,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                ),
            ),
            'RESET_PASSWORD' => array(
                'flags' => ACTIONLOG_FLAGS_NONE | ACTIONLOG_FLAG__USER_ACTION,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
                ),
            ),
            'EMAIL' => array(
                'flags' => ACTIONLOG_FLAGS_NONE | ACTIONLOG_FLAG__USER_ACTION,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => '_SEARCH:members:view:{ID}',
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
            case 'MEMBER_ADDED_TO_GROUP':
            case 'MEMBER_REMOVED_FROM_GROUP':
            case 'MEMBER_PRIMARY_GROUP_CHANGED':
                $username = $GLOBALS['FORUM_DRIVER']->get_username(intval($actionlog_row['param_a']));
                if ($username === null) {
                    $username = '#' . $actionlog_row['param_a'];
                }

                $_usergroup = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups', 'g_name', array('id' => intval($actionlog_row['param_b'])));
                if ($_usergroup === null) {
                    $usergroup = '#' . $actionlog_row['param_b'];
                } else {
                    $usergroup = get_translated_text($_usergroup, $GLOBALS['FORUM_DB']);
                }

                $written_context = do_lang('SOMETHING_TO', $username, $usergroup);
                return $written_context;

            case 'MERGE_MEMBERS':
                $written_context = do_lang('SOMETHING_TO', $actionlog_row['param_a'], $actionlog_row['param_b']);
                return $written_context;
        }

        return parent::get_written_context($actionlog_row, $handler_data, $identifier);
    }
}
