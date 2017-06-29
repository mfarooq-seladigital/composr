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
 * @package    news
 */

/*
RSS IMPORT (works very well with Wordpress and Blogger, which use RSS as an interchange)
*/

/**
 * Add a news category of the specified details.
 *
 * @param  SHORT_TEXT $title The news category title
 * @param  ID_TEXT $img The theme image ID of the picture to use for the news category
 * @param  LONG_TEXT $notes Notes for the news category
 * @param  ?MEMBER $owner The owner (null: public)
 * @param  ?AUTO_LINK $id Force an ID (null: don't force an ID)
 * @return AUTO_LINK The ID of our new news category
 */
function add_news_category($title, $img, $notes, $owner = null, $id = null)
{
    require_code('global4');
    prevent_double_submit('ADD_NEWS_CATEGORY', null, $title);

    $map = array(
        'nc_img' => $img,
        'notes' => $notes,
        'nc_owner' => $owner,
    );
    $map += insert_lang('nc_title', $title, 1);
    if ($id !== null) {
        $map['id'] = $id;
    }
    $id = $GLOBALS['SITE_DB']->query_insert('news_categories', $map, true);

    log_it('ADD_NEWS_CATEGORY', strval($id), $title);

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resource_fs_moniker('news_category', strval($id), null, null, true);
    }

    delete_cache_entry('side_news_categories');

    require_code('member_mentions');
    dispatch_member_mention_notifications('news_category', strval($id));

    require_code('sitemap_xml');
    notify_sitemap_node_add('SEARCH:news:browse:' . strval($id), null, null, SITEMAP_IMPORTANCE_HIGH, 'daily', has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(), 'news', strval($id)));

    return $id;
}

/**
 * Edit a news category.
 *
 * @param  AUTO_LINK $id The news category to edit
 * @param  ?SHORT_TEXT $title The title (null: keep as-is)
 * @param  ?SHORT_TEXT $img The image (null: keep as-is)
 * @param  ?LONG_TEXT $notes The notes (null: keep as-is)
 * @param  ?MEMBER $owner The owner (null: public)
 */
function edit_news_category($id, $title, $img, $notes, $owner)
{
    $myrows = $GLOBALS['SITE_DB']->query_select('news_categories', array('nc_title', 'nc_img', 'notes'), array('id' => $id), '', 1);
    if (!array_key_exists(0, $myrows)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'news_category'));
    }
    $myrow = $myrows[0];

    $old_title = get_translated_text($myrow['nc_title']);

    require_code('urls2');
    suggest_new_idmoniker_for('news', 'browse', strval($id), '', $title);

    // Sync meta keywords, if we have auto-sync for these
    if (get_option('enable_seo_fields') === '0') {
        $sql = 'SELECT * FROM ' . get_table_prefix() . 'seo_meta_keywords m WHERE ';
        $sql .= db_string_equal_to('meta_for_type', 'news');
        $meta_keywords_field = $GLOBALS['SITE_DB']->translate_field_ref('meta_keyword');
        $sql .= ' AND ' . db_string_equal_to($meta_keywords_field, $old_title);
        $affected_news = $GLOBALS['SITE_DB']->query($sql, null, 0, false, false, array('meta_keyword' => 'SHORT_TRANS'));
        foreach ($affected_news as $af_row) {
            $GLOBALS['SITE_DB']->query_update('seo_meta_keywords', lang_remap('meta_keyword', $af_row['meta_keyword'], $title), $af_row);
        }
    }

    log_it('EDIT_NEWS_CATEGORY', strval($id), $title);

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resource_fs_moniker('news_category', strval($id));
    }

    if ($title === null) {
        $title = get_translated_text($myrow['nc_title']);
    }
    if ($img === null) {
        $img = $myrow['nc_img'];
    }
    if ($notes === null) {
        $notes = $myrow['notes'];
    }

    $update_map = array(
        'nc_img' => $img,
        'notes' => $notes,
    );
    $update_map += lang_remap('nc_title', $myrow['nc_title'], $title);
    $update_map['nc_owner'] = $owner;

    $GLOBALS['SITE_DB']->query_update('news_categories', $update_map, array('id' => $id), '', 1);

    require_code('themes2');
    tidy_theme_img_code($img, $myrow['nc_img'], 'news_categories', 'nc_img');

    delete_cache_entry('main_news');
    delete_cache_entry('main_image_fader_news');
    delete_cache_entry('side_news');
    delete_cache_entry('side_news_archive');
    delete_cache_entry('bottom_news');
    delete_cache_entry('side_news_categories');

    require_code('sitemap_xml');
    notify_sitemap_node_edit('SEARCH:news:browse:' . strval($id), has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(), 'news', strval($id)));
}

/**
 * Delete a news category.
 *
 * @param  AUTO_LINK $id The news category to delete
 */
