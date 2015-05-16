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
 * @package    bookmarks
 */

/**
 * Script to make a bookmark add-form popup.
 */
function bookmarks_script()
{
    require_lang('bookmarks');

    $type = get_param_string('type');
    switch ($type) {
        case '_add':
            $title = get_screen_title('ADD_BOOKMARK');

            $folder = post_param_string('folder_new', '');
            if ($folder == '') {
                $folder = post_param_string('folder');
            }
            if ($folder == '!') {
                $folder = '';
            }

            add_bookmark(get_member(), $folder, post_param_string('title'), post_param_string('page_link'));

            $content = inform_screen($title, do_lang_tempcode('SUCCESS'));
            $content->attach('<script>// <![CDATA[
                    if (window.opener) window.close();
            //]]></script>');
            break;
        default:
            $url = find_script('bookmarks') . '?no_redirect=1&type=_add';
            $keep = symbol_tempcode('KEEP');
            $url .= $keep->evaluate();
            $content = add_bookmark_form($url);
            break;
    }
    $echo = do_template('STANDALONE_HTML_WRAP', array('_GUID' => '625e1e34e0526fc84f97954844958a0b', 'TITLE' => do_lang_tempcode('ADD_BOOKMARK'), 'POPUP' => true, 'CONTENT' => $content));
    $echo->handle_symbol_preprocessing();
    $echo->evaluate_echo();
}

/**
 * Get the form to add a bookmark / set breadcrumbs.
 *
 * @param  mixed $post_url Where the form should go to
 * @return tempcode The form
 */
function add_bookmark_form($post_url)
{
    $title = get_screen_title('ADD_BOOKMARK');

    require_lang('zones');

    require_code('character_sets');

    $url = base64_decode(get_param_string('url', '', true));
    $url = convert_to_internal_encoding($url, 'UTF-8'); // Note that this is intentionally passed in to not be a short URL
    $page_link = convert_to_internal_encoding(url_to_page_link($url, false, false), 'UTF-8');
    $default_title = get_param_string('title', '', true);
    $default_title = convert_to_internal_encoding($default_title, 'UTF-8');
    $default_title = preg_replace('#\s.\s' . preg_quote(get_site_name(), '#') . '$#s', '', $default_title);
    $default_title = preg_replace('#^' . preg_quote(get_site_name(), '#') . '\s.\s#s', '', $default_title);
    $default_title_2 = @preg_replace('#\s.\s' . preg_quote(get_site_name(), '#') . '$#su', '', $default_title);
    $default_title_2 = @preg_replace('#^' . preg_quote(get_site_name(), '#') . '\s.\s#su', '', $default_title_2);
    if ($default_title_2 !== false) {
        $default_title = $default_title_2;
    }
    if (!is_string($default_title)) {
        $default_title = '';
    }

    require_code('form_templates');
    $rows = $GLOBALS['SITE_DB']->query_select('bookmarks', array('DISTINCT b_folder'), array('b_owner' => get_member()), 'ORDER BY b_folder');
    $list = new Tempcode();
    $list->attach(form_input_list_entry('', false, do_lang_tempcode('NA_EM')));
    $list->attach(form_input_list_entry('!', true, do_lang_tempcode('ROOT_EM')));
    foreach ($rows as $row) {
        if ($row['b_folder'] != '') {
            $list->attach(form_input_list_entry($row['b_folder']));
        }
    }
    $fields = new Tempcode();

    $set_name = 'folder';
    $required = true;
    $set_title = do_lang_tempcode('BOOKMARK_FOLDER');
    $field_set = alternate_fields_set__start($set_name);

    $field_set->attach(form_input_list(do_lang_tempcode('EXISTING'), do_lang_tempcode('DESCRIPTION_OLD_BOOKMARK_FOLDER'), 'folder', $list, null, false, false));

    $field_set->attach(form_input_line(do_lang_tempcode('NEW'), do_lang_tempcode('DESCRIPTION_NEW_BOOKMARK_FOLDER'), 'folder_new', '', false));

    $fields->attach(alternate_fields_set__end($set_name, $set_title, '', $field_set, $required));

    $fields->attach(form_input_line(do_lang_tempcode('TITLE'), do_lang_tempcode('DESCRIPTION_TITLE'), 'title', ($default_title == '') ? '' : substr($default_title, 0, 200), true));
    $fields->attach(form_input_line(do_lang_tempcode('PAGE_LINK'), do_lang_tempcode('DESCRIPTION_PAGE_LINK_BOOKMARK'), 'page_link', $page_link, true));
    $submit_name = do_lang_tempcode('ADD_BOOKMARK');

    breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('MANAGE_BOOKMARKS'))));

    $javascript = 'var title=document.getElementById(\'title\'); if (((title.value==\'\') || (title.value==\'0\')) && (window.opener)) title.value=get_inner_html(window.opener.document.getElementsByTagName(\'title\')[0]); ';

    return do_template('FORM_SCREEN', array('_GUID' => '7e94bb97008de4fa0fffa2b5f91c95eb', 'TITLE' => $title, 'HIDDEN' => '', 'TEXT' => '', 'FIELDS' => $fields, 'URL' => $post_url, 'SUBMIT_ICON' => 'menu___generic_admin__add_one', 'SUBMIT_NAME' => $submit_name, 'JAVASCRIPT' => $javascript));
}

/**
 * Add a bookmark.
 *
 * @param  MEMBER $member Member who it will belong to
 * @param  string $folder Folder (blank: root)
 * @param  string $title Title/caption
 * @param  string $page_link The page-link
 * @return AUTO_LINK The ID
 */
function add_bookmark($member, $folder, $title, $page_link)
{
    $id = $GLOBALS['SITE_DB']->query_insert('bookmarks', array(
        'b_owner' => $member,
        'b_folder' => $folder,
        'b_title' => $title,
        'b_page_link' => $page_link,
    ), true);

    decache('menu');

    return $id;
}

/**
 * Edit a bookmark.
 *
 * @param  AUTO_LINK $id The ID
 * @param  MEMBER $member Member who it belongs to
 * @param  string $title Title/caption
 * @param  string $page_link The page-link
 */
function edit_bookmark($id, $member, $title, $page_link)
{
    $GLOBALS['SITE_DB']->query_update('bookmarks', array('b_page_link' => $page_link, 'b_title' => $title), array('id' => $id, 'b_owner' => $member), '', 1); // Second select param for needed security

    decache('menu');
}

/**
 * Delete a bookmark.
 *
 * @param  AUTO_LINK $id The ID
 * @param  ?MEMBER $member Member who it belongs to (null: do not check)
 */
function delete_bookmark($id, $member = null)
{
    $where = array('id' => $id);
    if (!is_null($member)) {
        $where['b_owner'] = $member; // Second select param for needed security
    }
    $GLOBALS['SITE_DB']->query_delete('bookmarks', $where, '', 1);

    decache('menu');
}
