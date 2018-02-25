<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    news
 */

/**
 * Block class.
 */
class Block_bottom_news
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled)
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array('param', 'select', 'select_and', 'zone', 'blogs', 'as_guest', 'check');
        return $info;
    }

    /**
     * Find caching details for the block.
     *
     * @return ?array Map of cache details (cache_on and ttl) (null: block is disabled)
     */
    public function caching_environment()
    {
        $info = array();
        $info['cache_on'] = 'array(array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false,array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'news\'),array_key_exists(\'select\',$map)?$map[\'select\']:\'\',array_key_exists(\'param\',$map)?intval($map[\'param\']):5,array_key_exists(\'blogs\',$map)?$map[\'blogs\']:\'-1\',array_key_exists(\'select_and\',$map)?$map[\'select_and\']:\'\',array_key_exists(\'check\',$map)?($map[\'check\']==\'1\'):true)';
        $info['special_cache_flags'] = CACHE_AGAINST_DEFAULT | CACHE_AGAINST_PERMISSIVE_GROUPS;
        if (addon_installed('content_privacy')) {
            $info['special_cache_flags'] |= CACHE_AGAINST_MEMBER;
        }
        $info['ttl'] = (get_value('disable_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 15;
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters
     * @return Tempcode The result of execution
     */
    public function run($map)
    {
        $block_id = get_block_id($map);

        $check_perms = array_key_exists('check', $map) ? ($map['check'] == '1') : true;

        $max = empty($map['param']) ? 5 : intval($map['param']);
        $zone = array_key_exists('zone', $map) ? $map['zone'] : get_module_zone('news');
        $blogs = array_key_exists('blogs', $map) ? intval($map['blogs']) : -1;
        $select_and = array_key_exists('select_and', $map) ? $map['select_and'] : '';
        require_lang('news');

        $content = new Tempcode();

        // News Query
        $select = array_key_exists('select', $map) ? $map['select'] : '*';
        if ($select == '*') {
            $q_filter = '1=1';
        } else {
            require_code('selectcode');
            $selects_1 = selectcode_to_sqlfragment($select, 'p.news_category', 'news_categories', null, 'p.news_category', 'id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
            $selects_2 = selectcode_to_sqlfragment($select, 'd.news_entry_category', 'news_categories', null, 'd.news_category', 'id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
            $q_filter = '(' . $selects_1 . ' OR ' . $selects_2 . ')';
        }
        if ($blogs === 0) {
            if ($q_filter != '') {
                $q_filter .= ' AND ';
            }
            $q_filter .= 'nc_owner IS NULL';
        } elseif ($blogs === 1) {
            if ($q_filter != '') {
                $q_filter .= ' AND ';
            }
            $q_filter .= '(nc_owner IS NOT NULL)';
        }
        if ($blogs != -1) {
            $join = ' LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news_categories c ON c.id=p.news_category';
        } else {
            $join = '';
        }

        if ($select_and != '') {
            require_code('selectcode');
            $selects_and_1 = selectcode_to_sqlfragment($select_and, 'p.news_category', 'news_categories', null, 'p.news_category', 'id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
            $selects_and_2 = selectcode_to_sqlfragment($select_and, 'd.news_entry_category', 'news_categories', null, 'd.news_category', 'id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
            $q_filter .= ' AND (' . $selects_and_1 . ' OR ' . $selects_and_2 . ')';
        }

        if (addon_installed('content_privacy')) {
            require_code('content_privacy');
            $as_guest = array_key_exists('as_guest', $map) ? ($map['as_guest'] == '1') : false;
            $viewing_member_id = $as_guest ? $GLOBALS['FORUM_DRIVER']->get_guest_id() : null;
            list($privacy_join, $privacy_where) = get_privacy_where_clause('news', 'p', $viewing_member_id);
            $join .= $privacy_join;
            $q_filter .= $privacy_where;
        }

        if (get_option('filter_regions') == '1') {
            require_code('locations');
            $q_filter .= sql_region_filter('news', 'p.id');
        }

        if ((!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) && ($check_perms)) {
            $join .= get_permission_join_clause('news', 'news_category', 'a', 'ma', 'p');
            $q_filter .= get_permission_where_clause(get_member(), get_permission_where_clause_groups(get_member()));
        }

        $news = $GLOBALS['SITE_DB']->query('SELECT p.* FROM ' . get_table_prefix() . 'news p LEFT JOIN ' . get_table_prefix() . 'news_category_entries d ON d.news_entry=p.id' . $join . ' WHERE ' . $q_filter . ' AND validated=1 ORDER BY date_and_time DESC', $max, 0, false, true);

        $_postdetailss = array();

        foreach ($news as $item) {
            $url_map = array('page' => 'news', 'type' => 'view', 'id' => $item['id']);
            if ($select != '*') {
                $url_map['select'] = $select;
            }
            if (($select_and != '*') && ($select_and != '')) {
                $url_map['select_and'] = $select_and;
            }
            if ($blogs === 1) {
                $url_map['blog'] = 1;
            }
            $full_url = build_url($url_map, $zone);

            $just_news_row = db_map_restrict($item, array('id', 'title', 'news', 'news_article'));
            $_title = get_translated_tempcode('news', $just_news_row, 'title');
            $date = get_timezoned_date_tempcode($item['date_and_time']);

            $_postdetailss[] = array('DATE' => $date, 'FULL_URL' => $full_url, 'NEWS_TITLE' => $_title);
        }

        return do_template('BLOCK_BOTTOM_NEWS', array(
            '_GUID' => 'a2076520b171bdf36e5369f0541f92c5',
            'BLOCK_ID' => $block_id,
            'BLOG' => $blogs === 1,
            'POSTS' => $_postdetailss,
        ));
    }
}