function delete_news_category($id)
{
    $rows = $GLOBALS['SITE_DB']->query_select('news_categories', array('nc_title', 'nc_img'), array('id' => $id), '', 1);
    if (!array_key_exists(0, $rows)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'news_category'));
    }
    $myrow = $rows[0];

    $min = $GLOBALS['SITE_DB']->query_value_if_there('SELECT c.id FROM ' . get_table_prefix() . 'news_categories c WHERE c.id<>' . strval($id) . ' AND ' . db_string_equal_to($GLOBALS['SITE_DB']->translate_field_ref('nc_title'), do_lang('news:NC_general')), false, false, array('nc_title' => 'SHORT_TRANS'));
    if ($min === null) {
        $min = $GLOBALS['SITE_DB']->query_value_if_there('SELECT MIN(id) FROM ' . get_table_prefix() . 'news_categories WHERE id<>' . strval($id));
    }
    if ($min === null) {
        warn_exit(do_lang_tempcode('YOU_MUST_KEEP_ONE_NEWS_CAT'));
    }

    $old_title = get_translated_text($myrow['nc_title']);

    if (addon_installed('catalogues')) {
        update_catalogue_content_ref('news_category', strval($id), '');
    }

    delete_lang($myrow['nc_title']);

    $GLOBALS['SITE_DB']->query_update('news', array('news_category' => $min), array('news_category' => $id));
    $GLOBALS['SITE_DB']->query_delete('news_categories', array('id' => $id), '', 1);
    $GLOBALS['SITE_DB']->query_delete('news_category_entries', array('news_entry_category' => $id));

    $GLOBALS['SITE_DB']->query_delete('group_category_access', array('module_the_name' => 'news', 'category_name' => strval($id)));
    $GLOBALS['SITE_DB']->query_delete('group_privileges', array('module_the_name' => 'news', 'category_name' => strval($id)));

    require_code('themes2');
    tidy_theme_img_code(null, $myrow['nc_img'], 'news_categories', 'nc_img');

    delete_cache_entry('side_news_categories');

    log_it('DELETE_NEWS_CATEGORY', strval($id), $old_title);

    // Sync meta keywords, if we have auto-sync for these
    if (get_option('enable_seo_fields') === '0') {
        $sql = 'SELECT m.* FROM ' . get_table_prefix() . 'seo_meta_keywords m WHERE ';
        $sql .= db_string_equal_to('meta_for_type', 'news');
        $meta_keywords_field = $GLOBALS['SITE_DB']->translate_field_ref('meta_keyword');
        $sql .= ' AND ' . db_string_equal_to($meta_keywords_field, $old_title);
        $affected_news = $GLOBALS['SITE_DB']->query($sql, null, 0, false, false, array('meta_keyword' => 'SHORT_TRANS'));
        foreach ($affected_news as $af_row) {
            $GLOBALS['SITE_DB']->query_delete('seo_meta_keywords', array('id' => $af_row['id']), '', 1);
        }
    }

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        expunge_resource_fs_moniker('news_category', strval($id));
    }

    require_code('sitemap_xml');
    notify_sitemap_node_delete('SEARCH:news:browse:' . strval($id));

    if (addon_installed('ecommerce')) {
        require_code('ecommerce_permission_products');
        delete_prod_permission('news', strval($id));
    }
}

/**
 * Adds a news entry to the database, and send out the news to any RSS cloud listeners.
 *
 * @param  SHORT_TEXT $title The news title
 * @param  LONG_TEXT $news The news summary (or if not an article, the full news)
 * @param  ?ID_TEXT $author The news author (possibly, a link to an existing author in the system, but does not need to be) (null: current username)
 * @param  BINARY $validated Whether the news has been validated
 * @param  BINARY $allow_rating Whether the news may be rated
 * @param  SHORT_INTEGER $allow_comments Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY $allow_trackbacks Whether the news may have trackbacks
 * @param  LONG_TEXT $notes Notes for the news
 * @param  LONG_TEXT $news_article The news entry (blank means no entry)
 * @param  ?AUTO_LINK $main_news_category The primary news category (null: personal)
 * @param  array $news_categories The IDs of the news categories that this is in
 * @param  ?TIME $time The time of submission (null: now)
 * @param  ?MEMBER $submitter The news submitter (null: current member)
 * @param  integer $views The number of views the article has had
 * @param  ?TIME $edit_date The edit date (null: never)
 * @param  ?AUTO_LINK $id Force an ID (null: don't force an ID)
 * @param  URLPATH $image URL to the image for the news entry (blank: use cat image)
 * @param  ?SHORT_TEXT $meta_keywords Meta keywords for this resource (null: do not edit) (blank: implicit)
 * @param  ?LONG_TEXT $meta_description Meta description for this resource (null: do not edit) (blank: implicit)
 * @param  array $regions The regions (empty: not region-limited)
 * @return AUTO_LINK The ID of the news just added
 */
