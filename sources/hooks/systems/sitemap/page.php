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
 * Hook class.
 */
class Hook_sitemap_page extends Hook_sitemap_base
{
    /**
     * Find if a page-link will be covered by this node.
     *
     * @param  ID_TEXT $page_link The page-link.
     * @return integer A SITEMAP_NODE_* constant.
     */
    public function handles_page_link($page_link)
    {
        $matches = array();
        if (preg_match('#^([^:]*):([^:]+)(:browse)?$#', $page_link, $matches) != 0) {
            $zone = $matches[1];
            $page = $matches[2];

            $details = $this->_request_page_details($page, $zone);
            if ($details !== false) {
                if (strpos($details[0], 'COMCODE') === false) { // We don't handle Comcode pages here, comcode_page handles those
                    return SITEMAP_NODE_HANDLED;
                }
            }
        }
        return SITEMAP_NODE_NOT_HANDLED;
    }

    /**
     * Find details of a position in the Sitemap.
     *
     * @param  ID_TEXT $page_link The page-link we are finding.
     * @param  ?string $callback Callback function to send discovered page-links to (null: return).
     * @param  ?array $valid_node_types List of node types we will return/recurse-through (null: no limit)
     * @param  ?integer $child_cutoff Maximum number of children before we cut off all children (null: no limit).
     * @param  ?integer $max_recurse_depth How deep to go from the Sitemap root (null: no limit).
     * @param  integer $recurse_level Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important]).
     * @param  integer $options A bitmask of SITEMAP_GEN_* options.
     * @param  ID_TEXT $zone The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
     * @param  integer $meta_gather A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
     * @param  ?array $row Database row (null: lookup).
     * @param  boolean $return_anyway Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
     * @return ?array Node structure (null: working via callback / error).
     */
    public function get_node($page_link, $callback = null, $valid_node_types = null, $child_cutoff = null, $max_recurse_depth = null, $recurse_level = 0, $options = 0, $zone = '_SEARCH', $meta_gather = 0, $row = null, $return_anyway = false)
    {
        $matches = array();
        preg_match('#^([^:]*):([^:]*)(.*$)#', $page_link, $matches);
        $page = $matches[2];
        $extra = $matches[3];

        $this->_make_zone_concrete($zone, $page_link);

        $zone_default_page = get_zone_default_page($zone);

        $details = $this->_request_page_details($page, $zone);
        if ($details === false) {
            return null;
        }

        $path = end($details);
        $row = $this->_load_row_from_page_groupings($row, $zone, $page);

        $struct = array(
            'title' => make_string_tempcode(escape_html(titleify($page))),
            'content_type' => 'page',
            'content_id' => $zone . ':' . $page,
            'modifiers' => array(),
            'only_on_page' => '',
            'page_link' => $page_link,
            'url' => null,
            'extra_meta' => array(
                'description' => null,
                'image' => null,
                'image_2x' => null,
                'add_date' => (($meta_gather & SITEMAP_GATHER_TIMES) != 0) ? filectime(get_file_base() . '/' . $path) : null,
                'edit_date' => (($meta_gather & SITEMAP_GATHER_TIMES) != 0) ? filemtime(get_file_base() . '/' . $path) : null,
                'submitter' => null,
                'views' => null,
                'rating' => null,
                'meta_keywords' => null,
                'meta_description' => null,
                'categories' => null,
                'validated' => null,
                'db_row' => (($meta_gather & SITEMAP_GATHER_DB_ROW) != 0) ? $row : null,
            ),
            'permissions' => array(
                array(
                    'type' => 'zone',
                    'zone_name' => $zone,
                    'is_owned_at_this_level' => false,
                ),
                array(
                    'type' => 'page',
                    'zone_name' => $zone,
                    'page_name' => $page,
                    'is_owned_at_this_level' => true,
                ),
            ),
            'children' => null,
            'has_possible_children' => false,

            // These are likely to be changed in individual hooks
            'sitemap_priority' => ($zone_default_page == $page) ? SITEMAP_IMPORTANCE_ULTRA : SITEMAP_IMPORTANCE_HIGH,
            'sitemap_refreshfreq' => ($zone_default_page == $page) ? 'daily' : 'weekly',

            'privilege_page' => null,
        );

        switch ($details[0]) {
            case 'HTML':
            case 'HTML_CUSTOM':
                $page_contents = file_get_contents(get_file_base() . '/' . $path);
                $matches = array();
                if (preg_match('#\<title[^\>]*\>#', $page_contents, $matches) != 0) {
                    $start = strpos($page_contents, $matches[0]) + strlen($matches[0]);
                    $end = strpos($page_contents, '</title>', $start);
                    $_title = substr($page_contents, $start, $end - $start);
                    if ($_title != '') {
                        $struct['title'] = make_string_tempcode($_title);
                    }
                }

                if (($options & SITEMAP_GEN_LABEL_CONTENT_TYPES) != 0) {
                    $struct['title'] = make_string_tempcode('HTML: ' . $page);
                }

                break;

            case 'MODULES':
            case 'MODULES_CUSTOM':
                require_all_lang();
                $test = do_lang('MODULE_TRANS_NAME_' . $page, null, null, null, null, false);
                if ($test !== null) {
                    $struct['title'] = do_lang_tempcode('MODULE_TRANS_NAME_' . $page);
                }

                if (($options & SITEMAP_GEN_LABEL_CONTENT_TYPES) != 0) {
                    $struct['title'] = make_string_tempcode(do_lang('MODULE') . ': ' . $page);

                    $matches = array();
                    $normal_path = str_replace('_custom', '', $path); // We want to find normal package, not package of an override
                    if (!is_file($normal_path)) {
                        $normal_path = $path;
                    }
                    if (preg_match('#@package\s+(\w+)#', file_get_contents(zone_black_magic_filterer(get_file_base() . '/' . $normal_path)), $matches) != 0) {
                        $package = $matches[1];
                        $path_addon = get_file_base() . '/sources/hooks/systems/addon_registry/' . $package . '.php';
                        if (!file_exists($path_addon)) {
                            $path_addon = get_file_base() . '/sources_custom/hooks/systems/addon_registry/' . $package . '.php';
                        }
                        if (file_exists($path_addon)) {
                            require_lang('zones');
                            require_code('zones2');
                            $functions = extract_module_functions($path_addon, array('get_description'));
                            $description = is_array($functions[0]) ? call_user_func_array($functions[0][0], $functions[0][1]) : eval($functions[0]);
                            $description = do_lang('FROM_ADDON', $package, $description);
                            $struct['description'] = $description;
                        }
                    }

                    $info = extract_module_info(zone_black_magic_filterer(get_file_base() . '/' . $path));
                    if ((!is_null($info)) && (array_key_exists('author', $info))) {
                        $struct['author'] = $info['author'];
                        $struct['organisation'] = $info['organisation'];
                        $struct['version'] = $info['version'];
                    }
                }

                break;

            case 'MINIMODULES':
            case 'MINIMODULES_CUSTOM':
                if (($options & SITEMAP_GEN_LABEL_CONTENT_TYPES) != 0) {
                    $struct['title'] = make_string_tempcode(do_lang('MINIMODULE') . ': ' . $page);
                }
                break;

            default:
                if (($options & SITEMAP_GEN_LABEL_CONTENT_TYPES) != 0) {
                    $struct['title'] = make_string_tempcode(do_lang('PAGE') . ': ' . $page);
                }
                break;
        }

        // Get more details from menu link / page grouping?
        $this->_ameliorate_with_row($options, $struct, $row, $meta_gather);

        if (!$this->_check_node_permissions($struct)) {
            return null;
        }

        $call_struct = true;

        $children = array();

        $has_entry_points = true;

        $require_permission_support = (($options & SITEMAP_GEN_REQUIRE_PERMISSION_SUPPORT) != 0);
        $check_perms = (($options & SITEMAP_GEN_CHECK_PERMS) != 0);

        if (($max_recurse_depth === null) || ($recurse_level < $max_recurse_depth) || (!isset($row[1]))) {
            // Look for entry points to put under this
            if (($details[0] == 'MODULES' || $details[0] == 'MODULES_CUSTOM') && (!$require_permission_support)) {
                $simplified = (strpos($extra, ':catalogue_name=') !== false);

                $use_page_groupings = (($options & SITEMAP_GEN_USE_PAGE_GROUPINGS) != 0);

                $functions = extract_module_functions(get_file_base() . '/' . $path, array('get_entry_points', 'get_wrapper_icon'), array(
                    $check_perms, // $check_perms
                    null, // $member_id
                    $simplified || $use_page_groupings, // $support_crosslinks
                    $simplified || $use_page_groupings // $be_deferential
                ));

                $has_entry_points = false;

                if (!is_null($functions[0])) {
                    $entry_points = is_array($functions[0]) ? call_user_func_array($functions[0][0], $functions[0][1]) : eval($functions[0]);

                    if (is_null($entry_points)) {
                        return null;
                    }

                    if (count($entry_points) > 0) {
                        $struct['has_possible_children'] = true;

                        $entry_point_sitemap_ob = $this->_get_sitemap_object('entry_point');
                        $comcode_page_sitemap_ob = $this->_get_sitemap_object('comcode_page');

                        $has_entry_points = true;

                        if (isset($entry_points['!'])) {
                            // "!" indicates no entry-points but that the page is accessible without them
                            if (!isset($row[1])) {
                                if (($options & SITEMAP_GEN_LABEL_CONTENT_TYPES) == 0) {
                                    $_title = $entry_points['!'][0];
                                    if (is_object($_title)) {
                                        $struct['title'] = $_title;
                                    } else {
                                        $struct['title'] = (preg_match('#^[A-Z\_]+$#', $_title) == 0) ? make_string_tempcode($_title) : do_lang_tempcode($_title);
                                    }
                                }
                                if (!is_null($entry_points['!'][1])) {
                                    if (($meta_gather & SITEMAP_GATHER_IMAGE) != 0) {
                                        $struct['extra_meta']['image'] = find_theme_image('icons/24x24/' . $entry_points['!'][1]);
                                        $struct['extra_meta']['image_2x'] = find_theme_image('icons/48x48/' . $entry_points['!'][1]);
                                    }
                                }
                            }
                            unset($entry_points['!']);
                        } elseif (((isset($entry_points['browse'])) || (count($entry_points) == 1)) && (($options & SITEMAP_GEN_KEEP_FULL_STRUCTURE) == 0)) {
                            // Browse/only moves some details down and is then skipped so it doesn't show separately beneath (alternatively we could haved blanked out our container node to make it a non-link)
                            $move_down_entry_point = (count($entry_points) == 1) ? key($entry_points) : 'browse';
                            if (!isset($row[1])) {
                                if (substr($struct['page_link'], -strlen(':' . $move_down_entry_point)) != ':' . $move_down_entry_point) {
                                    if ($move_down_entry_point != 'browse') {
                                        $struct['page_link'] .= ':' . $move_down_entry_point;
                                    }
                                }
                                if (!isset($entry_points['browse'])) {
                                    $_title = $entry_points[$move_down_entry_point][0];
                                    if (is_object($_title)) {
                                        $struct['title'] = $_title;
                                    } else {
                                        $struct['title'] = (preg_match('#^[A-Z\_]+$#', $_title) == 0) ? make_string_tempcode($_title) : do_lang_tempcode($_title);
                                    }
                                }
                                if (!is_null($entry_points[$move_down_entry_point][1])) {
                                    if (($meta_gather & SITEMAP_GATHER_IMAGE) != 0) {
                                        $struct['extra_meta']['image'] = find_theme_image('icons/24x24/' . $entry_points[$move_down_entry_point][1]);
                                        $struct['extra_meta']['image_2x'] = find_theme_image('icons/48x48/' . $entry_points[$move_down_entry_point][1]);
                                    }
                                }
                            }
                            unset($entry_points[$move_down_entry_point]);
                        } else {
                            if (($options & SITEMAP_GEN_NO_EMPTY_PAGE_LINKS) == 0) {
                                $struct['page_link'] = ''; // Container node is non-clickable
                            }

                            // Is the icon for the container explicitly defined within get_wrapper_icon()?
                            if (!is_null($functions[1])) {
                                if (($meta_gather & SITEMAP_GATHER_IMAGE) != 0) {
                                    $icon = is_array($functions[1]) ? call_user_func_array($functions[1][0], $functions[1][1]) : eval($functions[1]);
                                    $struct['extra_meta']['image'] = find_theme_image('icons/24x24/' . $icon);
                                    $struct['extra_meta']['image_2x'] = find_theme_image('icons/48x48/' . $icon);
                                }
                            }
                        }

                        if (($max_recurse_depth === null) || ($recurse_level < $max_recurse_depth)) {
                            foreach ($entry_points as $entry_point => $entry_point_details) {
                                $page_type = 'module';

                                if (strpos($entry_point, ':') === false) {
                                    $child_page_link = $zone . ':' . $page . ':' . $entry_point;
                                } else {
                                    $child_page_link = $entry_point;

                                    require_code('site');
                                    list($entry_point_zone, $entry_point_codename) = explode(':', $entry_point);
                                    $_page_type = __request_page($entry_point_codename, $entry_point_zone);
                                    if ($_page_type !== false) {
                                        $page_type = strtolower($_page_type[0]);
                                    }
                                }

                                if (strpos($page_type, 'comcode') !== false) {
                                    if (($valid_node_types !== null) && (!in_array('comcode_page', $valid_node_types))) {
                                        continue;
                                    }
                                    $child_node = $comcode_page_sitemap_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $zone, $meta_gather);
                                } else {
                                    if (($valid_node_types !== null) && (!in_array('page', $valid_node_types))) {
                                        continue;
                                    }

                                    if ((preg_match('#^([^:]*):([^:]*):([^:]*)(:.*|$)#', $child_page_link) != 0) || ($entry_point == '_SEARCH:topicview'/*special case*/)) {
                                        if (strpos($extra, ':catalogue_name=') !== false) {
                                            $child_page_link .= preg_replace('#^:\w+#', '', $extra);
                                        }
                                        $child_node = $entry_point_sitemap_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $zone, $meta_gather, $entry_point_details);
                                    } else {
                                        $child_node = $this->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $zone, $meta_gather);
                                    }
                                }
                                if ($child_node !== null) {
                                    $children[$child_node['page_link']] = $child_node;
                                }
                            }
                        }
                    }
                }
            }

            if (!$has_entry_points) {
                if (($options & SITEMAP_GEN_NO_EMPTY_PAGE_LINKS) == 0) {
                    $struct['page_link'] = '';
                }
            }

            // Look for virtual nodes to put under this
            $hooks = find_all_hooks('systems', 'sitemap');
            foreach (array_keys($hooks) as $_hook) {
                require_code('hooks/systems/sitemap/' . $_hook);
                $ob = object_factory('Hook_sitemap_' . $_hook);
                if ($ob->is_active()) {
                    $is_handled = $ob->handles_page_link($page_link);
                    if ($is_handled == SITEMAP_NODE_HANDLED_VIRTUALLY) {
                        $struct['privilege_page'] = $ob->get_privilege_page($page_link);
                        $struct['has_possible_children'] = true;

                        $virtual_child_nodes = $ob->get_virtual_nodes($page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $zone, $meta_gather, true);
                        if (is_null($virtual_child_nodes)) {
                            $virtual_child_nodes = array();
                        }
                        foreach ($virtual_child_nodes as $child_node) {
                            if ((count($virtual_child_nodes) == 1) && (preg_match('#^' . preg_quote($page_link, '#') . ':browse(:[^:=]*$|$)#', $child_node['page_link']) != 0) && (!$require_permission_support) && (($options & SITEMAP_GEN_KEEP_FULL_STRUCTURE) == 0) && (empty($child_node['extra_meta']['is_a_category_tree_root']))) {
                                // Put as container instead
                                if ($child_node['extra_meta']['image'] == '') {
                                    $child_node['extra_meta']['image'] = $struct['extra_meta']['image'];
                                    $child_node['extra_meta']['image_2x'] = $struct['extra_meta']['image_2x'];
                                }
                                $struct = $child_node;
                                if (!empty($struct['children'])) {
                                    $children = array_merge($children, $struct['children']);
                                }
                                $struct['children'] = null;
                                $call_struct = false; // Already been called in get_virtual_nodes
                            } else {
                                if (($max_recurse_depth === null) || ($recurse_level < $max_recurse_depth)) {
                                    if ($callback === null) {
                                        $children[$child_node['page_link']] = $child_node;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!$has_entry_points && (($options & SITEMAP_GEN_KEEP_FULL_STRUCTURE) == 0)) {
                if ($children == array()) {
                    return null;
                }
            }
        }

        if ($callback !== null && $call_struct) {
            call_user_func($callback, $struct);
        }

        if (($max_recurse_depth === null) || ($recurse_level < $max_recurse_depth)) {
            // Finalise children
            if ($callback !== null) {
                foreach ($children as $child_struct) {
                    call_user_func($callback, $child_struct);
                }
                $children = array();
            }
            $struct['children'] = array_values($children);

            sort_maps_by($children, 'title');
        }

        return ($callback === null || $return_anyway) ? $struct : null;
    }
}
