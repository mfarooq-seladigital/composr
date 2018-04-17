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
 * @package    core
 */

/**
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__content2()
{
    if (!defined('METADATA_HEADER_NO')) {
        define('METADATA_HEADER_NO', 0);
        define('METADATA_HEADER_YES', 1);
        define('METADATA_HEADER_FORCE', 2);

        define('ORDER_AUTOMATED_CRITERIA', 2147483647); // lowest order, shared for all who care not about order, so other SQL ordering criterias take precedence
    }
}

/**
 * Get an order inputter.
 *
 * @param  ID_TEXT $entry_type The type of resource being ordered
 * @param  ?ID_TEXT $category_type The type of resource being ordered within (null: no categories involved)
 * @param  ?integer $current_order The current order (null: new, so add to end)
 * @param  ?integer $max Maximum order field (null: work out from content type metadata)
 * @param  ?integer $total Number of entries, alternative to supplying $max (null: work out from content type metadata)
 * @param  ID_TEXT $order_field The POST field to save under
 * @param  ?Tempcode $description Description for field input (null: {!ORDER})
 * @return Tempcode Ordering field
 */
function get_order_field($entry_type, $category_type, $current_order, $max = null, $total = null, $order_field = 'order', $description = null)
{
    $new = is_null($current_order);

    $min = 0;

    require_code('content');
    $ob = get_content_object($entry_type);
    $info = $ob->info();

    $db_order_field = isset($info['order_field']) ? $info['order_field'] : 'order';

    if (is_null($max)) {
        $max = $info['connection']->query_select_value($info['table'], 'MAX(' . $db_order_field . ')', null, 'WHERE ' . $db_order_field . '<>' . strval(ORDER_AUTOMATED_CRITERIA));
        if (is_null($max)) {
            $max = 0;
        }
    }

    if (is_null($total)) {
        $total = $info['connection']->query_select_value($info['table'], 'COUNT(*)');
    }

    if ($total > $max) {
        // Need to make sure there's always enough slots to pick from
        $max = $total - 1;
    }

    if ($new) {
        $test = $info['connection']->query_select_value($info['table'], 'COUNT(' . $db_order_field . ')', null, 'WHERE ' . $db_order_field . '=' . strval(ORDER_AUTOMATED_CRITERIA));

        if ($test > 0) {
            $current_order = ORDER_AUTOMATED_CRITERIA; // Ah, we are already in the habit of automated ordering here
        } else {
            $max++; // Space for new one on end
            $current_order = $max;
        }
    }

    if (is_null($description)) {
        if (is_null($category_type)) {
            $description = do_lang_tempcode('DESCRIPTION_ORDER_NO_CATS', escape_html($entry_type));
        } else {
            $description = do_lang_tempcode('DESCRIPTION_ORDER', escape_html($entry_type), escape_html($category_type));
        }
    }

    if ($max > 100) {
        // Too much for a list, so do a typed integer input
        return form_input_integer(do_lang_tempcode('ORDER'), $description, $order_field, $current_order, false);
    }

    // List input
    $order_list = new Tempcode();
    for ($i = $min; $i <= $max; $i++) {
        $selected = ($i === $current_order);
        $order_list->attach(form_input_list_entry(strval($i), $selected, integer_format($i + 1)));
    }
    $order_list->attach(form_input_list_entry('', $current_order == ORDER_AUTOMATED_CRITERIA, do_lang_tempcode('ORDER_AUTOMATED_CRITERIA')));
    return form_input_list(do_lang_tempcode('ORDER'), $description, $order_field, $order_list, null, false, false);
}

/**
 * Get submitted order value.
 *
 * @param  ID_TEXT $order_field The POST field
 * @return integer The order value
 */
function post_param_order_field($order_field = 'order')
{
    $ret = post_param_integer($order_field, null);
    if (is_null($ret)) {
        $ret = ORDER_AUTOMATED_CRITERIA;
    }
    return $ret;
}

/**
 * Get template fields to insert into a form page, for manipulation of metadata.
 *
 * @param  ID_TEXT $content_type The type of resource (e.g. download)
 * @param  ?ID_TEXT $content_id The ID of the resource (null: adding)
 * @param  boolean $allow_no_owner Whether to allow owner to be left blank (meaning no owner)
 * @param  ?array $fields_to_skip List of fields to NOT take in (null: empty list)
 * @param  integer $show_header Whether to show a header (a METADATA_HEADER_* constant)
 * @return Tempcode Form page Tempcode fragment
 */