function add_news($title, $news, $author = null, $validated = 1, $allow_rating = 1, $allow_comments = 1, $allow_trackbacks = 1, $notes = '', $news_article = '', $main_news_category = null, $news_categories = array(), $time = null, $submitter = null, $views = 0, $edit_date = null, $id = null, $image = '', $meta_keywords = '', $meta_description = '', $regions = array())
{
    if ($author === null) {
        $author = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
    }
    if ($time === null) {
        $time = time();
    }
    if ($submitter === null) {
        $submitter = get_member();
    }
    $already_created_personal_category = false;

    require_code('comcode_check');
    check_comcode($news_article, null, false, null, true);

    require_code('global4');
    prevent_double_submit('ADD_NEWS', null, $title);

    if ($main_news_category === null) {
        $main_news_category_id = $GLOBALS['SITE_DB']->query_select_value_if_there('news_categories', 'id', array('nc_owner' => $submitter));
        if ($main_news_category_id === null) {
            if ((!has_privilege(get_member(), 'have_personal_category', 'cms_news')) && (!running_script('stress_test_loader'))) {
                fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
            }

            $map = array(
                'nc_img' => 'newscats/community',
                'notes' => '',
                'nc_owner' => $submitter,
            );
            $map += insert_lang('nc_title', do_lang('MEMBER_CATEGORY', $GLOBALS['FORUM_DRIVER']->get_username($submitter, true)), 2);

            $main_news_category_id = $GLOBALS['SITE_DB']->query_insert('news_categories', $map, true);
            $already_created_personal_category = true;

            require_code('permissions2');
            set_global_category_access('news', $main_news_category_id);
        }
    } else {
        $main_news_category_id = $main_news_category;
    }

    if (!addon_installed('unvalidated')) {
        $validated = 1;
    }
    $map = array(
        'news_image' => $image,
        'edit_date' => $edit_date,
        'news_category' => $main_news_category_id,
        'news_views' => $views,
        'allow_rating' => $allow_rating,
        'allow_comments' => $allow_comments,
        'allow_trackbacks' => $allow_trackbacks,
        'notes' => $notes,
        'submitter' => $submitter,
        'validated' => $validated,
        'date_and_time' => $time,
        'author' => $author,
    );
    if (multi_lang_content()) {
        $map['news_article'] = 0;
    } else {
        $map['news_article'] = '';
        $map['news_article__text_parsed'] = '';
        $map['news_article__source_user'] = get_member();
    }
    $map += insert_lang_comcode('title', $title, 1);
    $map += insert_lang_comcode('news', $news, 1);
    if ($id !== null) {
        $map['id'] = $id;
    }
    $id = $GLOBALS['SITE_DB']->query_insert('news', $map, true);

    if ($news_categories !== null) {
        $news_categories = array_unique($news_categories);
        foreach ($news_categories as $i => $value) {
            if (($value === null) && (!$already_created_personal_category)) {
                $map = array(
                    'nc_img' => 'newscats/community',
                    'notes' => '',
                    'nc_owner' => $submitter,
                );
                $map += insert_lang('nc_title', do_lang('MEMBER_CATEGORY', $GLOBALS['FORUM_DRIVER']->get_username($submitter, true)), 2);
                $news_category_id = $GLOBALS['SITE_DB']->query_insert('news_categories', $map, true);

                require_code('permissions2');
                set_global_category_access('news', $news_category_id);
            } else {
                $news_category_id = $value;
            }

            if ($news_category_id === null) {
                continue; // Double selected
            }

            $GLOBALS['SITE_DB']->query_insert('news_category_entries', array('news_entry' => $id, 'news_entry_category' => $news_category_id));

            $news_categories[$i] = $news_category_id;
        }
    }

    require_code('attachments2');
    $GLOBALS['SITE_DB']->query_update('news', insert_lang_comcode_attachments('news_article', 2, $news_article, 'news', strval($id)), array('id' => $id), '', 1);

    foreach ($regions as $region) {
        $GLOBALS['SITE_DB']->query_insert('content_regions', array('content_type' => 'news', 'content_id' => strval($id), 'region' => $region));
    }

    log_it('ADD_NEWS', strval($id), $title);

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resource_fs_moniker('news', strval($id), null, null, true);
    }

    if (php_function_allowed('fsockopen')) {
        if (php_function_allowed('set_time_limit')) {
            @set_time_limit(0);
        }

        // Send out on RSS cloud
        if (!$GLOBALS['SITE_DB']->table_is_locked('news_rss_cloud')) {
            $GLOBALS['SITE_DB']->query('DELETE FROM ' . get_table_prefix() . 'news_rss_cloud WHERE register_time<' . strval(time() - 25 * 60 * 60));
        }
        $start = 0;
        do {
            $listeners = $GLOBALS['SITE_DB']->query_select('news_rss_cloud', array('*'), array(), '', 100, $start);
            foreach ($listeners as $listener) {
                $data = $listener['watching_channel'];
                if ($listener['rem_protocol'] == 'xml-rpc') {
                    require_code('xmlrpc');
                    xml_rpc('http://' . $listener['rem_ip'] . ':' . strval($listener['rem_port']) . '/' . $listener['rem_path'], $listener['rem_procedure'], $data, true);
                } // Other protocols not supported
            }
            $start += 100;
        } while (array_key_exists(0, $listeners));
    }

    require_code('seo2');
    if (get_option('enable_seo_fields') === '0') {
        $meta_keywords = '';
        foreach (array_unique(array_merge(($news_categories === null) ? array() : $news_categories, array($main_news_category_id))) as $news_category_id) {
            if ($meta_keywords != '') {
                $meta_keywords .= ',';
            }
            $meta_keywords .= get_translated_text($GLOBALS['SITE_DB']->query_select_value('news_categories', 'nc_title', array('id' => $news_category_id)));
        }
    }
    if (($meta_keywords == '') && ($meta_description == '')) {
        $meta_description = ($news == '') ? $news_article : $news;
        seo_meta_set_for_implicit('news', strval($id), array($title, $meta_description/*, $news_article*/), $meta_description); // News article could be used, but it's probably better to go for the summary only to avoid crap
    } else {
        seo_meta_set_for_explicit('news', strval($id), $meta_keywords, $meta_description);
    }

    if ($validated == 1) {
        delete_cache_entry('main_news');
        delete_cache_entry('main_image_fader_news');
        delete_cache_entry('side_news');
        delete_cache_entry('side_news_archive');
        delete_cache_entry('bottom_news');
        delete_cache_entry('side_news_categories');

        dispatch_news_notification($id, $title, $main_news_category_id);
    }

    if ((!get_mass_import_mode()) && ($validated == 1) && (get_option('site_closed') == '0') && (!$GLOBALS['DEV_MODE']) && (has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(), 'news', strval($main_news_category_id)))) {
        if (get_value('avoid_register_shutdown_function') === '1') {
            send_rss_ping();
        } else {
            register_shutdown_function('send_rss_ping');
        }

        require_code('news_sitemap');
        if (get_value('avoid_register_shutdown_function') === '1') {
            build_news_sitemap();
        } else {
            register_shutdown_function('build_news_sitemap');
        }
    }

    require_code('member_mentions');
    dispatch_member_mention_notifications('news_category', strval($id));

    require_code('sitemap_xml');
    notify_sitemap_node_add('SEARCH:news:view:' . strval($id), $time, $edit_date, SITEMAP_IMPORTANCE_HIGH, 'monthly', has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(), 'news', strval($main_news_category_id)));

    return $id;
}

