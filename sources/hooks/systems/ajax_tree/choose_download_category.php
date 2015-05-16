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
 * @package    downloads
 */

/**
 * Hook class.
 */
class Hook_choose_download_category
{
    /**
     * Run function for ajax-tree hooks. Generates XML for a tree list, which is interpreted by JavaScript and expanded on-demand (via new calls).
     *
     * @param  ?ID_TEXT $id The ID to do under (null: root)
     * @param  array $options Options being passed through
     * @param  ?ID_TEXT $default The ID to select by default (null: none)
     * @return string XML in the special category,entry format
     */
    public function run($id, $options, $default = null)
    {
        require_code('downloads');
        require_lang('downloads');

        $compound_list = array_key_exists('compound_list', $options) ? $options['compound_list'] : false;
        $addable_filter = array_key_exists('addable_filter', $options) ? ($options['addable_filter']) : false;
        $stripped_id = ($compound_list ? preg_replace('#,.*$#', '', $id) : $id);

        $tree = get_download_category_tree(is_null($id) ? null : intval($id), null, null, false, $compound_list, is_null($id) ? 0 : 1, $addable_filter);

        $levels_to_expand = array_key_exists('levels_to_expand', $options) ? ($options['levels_to_expand']) : intval(get_value('levels_to_expand__' . substr(get_class($this), 5), true));
        $options['levels_to_expand'] = max(0, $levels_to_expand - 1);

        if (!has_actual_page_access(null, 'downloads')) {
            $tree = array();
        }

        $out = '';

        $out .= '<options>' . serialize($options) . '</options>';

        if ($compound_list) {
            list($tree,) = $tree;
        }

        foreach ($tree as $t) {
            if ($compound_list) {
                $_id = $t['compound_list'];
            } else {
                $_id = strval($t['id']);
            }

            if ($stripped_id === strval($t['id'])) {
                continue; // Possible when we look under as a root
            }
            $title = $t['title'];
            $has_children = ($t['child_count'] != 0);
            $selectable = ((!$addable_filter) || $t['addable']);

            $tag = 'category'; // category
            $out .= '<' . $tag . ' id="' . xmlentities($_id) . '" title="' . xmlentities($title) . '" has_children="' . ($has_children ? 'true' : 'false') . '" selectable="' . ($selectable ? 'true' : 'false') . '"></' . $tag . '>';

            if ($levels_to_expand > 0) {
                $out .= '<expand>' . xmlentities($_id) . '</expand>';
            }
        }

        // Mark parent cats for pre-expansion
        if ((!is_null($default)) && ($default != '')) {
            $cat = intval($default);
            while (!is_null($cat)) {
                $out .= '<expand>' . strval($cat) . '</expand>';
                $cat = $GLOBALS['SITE_DB']->query_select_value_if_there('download_categories', 'parent_id', array('id' => $cat));
            }
        }

        $tag = 'result'; // result
        return '<' . $tag . '>' . $out . '</' . $tag . '>';
    }

    /**
     * Generate a simple selection list for the ajax-tree hook. Returns a normal <select> style <option>-list, for fallback purposes
     *
     * @param  ?ID_TEXT $id The ID to do under (null: root) - not always supported
     * @param  array $options Options being passed through
     * @param  ?ID_TEXT $it The ID to select by default (null: none)
     * @return tempcode The nice list
     */
    public function simple($id, $options, $it = null)
    {
        require_code('downloads');

        $compound_list = array_key_exists('compound_list', $options) ? $options['compound_list'] : false;
        $addable_filter = array_key_exists('addable_filter', $options) ? ($options['addable_filter']) : false;

        return create_selection_list_download_category_tree(is_null($it) ? null : intval($it), $compound_list, $addable_filter);
    }
}
