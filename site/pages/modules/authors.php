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
 * @package    authors
 */

/**
 * Module page class.
 */
class Module_authors
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
        $info['version'] = 4;
        $info['locked'] = true;
        return $info;
    }

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('authors');
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
            $GLOBALS['SITE_DB']->create_table('authors', array(
                'author' => '*ID_TEXT',
                'url' => 'URLPATH',
                'member_id' => '?MEMBER',
                'description' => 'LONG_TRANS__COMCODE',
                'skills' => 'LONG_TRANS__COMCODE',
            ));

            $GLOBALS['SITE_DB']->create_index('authors', 'findmemberlink', array('member_id'));
        }

        if ((!is_null($upgrade_from)) && ($upgrade_from < 3)) {
            $GLOBALS['SITE_DB']->alter_table_field('authors', 'member_id', '?MEMBER');
        }

        if ((!is_null($upgrade_from)) && ($upgrade_from < 4)) {
            $GLOBALS['SITE_DB']->alter_table_field('authors', 'forum_handle', '?MEMBER', 'member_id');
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
        if ($check_perms && is_guest($member_id)) {
            return array();
        }
        return array(
            'browse' => array('VIEW_MY_AUTHOR_PROFILE', 'menu/rich_content/authors'),
        );
    }

    public $title;
    public $author;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        require_lang('authors');

        $author = get_param_string('id', null);
        if (is_null($author)) {
            if (is_guest()) {
                global $EXTRA_HEAD;
                $EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

                warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
            }

            $author = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
        }
        if ((is_null($author)) || ($author == '')) {
            warn_exit(do_lang_tempcode('INTERNAL_ERROR')); // Really don't want to have to search on this
        }

        if ((get_value('no_awards_in_titles') !== '1') && (addon_installed('awards'))) {
            require_code('awards');
            $awards = find_awards_for('author', $author);
        } else {
            $awards = array();
        }
        $this->title = get_screen_title('_AUTHOR', true, array(escape_html($author)), null, $awards);

        seo_meta_load_for('authors', $author);

        $this->author = $author;

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution.
     */
    public function run()
    {
        set_feed_url('?mode=authors&select=');

        require_code('authors');

        // Decide what we're doing
        $type = get_param_string('type', 'browse');

        if ($type == 'browse') {
            return $this->show_author();
        }

        return new Tempcode();
    }

    /**
     * The UI to view an author.
     *
     * @return Tempcode The UI
     */
    public function show_author()
    {
        $author = $this->author;

        $rows = $GLOBALS['SITE_DB']->query_select('authors', array('*'), array('author' => $author), '', 1);
        if (!array_key_exists(0, $rows)) {
            if ((has_actual_page_access(get_member(), 'cms_authors')) && (has_edit_author_permission(get_member(), $author))) {
                set_http_status_code('404');

                $_author_add_url = build_url(array('page' => 'cms_authors', 'type' => '_add', 'author' => $author), get_module_zone('cms_authors'));
                $author_add_url = $_author_add_url->evaluate();
                $message = do_lang_tempcode('NO_SUCH_AUTHOR_CONFIGURE_ONE', escape_html($author), escape_html($author_add_url));

                attach_message($message, 'inform');
            } else {
                $message = do_lang_tempcode('NO_SUCH_AUTHOR', escape_html($author));
            }
            $details = array('author' => $author, 'url' => '', 'member_id' => $GLOBALS['FORUM_DRIVER']->get_member_from_username($author), 'description' => null, 'skills' => null,);
        } else {
            $details = $rows[0];
        }

        // Links associated with the mapping between the author and a forum member
        $handle = get_author_id_from_name($author);
        if (!is_null($handle)) {
            $forum_details = do_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY', array('_GUID' => 'b90b606f263eeabeba38e06eef40a21e', 'ACTION' => hyperlink($GLOBALS['FORUM_DRIVER']->member_profile_url($handle, true, true), do_lang_tempcode('AUTHOR_PROFILE'), false, false, '', null, null, 'me')));
            if (addon_installed('points')) {
                $give_points_url = build_url(array('page' => 'points', 'type' => 'member', 'id' => $handle), get_module_zone('points'));
                $point_details = do_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY', array('_GUID' => '2bfb9bf9b5fdf1dad34102abd4bc4648', 'ACTION' => hyperlink($give_points_url, do_lang_tempcode('AUTHOR_POINTS'), false, false)));
            } else {
                $point_details = new Tempcode();
            }
        } else {
            $forum_details = new Tempcode();
            $point_details = new Tempcode();
        }

        // Homepage
        $url = $details['url'];
        if (strlen($url) > 0) {
            $url_details = do_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY', array('_GUID' => '4276bac5acb0ce5839a90614438c1049', 'ACTION' => hyperlink($url, do_lang_tempcode('AUTHOR_HOMEPAGE'), false, false, '', null, null, 'me')));
        } else {
            $url_details = new Tempcode();
        }

        // (Self?) description
        $description = empty($details['description']) ? new Tempcode() : get_translated_tempcode('authors', $details, 'description');

        // Skills
        $skills = empty($details['skills']) ? new Tempcode() : get_translated_tempcode('authors', $details, 'skills');

        // Search link
        if (addon_installed('search')) {
            $search_url = build_url(array('page' => 'search', 'author' => $author), get_module_zone('search'));
            $search_details = do_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY', array('_GUID' => '6fccd38451bc1198024e2452f8539411', 'ACTION' => hyperlink($search_url, do_lang_tempcode('SEARCH'), false, false)));
        } else {
            $search_details = new Tempcode();
        }

        // Downloads
        // Not done via main_multi_content block due to need for custom query
        $downloads_released = new Tempcode();
        if (addon_installed('downloads')) {
            require_code('downloads');
            require_lang('downloads');

            $count = $GLOBALS['SITE_DB']->query_select_value('download_downloads', 'COUNT(*)', array('author' => $author, 'validated' => 1));
            if ($count > 50) {
                $downloads_released = paragraph(do_lang_tempcode('TOO_MANY_TO_CHOOSE_FROM'));
            } else {
                $rows = $GLOBALS['SITE_DB']->query_select('download_downloads', array('*'), array('author' => $author, 'validated' => 1));
                foreach ($rows as $myrow) {
                    if (addon_installed('content_privacy')) {
                        require_code('content_privacy');
                        if (!has_privacy_access('download', strval($myrow['id']))) {
                            continue;
                        }
                    }

                    if (has_category_access(get_member(), 'downloads', strval($myrow['category_id']))) {
                        require_code('downloads');
                        $downloads_released->attach(render_download_box($myrow, true, true/*breadcrumbs?*/, null, null, false/*context?*/));
                    }
                }
            }
        }

        // News
        // Not done via main_multi_content block due to need for custom query
        $news_released = new Tempcode();
        if (addon_installed('news')) {
            require_lang('news');

            $count = $GLOBALS['SITE_DB']->query_select_value('news', 'COUNT(*)', array('author' => $author, 'validated' => 1));
            if ($count > 50) {
                $news_released = paragraph(do_lang_tempcode('TOO_MANY_TO_CHOOSE_FROM'));
            } else {
                $rows = $GLOBALS['SITE_DB']->query_select('news', array('*'), array('author' => $author, 'validated' => 1));
                foreach ($rows as $i => $row) {
                    if (addon_installed('content_privacy')) {
                        require_code('content_privacy');
                        if (!has_privacy_access('news', strval($row['id']))) {
                            continue;
                        }
                    }

                    if (has_category_access(get_member(), 'news', strval($row['news_category']))) {
                        require_code('news');
                        $news_released->attach(render_news_box($row, '_SEARCH', false, true));
                    }
                }
            }
        }

        // Edit link
        $edit_url = new Tempcode();
        if (has_edit_author_permission(get_member(), $author)) {
            $edit_url = build_url(array('page' => 'cms_authors', 'type' => '_add', 'id' => $author), get_module_zone('cms_authors'));
        }

        return do_template('AUTHOR_SCREEN', array(
            '_GUID' => 'ea789367b15bc90fc28d1c586e6e6536',
            'TAGS' => get_loaded_tags(),
            'TITLE' => $this->title,
            'EDIT_URL' => $edit_url,
            'AUTHOR' => $author,
            'NEWS_RELEASED' => $news_released,
            'DOWNLOADS_RELEASED' => $downloads_released,
            'POINT_DETAILS' => $point_details,
            'SEARCH_DETAILS' => $search_details,
            'URL_DETAILS' => $url_details,
            'FORUM_DETAILS' => $forum_details,
            'SKILLS' => $skills,
            'DESCRIPTION' => $description,
        ));
    }
}