/**
 * Send out a ping to configured services.
 *
 * @param  boolean $show_errors Whether to show errors
 * @return string HTTP result output
 */
function send_rss_ping($show_errors = true)
{
    $url = find_script('backend') . '?type=rss&mode=news';

    require_code('files');
    $out = '';
    $_ping_url = str_replace('{url}', urlencode(get_base_url()), str_replace('{rss}', urlencode($url), str_replace('{title}', urlencode(get_site_name()), get_option('ping_url'))));
    $ping_urls = explode("\n", $_ping_url);
    foreach ($ping_urls as $ping_url) {
        $ping_url = trim($ping_url);
        if ($ping_url != '') {
            $out .= http_get_contents($ping_url, array('trigger_error' => $show_errors));
        }
    }

    require_code('sitemap_xml');
    $out .= ping_sitemap_xml($url);

    return $out;
}

/**
 * Edit a news entry.
 *
 * @param  AUTO_LINK $id The ID of the news to edit
 * @param  SHORT_TEXT $title The news title
 * @param  LONG_TEXT $news The news summary (or if not an article, the full news)
 * @param  ID_TEXT $author The news author (possibly, a link to an existing author in the system, but does not need to be)
 * @param  BINARY $validated Whether the news has been validated
 * @param  BINARY $allow_rating Whether the news may be rated
 * @param  SHORT_INTEGER $allow_comments Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY $allow_trackbacks Whether the news may have trackbacks
 * @param  LONG_TEXT $notes Notes for the news
 * @param  LONG_TEXT $news_article The news entry (blank means no entry)
 * @param  ?AUTO_LINK $main_news_category The primary news category (null: personal)
 * @param  ?array $news_categories The IDs of the news categories that this is in (null: do not change)
 * @param  SHORT_TEXT $meta_keywords Meta keywords
 * @param  LONG_TEXT $meta_description Meta description
 * @param  ?URLPATH $image URL to the image for the news entry (blank: use cat image) (null: don't delete existing)
 * @param  ?TIME $add_time Add time (null: do not change)
 * @param  ?TIME $edit_time Edit time (null: either means current time, or if $null_is_literal, means reset to to null)
 * @param  ?integer $views Number of views (null: do not change)
 * @param  ?MEMBER $submitter Submitter (null: do not change)
 * @param  array $regions The regions (empty: not region-limited)
 * @param  boolean $null_is_literal Determines whether some nulls passed mean 'use a default' or literally mean 'set to null'
 */
