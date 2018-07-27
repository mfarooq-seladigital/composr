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
 * @package    securitylogging
 */

/**
 * Hook class.
 */
class Hook_actionlog_securitylogging extends Hook_actionlog
{
    /**
     * Get details of action log entry types handled by this hook.
     *
     * @return array Map of handler data in standard format
     */
    public function get_handlers()
    {
        if (!addon_installed('securitylogging')) {
            return array();
        }

        require_lang('submitban');

        return array(
            'IP_BANNED' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'IP_BANS' => '_SEARCH:admin_ip_ban',
                    'VIEW_ACTIONLOGS' => '_SEARCH:admin_actionlog',
                    'INVESTIGATE_USER' => '_SEARCH:admin_lookup:param={0}',
                ),
            ),
            'IP_UNBANNED' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'IP_BANS' => '_SEARCH:admin_ip_ban',
                    'VIEW_ACTIONLOGS' => '_SEARCH:admin_actionlog',
                    'INVESTIGATE_USER' => '_SEARCH:admin_lookup:param={0}',
                ),
            ),
            'SYNDICATED_IP_BAN' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'IP_BANS' => '_SEARCH:admin_ip_ban',
                    'VIEW_ACTIONLOGS' => '_SEARCH:admin_actionlog',
                    'INVESTIGATE_USER' => '_SEARCH:admin_lookup:param={0}',
                ),
            ),
            'MADE_IP_BANNABLE' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'IP_BANS' => '_SEARCH:admin_ip_ban',
                    'INVESTIGATE_USER' => '_SEARCH:admin_lookup:param={0}',
                ),
            ),
            'MADE_IP_UNBANNABLE' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => null,
                'identifier_index' => null,
                'written_context_index' => 0,
                'followup_page_links' => array(
                    'IP_BANS' => '_SEARCH:admin_ip_ban',
                    'INVESTIGATE_USER' => '_SEARCH:admin_lookup:param={0}',
                ),
            ),
            'SUBMITTER_BANNED' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => array('FORUM_DRIVER__PROFILE_URL', '{ID}'),
                    'VIEW_ACTIONLOGS' => '_SEARCH:admin_actionlog',
                    'INVESTIGATE_USER' => '_SEARCH:admin_lookup:param={0}',
                ),
            ),
            'SUBMITTER_UNBANNED' => array(
                'flags' => ACTIONLOG_FLAGS_NONE,
                'cma_hook' => 'member',
                'identifier_index' => 0,
                'written_context_index' => 1,
                'followup_page_links' => array(
                    'VIEW_PROFILE' => array('FORUM_DRIVER__PROFILE_URL', '{ID}'),
                    'VIEW_ACTIONLOGS' => '_SEARCH:admin_actionlog',
                    'INVESTIGATE_USER' => '_SEARCH:admin_lookup:param={0}',
                ),
            ),
        );
    }
}
