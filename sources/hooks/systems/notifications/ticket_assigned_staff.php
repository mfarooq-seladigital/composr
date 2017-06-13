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
 * @package    tickets
 */

/**
 * Hook class.
 */
class Hook_notification_ticket_assigned_staff extends Hook_Notification
{
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

        $tickets = $GLOBALS['SITE_DB']->query_select('notifications_enabled', array('l_code_category'), array('l_notification_code' => 'ticket_assigned_staff', 'l_member_id' => get_member()), 'ORDER BY id DESC', 200/*reasonable limit*/);
        if (count($tickets) == 200) {
            $types2 = array(); // Too many to consider
        }

        require_code('tickets');
        require_code('tickets2');

        foreach ($tickets as $ticket) {
            $details = get_ticket_details($ticket['l_code_category'], false);
            if (is_null($details)) {
                continue;
            }
            list($ticket_title) = $details;

            $page_links[] = array(
                'id' => $ticket['l_code_category'],
                'title' => $ticket_title,
            );
        }

        return $page_links;
    }

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
     * Find a bitmask of settings (email, SMS, etc) a notification code supports for listening on.
     *
     * @param  ID_TEXT $notification_code Notification code
     * @return integer Allowed settings
     */
    public function allowed_settings($notification_code)
    {
        return A__ALL & ~A_INSTANT_PT;
    }

    /**
     * Find the setting that members have for a notification code if they have done some action triggering automatic setting (e.g. posted within a topic).
     *
     * @param  ID_TEXT $notification_code Notification code
     * @param  ?SHORT_TEXT $category The category within the notification code (null: none)
     * @return integer Automatic setting
     */
    public function get_default_auto_setting($notification_code, $category = null)
    {
        return A__STATISTICAL;
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
        $list['ticket_assigned_staff'] = array(do_lang('MESSAGES'), do_lang('tickets:NOTIFICATION_TYPE_ticket_assigned_staff'));
        return $list;
    }

    /**
     * Get a list of members who have enabled this notification (i.e. have permission to AND have chosen to or are defaulted to).
     *
     * @param  ID_TEXT $notification_code Notification code
     * @param  ?SHORT_TEXT $category The category within the notification code (null: none)
     * @param  ?array $to_member_ids List of member IDs we are restricting to (null: no restriction). This effectively works as a intersection set operator against those who have enabled.
     * @param  integer $start Start position (for pagination)
     * @param  integer $max Maximum (for pagination)
     * @return array A pair: Map of members to their notification setting, and whether there may be more
     */
    public function list_members_who_have_enabled($notification_code, $category = null, $to_member_ids = null, $start = 0, $max = 300)
    {
        $members = $this->_all_members_who_have_enabled($notification_code, $category, $to_member_ids, $start, $max, false);
        $members = $this->_all_members_who_have_enabled_with_privilege($members, 'support_operator', $notification_code, $category, $to_member_ids, $start, $max);

        unset($members[0][get_member()]); // Don't e-mail originator of the notification

        return $members;
    }

    /**
     * Find whether a member could enable this notification (i.e. have permission to).
     *
     * @param  ID_TEXT $notification_code Notification code
     * @param  MEMBER $member_id Member to check against
     * @param  ?SHORT_TEXT $category The category within the notification code (null: none)
     * @return boolean Whether they could
     */
    public function member_could_potentially_enable($notification_code, $member_id, $category = null)
    {
        return $this->_is_staff(null, null, $member_id);
    }

    /**
     * Find whether a member has enabled this notification (i.e. have permission to AND have chosen to or are defaulted to).
     * (Separate implementation to list_members_who_have_enabled, for performance reasons.)
     *
     * @param  ID_TEXT $notification_code Notification code
     * @param  MEMBER $member_id Member to check against
     * @param  ?SHORT_TEXT $category The category within the notification code (null: none)
     * @return boolean Whether they are
     */
    public function member_has_enabled($notification_code, $member_id, $category = null)
    {
        if ($member_id == get_member()) {
            return false;
        }

        return $this->_is_staff($notification_code, $category, $member_id);
    }

    /**
     * Find whether someone has permission to view staff notifications and possibly if they actually are.
     *
     * @param  ?ID_TEXT $only_if_enabled_on__notification_code Notification code (null: don't check if they are)
     * @param  ?SHORT_TEXT $only_if_enabled_on__category The category within the notification code (null: none)
     * @param  MEMBER $member_id Member to check against
     * @return boolean Whether they do
     */
    protected function _is_staff($only_if_enabled_on__notification_code, $only_if_enabled_on__category, $member_id)
    {
        $test = is_null($only_if_enabled_on__notification_code) ? true : notifications_enabled($only_if_enabled_on__notification_code, $only_if_enabled_on__category, $member_id);

        require_code('permissions');
        return (($test) && (has_privilege($member_id, 'support_operator')));
    }
}
