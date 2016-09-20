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
 * @package    core_adminzone_dashboard
 */

/**
 * Hook class.
 */
class Hook_snippet_checklist_task_manage
{
    /**
     * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
     *
     * @return Tempcode The snippet
     */
    public function run()
    {
        $type = post_param_string('type');

        if (!has_zone_access(get_member(), 'adminzone')) {
            return new Tempcode();
        }

        decache('main_staff_checklist');

        require_lang('staff_checklist');

        switch ($type) {
            case 'add':
                $recur_interval = post_param_integer('recur_interval', 0);

                $task_title = post_param_string('task_title', false, true);

                $id = $GLOBALS['SITE_DB']->query_insert('staff_checklist_cus_tasks', array(
                    'task_title' => $task_title,
                    'add_date' => time(),
                    'recur_interval' => $recur_interval,
                    'recur_every' => post_param_string('recur_every'),
                    'task_is_done' => null,
                ), true);

                require_code('notifications');
                $subject = do_lang('CT_NOTIFICATION_MAIL_SUBJECT', get_site_name(), $task_title);
                $mail = do_notification_lang('CT_NOTIFICATION_MAIL', comcode_escape(get_site_name()), comcode_escape($task_title));
                dispatch_notification('checklist_task', null, $subject, $mail);

                log_it('SITE_WATCHLIST');

                log_it('CHECK_LIST_ADD', strval($id), $task_title);

                return do_template('BLOCK_MAIN_STAFF_CHECKLIST_CUSTOM_TASK', array(
                    '_GUID' => 'e95228a3740dc7eda2d1b0ccc7d3d9d3',
                    'TASK_TITLE' => comcode_to_tempcode(post_param_string('task_title', false, true)),
                    'ADD_DATE' => display_time_period(time()),
                    'RECUR_INTERVAL' => ($recur_interval == 0) ? '' : integer_format($recur_interval),
                    'RECUR_EVERY' => post_param_string('recur_every'),
                    'TASK_DONE' => 'not_completed',
                    'ID' => strval($id),
                ));

            case 'delete':
                $id = post_param_integer('id');
                $task_title = $GLOBALS['SITE_DB']->query_select_value_if_there('staff_checklist_cus_tasks', 'task_title', array('id' => $id));
                if ($task_title !== null) {
                    $GLOBALS['SITE_DB']->query_delete('staff_checklist_cus_tasks', array(
                        'id' => $id,
                    ), '', 1);

                    log_it('CHECK_LIST_DELETE', strval($id), $task_title);
                }

                break;

            case 'mark_done':
                $id = post_param_integer('id');
                $task_title = $GLOBALS['SITE_DB']->query_select_value('staff_checklist_cus_tasks', 'task_title', array('id' => $id));

                $GLOBALS['SITE_DB']->query_update('staff_checklist_cus_tasks', array('task_is_done' => time()), array('id' => $id), '', 1);

                log_it('CHECK_LIST_MARK_DONE', strval($id), $task_title);

                break;

            case 'mark_undone':
                $id = post_param_integer('id');
                $task_title = $GLOBALS['SITE_DB']->query_select_value('staff_checklist_cus_tasks', 'task_title', array('id' => $id));

                $GLOBALS['SITE_DB']->query_update('staff_checklist_cus_tasks', array('task_is_done' => null), array('id' => $id), '', 1);

                log_it('CHECK_LIST_MARK_UNDONE', strval($id), $task_title);

                break;
        }

        return new Tempcode();
    }
}
