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
 * @package    cns_clubs
 */

/**
 * Hook class.
 */
class Hook_search_cns_clubs extends FieldsSearchHook
{
    /**
     * Find details for this search hook.
     *
     * @param  boolean $check_permissions Whether to check permissions.
     * @return ?array Map of search hook details (null: hook is disabled).
     */
    public function info($check_permissions = true)
    {
        if (get_forum_type() != 'cns') {
            return null;
        }

        if ($check_permissions) {
            if (!has_actual_page_access(get_member(), 'groups')) {
                return null;
            }
        }

        if ($GLOBALS['FORUM_DB']->query_select_value('f_groups', 'COUNT(*)', array('g_is_private_club' => 1)) == 0) {
            return null;
        }

        require_lang('cns');

        $info = array();
        $info['lang'] = do_lang_tempcode('CLUBS');
        $info['default'] = false;
        $info['extra_sort_fields'] = $this->_get_extra_sort_fields('_group');

        $info['permissions'] = array(
            array(
                'type' => 'zone',
                'zone_name' => get_module_zone('groups'),
            ),
            array(
                'type' => 'page',
                'zone_name' => get_module_zone('groups'),
                'page_name' => 'groups',
            ),
        );

        return $info;
    }

    /**
     * Get a list of extra fields to ask for.
     *
     * @return array A list of maps specifying extra fields
     */
    public function get_fields()
    {
        return $this->_get_fields('_group');
    }

    /**
     * Run function for search results.
     *
     * @param  string $content Search string
     * @param  boolean $only_search_meta Whether to only do a META (tags) search
     * @param  ID_TEXT $direction Order direction
     * @param  integer $max Start position in total results
     * @param  integer $start Maximum results to return in total
     * @param  boolean $only_titles Whether only to search titles (as opposed to both titles and content)
     * @param  string $content_where Where clause that selects the content according to the main search string (SQL query fragment) (blank: full-text search)
     * @param  SHORT_TEXT $author Username/Author to match for
     * @param  ?MEMBER $author_id Member-ID to match for (null: unknown)
     * @param  mixed $cutoff Cutoff date (TIME or a pair representing the range)
     * @param  string $sort The sort type (gets remapped to a field in this function)
     * @set    title add_date
     * @param  integer $limit_to Limit to this number of results
     * @param  string $boolean_operator What kind of boolean search to do
     * @set    or and
     * @param  string $where_clause Where constraints known by the main search code (SQL query fragment)
     * @param  string $search_under Comma-separated list of categories to search under
     * @param  boolean $boolean_search Whether it is a boolean search
     * @return array List of maps (template, orderer)
     */
    public function run($content, $only_search_meta, $direction, $max, $start, $only_titles, $content_where, $author, $author_id, $cutoff, $sort, $limit_to, $boolean_operator, $where_clause, $search_under, $boolean_search)
    {
        if (get_forum_type() != 'cns') {
            return array();
        }

        $remapped_orderer = '';
        switch ($sort) {
            case 'title':
                $remapped_orderer = 'g_name';
                break;
        }

        require_lang('cns');

        // Calculate our where clause (search)
        $sq = build_search_submitter_clauses('g_group_leader', $author_id, $author);
        if (is_null($sq)) {
            return array();
        } else {
            $where_clause .= $sq;
        }

        $where_clause .= ' AND ';
        $where_clause .= 'g_hidden=0 AND g_is_private_club=1';

        $table = 'f_groups r';
        $trans_fields = array('!' => '!', 'r.g_name' => 'SHORT_TRANS', 'r.g_title' => 'SHORT_TRANS');
        $nontrans_fields = array();
        $this->_get_search_parameterisation_advanced_for_content_type('_group', $table, $where_clause, $trans_fields, $nontrans_fields);

        // Calculate and perform query
        $rows = get_search_rows(null, null, $content, $boolean_search, $boolean_operator, $only_search_meta, $direction, $max, $start, $only_titles, $table, $trans_fields, $where_clause, $content_where, $remapped_orderer, 'r.*', $nontrans_fields);

        $out = array();
        foreach ($rows as $i => $row) {
            $out[$i]['data'] = $row;
            unset($rows[$i]);
            if (($remapped_orderer != '') && (array_key_exists($remapped_orderer, $row))) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            }
        }

        return $out;
    }

    /**
     * Run function for rendering a search result.
     *
     * @param  array $row The data row stored when we retrieved the result
     * @return tempcode The output
     */
    public function render($row)
    {
        $leader = $GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['g_group_leader']);

        require_code('cns_groups');
        $group_name = cns_get_group_name($row['id']);

        require_code('cns_groups2');
        $num_members = cns_get_group_members_raw_count($row['id'], false, false, true, false);

        $title = do_lang('CONTENT_IS_OF_TYPE', do_lang('CLUB'), $group_name);

        $summary = do_lang_tempcode(($row['g_open_membership'] == 1) ? 'CLUB_WITH_MEMBERS_OPEN' : 'CLUB_WITH_MEMBERS_APPROVAL', escape_html($group_name), escape_html(integer_format($num_members)), $leader);

        $url = build_url(array('page' => 'groups', 'type' => 'view', 'id' => $row['id']), get_module_zone('groups'));

        return do_template('SIMPLE_PREVIEW_BOX', array(
            '_GUID' => '2f7814a2e1f868d2ac73fba69f3aeee1',
            'ID' => strval($row['id']),
            'TITLE' => $title,
            'SUMMARY' => $summary,
            'URL' => $url,
        ));
    }
}
