<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

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
class Hook_notification_pointstore_request_custom extends Hook_notification__Staff
{
    /**
     * Find the initial setting that members have for a notification code (only applies to the member_could_potentially_enable members).
     *
     * @param  ID_TEXT $notification_code Notification code
     * @param  ?SHORT_TEXT $category The category within the notification code (null: none)
     * @return integer Initial setting
     */
    public function get_initial_setting($notification_code, $category = null)
    {
        return A_NA;
    }

    /**
     * Find whether a handled notification code supports categories.
     * (Content types, for example, will define notifications on specific categories, not just in general. The categories are interpreted by the hook and may be complex. E.g. it might be like a regexp match, or like FORUM:3 or TOPIC:100)
     *
     * @param  ID_TEXT $notification_code Notification code
     * @return boolean Whether it does
     */
    public function supports_categories($notification_code)
    {
        return true;
    }

    /**
     * Standard function to create the standardised category tree
     *
     * @param  ID_TEXT $notification_code Notification code
     * @param  ?ID_TEXT $id The ID of where we're looking under (null: N/A)
     * @return array Tree structure
     */
    public function create_category_tree($notification_code, $id)
    {
        $page_links = array();

        $types = $GLOBALS['SITE_DB']->query_select('pstore_customs', array('id', 'c_title'));
        foreach ($types as $type) {
            $page_links[] = array(
                'id' => $type['id'],
                'title' => get_translated_text($type['c_title']),
            );
        }
        sort_maps_by($page_links, 'title');

        return $page_links;
    }

    /**
     * Get a list of all the notification codes this hook can handle.
     * (Addons can define hooks that handle whole sets of codes, so hooks are written so they can take wide authority)
     *
     * @return array List of codes (mapping between code names, and a pair: section and labelling for those codes)
     */
    public function list_handled_codes()
    {
        $list = array();
        $list['pointstore_request_custom'] = array(do_lang('POINTSTORE'), do_lang('pointstore:NOTIFICATION_TYPE_pointstore_request_custom'));
        return $list;
    }
}
