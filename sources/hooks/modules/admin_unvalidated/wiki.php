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
 * @package    wiki
 */

/**
 * Hook class.
 */
class Hook_unvalidated_wiki
{
    /**
     * Find details on the unvalidated hook.
     *
     * @return ?array Map of hook info (null: hook is disabled).
     */
    public function info()
    {
        if (!module_installed('wiki')) {
            return null;
        }

        require_lang('wiki');

        $info = array();
        $info['db_table'] = 'wiki_posts';
        $info['db_identifier'] = 'id';
        $info['db_validated'] = 'validated';
        $info['db_title'] = 'the_message';
        $info['db_title_dereference'] = true;
        $info['db_add_date'] = 'date_and_time';
        $info['db_edit_date'] = 'edit_date';
        $info['edit_module'] = 'wiki';
        $info['edit_type'] = 'post';
        $info['edit_identifier'] = 'post_id';
        $info['title'] = do_lang_tempcode('WIKI');
        $info['is_minor'] = true;

        return $info;
    }
}
