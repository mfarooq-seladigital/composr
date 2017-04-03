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
 * @package    core_rich_media
 */

/*
Viewing attachments (but not direct rendering - that is in media_rendering.php).
*/

/**
 * Get Tempcode for a Comcode rich-media attachment.
 *
 * @param  ID_TEXT $tag The attachment tag
 * @set attachment attachment_safe
 * @param  array $attributes A map of the attributes (name=>val) for the tag
 * @param  array $attachment_row A map of the attachment properties (name=>val) for the attachment
 * @param  string $pass_id A special identifier to mark where the resultant Tempcode is going to end up (e.g. the ID of a post)
 * @param  MEMBER $source_member The member who is responsible for this Comcode
 * @param  boolean $as_admin Whether to check as arbitrary admin
 * @param  object $connection The database connection to use
 * @param  ?array $highlight_bits A list of words to highlight (null: none)
 * @param  ?MEMBER $on_behalf_of_member The member we are running on behalf of, with respect to how attachments are handled; we may use this members attachments that are already within this post, and our new attachments will be handed to this member (null: member evaluating)
 * @param  boolean $semiparse_mode Whether to parse so as to create something that would fit inside a semihtml tag. It means we generate HTML, with Comcode written into it where the tag could never be reverse-converted (e.g. a block).
 * @return Tempcode The Tempcode for the attachment
 */
function render_attachment($tag, $attributes, $attachment_row, $pass_id, $source_member, $as_admin, $connection, $highlight_bits = null, $on_behalf_of_member = null, $semiparse_mode = false)
{
    require_code('comcode_renderer');
    require_code('media_renderer');
    require_code('mime_types');
    require_code('images');

    // Make sure formal thumbnail still exists / create if missing
    if (is_image($attachment_row['a_original_filename'])) {
        $attachment_row['a_thumb_url'] = ensure_thumbnail($attachment_row['a_url'], $attachment_row['a_thumb_url'], 'attachments', 'attachments', $attachment_row['id'], 'a_thumb_url', null, true);
    }

    // Copy in some standardised media details from what we know by other means (i.e. not coming in as Comcode-attributes)
    $attributes['filesize'] = strval($attachment_row['a_file_size']);
    $attributes['wysiwyg_editable'] = ($tag == 'attachment_safe') ? '1' : '0';
    $attributes['filename'] = $attachment_row['a_original_filename'];
    if ((!array_key_exists('mime_type', $attributes)) || ($attributes['mime_type'] == '')) {
        $attributes['mime_type'] = get_mime_type(get_file_extension($attachment_row['a_original_filename']), $as_admin || has_privilege($source_member, 'comcode_dangerous'));
    }

    // Work out description
    if ((!array_key_exists('description', $attributes)) && (array_key_exists('a_description', $attachment_row))) {
        $attributes['description'] = $attachment_row['a_description'];
    }

    // Work out URL, going through the attachment frontend script
    $url_safe = $attachment_row['a_url'];
    if (url_is_local($url_safe)) {
        $url_safe = get_custom_base_url() . '/' . $url_safe;
    }
    $url = mixed();
    $is_dat = (substr($url_safe, -4) == '.dat');
    if ($tag == 'attachment' || $is_dat) {
        $url = new Tempcode();

        $url->attach(find_script('attachment') . '?id=' . urlencode(strval($attachment_row['id'])));
        if (is_forum_db($connection)) {
            $url->attach('&forum_db=1');
            $attributes['num_downloads'] = symbol_tempcode('ATTACHMENT_DOWNLOADS', array(strval($attachment_row['id']), '1'));
        } else {
            $attributes['num_downloads'] = symbol_tempcode('ATTACHMENT_DOWNLOADS', array(strval($attachment_row['id']), '0'));
        }

        if ($is_dat) {
            $url_safe = $url->evaluate(); // We can't show file-path to a .dat, can't be downloaded and looks ugly
        }

        $keep = symbol_tempcode('KEEP');
        $url->attach($keep);
        if (get_option('anti_leech') == '1') {
            $url->attach('&for_session=');
            $url->attach(symbol_tempcode('SESSION_HASHED'));
        }

        if ((array_key_exists('thumb_url', $attributes)) && ($attributes['thumb_url'] != '')) {
            $attributes['thumb_url'] = new Tempcode();
            $attributes['thumb_url']->attach($url);
            $attributes['thumb_url']->attach('&thumb=1&no_count=1');
        }
    } else { // attachment_safe
        $url = $url_safe;

        if ((!array_key_exists('thumb_url', $attributes)) || ($attributes['thumb_url'] == '')) {
            $attributes['thumb_url'] = $attachment_row['a_thumb_url'];
        }
    }

    // Render
    $ret = render_media_url(
        $url,
        $url_safe,
        $attributes + array('context' => 'attachment'),
        $as_admin,
        $source_member,
        MEDIA_TYPE_ALL,
        ((array_key_exists('type', $attributes)) && ($attributes['type'] != '')) ? $attributes['type'] : null,
        $attachment_row['a_url']
    );
    if (is_null($ret)) {
        $ret = do_template('WARNING_BOX', array('_GUID' => '1e8a6c605fb61b9b5067a9d627506654', 'WARNING' => do_lang_tempcode('comcode:INVALID_ATTACHMENT')));
    }
    return $ret;
}

