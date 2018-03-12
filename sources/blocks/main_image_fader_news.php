<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.


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
class Block_main_image_fader_news
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
        $info['parameters'] = array('title', 'max', 'time', 'param', 'zone', 'blogs', 'as_guest', 'check');
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
        $info['cache_on'] = 'array(array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false,array_key_exists(\'blogs\',$map)?$map[\'blogs\']:\'-1\',array_key_exists(\'max\',$map)?intval($map[\'max\']):5,array_key_exists(\'title\',$map)?$map[\'title\']:\'\',array_key_exists(\'time\',$map)?intval($map[\'time\']):8000,array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'news\'),array_key_exists(\'param\',$map)?$map[\'param\']:\'\',array_key_exists(\'check\',$map)?($map[\'check\']==\'1\'):true)';
        $info['special_cache_flags'] = CACHE_AGAINST_DEFAULT | CACHE_AGAINST_PERMISSIVE_GROUPS;
        if (addon_installed('content_privacy')) {
            $info['special_cache_flags'] |= CACHE_AGAINST_MEMBER;
        }
        $info['ttl'] = 60;
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
        $error_msg = new Tempcode();
        if (!addon_installed__autoinstall('news', $error_msg)) {
            return $error_msg;
        }

        if (!addon_installed('news_shared')) {
            return paragraph(do_lang_tempcode('MISSING_ADDON', escape_html('news_shared')), 'rj087xxp2oo40zaibz8iyzwsxm8a6jok', 'red-alert');
        }

        require_lang('news');
        require_code('news');
        require_css('news');

        $block_id = get_block_id($map);

        $check_perms = array_key_exists('check', $map) ? ($map['check'] == '1') : true;

        $cat = array_key_exists('param', $map) ? $map['param'] : '*';
        if ($cat == '') {
            $cat = 'root';
        }
        $mill = array_key_exists('time', $map) ? intval($map['time']) : 8000; // milliseconds between animations
        $zone = array_key_exists('zone', $map) ? $map['zone'] : get_module_zone('news');
        $max = array_key_exists('max', $map) ? intval($map['max']) : 5;
        $blogs = array_key_exists('blogs', $map) ? intval($map['blogs']) : -1;

        $main_title = do_lang_tempcode('NEWS');
        $_title = array_key_exists('title', $map) ? $map['title'] : '';
        if ($_title != '') {
            $main_title = protect_from_escaping(escape_html($_title));
        }

        if ($cat == '*') {
            $select_sql = '1=1';
        } else {
            require_code('selectcode');
            $select_sql = selectcode_to_sqlfragment($cat, 'r.news_category', 'news_categories', null, 'r.news_category', 'id');
        }

        $q_filter = '';
        if ($blogs === 0) {
            $q_filter .= ' AND nc_owner IS NULL';
        } elseif ($blogs === 1) {
            $q_filter .= ' AND (nc_owner IS NOT NULL)';
        }
        if ($blogs != -1) {
            $join = ' LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news_categories c ON c.id=r.news_category';
        } else {
            $join = '';
        }

        if (addon_installed('content_privacy')) {
            require_code('content_privacy');
            $as_guest = array_key_exists('as_guest', $map) ? ($map['as_guest'] == '1') : false;
            $viewing_member_id = $as_guest ? $GLOBALS['FORUM_DRIVER']->get_guest_id() : null;
            list($privacy_join, $privacy_where) = get_privacy_where_clause('news', 'r', $viewing_member_id);
            $join .= $privacy_join;
            $q_filter .= $privacy_where;
        }

        if (get_option('filter_regions') == '1') {
            require_code('locations');
            $q_filter .= sql_region_filter('news', 'r.id');
        }

        if ((!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) && ($check_perms)) {
            $join .= get_permission_join_clause('news', 'news_category');
            $q_filter .= get_permission_where_clause(get_member(), get_permission_where_clause_groups(get_member()));
        }

        $query = 'SELECT r.* FROM ' . get_table_prefix() . 'news r' . $join . ' WHERE ' . $select_sql . $q_filter . ' AND validated=1 ORDER BY date_and_time DESC';
        $all_rows = $GLOBALS['SITE_DB']->query($query, 100/*reasonable amount*/);
        $news = array();
        require_code('images');
        foreach ($all_rows as $row) {
            $just_news_row = db_map_restrict($row, array('id', 'title', 'news', 'news_article'));

            $title = get_translated_tempcode('news', $just_news_row, 'title');

            $image_url = $row['news_image'];
            if ($image_url == '') {
                $article = get_translated_text($row['news_article']);
                $matches = array();
                if (preg_match('#["\'\]](http:[^\'"\[\]]+\.(jpeg|jpg|gif|png))["\'\[]#i', $article, $matches) != 0) {
                    $image_url = $matches[1];
                } else {
                    $image_url = find_theme_image('no_image');
                }
            }
            if (url_is_local($image_url)) {
                $image_url = get_custom_base_url() . '/' . $image_url;
            }

            $url_map = array('page' => 'news', 'type' => 'view', 'id' => $row['id'], 'select' => ($cat == '') ? null : $cat);
            if ($blogs === 1) {
                $url_map['blog'] = 1;
            }
            $url = build_url($url_map, $zone);

            $body = get_translated_tempcode('news', $just_news_row, 'news');
            if ($body->is_empty()) {
                $body = get_translated_tempcode('news', $just_news_row, 'news_article');
            }
            if ($body->is_empty()) {
                continue; // Invalid: empty text
            }

            $date = get_timezoned_date_time_tempcode($row['date_and_time']);
            $date_raw = strval($row['date_and_time']);

            $author_url = (addon_installed('authors')) ? build_url(array('page' => 'authors', 'type' => 'browse', 'id' => $row['author']), get_module_zone('authors')) : new Tempcode();

            $news[] = array(
                'TITLE' => $title,
                'IMAGE_URL' => $image_url,
                'URL' => $url,
                'BODY' => $body,
                'DATE' => $date,
                'DATE_RAW' => $date_raw,
                'SUBMITTER' => strval($row['submitter']),
                'AUTHOR' => $row['author'],
                'AUTHOR_URL' => $author_url,
            );

            if (count($news) == $max) {
                break;
            }
        }

        if (count($news) == 0) {
            $submit_url = null;
            if ((has_actual_page_access(null, ($blogs === 1) ? 'cms_blogs' : 'cms_news', null, null)) && (has_submit_permission('mid', get_member(), get_ip_address(), ($blogs === 1) ? 'cms_blogs' : 'cms_news', array('news', $cat)))) {
                $submit_url = build_url(array('page' => ($blogs === 1) ? 'cms_blogs' : 'cms_news', 'type' => 'add', 'cat' => $cat, 'redirect' => protect_url_parameter(SELF_REDIRECT_RIP)), get_module_zone(($blogs === 1) ? 'cms_blogs' : 'cms_news'));
            }
            return do_template('BLOCK_NO_ENTRIES', array(
                '_GUID' => 'ba84d65b8dd134ba6cd7b1b7bde99de2',
                'BLOCK_ID' => $block_id,
                'HIGH' => false,
                'TITLE' => $main_title,
                'MESSAGE' => do_lang_tempcode('NO_ENTRIES', 'news'),
                'ADD_NAME' => do_lang_tempcode('ADD_NEWS'),
                'SUBMIT_URL' => $submit_url,
            ));
        }

        $tmp = array('page' => 'news', 'type' => 'browse', 'select' => ($cat == '') ? null : $cat);
        if ($blogs != -1) {
            $tmp['blog'] = $blogs;
        }
        $archive_url = build_url($tmp, $zone);

        return do_template('BLOCK_MAIN_IMAGE_FADER_NEWS', array(
            '_GUID' => 'dbe34e6f670edfd74b15d3c4afbe615e',
            'BLOCK_ID' => $block_id,
            'TITLE' => $main_title,
            'ARCHIVE_URL' => $archive_url,
            'NEWS' => $news,
            'MILL' => strval($mill),
        ));
    }
}