function edit_news($id, $title, $news, $author, $validated, $allow_rating, $allow_comments, $allow_trackbacks, $notes, $news_article, $main_news_category, $news_categories, $meta_keywords, $meta_description, $image, $add_time = null, $edit_time = null, $views = null, $submitter = null, $regions = array(), $null_is_literal = false)
{
    if ($edit_time === null) {
        $edit_time = $null_is_literal ? null : time();
    }

    $rows = $GLOBALS['SITE_DB']->query_select('news', array('*'), array('id' => $id), '', 1);
    $_title = $rows[0]['title'];
    $_news = $rows[0]['news'];
    $_news_article = $rows[0]['news_article'];

    require_code('urls2');

    suggest_new_idmoniker_for('news', 'view', strval($id), '', $title);

    require_code('attachments2');
    require_code('attachments3');

    if (!addon_installed('unvalidated')) {
        $validated = 1;
    }

    require_code('submit');
    $just_validated = (!content_validated('news', strval($id))) && ($validated == 1);
    if ($just_validated) {
        send_content_validated_notification('news', strval($id));
    }

    $update_map = array(
        'news_category' => $main_news_category,
        'allow_rating' => $allow_rating,
        'allow_comments' => $allow_comments,
        'allow_trackbacks' => $allow_trackbacks,
        'notes' => $notes,
        'validated' => $validated,
        'author' => $author,
    );
    $update_map += update_lang_comcode_attachments('news_article', $_news_article, $news_article, 'news', strval($id), null, $rows[0]['submitter']);
    $update_map += lang_remap_comcode('title', $_title, $title);
    $update_map += lang_remap_comcode('news', $_news, $news);

    $update_map['edit_date'] = $edit_time;
    if ($add_time !== null) {
        $update_map['date_and_time'] = $add_time;
    }
    if ($views !== null) {
        $update_map['news_views'] = $views;
    }
    if ($submitter !== null) {
        $update_map['submitter'] = $submitter;
    }

    if ($image !== null) {
        $update_map['news_image'] = $image;
        require_code('files2');
        delete_upload('uploads/repimages', 'news', 'news_image', 'id', $id, $image);
    }

    if ($news_categories !== null) {
        $GLOBALS['SITE_DB']->query_delete('news_category_entries', array('news_entry' => $id));

        foreach ($news_categories as $value) {
            $GLOBALS['SITE_DB']->query_insert('news_category_entries', array('news_entry' => $id, 'news_entry_category' => $value));
        }
    }

    $GLOBALS['SITE_DB']->query_delete('content_regions', array('content_type' => 'news', 'content_id' => strval($id)));
    foreach ($regions as $region) {
        $GLOBALS['SITE_DB']->query_insert('content_regions', array('content_type' => 'news', 'content_id' => strval($id), 'region' => $region));
    }

    log_it('EDIT_NEWS', strval($id), $title);

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resource_fs_moniker('news', strval($id));
    }

    $GLOBALS['SITE_DB']->query_update('news', $update_map, array('id' => $id), '', 1);

    $self_url = build_url(array('page' => 'news', 'type' => 'view', 'id' => $id), get_module_zone('news'), array(), false, false, true);

    if ($just_validated) {
        dispatch_news_notification($id, $title, $main_news_category);
    }

    require_code('seo2');
    if (get_option('enable_seo_fields') === '0') {
        $meta_description = ($news == '') ? $news_article : $news;
        $meta_keywords = '';
        foreach (array_unique(array_merge(($news_categories === null) ? array() : $news_categories, array($main_news_category))) as $news_category_id) {
            if ($meta_keywords != '') {
                $meta_keywords .= ',';
            }
            $meta_keywords .= get_translated_text($GLOBALS['SITE_DB']->query_select_value('news_categories', 'nc_title', array('id' => $news_category_id)));
        }
    }
    seo_meta_set_for_explicit('news', strval($id), $meta_keywords, $meta_description);

    delete_cache_entry('main_news');
    delete_cache_entry('main_image_fader_news');
    delete_cache_entry('side_news');
    delete_cache_entry('side_news_archive');
    delete_cache_entry('bottom_news');
    delete_cache_entry('side_news_categories');

    if (($validated == 1) && (has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(), 'news', strval($main_news_category)))) {
        if (get_value('avoid_register_shutdown_function') === '1') {
            send_rss_ping();
        } else {
            register_shutdown_function('send_rss_ping');
        }
    }

    require_code('feedback');
    update_spacer_post(
        $allow_comments != 0,
        'news',
        strval($id),
        $self_url,
        $title,
        process_overridden_comment_forum('news', strval($id), strval($main_news_category), strval($rows[0]['news_category']))
    );

    require_code('sitemap_xml');
    notify_sitemap_node_edit('SEARCH:news:view:' . strval($id), has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(), 'news', strval($main_news_category)));
}

/**
 * Send out a notification of some new news.
 *
 * @param  AUTO_LINK $id The ID of the news
 * @param  SHORT_TEXT $title The title
 * @param  AUTO_LINK $main_news_category The main news category
 */
