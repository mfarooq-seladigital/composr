<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    workflows
 */

/**
 * Hook class.
 */
class Hook_page_groupings_workflows
{
    /**
     * Run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
     *
     * @param  ?MEMBER $member_id Member ID to run as (null: current member)
     * @param  boolean $extensive_docs Whether to use extensive documentation tooltips, rather than short summaries
     * @return array List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
     */
    public function run($member_id = null, $extensive_docs = false)
    {
        return array(
            array('setup', 'menu/workflows', array('admin_workflow', array('type' => 'browse'), get_module_zone('admin_workflow')), do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('workflows:WORKFLOWS'), make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value('workflows', 'COUNT(*)'))))), 'workflows:DOC_WORKFLOWS'),
        );
    }
}
