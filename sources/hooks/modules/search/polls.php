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
 * @package    polls
 */

/**
 * Hook class.
 */
class Hook_search_polls extends FieldsSearchHook
{
    /**
     * Find details for this search hook.
     *
     * @param  boolean $check_permissions Whether to check permissions.
     * @return ?array Map of search hook details (null: hook is disabled).
     */
    public function info($check_permissions = true)
    {
        if (!module_installed('polls')) {
            return null;
        }

        if ($check_permissions) {
            if (!has_actual_page_access(get_member(), 'polls')) {
                return null;
            }
        }

        if ($GLOBALS['SITE_DB']->query_select_value('poll', 'COUNT(*)') == 0) {
            return null;
        }

        require_lang('polls');

        $info = array();
        $info['lang'] = do_lang_tempcode('POLL_ARCHIVE');
        $info['default'] = true;
        $info['extra_sort_fields'] = $this->_get_extra_sort_fields('_poll');

        $info['permissions'] = array(
            array(
                'type' => 'zone',
                'zone_name' => get_module_zone('polls'),
            ),
            array(
                'type' => 'page',
                'zone_name' => get_module_zone('polls'),
                'page_name' => 'polls',
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
        return $this->_get_fields('_poll');
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
        $remapped_orderer = '';
        switch ($sort) {
            case 'average_rating':
            case 'compound_rating':
                $remapped_orderer = $sort . ':polls:id';
                break;

            case 'title':
                $remapped_orderer = 'question';
                break;

            case 'add_date':
                $remapped_orderer = 'add_time';
                break;
        }

        require_code('polls');
        require_lang('polls');
        require_css('polls');

        // Calculate our where clause (search)
        $sq = build_search_submitter_clauses('submitter', $author_id, $author);
        if (is_null($sq)) {
            return array();
        } else {
            $where_clause .= $sq;
        }
        $this->_handle_date_check($cutoff, 'add_time', $where_clause);

        $table = 'poll r';
        $trans_fields = array('r.question' => 'SHORT_TRANS__COMCODE', 'r.option1' => 'SHORT_TRANS__COMCODE', 'r.option2' => 'SHORT_TRANS__COMCODE', 'r.option3' => 'SHORT_TRANS__COMCODE', 'r.option4' => 'SHORT_TRANS__COMCODE', 'r.option5' => 'SHORT_TRANS__COMCODE');
        $nontrans_fields = array();
        $this->_get_search_parameterisation_advanced_for_content_type('_poll', $table, $where_clause, $trans_fields, $nontrans_fields);

        // Calculate and perform query
        $rows = get_search_rows(null, null, $content, $boolean_search, $boolean_operator, $only_search_meta, $direction, $max, $start, $only_titles, $table, $trans_fields, $where_clause, $content_where, $remapped_orderer, 'r.*', $nontrans_fields);

        $out = array();
        foreach ($rows as $i => $row) {
            $out[$i]['data'] = $row;
            unset($rows[$i]);
            if (($remapped_orderer != '') && (array_key_exists($remapped_orderer, $row))) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            } elseif (strpos($remapped_orderer, '_rating:') !== false) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            }
        }

        return $out;
    }

    /**
     * Run function for rendering a search result.
     *
     * @param  array $row The data row stored when we retrieved the result
     * @return Tempcode The output
     */
    public function render($row)
    {
        require_code('polls');
        return render_poll_box(true, $row);
    }
}