/**
 * Find if the specified member has access to view the specified attachment.
 *
 * @param  MEMBER $member The member being checked whether to have the access
 * @param  AUTO_LINK $id The ID code for the attachment being checked
 * @param  ?object $connection The database connection to use (null: site DB)
 * @return boolean Whether the member has attachment access
 */
function has_attachment_access($member, $id, $connection = null)
{
    if (is_null($connection)) {
        $connection = $GLOBALS['SITE_DB'];
    }

    if ($GLOBALS['FORUM_DRIVER']->is_super_admin($member)) {
        return true;
    }

    $refs = $connection->query_select('attachment_refs', array('r_referer_type', 'r_referer_id'), array('a_id' => $id));

    foreach ($refs as $ref) {
        $type = $ref['r_referer_type'];
        $ref_id = $ref['r_referer_id'];
        if ((file_exists(get_file_base() . '/sources/hooks/systems/attachments/' . filter_naughty_harsh($type) . '.php')) || (file_exists(get_file_base() . '/sources_custom/hooks/systems/attachments/' . filter_naughty_harsh($type) . '.php'))) {
            require_code('hooks/systems/attachments/' . filter_naughty_harsh($type));
            $object = object_factory('Hook_attachments_' . filter_naughty_harsh($type));

            if ($object->run($ref_id, $connection)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Show the image of an attachment/thumbnail.
 */
function attachments_script()
{
    // Closed site
    $site_closed = get_option('site_closed');
    if (($site_closed == '1') && (!has_privilege(get_member(), 'access_closed_site')) && (!$GLOBALS['IS_ACTUALLY_ADMIN'])) {
        header('Content-type: text/plain; charset=' . get_charset());
        @exit(get_option('closed'));
    }

    $id = get_param_integer('id', 0);
    $connection = $GLOBALS[(get_param_integer('forum_db', 0) == 1) ? 'FORUM_DB' : 'SITE_DB'];
    $has_no_restricts = !is_null($connection->query_select_value_if_there('attachment_refs', 'id', array('r_referer_type' => 'null', 'a_id' => $id)));

    if (!$has_no_restricts) {
        global $SITE_INFO;
        if ((!is_guest()) || (!isset($SITE_INFO['any_guest_cached_too'])) || ($SITE_INFO['any_guest_cached_too'] == '0')) {
            if ((get_param_string('for_session', '') != md5(get_session_id())) && (get_option('anti_leech') == '1') && (cms_srv('HTTP_REFERER') != '')) {
                warn_exit(do_lang_tempcode('LEECH_BLOCK'));
            }
        }
    }

    require_lang('comcode');

    // Lookup
    $rows = $connection->query_select('attachments', array('*'), array('id' => $id), 'ORDER BY a_add_time DESC');
    if (!array_key_exists(0, $rows)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE', do_lang_tempcode('ATTACHMENT')));
    }
    $myrow = $rows[0];
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $myrow['a_add_time']));
    if ($myrow['a_url'] == '') {
        warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
    }

    if (!$has_no_restricts) {
        // Permission
        if (substr($myrow['a_url'], 0, 20) == 'uploads/attachments/') {
            if (!has_attachment_access(get_member(), $id, $connection)) {
                access_denied('ATTACHMENT_ACCESS');
            }
        }
    }

    $thumb = get_param_integer('thumb', 0);

    if ($thumb == 1) {
        require_code('images');
        $myrow['a_thumb_url'] = ensure_thumbnail($myrow['a_url'], $myrow['a_thumb_url'], 'attachments', 'attachments', $id, 'a_thumb_url', null, true);
        $full = $myrow['a_thumb_url'];
    } else {
        $full = $myrow['a_url'];

        if (get_param_integer('no_count', 0) == 0) {
            // Update download count
            if (cms_srv('HTTP_RANGE') == '') {
                $connection->query_update('attachments', array('a_num_downloads' => $myrow['a_num_downloads'] + 1, 'a_last_downloaded_time' => time()), array('id' => $id), '', 1, null, false, true);
            }
        }
    }

    $original_filename = ($thumb == 1 && $myrow['a_thumb_url'] != '') ? rawurldecode(basename($myrow['a_thumb_url'])) : $myrow['a_original_filename'];
    $extension = get_file_extension($original_filename);

    // Send header
    require_code('mime_types');
    $mime_type = get_mime_type($extension, has_privilege($myrow['a_member_id'], 'comcode_dangerous'));

    // Send header
    header('Content-Type: ' . $mime_type . '; authoritative=true;');
    if ((strpos($original_filename, "\n") !== false) || (strpos($original_filename, "\r") !== false)) {
        log_hack_attack_and_exit('HEADER_SPLIT_HACK');
    }
    header('Content-Disposition: inline; filename="' . escape_header($original_filename, true) . '"');

    // Is it non-local? If so, redirect
    if (!url_is_local($full)) {
        if ((strpos($full, "\n") !== false) || (strpos($full, "\r") !== false)) {
            log_hack_attack_and_exit('HEADER_SPLIT_HACK');
        }
        header('Location: ' . escape_header($full));
        return;
    }

    $_full = get_custom_file_base() . '/' . rawurldecode($full);
    if (!file_exists($_full)) {
        warn_exit(do_lang_tempcode('_MISSING_RESOURCE', 'url:' . escape_html($full))); // File is missing, we can't do anything
    }
    $size = filesize($_full);

    header('Accept-Ranges: bytes');

    // Caching
    header('Pragma: private');
    header('Cache-Control: private');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60 * 60 * 24 * 365) . ' GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $myrow['a_add_time']) . ' GMT');

    // Default to no resume
    $from = 0;
    $new_length = $size;

    safe_ini_set('zlib.output_compression', 'Off'); // So ranges work, plus workaround to bugs caused by IE being 'smart' http://blogs.msdn.com/b/ieinternals/archive/2014/10/21/http-compression-optimize-file-formats-with-deflate.aspx

    // They're trying to resume (so update our range)
    $httprange = cms_srv('HTTP_RANGE');
    if (strlen($httprange) > 0) {
        $_range = explode('=', cms_srv('HTTP_RANGE'));
        if (count($_range) == 2) {
            if (strpos($_range[0], '-') === false) {
                $_range = array_reverse($_range);
            }
            $range = $_range[0];
            if (substr($range, 0, 1) == '-') {
                $range = strval($size - intval(substr($range, 1)) - 1) . $range;
            }
            if (substr($range, -1, 1) == '-') {
                $range .= strval($size - 1);
            }
            $bits = explode('-', $range);
            if (count($bits) == 2) {
                list($from, $to) = array_map('intval', $bits);
                if (($to - $from != 0) || ($from == 0)) { // Workaround to weird behaviour on Chrome
                    $new_length = $to - $from + 1;

                    header('HTTP/1.1 206 Partial Content');
                    header('Content-Range: bytes ' . $range . '/' . strval($size));
                } else {
                    $from = 0;
                }
            }
        }
    }
    header('Content-Length: ' . strval($new_length));
    if (php_function_allowed('set_time_limit')) {
        @set_time_limit(0);
    }
    error_reporting(0);

    if ($from == 0) {
        $GLOBALS['SITE_DB']->query('UPDATE ' . get_table_prefix() . 'values SET the_value=(the_value+' . strval($size) . ') WHERE the_name=\'download_bandwidth\'', 1);
    }

    safe_ini_set('ocproducts.xss_detect', '0');

    if (cms_srv('REQUEST_METHOD') == 'HEAD') {
        return;
    }

    // Send actual data
    $myfile = fopen($_full, 'rb');
    fseek($myfile, $from);
    /*if ($size == $new_length)    Uses a lot of memory :S
    {
        fpassthru($myfile);
    } else {*/
    $i = 0;
    flush(); // Works around weird PHP bug that sends data before headers, on some PHP versions
    while ($i < $new_length) {
        $content = fread($myfile, min($new_length - $i, 1048576));
        echo $content;
        $len = strlen($content);
        if ($len == 0) {
            break;
        }
        $i += $len;
    }
    fclose($myfile);
    //}
}

/**
 * Shows an HTML page of all attachments we can access with selection buttons.
 */
function attachment_popup_script()
{
    require_lang('comcode');
    require_javascript('editing');

    $connection = (get_page_name() == 'topics') ? $GLOBALS['FORUM_DB'] : $GLOBALS['SITE_DB'];

    $members = array();
    if (!is_guest()) {
        $members[get_member()] = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
    }
    if (has_privilege(get_member(), 'reuse_others_attachments')) {
        $_members = $connection->query_select('attachments', array('DISTINCT a_member_id'));
        foreach ($_members as $_member) {
            $members[$_member['a_member_id']] = $GLOBALS['FORUM_DRIVER']->get_username($_member['a_member_id']);
        }
    }
    asort($members);

    $member_now = post_param_integer('member_id', get_member());
    if (!array_key_exists($member_now, $members)) {
        access_denied('REUSE_ATTACHMENT');
    }

    $list = new Tempcode();
    foreach ($members as $member_id => $username) {
        $list->attach(form_input_list_entry(strval($member_id), $member_id == $member_now, $username));
    }

    $field_name = filter_naughty_harsh(get_param_string('field_name', 'post'));
    $post_url = get_self_url();

    $rows = $connection->query_select('attachments', array('*'), array('a_member_id' => $member_now));
    $attachments = array();
    foreach ($rows as $myrow) {
        $may_delete = (get_member() == $myrow['a_member_id']) && ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()));

        if ((post_param_integer('delete_' . strval($myrow['id']), 0) == 1) && ($may_delete)) {
            require_code('attachments3');
            _delete_attachment($myrow['id'], $connection);
            continue;
        }

        $myrow['description'] = $myrow['a_description'];
        $tpl = render_attachment('attachment', array(), $myrow, uniqid('', true), get_member(), false, $connection, null, get_member());
        $attachments[] = array(
            'FIELD_NAME' => $field_name,
            'TPL' => $tpl,
            'DESCRIPTION' => $myrow['a_description'],
            'ID' => strval($myrow['id']),
            'MAY_DELETE' => $may_delete,
            'DELETE_URL' => $post_url,
        );
    }

    $content = do_template('ATTACHMENTS_BROWSER', array('_GUID' => '7773aad46fb0bfe563a142030beb1a36', 'LIST' => $list, 'ATTACHMENTS' => $attachments, 'URL' => $post_url));

    require_code('site');
    attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

    $echo = do_template('STANDALONE_HTML_WRAP', array('_GUID' => '954617cc747b5cece4cc406d8c110150', 'TITLE' => do_lang_tempcode('ATTACHMENT_POPUP'), 'POPUP' => true, 'CONTENT' => $content));
    $echo->handle_symbol_preprocessing();
    $echo->evaluate_echo();
}
