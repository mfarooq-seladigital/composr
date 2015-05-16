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
 * @package    quizzes
 */

/**
 * Hook class.
 */
class Hook_unvalidated_quiz
{
    /**
     * Find details on the unvalidated hook.
     *
     * @return ?array Map of hook info (null: hook is disabled).
     */
    public function info()
    {
        if (!module_installed('quiz')) {
            return null;
        }

        require_lang('quiz');

        $info = array();
        $info['db_table'] = 'quizzes';
        $info['db_identifier'] = 'id';
        $info['db_validated'] = 'q_validated';
        $info['db_title'] = 'q_name';
        $info['db_title_dereference'] = true;
        $info['db_add_date'] = 'q_add_date';
        $info['db_edit_date'] = 'q_add_date';
        $info['edit_module'] = 'cms_quiz';
        $info['edit_type'] = '_edit';
        $info['edit_identifier'] = 'id';
        $info['title'] = do_lang_tempcode('QUIZZES');

        return $info;
    }
}
