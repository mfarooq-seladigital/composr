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
 * @package    tickets
 */

/**
 * Hook class.
 */
class Hook_cron_ticket_type_lead_times
{
    /**
     * Run function for CRON hooks. Searches for tasks to perform.
     */
    public function run()
    {
        if (!addon_installed('tickets')) {
            return;
        }

        $time = time();
        $last_time = intval(get_value('last_ticket_lead_time_calc', null, true));
        if ($last_time > time() - 24 * 60 * 60) {
            return;
        }
        set_value('last_ticket_lead_time_calc', strval($time), true);

        require_code('tickets');
        require_code('tickets2');
        require_lang('tickets');
        update_ticket_type_lead_times();
    }
}
