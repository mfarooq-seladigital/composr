<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

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
 * Shows an HTML page for making block Comcode.
 */
function block_helper_script()
{
    require_lang('comcode');
    require_lang('blocks');
    require_code('zones2');
    require_code('zones3');
    require_code('addons');

    check_privilege('comcode_dangerous');

    $title = get_screen_title('BLOCK_HELPER');

    require_code('form_templates');
    require_all_lang();

    $type_wanted = get_param_string('block_type', 'main');

    $type = get_param_string('type', 'step1');

    $content = new Tempcode();

    if ($type == 'step1') { // Ask for block
        // Find what addons all our block files are in, and icons if possible
        $hooks = find_all_hooks('systems', 'addon_registry');
        $hook_keys = array_keys($hooks);
        $hook_files = array();
        foreach ($hook_keys as $hook) {
            $path = get_file_base() . '/sources_custom/hooks/systems/addon_registry/' . filter_naughty_harsh($hook) . '.php';
            if (!file_exists($path)) {
                $path = get_file_base() . '/sources/hooks/systems/addon_registry/' . filter_naughty_harsh($hook) . '.php';
            }
            $hook_files[$hook] = file_get_contents($path);
        }
        unset($hook_keys);
        $addon_icons = array();
        $addons_blocks = array();
        foreach ($hook_files as $addon_name => $hook_file) {
            $matches = array();
            if (preg_match('#function get_file_list\(\)\s*\{([^\}]*)\}#', $hook_file, $matches) != 0) {
                $addon_files = eval($matches[1]);
                if ($addon_files === false) {
                    $addon_files = array(); // Some kind of PHP error
                }
                foreach ($addon_files as $file) {
                    if ((substr($file, 0, 21) == 'sources_custom/blocks/') || (substr($file, 0, 15) == 'sources/blocks/')) {
                        $addons_blocks[basename($file, '.php')] = $addon_name;
                    }
                }
            }
            $addon_icons[$addon_name] = find_addon_icon($addon_name);
        }

        // Find where blocks have been used
        $block_usage = array();
        $zones = find_all_zones(false, true);
        foreach ($zones as $_zone) {
            $zone = $_zone[0];
            $pages = find_all_pages_wrap($zone, true);
            foreach ($pages as $filename => $type) {
                if (substr(strtolower($filename), -4) == '.txt') {
                    $matches = array();
                    $contents = file_get_contents(zone_black_magic_filterer(((substr($type, 0, 15) == 'comcode_custom/') ? get_custom_file_base() : get_file_base()) . '/' . (($zone == '') ? '' : ($zone . '/')) . 'pages/' . $type . '/' . $filename));
                    $num_matches = preg_match_all('#\[block[^\]]*\](.*)\[/block\]#U', $contents, $matches);
                    for ($i = 0; $i < $num_matches; $i++) {
                        $block_used = $matches[1][$i];
                        if (!array_key_exists($block_used, $block_usage)) {
                            $block_usage[$block_used] = array();
                        }
                        $block_usage[$block_used][] = $zone . ':' . basename($filename, '.txt');
                    }
                }
            }
        }

        // Find all blocks
        $blocks = find_all_blocks();
        if (!in_safe_mode()) {
            $dh = @opendir(get_file_base() . '/sources_custom/miniblocks');
            if ($dh !== false) {
                while (($file = readdir($dh)) !== false) {
                    if ((substr($file, -4) == '.php') && (preg_match('#^[\w\-]*$#', substr($file, 0, strlen($file) - 4)) != 0)) {
                        $blocks[substr($file, 0, strlen($file) - 4)] = 'sources_custom';
                    }
                }
                closedir($dh);
            }
        }

        // Show block list
        $links = new Tempcode();
        $block_types = array();
        $block_types_icon = array();
        $block_meta = array();
        $keep = symbol_tempcode('KEEP');
        foreach (array_keys($blocks) as $block) {
            if (array_key_exists($block, $addons_blocks)) {
                $addon_name = $addons_blocks[$block];
                $addon_icon = array_key_exists($addon_name, $addon_icons) ? $addon_icons[$addon_name] : null;
                $addon_name = preg_replace('#^core_#', '', $addon_name);
            } else {
                $addon_name = null;
                $addon_icon = null;
            }

            $this_block_type = (($addon_name === null) || (strpos($addon_name, 'block') !== false) || ($addon_name == 'core')) ? substr($block, 0, (strpos($block, '_') === false) ? strlen($block) : strpos($block, '_')) : $addon_name;
            if (!array_key_exists($this_block_type, $block_types)) {
                $block_types[$this_block_type] = new Tempcode();
            }
            if ($addon_icon !== null) {
                $block_types_icon[$this_block_type] = $addon_icon;
            }

            $block_description = do_lang('BLOCK_' . $block . '_DESCRIPTION', null, null, null, null, false);
            $block_use = do_lang('BLOCK_' . $block . '_USE', null, null, null, null, false);
            if ($block_description === null) {
                $block_description = '';
            }
            if ($block_use === null) {
                $block_use = '';
            }
            $descriptiont = ($block_description == '' && $block_use == '') ? new Tempcode() : do_lang_tempcode('BLOCK_HELPER_1X', $block_description, $block_use);

            $url = find_script('block_helper') . '?type=step2&block=' . urlencode($block) . '&field_name=' . urlencode(get_param_string('field_name')) . $keep->evaluate();
            if (get_param_string('utheme', '') != '') {
                $url .= '&utheme=' . urlencode(get_param_string('utheme'));
            }
            $url .= '&block_type=' . urlencode($type_wanted);
            if (get_param_string('save_to_id', '') != '') {
                $url .= '&save_to_id=' . urlencode(get_param_string('save_to_id'));
            }

            $block_title = cleanup_block_name($block);
            $link_caption = do_lang_tempcode('NICE_BLOCK_NAME', escape_html($block_title), escape_html($block));

            $usage = array_key_exists($block, $block_usage) ? $block_usage[$block] : array();

            $block_meta[$block_title . ': ' . $block] = array(
                $this_block_type,
                $usage,
                $descriptiont,
                $url,
                $link_caption,
            );
        }
        ksort($block_meta);
        foreach ($block_meta as $bits) {
            list($this_block_type, $usage, $descriptiont, $url, $link_caption) = $bits;

            $block_types[$this_block_type]->attach(do_template('BLOCK_HELPER_BLOCK_CHOICE', array(
                '_GUID' => '079e9b37fc142d292d4a64940243178a',
                'USAGE' => $usage,
                'DESCRIPTION' => $descriptiont,
                'URL' => $url,
                'LINK_CAPTION' => $link_caption,
            )));
        }
        ksort($block_types);
        $move_after = $block_types['adminzone_dashboard'];
        unset($block_types['adminzone_dashboard']);
        $block_types['adminzone_dashboard'] = $move_after;
        foreach ($block_types as $block_type => $_links) {
            if (($block_type == 'bottom') && ($type_wanted == 'side')) {
                continue;
            }

            switch ($block_type) {
                case 'side':
                case 'main':
                case 'bottom':
                    $type_title = do_lang_tempcode('BLOCKS_TYPE_' . $block_type);
                    $img = null;
                    break;
                default:
                    $type_title = do_lang_tempcode('BLOCKS_TYPE_ADDON', escape_html(cleanup_block_name($block_type)));
                    $img = array_key_exists($block_type, $block_types_icon) ? $block_types_icon[$block_type] : null;
                    break;
            }
            $links->attach(do_template('BLOCK_HELPER_BLOCK_GROUP', array('_GUID' => '975a881f5dbd054ced9d2e3b35ed59bf', 'IMG' => $img, 'TITLE' => $type_title, 'LINKS' => $_links)));
        }
        $content = do_template('BLOCK_HELPER_START', array('_GUID' => '1d58238a6d00eb7f79d5a4f0e85fb1a4', 'GET' => true, 'TITLE' => $title, 'LINKS' => $links));
    }

    if ($type == 'step2') { // Ask for block fields
        require_code('comcode_compiler');
        $defaults = parse_single_comcode_tag(get_param_string('parse_defaults', '', INPUT_FILTER_GET_COMPLEX), 'block');

        $keep = symbol_tempcode('KEEP');
        $back_url = find_script('block_helper') . '?type=step1&field_name=' . urlencode(get_param_string('field_name')) . $keep->evaluate();
        if (get_param_string('utheme', '') != '') {
            $back_url .= '&utheme=' . urlencode(get_param_string('utheme'));
        }
        if (get_param_string('save_to_id', '') != '') {
            $back_url .= '&save_to_id=' . urlencode(get_param_string('save_to_id'));
        }

        $block = trim(get_param_string('block'));
        $title = get_screen_title('_BLOCK_HELPER', true, array(escape_html($block), escape_html($back_url)));
        $fields = new Tempcode();

        // Load up renderer hooks
        $block_ui_renderers = find_all_hook_obs('systems', 'block_ui_renderers', 'Hook_block_ui_renderers_');

        // Work out parameters involved, and their sets ("classes")
        $parameters = get_block_parameters($block, true);
        // NB: Also update sources/hooks/systems/preview/block_comcode.php
        if (!isset($defaults['cache'])) {
            $defaults['cache'] = block_cache_default($block);
        }
        if ($parameters === null) {
            $parameters = array();
        }
        $advanced_ind = do_lang('BLOCK_IND_ADVANCED');
        $param_classes = array('normal' => array(), 'advanced' => array());
        foreach ($parameters as $parameter) {
            $param_class = 'normal';
            if (in_array($parameter, get_standard_block_parameters())) {
                $param_class = 'advanced';
            } else {
                $field_description = do_lang('BLOCK_' . $block . '_PARAM_' . $parameter, get_brand_base_url(), null, null, null, false);
                if (($field_description !== null) && (strpos($field_description, $advanced_ind) !== false)) {
                    $param_class = 'advanced';
                }
            }
            $param_classes[$param_class][] = $parameter;
        }

        // Go over each set of parameters
        foreach ($param_classes as $param_class => $parameters) {
            if (count($parameters) == 0) {
                if ($param_class == 'normal') {
                    $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => 'e50ed41cc58bc234ccd314127583a1f2', 'SECTION_HIDDEN' => false, 'TITLE' => do_lang_tempcode('PARAMETERS'), 'HELP' => protect_from_escaping(paragraph(do_lang_tempcode('BLOCK_HELPER_NO_PARAMETERS'), '', 'nothing_here')))));
                }

                continue;
            }

            if ($param_class == 'advanced') {
                $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '3d9642b17f6be2067f4fd6e102c344bf', 'SECTION_HIDDEN' => true, 'TITLE' => do_lang_tempcode('ADVANCED'))));
            }

            foreach ($parameters as $parameter) {
                // Work out and cleanup the title
                $parameter_title = titleify($parameter);
                $test = do_lang('BLOCK_' . $block . '_PARAM_' . $parameter . '_TITLE', null, null, null, null, false);
                if ($test !== null) {
                    $parameter_title = $test;
                }

                // Work out and cleanup the description
                $matches = array();
                switch ($parameter) {
                    case 'quick_cache':
                    case 'cache':
                    case 'defer':
                    case 'block_id':
                    case 'failsafe':
                        $description = do_lang('BLOCK_PARAM_' . $parameter, get_brand_base_url());
                        break;
                    default:
                        $description = do_lang('BLOCK_' . $block . '_PARAM_' . $parameter, get_brand_base_url(), null, null, null, false);
                        if ($description === null) {
                            $description = '';
                        }
                        break;
                }
                $description = str_replace(do_lang('BLOCK_IND_STRIPPABLE_1'), '', $description);
                $description = trim(str_replace(do_lang('BLOCK_IND_ADVANCED'), '', $description));

                // Work out default value for field
                $default = '';
                if (preg_match('#' . do_lang('BLOCK_IND_DEFAULT') . ': ["\']([^"]*)["\']#Ui', $description, $matches) != 0) {
                    $default = $matches[1];
                    $has_default = true;
                    $description = preg_replace('#\s*' . do_lang('BLOCK_IND_DEFAULT') . ': ["\']([^"]*)["\'](?-U)\.?(?U)#Ui', '', $description);
                } else {
                    $has_default = false;
                }

                if (isset($defaults[$parameter])) {
                    $default = $defaults[$parameter];
                    $has_default = true;
                }

                // Show field
                foreach ($block_ui_renderers as $block_ui_renderer) {
                    $test = $block_ui_renderer->render_block_ui($block, $parameter, $has_default, $default, $description);
                    if ($test !== null) {
                        $fields->attach($test);
                        continue 2;
                    }
                }
                if ($block . ':' . $parameter == 'menu:type') { // special case for menus
                    $matches = array();
                    $dh = opendir(get_file_base() . '/themes/default/templates/');
                    $options = array();
                    while (($file = readdir($dh)) !== false) {
                        if (preg_match('^MENU_([a-z]+)\.tpl$^', $file, $matches) != 0) {
                            $options[] = $matches[1];
                        }
                    }
                    closedir($dh);
                    $dh = opendir(get_custom_file_base() . '/themes/default/templates_custom/');
                    while (($file = readdir($dh)) !== false) {
                        if ((preg_match('^MENU_([a-z]+)\.tpl$^', $file, $matches) != 0) && (!file_exists(get_file_base() . '/themes/default/templates/' . $file))) {
                            $options[] = $matches[1];
                        }
                    }
                    closedir($dh);
                    sort($options);
                    $list = new Tempcode();
                    foreach ($options as $option) {
                        $list->attach(form_input_list_entry($option, $has_default && $option == $default));
                    }
                    $fields->attach(form_input_list($parameter_title, escape_html($description), $parameter, $list, null, false, false));
                } elseif ($block . ':' . $parameter == 'menu:param') { // special case for menus
                    $list = new Tempcode();
                    $rows = $GLOBALS['SITE_DB']->query_select('menu_items', array('DISTINCT i_menu'), array(), 'ORDER BY i_menu');
                    foreach ($rows as $row) {
                        $list->attach(form_input_list_entry($row['i_menu'], $has_default && $row['i_menu'] == $default));
                    }
                    $fields->attach(form_input_combo($parameter_title, escape_html($description), $parameter, $default, $list, null, false));
                } elseif ($parameter == 'zone') { // zone list
                    $list = new Tempcode();
                    $list->attach(form_input_list_entry('_SEARCH', ($default == '')));
                    $list->attach(create_selection_list_zones(($default == '') ? null : $default));
                    $fields->attach(form_input_list($parameter_title, escape_html($description), $parameter, $list, null, false, false));
                } elseif ((($default == '') || (is_numeric(str_replace(',', '', $default)))) && ((($parameter == 'forum') || (($parameter == 'param') && (in_array($block, array('main_forum_topics'))))) && (get_forum_type() == 'cns'))) { // Conversr forum list
                    require_code('cns_forums');
                    require_code('cns_forums2');
                    if (!addon_installed('cns_forum')) {
                        warn_exit(do_lang_tempcode('NO_FORUM_INSTALLED'));
                    }
                    $list = create_selection_list_forum_tree(null, null, array_map('intval', explode(',', $default)));
                    $fields->attach(form_input_multi_list($parameter_title, escape_html($description), $parameter, $list));
                } elseif ($parameter == 'font') { // font choice
                    $fonts = array();
                    $dh = opendir(get_file_base() . '/data/fonts');
                    while (($f = readdir($dh))) {
                        if (substr($f, -4) == '.ttf') {
                            $fonts[] = substr($f, 0, strlen($f) - 4);
                        }
                    }
                    closedir($dh);
                    $dh = opendir(get_custom_file_base() . '/data_custom/fonts');
                    while (($f = readdir($dh))) {
                        if (substr($f, -4) == '.ttf') {
                            $fonts[] = substr($f, 0, strlen($f) - 4);
                        }
                    }
                    closedir($dh);
                    $fonts = array_unique($fonts);
                    sort($fonts);
                    $list = new Tempcode();
                    foreach ($fonts as $font) {
                        $list->attach(form_input_list_entry($font, $font == $default));
                    }
                    $fields->attach(form_input_list($parameter_title, escape_html($description), $parameter, $list, null, false, false));
                } elseif (preg_match('#' . do_lang('BLOCK_IND_EITHER') . ' (.+)#i', $description, $matches) != 0) { // list
                    $description = preg_replace('# \(' . do_lang('BLOCK_IND_EITHER') . '.*\)#U', '', $description); // predefined selections
                    $description = preg_replace('# ' . do_lang('BLOCK_IND_EITHER') . '.*$#Ui', '', $description);

                    $list = new Tempcode();
                    $matches2 = array();
                    $num_matches = preg_match_all('#\'([^\']*)\'="([^"]*)"#', $matches[1], $matches2);
                    if ($num_matches != 0) {
                        for ($i = 0; $i < $num_matches; $i++) {
                            $list->attach(form_input_list_entry($matches2[1][$i], $matches2[1][$i] == $default, $matches2[2][$i]));
                        }
                    } else {
                        $num_matches = preg_match_all('#\'([^\']*)\'#', $matches[1], $matches2);
                        for ($i = 0; $i < $num_matches; $i++) {
                            $list->attach(form_input_list_entry($matches2[1][$i], $matches2[1][$i] == $default));
                        }
                    }
                    $fields->attach(form_input_list($parameter_title, escape_html($description), $parameter, $list, null, false, false));
                } elseif (preg_match('#\(' . do_lang('BLOCK_IND_HOOKTYPE') . ': \'([^\'/]*)/([^\'/]*)\'\)#i', $description, $matches) != 0) { // hook list
                    $description = preg_replace('#\s*\(' . do_lang('BLOCK_IND_HOOKTYPE') . ': \'([^\'/]*)/([^\'/]*)\'\)#i', '', $description);

                    $list = new Tempcode();
                    $hooks = find_all_hooks($matches[1], $matches[2]);
                    ksort($hooks);
                    $is_multi_list = (($block == 'main_search') && ($parameter == 'limit_to')) || ($block == 'side_tag_cloud');
                    if (($default == '') && ($has_default) && (!$is_multi_list)) {
                        $list->attach(form_input_list_entry('', true));
                    }
                    foreach (array_keys($hooks) as $hook) {
                        $list->attach(form_input_list_entry($hook, $hook == $default));
                    }
                    if ($is_multi_list) {
                        $fields->attach(form_input_multi_list($parameter_title, escape_html($description), $parameter, $list, null, 0));
                    } else {
                        $fields->attach(form_input_list($parameter_title, escape_html($description), $parameter, $list, null, false, false));
                    }
                } elseif ((($default == '0') || ($default == '1') || (strpos($description, '\'0\'') !== false) || (strpos($description, '\'1\'') !== false)) && (do_lang('BLOCK_IND_WHETHER') != '') && (stripos($description, do_lang('BLOCK_IND_WHETHER')) !== false)) { // checkbox
                    $fields->attach(form_input_tick($parameter_title, escape_html($description), $parameter, $default == '1'));
                } elseif ((do_lang('BLOCK_IND_NUMERIC') != '') && (strpos($description, do_lang('BLOCK_IND_NUMERIC')) !== false)) { // numeric
                    $fields->attach(form_input_integer($parameter_title, escape_html($description), $parameter, ($default == '') ? null : intval($default), false));
                } else { // normal
                    $fields->attach(form_input_line($parameter_title, escape_html($description), $parameter, $default, false));
                }
            }
        }
        $post_url = find_script('block_helper') . '?type=step3&field_name=' . urlencode(get_param_string('field_name')) . $keep->evaluate();
        if (get_param_string('utheme', '') != '') {
            $post_url .= '&utheme=' . urlencode(get_param_string('utheme'));
        }
        $post_url .= '&block_type=' . urlencode($type_wanted);
        if (get_param_string('save_to_id', '') != '') {
            $post_url .= '&save_to_id=' . urlencode(get_param_string('save_to_id'));
            $submit_name = do_lang_tempcode('SAVE');

            // Allow remove option
            $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '9fafd87384a20a8ccca561b087cbe1fc', 'SECTION_HIDDEN' => false, 'TITLE' => do_lang_tempcode('ACTIONS'), 'HELP' => '')));
            $fields->attach(form_input_tick(do_lang_tempcode('REMOVE'), '', '_delete', false));
        } else {
            $submit_name = do_lang_tempcode('USE');
        }
        $block_description = do_lang('BLOCK_' . $block . '_DESCRIPTION', null, null, null, null, false);
        if ($block_description === null) {
            $block_description = '';
        }
        $block_use = do_lang('BLOCK_' . $block . '_USE', null, null, null, null, false);
        if ($block_use === null) {
            $block_use = '';
        }
        if (($block_description == '') && ($block_use == '')) {
            $text = new Tempcode();
        } else {
            $text = do_lang_tempcode('BLOCK_HELPER_2', escape_html(cleanup_block_name($block)), escape_html($block_description), escape_html($block_use));
        }
        $hidden = form_input_hidden('block', $block);
        $content = do_template('FORM_SCREEN', array(
            '_GUID' => '62f8688bf0ae4223a2ba1f76fef3b0b4',
            'TITLE' => $title,
            'TARGET' => '_self',
            'SKIP_WEBSTANDARDS' => true,
            'FIELDS' => $fields,
            'URL' => $post_url,
            'TEXT' => $text,
            'SUBMIT_ICON' => 'buttons__proceed',
            'SUBMIT_NAME' => $submit_name,
            'HIDDEN' => $hidden,
            'PREVIEW' => true,
            'THEME' => $GLOBALS['FORUM_DRIVER']->get_theme(),
        ));

        if ($fields->is_empty()) {
            $type = 'step3';
        }
    }

    if ($type == 'step3') { // Close off, and copy in Comcode to browser
        require_javascript('posting');
        require_javascript('editing');

        $field_name = filter_naughty_harsh(get_param_string('field_name'));

        $bparameters = '';
        $bparameters_tempcode = '';
        $block = trim(either_param_string('block'));
        $parameters = get_block_parameters($block, true);
        if (in_array('param', $parameters)) {
            $_parameters = array('param');
            unset($parameters[array_search('param', $parameters)]);
            $parameters = array_merge($_parameters, $parameters);
        }
        foreach ($parameters as $parameter) {
            $value = post_param_string($parameter, post_param_string($parameter . '_fallback_list', null));
            if ($value === null) {
                if (post_param_integer('tick_on_form__' . $parameter, null) === null) {
                    continue; // If not on form, continue, otherwise must be 0
                }
                $value = '0';
            }
            if (($value != '') && (($parameter != 'block_id') || ($value != '')) && (($parameter != 'failsafe') || ($value == '1')) && (($parameter != 'defer') || ($value == '1')) && (($parameter != 'cache') || ($value != block_cache_default($block))) && (($parameter != 'quick_cache') || ($value == '1'))) {
                if ($parameter == 'param') {
                    $bparameters .= '="' . str_replace('"', '\"', $value) . '"';
                } else {
                    $bparameters .= ' ' . $parameter . '="' . str_replace('"', '\"', $value) . '"';
                }
                $bparameters_tempcode .= ',' . $parameter . '=' . str_replace(',', '\,', $value);
            }
        }

        $comcode = '[block' . $bparameters . ']' . $block . '[/block]';
        $tempcode = '{$BLOCK,block=' . $block . $bparameters_tempcode . '}';
        if ($type_wanted == 'template') {
            $comcode = $tempcode; // This is what will be written in
        }

        $comcode_semihtml = comcode_to_tempcode($comcode, null, false, null, null, COMCODE_SEMIPARSE_MODE);

        $content = do_template('BLOCK_HELPER_DONE', array(
            '_GUID' => '575d6c8120d6001c8156560be518f296',
            'TITLE' => $title,
            'FIELD_NAME' => $field_name,
            'TAG_CONTENTS' => '',
            'SAVE_TO_ID' => get_param_string('save_to_id', ''),
            'DELETE' => (post_param_integer('_delete', 0) == 1),
            'BLOCK' => $block,
            'COMCODE' => $comcode,
            'COMCODE_SEMIHTML' => $comcode_semihtml,
        ));
    }

    require_code('site');
    attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

    $echo = do_template('STANDALONE_HTML_WRAP', array('_GUID' => 'ccb57d45d593eb8aabc2a5e99ea7711f', 'TITLE' => do_lang_tempcode('BLOCK_HELPER'), 'POPUP' => true, 'CONTENT' => $content));
    $echo->handle_symbol_preprocessing();
    $echo->evaluate_echo();
}
