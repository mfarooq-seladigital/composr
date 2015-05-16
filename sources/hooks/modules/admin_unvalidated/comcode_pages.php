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
 * @package    core_comcode_pages
 */

/**
 * Hook class.
 */
class Hook_unvalidated_comcode_pages
{
    /**
     * Find details on the unvalidated hook.
     *
     * @return ?array Map of hook info (null: hook is disabled).
     */
    public function info()
    {
        require_lang('zones');

        $info = array();
        $info['db_table'] = 'comcode_pages';
        $info['db_identifier'] = array('the_zone', 'the_page');
        $info['db_validated'] = 'p_validated';
        $info['db_add_date'] = 'p_add_date';
        $info['db_edit_date'] = 'p_edit_date';
        $info['edit_module'] = 'cms_comcode_pages';
        $info['edit_type'] = '_edit';
        $info['edit_identifier'] = 'page_link';
        $info['title'] = do_lang_tempcode('COMCODE_PAGE');

        return $info;
    }
}
