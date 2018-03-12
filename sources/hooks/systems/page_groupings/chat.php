<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    chat
 */

/**
 * Hook class.
 */
class Hook_page_groupings_chat
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
        if (!addon_installed('chat')) {
            return array();
        }

        return array(
            array('cms', 'menu/social/chat/chat', array('cms_chat', array('type' => 'browse'), get_module_zone('cms_chat')), do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('chat:CHAT_MODERATION'), make_string_tempcode(escape_html(integer_format(intval($GLOBALS['SITE_DB']->query_select_value('chat_rooms', 'COUNT(*)')))))), 'chat:DOC_CHAT'),
            array('structure', 'menu/social/chat/chat', array('admin_chat', array('type' => 'browse'), get_module_zone('admin_chat')), do_lang_tempcode('chat:CHATROOMS'), 'chat:DOC_CHAT'),
            array('social', 'menu/social/chat/chat', array('chat', array(), get_module_zone('chat')), do_lang_tempcode('chat:CHAT_LOBBY')),
            // userguide_chatcode and popup_blockers are children of help_page
        );
    }
}