function metadata_get_fields($content_type, $content_id, $allow_no_owner = false, $fields_to_skip = null, $show_header = 1)
{
    require_lang('metadata');

    $fields = new Tempcode();

    if (has_privilege(get_member(), 'edit_meta_fields')) {
        if (is_null($fields_to_skip)) {
            $fields_to_skip = array();
        }

        require_code('content');
        $ob = get_content_object($content_type);
        $info = $ob->info();

        require_code('content');
        $content_row = mixed();
        if (!is_null($content_id)) {
            list(, , , $content_row) = content_get_details($content_type, $content_id);
        }

        $views_field = in_array('views', $fields_to_skip) ? null : $info['views_field'];
        if (!is_null($views_field)) {
            $views = is_null($content_row) ? 0 : $content_row[$views_field];
            $fields->attach(form_input_integer(do_lang_tempcode('COUNT_VIEWS'), do_lang_tempcode('DESCRIPTION_META_VIEWS'), 'meta_views', null, false));
        }

        $submitter_field = in_array('submitter', $fields_to_skip) ? null : $info['submitter_field'];
        if (!is_null($submitter_field)) {
            $submitter = is_null($content_row) ? get_member() : $content_row[$submitter_field];
            $username = $GLOBALS['FORUM_DRIVER']->get_username($submitter);
            if (is_null($username)) {
                $username = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
            }
            $fields->attach(form_input_username(do_lang_tempcode('OWNER'), do_lang_tempcode('DESCRIPTION_OWNER'), 'meta_submitter', $username, !$allow_no_owner));
        }

        $add_time_field = in_array('add_time', $fields_to_skip) ? null : $info['add_time_field'];
        if (!is_null($add_time_field)) {
            $add_time = is_null($content_row) ? null : $content_row[$add_time_field];
            $fields->attach(form_input_date(do_lang_tempcode('ADD_TIME'), do_lang_tempcode('DESCRIPTION_META_ADD_TIME'), 'meta_add_time', !is_null($content_row), is_null($content_row), true, $add_time, 40, intval(date('Y')) - 20, null));
        }

        if (!is_null($content_id)) {
            $edit_time_field = in_array('edit_time', $fields_to_skip) ? null : $info['edit_time_field'];
            if (!is_null($edit_time_field)) {
                $edit_time = is_null($content_row) ? null : (is_null($content_row[$edit_time_field]) ? time() : max(time(), $content_row[$edit_time_field]));
                $fields->attach(form_input_date(do_lang_tempcode('EDIT_TIME'), do_lang_tempcode('DESCRIPTION_META_EDIT_TIME'), 'meta_edit_time', false, is_null($edit_time), true, $edit_time, 10, null, null));
            }
        }

        if (($info['support_url_monikers']) && (!in_array('url_moniker', $fields_to_skip))) {
            $url_moniker = mixed();
            if (!is_null($content_id)) {
                if ($content_type == 'comcode_page') {
                    list($zone, $_content_id) = explode(':', $content_id);
                    $attributes = array();
                    $url_moniker = find_id_moniker(array('page' => $_content_id) + $attributes, $zone);
                } else {
                    $_content_id = $content_id;
                    list($zone, $attributes,) = page_link_decode($info['view_page_link_pattern']);
                    $url_moniker = find_id_moniker(array('id' => $_content_id) + $attributes, $zone);
                }

                if (is_null($url_moniker)) {
                    $url_moniker = '';
                }

                $moniker_where = array(
                    'm_manually_chosen' => 1,
                    'm_resource_page' => ($content_type == 'comcode_page') ? $_content_id : $attributes['page'],
                    'm_resource_type' => ($content_type == 'comcode_page') ? '' : (isset($attributes['type']) ? $attributes['type'] : ''),
                    'm_resource_id' => ($content_type == 'comcode_page') ? $zone : $_content_id
                );
                $manually_chosen = !is_null($GLOBALS['SITE_DB']->query_select_value_if_there('url_id_monikers', 'm_moniker', $moniker_where));
            } else {
                $url_moniker = '';
                $manually_chosen = false;
            }
            $fields->attach(form_input_codename(do_lang_tempcode('URL_MONIKER'), do_lang_tempcode('DESCRIPTION_META_URL_MONIKER', escape_html($url_moniker)), 'meta_url_moniker', $manually_chosen ? $url_moniker : '', false, null, null, array('/')));
        }
    } else {
        if ($show_header != METADATA_HEADER_FORCE) {
            return new Tempcode();
        }
    }

    if ((!$fields->is_empty()) && ($show_header != METADATA_HEADER_NO)) {
        $_fields = new Tempcode();
        $_fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array(
            '_GUID' => 'adf2a2cda231619243763ddbd0cc9d4e',
            'SECTION_HIDDEN' => true,
            'TITLE' => do_lang_tempcode('METADATA'),
            'HELP' => do_lang_tempcode('DESCRIPTION_METADATA', is_null($content_id) ? do_lang_tempcode('RESOURCE_NEW') : $content_id),
        )));
        $_fields->attach($fields);
        return $_fields;
    }

    return $fields;
}