function dispatch_news_notification($id, $title, $main_news_category)
{
    $self_url = build_url(array('page' => 'news', 'type' => 'view', 'id' => $id), get_module_zone('news'), array(), false, false, true);

    $is_blog = ($GLOBALS['SITE_DB']->query_select_value('news_categories', 'nc_owner', array('id' => $main_news_category)) !== null);

    if (addon_installed('content_privacy')) {
        require_code('content_privacy');
        $privacy_limits = privacy_limits_for('news', strval($id));
    } else {
        $privacy_limits = array();
    }

    require_code('notifications');
    require_lang('news');
    if ($is_blog) {
        $subject = do_lang('BLOG_NOTIFICATION_MAIL_SUBJECT', get_site_name(), $title);
        $mail = do_notification_lang('BLOG_NOTIFICATION_MAIL', comcode_escape(get_site_name()), comcode_escape($title), array($self_url->evaluate()));
        dispatch_notification('news_entry', strval($main_news_category), $subject, $mail, $privacy_limits);
    } else {
        $subject = do_lang('NEWS_NOTIFICATION_MAIL_SUBJECT', get_site_name(), $title);
        $mail = do_notification_lang('NEWS_NOTIFICATION_MAIL', comcode_escape(get_site_name()), comcode_escape($title), array($self_url->evaluate()));
        dispatch_notification('news_entry', strval($main_news_category), $subject, $mail, $privacy_limits);
    }
}

/**
 * Delete a news entry.
 *
 * @param  AUTO_LINK $id The ID of the news to edit
 */
function delete_news($id)
{
    $rows = $GLOBALS['SITE_DB']->query_select('news', array('*'), array('id' => $id), '', 1);
    if (!array_key_exists(0, $rows)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'news'));
    }
    $title = $rows[0]['title'];
    $news = $rows[0]['news'];
    $news_article = $rows[0]['news_article'];

    $_title = get_translated_text($title);

    require_code('files2');
    delete_upload('uploads/repimages', 'news', 'news_image', 'id', $id);

    $GLOBALS['SITE_DB']->query_delete('news', array('id' => $id), '', 1);
    $GLOBALS['SITE_DB']->query_delete('news_category_entries', array('news_entry' => $id));

    $GLOBALS['SITE_DB']->query_delete('rating', array('rating_for_type' => 'news', 'rating_for_id' => strval($id)));
    $GLOBALS['SITE_DB']->query_delete('trackbacks', array('trackback_for_type' => 'news', 'trackback_for_id' => strval($id)));
    $GLOBALS['SITE_DB']->query_delete('content_regions', array('content_type' => 'news', 'content_id' => strval($id)));
    require_code('notifications');
    delete_all_notifications_on('comment_posted', 'news_' . strval($id));

    delete_lang($title);
    delete_lang($news);
    require_code('attachments2');
    require_code('attachments3');
    if ($news_article !== null) {
        delete_lang_comcode_attachments($news_article, 'news', strval($id));
    }

    require_code('seo2');
    seo_meta_erase_storage('news', strval($id));

    delete_cache_entry('main_news');
    delete_cache_entry('main_image_fader_news');
    delete_cache_entry('side_news');
    delete_cache_entry('side_news_archive');
    delete_cache_entry('bottom_news');
    delete_cache_entry('side_news_categories');

    if (addon_installed('catalogues')) {
        update_catalogue_content_ref('news', strval($id), '');
    }

    log_it('DELETE_NEWS', strval($id), $_title);

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        expunge_resource_fs_moniker('news', strval($id));
    }

    require_code('sitemap_xml');
    notify_sitemap_node_delete('SEARCH:news:view:' . strval($id));
}

/**
 * Import wordpress db
 * Get UI fields for starting news import.
 *
 * @param  boolean $import_to_blog Whether to import to blogs, by default
 * @return Tempcode UI fields
 */
