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
 * @package    cns_forum
 */

/**
 * Hook class.
 */
class Hook_cleanup_cns_topics
{
    /**
     * Find details about this cleanup hook.
     *
     * @return ?array Map of cleanup hook info (null: hook is disabled).
     */
    public function info()
    {
        if (get_forum_type() != 'cns') {
            return null;
        } else {
            cns_require_all_forum_stuff();
        }

        require_lang('cns');

        $info = array();
        $info['title'] = do_lang_tempcode('FORUM_TOPICS');
        $info['description'] = do_lang_tempcode('DESCRIPTION_CACHE_TOPICS');
        $info['type'] = 'cache';

        return $info;
    }

    /**
     * Run the cleanup hook action.
     *
     * @return tempcode Results
     */
    public function run()
    {
        if (get_forum_type() != 'cns') {
            return new Tempcode();
        }

        require_code('tasks');
        return call_user_func_array__long_task(do_lang('CACHE_TOPICS'), null, 'cns_topics_recache');
    }
}
