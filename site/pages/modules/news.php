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
 * @package    news
 */

/**
 * Module page class.
 */
class Module_news
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 7;
        $info['update_require_upgrade'] = 1;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('news');
        $GLOBALS['SITE_DB']->drop_table_if_exists('news_categories');
        $GLOBALS['SITE_DB']->drop_table_if_exists('news_rss_cloud');
        $GLOBALS['SITE_DB']->drop_table_if_exists('news_category_entries');

        $GLOBALS['SITE_DB']->query_delete('group_category_access', array('module_the_name' => 'news'));

        $GLOBALS['SITE_DB']->query_delete('trackbacks', array('trackback_for_type' => 'news'));
        $GLOBALS['SITE_DB']->query_delete('rating', array('rating_for_type' => 'news'));

        delete_attachments('news');

        delete_privilege('autocomplete_keyword_news');
        delete_privilege('autocomplete_title_news');
    }

    /**
     * Install the module.
     *
     * @param  ?integer $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        if (is_null($upgrade_from)) {
            $GLOBALS['SITE_DB']->create_table('news', array(
                'id' => '*AUTO',
                'date_and_time' => 'TIME',
                'title' => 'SHORT_TRANS__COMCODE',
                'news' => 'LONG_TRANS__COMCODE',
                'news_article' => 'LONG_TRANS__COMCODE',
                'allow_rating' => 'BINARY',
                'allow_comments' => 'SHORT_INTEGER',
                'allow_trackbacks' => 'BINARY',
                'notes' => 'LONG_TEXT',
                'author' => 'ID_TEXT',
                'submitter' => 'MEMBER',
                'validated' => 'BINARY',
                'edit_date' => '?TIME',
                'news_category' => 'AUTO_LINK',
                'news_views' => 'INTEGER',
                'news_image' => 'URLPATH'
            ));
            $GLOBALS['SITE_DB']->create_index('news', 'news_views', array('news_views'));
            $GLOBALS['SITE_DB']->create_index('news', 'findnewscat', array('news_category'));
            $GLOBALS['SITE_DB']->create_index('news', 'newsauthor', array('author'));
            $GLOBALS['SITE_DB']->create_index('news', 'nes', array('submitter'));
            $GLOBALS['SITE_DB']->create_index('news', 'headlines', array('date_and_time', 'id'));
            $GLOBALS['SITE_DB']->create_index('news', 'nvalidated', array('validated'));

            $GLOBALS['SITE_DB']->create_table('news_categories', array(
                'id' => '*AUTO',
                'nc_title' => 'SHORT_TRANS',
                'nc_owner' => '?MEMBER',
                'nc_img' => 'ID_TEXT',
                'notes' => 'LONG_TEXT'
            ));
            $GLOBALS['SITE_DB']->create_index('news_categories', 'ncs', array('nc_owner'));

            $default_categories = array('general', 'technology', 'difficulties', 'community', 'entertainment', 'business', 'art');
            require_lang('news');
            foreach ($default_categories as $category) {
                $map = array(
                    'notes' => '',
                    'nc_img' => 'newscats/' . $category,
                    'nc_owner' => null,
                );
                $map += lang_code_to_default_content('nc_title', 'NC_' . $category);
                $GLOBALS['SITE_DB']->query_insert('news_categories', $map);
            }

            $GLOBALS['SITE_DB']->create_table('news_rss_cloud', array(
                'id' => '*AUTO',
                'rem_procedure' => 'ID_TEXT',
                'rem_port' => 'INTEGER',
                'rem_path' => 'SHORT_TEXT',
                'rem_protocol' => 'ID_TEXT',
                'rem_ip' => 'IP',
                'watching_channel' => 'URLPATH',
                'register_time' => 'TIME'
            ));

            $GLOBALS['SITE_DB']->create_table('news_category_entries', array(
                'news_entry' => '*AUTO_LINK',
                'news_entry_category' => '*AUTO_LINK'
            ));
            $GLOBALS['SITE_DB']->create_index('news_category_entries', 'news_entry_category', array('news_entry_category'));

            $groups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list(false, true);
            $categories = $GLOBALS['SITE_DB']->query_select('news_categories', array('id'));
            foreach ($categories as $_id) {
                foreach (array_keys($groups) as $group_id) {
                    $GLOBALS['SITE_DB']->query_insert('group_category_access', array('module_the_name' => 'news', 'category_name' => strval($_id['id']), 'group_id' => $group_id));
                }
            }

            $GLOBALS['SITE_DB']->create_index('news', 'ftjoin_ititle', array('title'));
            $GLOBALS['SITE_DB']->create_index('news', 'ftjoin_nnews', array('news'));
            $GLOBALS['SITE_DB']->create_index('news', 'ftjoin_nnewsa', array('news_article'));
        }

        if ((is_null($upgrade_from)) || ($upgrade_from < 7)) {
            $GLOBALS['SITE_DB']->create_index('news', '#news_search__combined', array('title', 'news', 'news_article'));

            add_privilege('SEARCH', 'autocomplete_keyword_news', false);
            add_privilege('SEARCH', 'autocomplete_title_news', false);
        }
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions.
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user).
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        $has_blogs = ($GLOBALS['SITE_DB']->query_select_value('news_categories', 'COUNT(*)', null, 'WHERE nc_owner IS NOT NULL') > 0);

        $ret = array(
            'browse' => array('NEWS_ARCHIVE', 'menu/rich_content/news'),
            'cat_select' => array('NEWS_CATEGORIES', 'menu/_generic_admin/view_archive'),
        );
        if ($has_blogs) {
            $ret['select'] = array('JUST_NEWS_CATEGORIES', 'menu/rich_content/news');
            $ret['blog_select'] = array('BLOGS', 'tabs/member_account/blog');
        }
        return $ret;
    }

    public $title;
    public $id;
    public $blog;
    public $select;
    public $select_and;
    public $myrow;
    public $_title;
    public $title_to_use;
    public $img;
    public $news_full;
    public $news_cats;
    public $category;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        require_lang('news');

        inform_non_canonical_parameter('select');
        inform_non_canonical_parameter('select_and');
        inform_non_canonical_parameter('blog');

        if ($type == 'cat_select') {
            $this->title = get_screen_title('JUST_NEWS_CATEGORIES');
        }

        if ($type == 'select') {
            $this->title = get_screen_title('NEWS_CATEGORIES');
        }

        if ($type == 'blog_select') {
            $this->title = get_screen_title('BLOGS');
        }

        if ($type == 'browse') {
            $blog = get_param_integer('blog', null);

            $select = get_param_string('id', get_param_string('select', '*'));
            $select_and = get_param_string('select_and', '*');

            // Title
            if ($blog === 1) {
                $this->title = get_screen_title('BLOG_NEWS_ARCHIVE');
            } else {
                if (is_numeric($select)) {
                    $news_cat_title = $GLOBALS['SITE_DB']->query_select('news_categories', array('nc_title'), array('id' => intval($select)), '', 1);
                    if (array_key_exists(0, $news_cat_title)) {
                        $news_cat_title[0]['_nc_title'] = get_translated_text($news_cat_title[0]['nc_title']);
                        $this->title = get_screen_title(make_fractionable_editable('news_category', $select, $news_cat_title[0]['_nc_title']), false);
                    } else {
                        $this->title = get_screen_title('NEWS_ARCHIVE');
                    }
                } else {
                    $this->title = get_screen_title('NEWS_ARCHIVE');
                }
            }

            // Breadcrumbs
            if ($blog === 1) {
                $first_bc = array('_SELF:_SELF:blog_select', do_lang_tempcode('BLOGS'));
            } elseif ($blog === 0) {
                $first_bc = array('_SELF:_SELF:cat_select', do_lang_tempcode('JUST_NEWS_CATEGORIES'));
            } else {
                $first_bc = array('_SELF:_SELF:select', do_lang_tempcode('NEWS_CATEGORIES'));
            }
            breadcrumb_set_parents(array($first_bc));

            $this->blog = $blog;
            $this->select = $select;
            $this->select_and = $select_and;
        }

        if ($type == 'view') {
            $id = get_param_integer('id');

            if (addon_installed('content_privacy')) {
                require_code('content_privacy');
                check_privacy('news', strval($id));
            }

            $blog = get_param_integer('blog', null);

            $select = get_param_string('select', '*');
            $select_and = get_param_string('select_and', '*');

            // Load from database
            $rows = $GLOBALS['SITE_DB']->query_select('news', array('*'), array('id' => $id), '', 1);
            if (!array_key_exists(0, $rows)) {
                return warn_screen(get_screen_title('NEWS'), do_lang_tempcode('MISSING_RESOURCE'));
            }
            $myrow = $rows[0];

            // Breadcrumbs
            if ($blog === 1) {
                $first_bc = array('_SELF:_SELF:blog_select', do_lang_tempcode('BLOGS'));
            } elseif ($blog === 0) {
                $first_bc = array('_SELF:_SELF:cat_select', do_lang_tempcode('JUST_NEWS_CATEGORIES'));
            } else {
                $first_bc = array('_SELF:_SELF:select', do_lang_tempcode('NEWS_CATEGORIES'));
            }
            if ($blog === 1) {
                $parent_title = do_lang_tempcode('BLOG_NEWS_ARCHIVE');
            } else {
                if (is_numeric($select)) {
                    $news_cat_title = $GLOBALS['SITE_DB']->query_select('news_categories', array('nc_title'), array('id' => intval($select)), '', 1);
                    if (array_key_exists(0, $news_cat_title)) {
                        $news_cat_title[0]['_nc_title'] = get_translated_text($news_cat_title[0]['nc_title']);
                        $parent_title = make_string_tempcode(escape_html($news_cat_title[0]['_nc_title']));
                    } else {
                        $parent_title = do_lang_tempcode('NEWS_ARCHIVE');
                    }
                } else {
                    $parent_title = do_lang_tempcode('NEWS_ARCHIVE');
                }
            }
            breadcrumb_set_parents(array($first_bc, array('_SELF:_SELF:browse' . (($blog === 1) ? ':blog=1' : (($blog === 0) ? ':blog=0' : '')) . (($select == '*') ? '' : (is_numeric($select) ? (':id=' . $select) : (':select=' . $select))) . (($select_and == '*') ? '' : (':select_and=' . $select_and)) . propagate_filtercode_page_link(), $parent_title)));
            breadcrumb_set_self(get_translated_tempcode('news', $myrow, 'title'));

            // Permissions
            if (!has_category_access(get_member(), 'news', strval($myrow['news_category']))) {
                access_denied('CATEGORY_ACCESS');
            }

            // Title
            if ((get_value('no_awards_in_titles') !== '1') && (addon_installed('awards'))) {
                require_code('awards');
                $awards = find_awards_for('news', strval($id));
            } else {
                $awards = array();
            }
            $_title = get_translated_tempcode('news', $myrow, 'title');
            $title_to_use = do_lang_tempcode(($blog === 1) ? 'BLOG__NEWS' : '_NEWS', make_fractionable_editable('news', $id, $_title));
            $this->title = get_screen_title($title_to_use, false, null, null, $awards);

            // SEO
            seo_meta_load_for('news', strval($id), do_lang(($blog === 1) ? 'BLOG__NEWS' : '_NEWS', get_translated_text($myrow['title'])));

            // Category membership
            $news_cats = $GLOBALS['SITE_DB']->query('SELECT * FROM ' . get_table_prefix() . 'news_categories WHERE nc_owner IS NULL OR id=' . strval($myrow['news_category']));
            $news_cats = list_to_map('id', $news_cats);
            $img = ($news_cats[$myrow['news_category']]['nc_img'] == '') ? '' : find_theme_image($news_cats[$myrow['news_category']]['nc_img']);
            if (is_null($img)) {
                $img = '';
            }
            if ($myrow['news_image'] != '') {
                $img = $myrow['news_image'];
                if ((url_is_local($img)) && ($img != '')) {
                    $img = get_custom_base_url() . '/' . $img;
                }
            }
            $category = get_translated_text($news_cats[$myrow['news_category']]['nc_title']);

            $news_full = get_translated_tempcode('news', $myrow, 'news_article');
            if ($news_full->is_empty()) {
                $news_full = get_translated_tempcode('news', $myrow, 'news');
            }

            $og_img = $img;
            if ($og_img == '') {
                $news_full_eval = $news_full->evaluate();
                $matches = array();
                if (preg_match('#<img\s[^<>]*src="([^"]*)"#', $news_full_eval, $matches) != 0) {
                    $og_img = html_entity_decode($matches[1], ENT_QUOTES, get_charset());
                }
            }

            // Meta data
            set_extra_request_metadata(array(
                'created' => date('Y-m-d', $myrow['date_and_time']),
                'creator' => $myrow['author'],
                'publisher' => $GLOBALS['FORUM_DRIVER']->get_username($myrow['submitter']),
                'modified' => is_null($myrow['edit_date']) ? '' : date('Y-m-d', $myrow['edit_date']),
                'type' => 'News article',
                'title' => get_translated_text($myrow['title']),
                'identifier' => '_SEARCH:news:view:' . strval($id),
                'image' => $og_img,
                'description' => strip_comcode(get_translated_text($myrow['news'])),
                'category' => $category,
            ));

            $this->id = $id;
            $this->blog = $blog;
            $this->select = $select;
            $this->select_and = $select_and;
            $this->myrow = $myrow;
            $this->_title = $_title;
            $this->title_to_use = $title_to_use;
            $this->img = $img;
            $this->news_full = $news_full;
            $this->news_cats = $news_cats;
            $this->category = $category;
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution.
     */
    public function run()
    {
        require_code('feedback');
        require_code('news');
        require_css('news');

        $type = get_param_string('type', 'browse');

        if ($type == 'view') {
            return $this->view_news();
        }
        if ($type == 'browse') {
            return $this->news_archive();
        }
        if ($type == 'cat_select') {
            return $this->news_cat_select(0);
        }
        if ($type == 'blog_select') {
            return $this->news_cat_select(1);
        }
        if ($type == 'select') {
            return $this->news_cat_select(null);
        }

        return new Tempcode();
    }

    /**
     * The UI to select a news category to view news within.
     *
     * @param  ?integer $blogs What to show (null: news and blogs, 0: news, 1: blogs)
     * @return Tempcode The UI
     */
    public function news_cat_select($blogs)
    {
        $start = get_param_integer('news_categories_start', 0);
        $max = get_param_integer('news_categories_max', intval(get_option('news_categories_per_page')));

        require_code('selectcode');
        $select = get_param_string('select', '*');
        $where = selectcode_to_sqlfragment($select, 'r.news_category', 'news_categories', null, 'r.news_category', 'id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)

        if (is_null($blogs)) {
            $map = array();
            $categories = $GLOBALS['SITE_DB']->query_select('news_categories', array('*'), $map, 'ORDER BY nc_owner,' . $GLOBALS['SITE_DB']->translate_field_ref('nc_title'), $max, $start); // Ordered to show non-blogs first (nc_owner=NULL)
            $max_rows = $GLOBALS['SITE_DB']->query_select_value('news_categories', 'COUNT(*)', $map);
        } elseif ($blogs == 1) {
            $categories = $GLOBALS['SITE_DB']->query('SELECT c.* FROM ' . get_table_prefix() . 'news_categories c WHERE nc_owner IS NOT NULL ORDER BY nc_owner DESC,' . $GLOBALS['SITE_DB']->translate_field_ref('nc_title'), $max, $start, false, false, array('nc_title' => 'SHORT_TRANS')); // Ordered to show newest blogs first
            $max_rows = $GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . get_table_prefix() . 'news_categories WHERE nc_owner IS NOT NULL', false, false, array('nc_title' => 'SHORT_TRANS'));
        } else {
            $map = array('nc_owner' => null);
            $categories = $GLOBALS['SITE_DB']->query_select('news_categories', array('*'), $map, 'ORDER BY ' . $GLOBALS['SITE_DB']->translate_field_ref('nc_title'), $max, $start); // Ordered by title (can do efficiently as limited numbers of non-blogs)
            $max_rows = $GLOBALS['SITE_DB']->query_select_value('news_categories', 'COUNT(*)', $map);
        }
        $content = new Tempcode();
        $join = ' LEFT JOIN ' . get_table_prefix() . 'news_category_entries d ON d.news_entry=r.id';
        if ($blogs === 1) {
            $where .= ' AND c.nc_owner IS NOT NULL';

            $join .= ' LEFT JOIN ' . get_table_prefix() . 'news_categories c ON c.id=r.news_category';
        } elseif ($blogs === 0) {
            $where .= ' AND c.nc_owner IS NULL AND c.id IS NOT NULL';

            $join .= ' LEFT JOIN ' . get_table_prefix() . 'news_categories c ON c.id=r.news_category';
        }
        $_content = array();
        foreach ($categories as $category) {
            if (has_category_access(get_member(), 'news', strval($category['id']))) {
                $query = 'SELECT COUNT(*) FROM ' . get_table_prefix() . 'news r' . $join . ' WHERE ' . (((!has_privilege(get_member(), 'see_unvalidated')) && (addon_installed('unvalidated'))) ? 'validated=1 AND ' : '') . ' (news_entry_category=' . strval($category['id']) . ' OR news_category=' . strval($category['id']) . ') AND ' . $where . ' ORDER BY date_and_time DESC';
                $count = $GLOBALS['SITE_DB']->query_value_if_there($query);
                if ($count > 0) {
                    $_content[] = render_news_category_box($category, '_SELF', false, true, $blogs);
                }
            }
        }
        foreach ($_content as $c) { // To allow code overrides to easily shuffle it
            $content->attach($c);
        }
        if ($content->is_empty()) {
            inform_exit(do_lang_tempcode('NO_ENTRIES'));
        }

        if ((($blogs !== 1) || (has_privilege(get_member(), 'have_personal_category', 'cms_news'))) && (has_actual_page_access(null, ($blogs === 1) ? 'cms_blogs' : 'cms_news', null, null)) && (has_submit_permission('high', get_member(), get_ip_address(), 'cms_news'))) {
            $map = array('page' => ($blogs === 1) ? 'cms_blogs' : 'cms_news', 'type' => 'add');
            if (is_numeric($select)) {
                $map['cat'] = $select;
            }
            $submit_url = build_url($map, get_module_zone('cms_news'));
        } else {
            $submit_url = new Tempcode();
        }

        require_code('templates_pagination');
        $pagination = pagination(do_lang_tempcode('NEWS_CATEGORIES'), $start, 'news_categories_start', $max, 'news_categories_max', $max_rows);

        $tpl = do_template('PAGINATION_SCREEN', array('_GUID' => 'c61c945e0453c2145a819ca60e8faf09', 'TITLE' => $this->title, 'SUBMIT_URL' => $submit_url, 'CONTENT' => $content, 'PAGINATION' => $pagination));

        require_code('templates_internalise_screen');
        return internalise_own_screen($tpl);
    }

    /**
     * The UI to view the news archive.
     *
     * @return Tempcode The UI
     */
    public function news_archive()
    {
        $select = either_param_string('active_select', '');

        $blog = $this->blog;
        $select = $this->select;
        $select_and = $this->select_and;

        // Get category contents
        $inline = get_param_integer('inline', 0) == 1;
        $content = do_block('main_news', array(
            'param' => '0',
            'title' => '',
            'select' => $select,
            'select_and' => $select_and,
            'blogs' => is_null($blog) ? '-1' : strval($blog),
            'member_based' => ($blog === 1) ? '1' : '0',
            'zone' => '_SELF',
            'days' => '0',
            'fallback_full' => $inline ? '0' : '10',
            'fallback_archive' => $inline ? get_option('news_entries_per_page') : '0',
            'no_links' => '1',
            'pagination' => '1',
            'attach_to_url_filter' => '1',
            'block_id' => 'module',
        ));

        // Management links
        if ((($blog !== 1) || (has_privilege(get_member(), 'have_personal_category', 'cms_news'))) && (has_actual_page_access(null, ($blog === 1) ? 'cms_blogs' : 'cms_news', null, null)) && (has_submit_permission('high', get_member(), get_ip_address(), 'cms_news'))) {
            $map = array('page' => ($blog === 1) ? 'cms_blogs' : 'cms_news', 'type' => 'add');
            if (is_numeric($select)) {
                $map['cat'] = $select;
            }
            $submit_url = build_url($map, get_module_zone('cms_news'));
        } else {
            $submit_url = new Tempcode();
        }

        // Render
        return do_template('NEWS_ARCHIVE_SCREEN', array('_GUID' => '228918169ab1db445ee0c2d71f85983c', 'CAT' => is_numeric($select) ? $select : null, 'SUBMIT_URL' => $submit_url, 'BLOG' => $blog === 1, 'TITLE' => $this->title, 'CONTENT' => $content));
    }

    /**
     * The UI to view a news entry.
     *
     * @return Tempcode The UI
     */
    public function view_news()
    {
        $id = $this->id;
        $blog = $this->blog;
        $select = $this->select;
        $select_and = $this->select_and;
        $myrow = $this->myrow;
        $_title = $this->_title;
        $title_to_use = $this->title_to_use;
        $img = $this->img;
        $news_full = $this->news_full;
        $news_cats = $this->news_cats;
        $category = $this->category;

        // Rating and comments
        $self_url_map = array('page' => '_SELF', 'type' => 'view', 'id' => $id);
        /*if ($select != '*') $self_url_map['select'] = $select;      Potentially makes URL too long for content topic to store, and we probably don't want to store this assumptive context anyway
        if (($select_and != '*') && ($select_and != '')) $self_url_map['select_and'] = $filter_and;*/
        if (!is_null($blog)) {
            $self_url_map['blog'] = $blog;
        }
        list($rating_details, $comment_details, $trackback_details) = embed_feedback_systems(
            get_page_name(),
            strval($id),
            $myrow['allow_rating'],
            $myrow['allow_comments'],
            $myrow['allow_trackbacks'],
            $myrow['validated'],
            $myrow['submitter'],
            build_url($self_url_map, '_SELF', null, false, false, true),
            get_translated_text($myrow['title']),
            find_overridden_comment_forum('news', strval($myrow['news_category'])),
            $myrow['date_and_time']
        );

        // Load details
        $date = get_timezoned_date($myrow['date_and_time']);
        $author_url = addon_installed('authors') ? build_url(array('page' => 'authors', 'type' => 'browse', 'id' => $myrow['author']), get_module_zone('authors')) : new Tempcode();
        $author = $myrow['author'];
        $news_full_plain = get_translated_text($myrow['news_article']);
        if ($news_full->is_empty()) {
            $news_full_plain = get_translated_text($myrow['news']);
        }

        // Validation
        if (($myrow['validated'] == 0) && (addon_installed('unvalidated'))) {
            if ((!has_privilege(get_member(), 'jump_to_unvalidated')) && ((is_guest()) || ($myrow['submitter'] != get_member()))) {
                access_denied('PRIVILEGE', 'jump_to_unvalidated');
            }

            $warning_details = do_template('WARNING_BOX', array('_GUID' => '5fd82328dc2ac9695dc25646237065b0', 'WARNING' => do_lang_tempcode((get_param_integer('redirected', 0) == 1) ? 'UNVALIDATED_TEXT_NON_DIRECT' : 'UNVALIDATED_TEXT')));
        } else {
            $warning_details = new Tempcode();
        }

        // Views
        if ((get_db_type() != 'xml') && (get_value('no_view_counts') !== '1') && (is_null(get_bot_type()))) {
            $myrow['news_views']++;
            if (!$GLOBALS['SITE_DB']->table_is_locked('news')) {
                $GLOBALS['SITE_DB']->query_update('news', array('news_views' => $myrow['news_views']), array('id' => $id), '', 1, null, false, true);
            }
        }

        // Management links
        if ((has_actual_page_access(null, ($blog === 1) ? 'cms_blogs' : 'cms_news', null, null)) && (has_edit_permission('high', get_member(), $myrow['submitter'], ($blog === 1) ? 'cms_blogs' : 'cms_news', array('news', $myrow['news_category'])))) {
            $edit_url = build_url(array('page' => ($blog === 1) ? 'cms_blogs' : 'cms_news', 'type' => '_edit', 'id' => $id), get_module_zone(($blog === 1) ? 'cms_blogs' : 'cms_news'));
        } else {
            $edit_url = new Tempcode();
        }
        $tmp = array('page' => '_SELF', 'type' => 'browse');
        if ($select != '*') {
            $tmp[is_numeric($select) ? 'id' : 'select'] = $select;
        }
        if (($select_and != '*') && ($select_and != '')) {
            $tmp['select_and'] = $select_and;
        }
        if (!is_null($blog)) {
            $tmp['blog'] = $blog;
        }
        $archive_url = build_url($tmp + propagate_filtercode(), '_SELF');
        if ((($blog !== 1) || (has_privilege(get_member(), 'have_personal_category', 'cms_news'))) && (has_actual_page_access(null, ($blog === 1) ? 'cms_blogs' : 'cms_news', null, null)) && (has_submit_permission('high', get_member(), get_ip_address(), 'cms_news', array('news', $myrow['news_category'])))) {
            $map = array('page' => ($blog === 1) ? 'cms_blogs' : 'cms_news', 'type' => 'add');
            if (is_numeric($select)) {
                $map['cat'] = $select;
            }
            $submit_url = build_url($map, get_module_zone('cms_news'));
        } else {
            $submit_url = new Tempcode();
        }

        $categories = array(strval($myrow['news_category']) => $category);
        $all_categories_for_this = $GLOBALS['SITE_DB']->query_select('news_category_entries', array('*'), array('news_entry' => $id));
        $NEWS_CATS_CACHE = array();
        foreach ($all_categories_for_this as $category_for_this) {
            if (!array_key_exists($category_for_this['news_entry_category'], $news_cats)) {
                $_news_cats = $GLOBALS['SITE_DB']->query_select('news_categories', array('*'), array('id' => $category_for_this['news_entry_category']), '', 1);
                if (array_key_exists(0, $_news_cats)) {
                    $NEWS_CATS_CACHE[$category_for_this['news_entry_category']] = $_news_cats[0];
                }
            }

            if (array_key_exists($category_for_this['news_entry_category'], $news_cats)) {
                $categories[strval($category_for_this['news_entry_category'])] = get_translated_text($news_cats[$category_for_this['news_entry_category']]['nc_title']);
            }
        }

        // Newsletter tie-in
        $newsletter_url = new Tempcode();
        if (addon_installed('newsletter')) {
            require_lang('newsletter');
            if (has_actual_page_access(get_member(), 'admin_newsletter')) {
                $newsletter_url = build_url(array('page' => 'admin_newsletter', 'type' => 'new', 'from_news' => $id), get_module_zone('admin_newsletter'));
            }
        }

        // Render
        return do_template('NEWS_ENTRY_SCREEN', array(
            '_GUID' => '7686b23934e22c493d4ac10ba6c475c4',
            'TITLE' => $this->title,
            'ID' => strval($id),
            'CATEGORY_ID' => strval($myrow['news_category']),
            'BLOG' => $blog === 1,
            '_TITLE' => $_title,
            'TAGS' => get_loaded_tags('news'),
            'CATEGORIES' => $categories,
            'NEWSLETTER_URL' => $newsletter_url,
            'ADD_DATE_RAW' => strval($myrow['date_and_time']),
            'EDIT_DATE_RAW' => is_null($myrow['edit_date']) ? '' : strval($myrow['edit_date']),
            'SUBMITTER' => strval($myrow['submitter']),
            'CATEGORY' => $category,
            'IMG' => $img,
            'VIEWS' => integer_format($myrow['news_views']),
            'COMMENT_DETAILS' => $comment_details,
            'RATING_DETAILS' => $rating_details,
            'TRACKBACK_DETAILS' => $trackback_details,
            'DATE' => $date,
            'AUTHOR' => $author,
            'AUTHOR_URL' => $author_url,
            'NEWS_FULL' => $news_full,
            'NEWS_FULL_PLAIN' => $news_full_plain,
            'EDIT_URL' => $edit_url,
            'ARCHIVE_URL' => $archive_url,
            'SUBMIT_URL' => $submit_url,
            'WARNING_DETAILS' => $warning_details,
        ));
    }
}
