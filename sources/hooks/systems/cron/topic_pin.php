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
class Hook_cron_topic_pin
{
    /**
     * Run function for CRON hooks. Searches for tasks to perform.
     */
    public function run()
    {
        $_last_run = get_value('last_time_cron_topic_pin', null, true);
        $last_run = is_null($_last_run) ? 0 : intval($_last_run);
        if ($last_run < time() - 60 * 60 * 6) {
            $time = time();
            $sql = 'SELECT details FROM ' . get_table_prefix() . 'sales WHERE ' . db_string_equal_to('purchasetype', 'TOPIC_PINNING') . ' AND ' . db_string_not_equal_to('details2', '') . ' AND date_and_time<' . strval($time) . '-details2*24*60*60' . ' AND date_and_time>' . strval($last_run) . '-details2*24*60*60';
            $rows = $GLOBALS['SITE_DB']->query($sql);
            foreach ($rows as $row) {
                $GLOBALS['FORUM_DRIVER']->pin_topic(intval($row['details']), false);
            }
            set_value('last_time_cron_topic_pin', strval($time), true);
        }
    }
}