function import_rss_fields($import_to_blog)
{
    $fields = new Tempcode();

    $set_name = 'rss';
    $required = true;
    $set_title = do_lang_tempcode('FILE');
    $field_set = alternate_fields_set__start($set_name);

    $field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'), '', 'file_anytype', false, null, null, true, 'rss,xml,atom'));
    $field_set->attach(form_input_url(do_lang_tempcode('URL'), '', 'rss_feed_url', '', false));

    $fields->attach(alternate_fields_set__end($set_name, $set_title, do_lang_tempcode('DESCRIPTION_RSS_FEED'), $field_set, $required));

    $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '56ae4f6ded172f27ca37e86f4f6df8ef', 'SECTION_HIDDEN' => false, 'TITLE' => do_lang_tempcode('ADVANCED'))));

    $fields->attach(form_input_tick(do_lang_tempcode('IMPORT_BLOG_COMMENTS'), do_lang_tempcode('DESCRIPTION_IMPORT_BLOG_COMMENTS'), 'import_blog_comments', true));
    if (addon_installed('unvalidated')) {
        $fields->attach(form_input_tick(do_lang_tempcode('AUTO_VALIDATE_ALL_POSTS'), do_lang_tempcode('DESCRIPTION_VALIDATE_ALL_POSTS'), 'auto_validate', true));
    }
    if ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) {
        $fields->attach(form_input_tick(do_lang_tempcode('ADD_TO_OWN_ACCOUNT'), do_lang_tempcode('DESCRIPTION_ADD_TO_OWN_ACCOUNT'), 'to_own_account', false));
    }
    $fields->attach(form_input_tick(do_lang_tempcode('IMPORT_TO_BLOG'), do_lang_tempcode('DESCRIPTION_IMPORT_TO_BLOG'), 'import_to_blog', true));
    if (has_privilege(get_member(), 'draw_to_server')) {
        $fields->attach(form_input_tick(do_lang_tempcode('DOWNLOAD_IMAGES'), do_lang_tempcode('DESCRIPTION_DOWNLOAD_IMAGES'), 'download_images', true));
    }

    return $fields;
}

/*
DIRECT WORDPRESS DATABASE IMPORT (imports more than RSS import can)
*/

/**
 * Get data from the Wordpress DB
 *
 * @return array Result structure
 *
 * @ignore
 */
function _get_wordpress_db_data()
{
    $host_name = post_param_string('wp_host');
    $db_name = post_param_string('wp_db');
    $db_user = post_param_string('wp_db_user');
    $db_passwrod = post_param_string('wp_db_password', false, INPUT_FILTER_NONE);
    $db_table_prefix = post_param_string('wp_table_prefix');

    if (substr($db_table_prefix, -1) != '_') {
        $db_table_prefix .= '_';
    }

    // Create database connector
    $db = new DatabaseConnector($db_name, $host_name, $db_user, $db_passwrod, $db_table_prefix);

    $users = $db->query_select('users', array('*'), array(), '', null, 0, true);
    if ($users === null) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }

    $data = array();
    foreach ($users as $user) {
        $user_id = $user['ID'];
        $data[$user_id] = $user;

        // Fetch user posts/pages
        $posts = $db->query('SELECT * FROM ' . $db_table_prefix . 'posts WHERE post_author=' . strval($user_id) . ' AND (' . db_string_equal_to('post_type', 'post') . ' OR ' . db_string_equal_to('post_type', 'page') . ') AND ' . db_string_not_equal_to('post_status', 'auto-draft'));
        foreach ($posts as $post) {
            $post_id = $post['ID'];
            $post['post_id'] = $post_id; // Consistency with XML feed
            $data[$user_id]['POSTS'][$post_id] = $post;

            // Get categories
            $categories = $db->query_select('terms t1 JOIN ' . $db_table_prefix . 'term_taxonomy t2 ON t1.term_id=t2.term_id JOIN ' . $db_table_prefix . 'term_relationships t3 ON t2.term_taxonomy_id=t3.term_taxonomy_id', array('t1.slug', 't1.name'), array('t3.object_id' => $post_id), 'ORDER BY t3.term_order');
            foreach ($categories as $category) {
                $data[$user_id]['POSTS'][$post_id]['category'][$category['slug']] = $category['name'];
            }

            // Comments
            $comments = $db->query_select('comments', array('*'), array('comment_post_ID' => $post_id), 'ORDER BY comment_date_gmt');
            foreach ($comments as $comment) {
                $comment_id = $comment['comment_ID'];
                $comment['author_ip'] = $comment['comment_author_IP']; // Consistency with XML feed
                $data[$user_id]['POSTS'][$post_id]['COMMENTS'][$comment_id] = $comment;
            }
        }
    }

    return $data;
}

/*
NEWS IMPORT UTILITY FUNCTIONS
*/

/**
 * Get data from wordpress DB.
 *
 * @param  string $html HTML
 * @param  boolean $force_linebreaks Whether to add in HTML line breaks from whitespace ones.
 * @return string Comcode
 */
function import_foreign_news_html($html, $force_linebreaks = false)
{
    if (($force_linebreaks) && (strpos($html, '<br') === false)) {
        $html = nl2br($html);
    }

    // Wordpress images
    $matches = array();
    $num_matches = preg_match_all('#\[caption id="(\w+)" align="align(left|right|center|none)" width="(\d+)"\](.*)\[/caption\]#Us', $html, $matches);
    for ($i = 0; $i < $num_matches; $i++) {
        $test = strpos($matches[4][$i], ' /></a> ');
        if ($test !== false) {
            $matches[4][$i] = substr($matches[4][$i], 0, $test) . ' /></a> <p class="wp-caption-text">' . substr($matches[4][$i], $test + strlen(' /></a> ')) . '</p>';
        } else {
            $test = strpos($matches[4][$i], ' /> ');
            if ($test !== false) {
                $matches[4][$i] = substr($matches[4][$i], 0, $test) . ' /> <p class="wp-caption-text">' . substr($matches[4][$i], $test + strlen(' /> ')) . '</p>';
            }
        }
        $new = '[surround="attachment wp-caption align' . $matches[2][$i] . ' ' . $matches[1][$i] . '" style="width: ' . $matches[3][$i] . 'px"]' . $matches[4][$i] . '[/surround]';
        $html = str_replace($matches[0][$i], $new, $html);
    }
    $html = preg_replace('#<a([^>]*)><img #', '<a rel="lightbox"${1}><img ', $html);

    // Blogger images
    $html = str_replace('imageanchor="1"', 'rel="lightbox"', $html);

    // General conversion to Comcode
    require_code('comcode_from_html');
    return semihtml_to_comcode($html, false);
}