/**
 * Get field values for metadata.
 *
 * @param  ID_TEXT $content_type The type of resource (e.g. download)
 * @param  ?ID_TEXT $content_id The old ID of the resource (null: adding)
 * @param  ?array $fields_to_skip List of fields to NOT take in (null: empty list)
 * @param  ?ID_TEXT $new_content_id The new ID of the resource (null: not being renamed)
 * @return array A map of standard metadata fields (name to value). If adding, this map is accurate for adding. If editing, nulls mean do-not-edit or non-editable.
 */
function actual_metadata_get_fields($content_type, $content_id, $fields_to_skip = null, $new_content_id = null)
{
    require_lang('metadata');

    if (is_null($fields_to_skip)) {
        $fields_to_skip = array();
    }

    if (fractional_edit()) {
        return array(
            'views' => INTEGER_MAGIC_NULL,
            'submitter' => INTEGER_MAGIC_NULL,
            'add_time' => INTEGER_MAGIC_NULL,
            'edit_time' => INTEGER_MAGIC_NULL,
            /*'url_moniker' => null, was handled internally*/
        );
    }

    if (!has_privilege(get_member(), 'edit_meta_fields')) { // Pass through as how an edit would normally function (things left alone except edit time)
        return array(
            'views' => is_null($content_id) ? 0 : INTEGER_MAGIC_NULL,
            'submitter' => is_null($content_id) ? get_member() : INTEGER_MAGIC_NULL,
            'add_time' => is_null($content_id) ? time() : INTEGER_MAGIC_NULL,
            'edit_time' => time(),
            /*'url_moniker' => null, was handled internally*/
        );
    }

    require_code('content');
    $ob = get_content_object($content_type);
    $info = $ob->info();

    $views = mixed();
    $views_field = in_array('views', $fields_to_skip) ? null : $info['views_field'];
    if (!is_null($views_field)) {
        $views = post_param_integer('meta_views', null);
        if (is_null($views)) {
            if (is_null($content_id)) {
                $views = 0;
            } else {
                $views = INTEGER_MAGIC_NULL;
            }
        }
    }

    $submitter = mixed();
    $submitter_field = in_array('submitter', $fields_to_skip) ? null : $info['submitter_field'];
    if (!is_null($submitter_field)) {
        $_submitter = post_param_string('meta_submitter', '');
        if ($_submitter == '') {
            if (is_null($content_id)) {
                $submitter = get_member();
            } else {
                $submitter = INTEGER_MAGIC_NULL;
            }
        } else {
            $submitter = $GLOBALS['FORUM_DRIVER']->get_member_from_username($_submitter);
            if (is_null($submitter)) {
                $submitter = null; // Leave alone, we did not recognise the user
                attach_message(do_lang_tempcode('_MEMBER_NO_EXIST', escape_html($_submitter)), 'warn'); // ...but attach an error at least
            }
            if (is_null($submitter)) {
                if (is_null($content_id)) {
                    $submitter = get_member();
                }
            }
        }
    }

    $add_time = mixed();
    $add_time_field = in_array('add_time', $fields_to_skip) ? null : $info['add_time_field'];
    if (!is_null($add_time_field)) {
        $add_time = post_param_date('meta_add_time');
        if (is_null($add_time)) {
            if (is_null($content_id)) {
                $add_time = time();
            } else {
                $add_time = INTEGER_MAGIC_NULL;
            }
        } else {
            $add_time = min($add_time, 4294967295); // TODO #3046
        }
    }

    $edit_time = mixed();
    $edit_time_field = in_array('edit_time', $fields_to_skip) ? null : $info['edit_time_field'];
    if (!is_null($edit_time_field)) {
        $edit_time = post_param_date('meta_edit_time');
        if (is_null($edit_time)) {
            if (is_null($content_id)) {
                $edit_time = null; // No edit time
            } else {
                $edit_time = null; // Edit time explicitly wiped out
            }
        } else {
            $edit_time = min($edit_time, 4294967295); // TODO #3046
        }
    }

    if (!is_null($content_id)) {
        set_url_moniker($content_type, $content_id, $fields_to_skip, $new_content_id);
    }

    return array(
        'views' => $views,
        'submitter' => $submitter,
        'add_time' => $add_time,
        'edit_time' => $edit_time,
        /*'url_moniker' => $url_moniker, was handled internally*/
    );
}

