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
 * @package    core_cleanup_tools
 */

/**
 * Hook class.
 */
class Hook_task_find_orphaned_lang_strings
{
    /**
     * Run the task hook.
     *
     * @param  ?string $table The table to limit to (null: no limit)
     * @param  boolean $fix Whether to fix issues
     * @return ?array A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (null: show standard success message)
     */
    public function run($table = null, $fix = true)
    {
        require_lang('cleanup');

        push_db_scope_check(false);

        // When a language string isn't there
        $missing_lang_strings = array();
        $missing_lang_strings_zero = array();
        // When a language string isn't used
        $orphaned_lang_strings = array();
        // When a language string is used more than once
        $fused_lang_strings = array();

        $_all_ids = $GLOBALS['SITE_DB']->query_select('translate', array('DISTINCT id'));
        $all_ids = array();
        foreach ($_all_ids as $id) {
            $all_ids[$id['id']] = true;
        }

        $ids_seen_so_far = array();

        $where = array();
        if ($table !== null) {
            $where['m_table'] = $table;
        }

        $all_fields = $GLOBALS['SITE_DB']->query_select('db_meta', array('*'), $where);

        $langidfields = array();
        foreach ($all_fields as $f) {
            if (strpos($f['m_type'], '_TRANS') !== false) {
                $langidfields[] = array('m_name' => $f['m_name'], 'm_table' => $f['m_table']);
            }
        }
        foreach ($langidfields as $langidfield) {
            $select = array($langidfield['m_name']);
            foreach ($all_fields as $f) {
                if ((substr($f['m_type'], 0, 1) == '*') && ($f['m_table'] == $langidfield['m_table'])) {
                    $select[] = $f['m_name'];
                }
            }
            $ofs = $GLOBALS['SITE_DB']->query_select($langidfield['m_table'], $select, array(), '', null, 0, false, array());
            foreach ($ofs as $of) {
                $id = $of[$langidfield['m_name']];
                if ($id === null) {
                    continue;
                }

                if (!array_key_exists($id, $all_ids)) {
                    if (($fix) && (($id == 0) || (!array_key_exists($id, $missing_lang_strings)/*not fixed already*/))) {
                        $new_id = insert_lang($langidfield['m_name'], '', 2, null, false, $id);
                        if ($id[$langidfield['m_name']] != $new_id) {
                            $GLOBALS['SITE_DB']->query_update($langidfield['m_table'], $new_id, $of, '', 1);
                        }
                    }

                    $missing_lang_strings[$id] = true;
                    $missing_lang_strings_zero[json_encode($of)] = true;
                } elseif (array_key_exists($id, $ids_seen_so_far)) {
                    $looked_up = get_translated_text($id);
                    if ($fix) {
                        $_of = $GLOBALS['SITE_DB']->query_select($langidfield['m_table'], array('*'), $of, '', 1, 0, false, array());
                        $of = $_of[0];
                        $of_orig = $of;
                        $of = insert_lang($langidfield['m_name'], $looked_up, 2) + $of;
                        $GLOBALS['SITE_DB']->query_update($langidfield['m_table'], $of, $of_orig, '', 1);
                    }
                    $fused_lang_strings[$id] = $looked_up;
                }
                if ($langidfield['m_name'] != 't_cache_first_post') { // 'if..!=' is for special exception for one that may be re-used in a cache position
                    $ids_seen_so_far[$id] = true;
                }
            }
        }

        if ($table === null) {
            $orphaned_lang_strings = array_diff(array_keys($all_ids), array_keys($ids_seen_so_far));
            if ($fix) {
                foreach ($orphaned_lang_strings as $id) {
                    delete_lang($id);
                }
            }
        }

        $ret = do_template('BROKEN_LANG_STRINGS', array(
            '_GUID' => '318fcbe81e1e4324350114c3def020dd',
            'MISSING_LANG_STRINGS' => array_keys($missing_lang_strings),
            'MISSING_LANG_STRINGS_ZERO' => array_keys($missing_lang_strings_zero),
            'FUSED_LANG_STRINGS' => $fused_lang_strings,
            'ORPHANED_LANG_STRINGS' => $orphaned_lang_strings,
        ));
        return array('text/html', $ret);
    }
}