/**
 * Download remote images in some HTML and replace with local references under uploads/website_specific AND fix any links to other articles being imported to make them local links.
 *
 * @param  boolean $download_images Whether to download images to local
 * @param  string $data HTML (passed by reference)
 * @param  array $imported_news Imported items, in Composr's RSS-parsed format [list of maps containing full_url and import_id] (used to fix links)
 * @ignore
 */
function _news_import_grab_images_and_fix_links($download_images, &$data, $imported_news)
{
    $matches = array();
    if ($download_images) {
        $num_matches = preg_match_all('#<img[^<>]*\ssrc=["\']([^\'"]*)["\']#i', $data, $matches); // If there's an <a> to the same URL, this will be replaced too
        for ($i = 0; $i < $num_matches; $i++) {
            _news_import_grab_image($data, $matches[1][$i]);
        }
        $num_matches = preg_match_all('#<a[^<>]*\s*href=["\']([^\'"]*)["\']\s*imageanchor=["\']1["\']#i', $data, $matches);
        for ($i = 0; $i < $num_matches; $i++) {
            _news_import_grab_image($data, $matches[1][$i]);
        }
        $num_matches = preg_match_all('#<a rel="lightbox" href=["\']([^\'"]*)["\']#i', $data, $matches);
        for ($i = 0; $i < $num_matches; $i++) {
            _news_import_grab_image($data, $matches[1][$i]);
        }
    }

    // Go through other items, in case this news article/page is linking to them and needs a fixed link
    foreach ($imported_news as $item) {
        if (array_key_exists('full_url', $item)) {
            $num_matches = preg_match_all('#<a\s*([^<>]*)href="' . str_replace('#', '\#', preg_quote(escape_html($item['full_url']))) . '"([^<>]*)>(.*)</a>#isU', $data, $matches);
            for ($i = 0; $i < $num_matches; $i++) {
                if (($matches[1][$i] == '') && ($matches[2][$i] == '') && (strpos($data, '[html]') === false)) {
                    $data = str_replace($matches[0][$i], '[page="_SEARCH:news:view:' . strval($item['import_id']) . '"]' . $matches[3][$i] . '[/page]', $data);
                } else {
                    $new_url = build_url(array('page' => 'news', 'type' => 'view', 'id' => $item['import_id']), get_module_zone('news'), array(), false, false, true);
                    $data = str_replace($matches[0][$i], '<a ' . $matches[1][$i] . 'href="' . escape_html($new_url->evaluate()) . '"' . $matches[2][$i] . '>' . $matches[3][$i] . '</a>', $data);
                }
            }
        }
    }
}

/**
 * Download a specific remote image and sub in the new URL.
 *
 * @param  string $data HTML (passed by reference)
 * @param  URLPATH $url URL
 *
 * @ignore
 */
function _news_import_grab_image(&$data, $url)
{
    $url = qualify_url($url, get_base_url());
    if (substr($url, 0, strlen(get_custom_base_url() . '/')) == get_custom_base_url() . '/') {
        return;
    }
    require_code('images');
    if (!is_image($url, IMAGE_CRITERIA_WEBSAFE, true)) {
        return;
    }

    require_code('urls2');
    list($target_path, $target_url) = find_unique_path('uploads/attachments', basename($url));

    if (!file_exists(dirname($target_path))) {
        require_code('files2');
        make_missing_directory(dirname($target_path));
    }

    $target_handle = fopen($target_path, 'wb') or intelligent_write_error($target_path);
    flock($target_handle, LOCK_EX);
    $result = http_get_contents($url, array('trigger_error' => false, 'write_to_file' => $target_handle));
    flock($target_handle, LOCK_UN);
    fclose($target_handle);
    sync_file($target_path);
    fix_permissions($target_path);
    if ($result !== null) {
        $data = str_replace('"' . $url . '"', $target_url, $data);
        $data = str_replace('"' . preg_replace('#^http://.*/#U', '/', $url) . '"', $target_url, $data);
        $data = str_replace('\'' . $url . '\'', $target_url, $data);
        $data = str_replace('\'' . preg_replace('#^http://.*/#U', '/', $url) . '\'', $target_url, $data);
    }
}