/**
 * Set a URL moniker for a resource.
 *
 * @param  ID_TEXT $content_type The type of resource (e.g. download)
 * @param  ID_TEXT $content_id The old ID of the resource
 * @param  ?array $fields_to_skip List of fields to NOT take in (null: empty list)
 * @param  ?ID_TEXT $new_content_id The new ID of the resource (null: not being renamed)
 */
function set_url_moniker($content_type, $content_id, $fields_to_skip = null, $new_content_id = null)
{
    require_lang('metadata');

    if (is_null($fields_to_skip)) {
        $fields_to_skip = array();
    }

    require_code('content');
    $ob = get_content_object($content_type);
    $info = $ob->info();

    $url_moniker = mixed();
    if (($info['support_url_monikers']) && (!in_array('url_moniker', $fields_to_skip))) {
        $url_moniker = post_param_string('meta_url_moniker', '');
        if ($url_moniker == '') {
            if ($content_type == 'comcode_page') {
                $url_moniker = '';
                $parent = post_param_string('parent_page', '');
                while ($parent != '') {
                    $url_moniker = str_replace('_', '-', $parent) . (($url_moniker != '') ? ('/' . $url_moniker) : '');

                    $parent = $GLOBALS['SITE_DB']->query_select_value_if_there('comcode_pages', 'p_parent_page', array('the_page' => $parent));
                    if ($parent === null) {
                        $parent = '';
                    }
                }
                if ($url_moniker != '') {
                    $url_moniker .= '/' . preg_replace('#^.*:#', '', str_replace('_', '-', $content_id));
                } else {
                    $url_moniker = null;
                }
            } else {
                $url_moniker = null;
            }
        }

        if ($url_moniker !== null) {
            require_code('type_sanitisation');
            if (!is_alphanumeric(str_replace('/', '', $url_moniker))) {
                attach_message(do_lang_tempcode('BAD_CODENAME'), 'warn');
                $url_moniker = null;
            }

            if (!is_null($url_moniker)) {
                if ($content_type == 'comcode_page') {
                    list($zone, $page) = explode(':', $content_id);
                    $type = '';
                    $_content_id = $zone;

                    // Update ID of existing moniker(s)
                    if (!is_null($new_content_id)) {
                        $GLOBALS['SITE_DB']->query_update('url_id_monikers', array(
                            'm_resource_page' => $new_content_id,
                        ), array('m_resource_page' => $page, 'm_resource_type' => '', 'm_resource_id' => $zone));
                    }
                } else {
                    list($zone, $attributes,) = page_link_decode($info['view_page_link_pattern']);
                    $page = $attributes['page'];
                    $type = $attributes['type'];
                    $_content_id = $content_id;

                    // Update ID of existing moniker(s)
                    if (!is_null($new_content_id)) {
                        $GLOBALS['SITE_DB']->query_update('url_id_monikers', array(
                            'm_resource_id' => $new_content_id,
                        ), array('m_resource_page' => $page, 'm_resource_type' => $type, 'm_resource_id' => $content_id));
                    }
                }

                $ok = true;

                // Test for conflicts
                $conflict_test_map = array(
                    'm_moniker' => $url_moniker,
                );
                if (substr($url_moniker, 0, 1) != '/') { // Can narrow the conflict-check scope if it's relative to a module rather than a zone ('/' prefix)
                    $conflict_test_map += array(
                        'm_resource_page' => $page,
                        'm_resource_type' => $type,
                    );
                }
                $test = $GLOBALS['SITE_DB']->query_select('url_id_monikers', array('*'), $conflict_test_map);
                if ((array_key_exists(0, $test)) && ($test[0]['m_resource_id'] !== $_content_id)) {
                    if ($test[0]['m_deprecated'] == 0) {
                        $ok = false;

                        if ($content_type == 'comcode_page') {
                            $competing_page_link = $test[0]['m_resource_id'] . ':' . $test[0]['m_resource_page'];
                        } else {
                            $competing_page_link = '_WILD' . ':' . $test[0]['m_resource_page'];
                            if ($test[0]['m_resource_type']) {
                                $competing_page_link .= ':' . $test[0]['m_resource_type'];
                            }
                            if ($test[0]['m_resource_id'] != '') {
                                $competing_page_link .= ':' . $test[0]['m_resource_id'];
                            }
                        }
                        attach_message(do_lang_tempcode('URL_MONIKER_TAKEN', escape_html($competing_page_link), escape_html($url_moniker)), 'warn');
                    } else { // Deprecated, so we can claim it
                        $GLOBALS['SITE_DB']->query_delete('url_id_monikers', $conflict_test_map);
                    }
                }

                if (substr($url_moniker, 0, 1) == '/') { // ah, relative to zones, better run some anti-conflict tests!
                    $parts = explode('/', substr($url_moniker, 1), 3);

                    if ($ok) {
                        // Test there are no zone conflicts
                        if ((file_exists(get_file_base() . '/' . $parts[0])) || (file_exists(get_custom_file_base() . '/' . $parts[0]))) {
                            $ok = false;
                            attach_message(do_lang_tempcode('URL_MONIKER_CONFLICT_ZONE'), 'warn');
                        }
                    }

                    if ($ok) {
                        // Test there are no page conflicts, from perspective of welcome zone
                        require_code('site');
                        $test1 = (count($parts) < 2) ? _request_page($parts[0], '') : false;
                        $test2 = false;
                        if (isset($parts[1])) {
                            $test2 = (count($parts) < 3) ? _request_page($parts[1], $parts[0]) : false;
                        }
                        if (($test1 !== false) || ($test2 !== false)) {
                            $ok = false;
                            attach_message(do_lang_tempcode('URL_MONIKER_CONFLICT_PAGE'), 'warn');
                        }
                    }

                    if ($ok) {
                        // Test there are no page conflicts, from perspective of deep zones
                        require_code('site');
                        $start = 0;
                        $zones = array();
                        do {
                            $zones = find_all_zones(false, false, false, $start, 50);
                            foreach ($zones as $zone_name) {
                                $test1 = (count($parts) < 2) ? _request_page($parts[0], $zone_name) : false;
                                if ($test1 !== false) {
                                    $ok = false;
                                    attach_message(do_lang_tempcode('URL_MONIKER_CONFLICT_PAGE'), 'warn');
                                    break 2;
                                }
                            }
                            $start += 50;
                        } while (count($zones) != 0);
                    }
                }

                if ($ok) {
                    // Insert
                    require_code('urls2');
                    suggest_new_idmoniker_for($page, $type, $_content_id, ($content_type == 'comcode_page') ? $zone : '', $url_moniker, false, $url_moniker);
                }
            }
        }
    }
}

/**
 * Read in an additional metadata field, specific to a resource type.
 *
 * @param  array $metadata metadata already collected
 * @param  ID_TEXT $key The parameter name
 * @param  mixed $default The default if it was not set
 */
function actual_metadata_get_fields__special(&$metadata, $key, $default)
{
    $metadata[$key] = $default;
    if (has_privilege(get_member(), 'edit_meta_fields')) {
        if (is_integer($default)) {
            switch ($default) {
                case 0:
                case INTEGER_MAGIC_NULL:
                    $metadata[$key] = post_param_integer('meta_' . $key, $default);
                    break;
            }
        } else {
            switch ($default) {
                case '':
                case STRING_MAGIC_NULL:
                    $metadata[$key] = post_param_string('meta_' . $key, $default);
                    if ($metadata[$key] == '') {
                        $metadata[$key] = $default;
                    }
                    break;

                case null:
                    $metadata[$key] = post_param_integer('meta_' . $key, null);
                    break;
            }
        }
    }
}
