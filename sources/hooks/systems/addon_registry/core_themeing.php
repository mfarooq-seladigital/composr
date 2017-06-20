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
 * @package    core_themeing
 */

/**
 * Hook class.
 */
class Hook_addon_registry_core_themeing
{
    /**
     * Get a list of file permissions to set
     *
     * @param  boolean $runtime Whether to include wildcards represented runtime-created chmoddable files
     * @return array File permissions to set
     */
    public function get_chmod_array($runtime = false)
    {
        return array();
    }

    /**
     * Get the version of Composr this addon is for
     *
     * @return float Version number
     */
    public function get_version()
    {
        return cms_version_number();
    }

    /**
     * Get the description of the addon
     *
     * @return string Description of the addon
     */
    public function get_description()
    {
        return 'Themeing the website, via CSS, HTML, and images.';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_themes',
            'tut_releasing_themes',
            'tut_theme_lifecycle',
            'tut_tempcode',
            'tut_fixed_width',
            'tut_design',
            'tut_designer_themes',
            'tut_mobile',
        );
    }

    /**
     * Get a mapping of dependency types
     *
     * @return array File permissions to set
     */
    public function get_dependencies()
    {
        return array(
            'requires' => array(),
            'recommends' => array(),
            'conflicts_with' => array(),
        );
    }

    /**
     * Explicitly say which icon should be used
     *
     * @return URLPATH Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/menu/adminzone/style/themes/themes.png';
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array List of files
     */
    public function get_file_list()
    {
        return array(
            'themes/default/images/icons/24x24/menu/adminzone/style/themes/themes.png',
            'themes/default/images/icons/48x48/menu/adminzone/style/themes/themes.png',
            'themes/default/images/icons/24x24/menu/adminzone/style/themes/templates.png',
            'themes/default/images/icons/24x24/menu/adminzone/style/themes/theme_images.png',
            'themes/default/images/icons/48x48/menu/adminzone/style/themes/templates.png',
            'themes/default/images/icons/48x48/menu/adminzone/style/themes/theme_images.png',
            'themes/default/css/themes_editor.css',
            'sources/hooks/systems/snippets/exists_theme.php',
            'adminzone/load_template.php',
            'adminzone/tempcode_tester.php',
            'sources/hooks/systems/ajax_tree/choose_theme_files.php',
            'sources/hooks/systems/addon_registry/core_themeing.php',
            'adminzone/pages/modules/admin_themes.php',
            'themes/default/javascript/theme_colours.js',
            'themes/default/templates/THEME_MANAGE_SCREEN.tpl',
            'themes/default/templates/THEME_IMAGE_MANAGE_SCREEN.tpl',
            'themes/default/templates/THEME_IMAGE_PREVIEW.tpl',
            'themes/default/templates/THEME_TEMPLATE_EDITOR_TEMPLATE_DETAIL.tpl',
            'themes/default/templates/THEME_TEMPLATE_EDITOR_SCREEN.tpl',
            'themes/default/templates/THEME_TEMPLATE_EDITOR_TAB.tpl',
            'themes/default/templates/THEME_TEMPLATE_EDITOR_TEMPCODE_DROPDOWN.tpl',
            'themes/default/templates/THEME_TEMPLATE_EDITOR_RESTORE_REVISION.tpl',
            'themes/default/templates/TEMPCODE_TESTER_SCREEN.tpl',
            'themes/default/templates/TEMPLATE_EDIT_LINK.tpl',
            'themes/default/templates/THEME_SCREEN_PREVIEW.tpl',
            'themes/default/templates/THEME_SCREEN_PREVIEW_WRAP.tpl',
            'themes/default/templates/TEMPLATE_TREE.tpl',
            'themes/default/templates/TEMPLATE_TREE_ITEM.tpl',
            'themes/default/templates/TEMPLATE_TREE_ITEM_WRAP.tpl',
            'themes/default/templates/TEMPLATE_TREE_NODE.tpl',
            'themes/default/templates/TEMPLATE_TREE_SCREEN.tpl',
            'sources/themes2.php',
            'sources/themes3.php',
            'lang/EN/themes.ini',
            'sources/lorem.php',
            'sources/themes_meta_tree.php',
            'sources/hooks/systems/config/enable_theme_img_buttons.php',
            'sources/hooks/systems/snippets/template_editor_load.php',
            'sources/hooks/systems/snippets/template_editor_save.php',
            'themes/default/javascript/core_themeing.js',
        );
    }

    /**
     * Get mapping between template names and the method of this class that can render a preview of them
     *
     * @return array The mapping
     */
    public function tpl_previews()
    {
        return array(
            'templates/THEME_MANAGE_SCREEN.tpl' => 'administrative__theme_manage_screen',
            'templates/THEME_IMAGE_MANAGE_SCREEN.tpl' => 'administrative__theme_image_manage_screen',
            'templates/THEME_IMAGE_PREVIEW.tpl' => 'administrative__theme_image_preview',
            'templates/THEME_TEMPLATE_EDITOR_TEMPLATE_DETAIL.tpl' => 'administrative__theme_template_editor_template_detail',
            'templates/THEME_TEMPLATE_EDITOR_SCREEN.tpl' => 'administrative__theme_template_editor_screen',
            'templates/THEME_TEMPLATE_EDITOR_TAB.tpl' => 'administrative__theme_template_editor_tab',
            'templates/THEME_TEMPLATE_EDITOR_TEMPCODE_DROPDOWN.tpl' => 'administrative__theme_template_editor_tab',
            'templates/THEME_TEMPLATE_EDITOR_RESTORE_REVISION.tpl' => 'administrative__theme_template_editor_restore_revision',
            'templates/TEMPCODE_TESTER_SCREEN.tpl' => 'administrative__tempcode_tester_screen',
            'templates/TEMPLATE_EDIT_LINK.tpl' => 'administrative__template_edit_links_screen',
            'templates/THEME_SCREEN_PREVIEW.tpl' => 'administrative__screen_previews_screen',
            'templates/THEME_SCREEN_PREVIEW_WRAP.tpl' => 'administrative__screen_previews_screen',
            'templates/TEMPLATE_TREE.tpl' => 'administrative__template_tree_screen',
            'templates/TEMPLATE_TREE_ITEM.tpl' => 'administrative__template_tree_screen',
            'templates/TEMPLATE_TREE_ITEM_WRAP.tpl' => 'administrative__template_tree_screen',
            'templates/TEMPLATE_TREE_NODE.tpl' => 'administrative__template_tree_screen',
            'templates/TEMPLATE_TREE_SCREEN.tpl' => 'administrative__template_tree_screen',
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__theme_manage_screen()
    {
        require_lang('zones');

        $themes = array();
        foreach (placeholder_array() as $i => $value) {
            $themes[] = array(
                'THEME_USAGE' => lorem_phrase(),
                'SEED' => '123456',
                'DATE' => placeholder_date(),
                'RAW_DATE' => placeholder_date_raw(),
                'NAME' => $value,
                'DESCRIPTION' => lorem_paragraph_html(),
                'AUTHOR' => lorem_phrase(),
                'TITLE' => lorem_phrase(),
                'CSS_URL' => placeholder_url(),
                'TEMPLATES_URL' => placeholder_url(),
                'IMAGES_URL' => placeholder_url(),
                'DELETABLE' => placeholder_table(),
                'EDIT_URL' => placeholder_url(),
                'DELETE_URL' => placeholder_url(),
                'SCREEN_PREVIEW_URL' => placeholder_url(),
                'IS_MAIN_THEME' => ($i == 2),
            );
        }

        $zones = array();
        foreach (placeholder_array() as $v) {
            $zones[] = array(
                '0' => lorem_word(),
                '1' => lorem_word_2(),
            );
        }

        return array(
            lorem_globalise(do_lorem_template('THEME_MANAGE_SCREEN', array(
                'TITLE' => lorem_title(),
                'THEMES' => $themes,
                'THEME_DEFAULT_REASON' => lorem_phrase(),
                'ZONES' => $zones,
                'HAS_FREE_CHOICES' => true,
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__theme_image_manage_screen()
    {
        return array(
            lorem_globalise(do_lorem_template('THEME_IMAGE_MANAGE_SCREEN', array(
                'ADD_URL' => placeholder_url(),
                'TITLE' => lorem_title(),
                'FORM' => placeholder_form(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__theme_image_preview()
    {
        return array(
            lorem_globalise(do_lorem_template('THEME_IMAGE_PREVIEW', array(
                'WIDTH' => placeholder_number(),
                'HEIGHT' => placeholder_number(),
                'URL' => placeholder_image_url(),
                'UNMODIFIED' => lorem_phrase(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__theme_template_editor_template_detail()
    {
        return array(
            lorem_globalise(do_lorem_template('THEME_TEMPLATE_EDITOR_TEMPLATE_DETAIL', array(
                'FILE' => lorem_word(),
                'FULL_PATH' => lorem_word(),
                'LAST_EDITING_USERNAME' => lorem_word(),
                'LAST_EDITING_DATE' => placeholder_date(),
                'FILE_SIZE' => placeholder_number(),
                'ADDON' => lorem_word(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__theme_template_editor_screen()
    {
        return array(
            lorem_globalise(do_lorem_template('THEME_TEMPLATE_EDITOR_SCREEN', array(
                'TITLE' => lorem_title(),
                'FILES_TO_LOAD' => array(),
                'THEME' => lorem_word(),
                'LIVE_PREVIEW_URL' => placeholder_url(),
                'WARNING_DETAILS' => '',
                'PING_URL' => placeholder_url(),
                'ACTIVE_GUID' => placeholder_id(),
                'DEFAULT_THEME_FILES_LOCATION' => '',
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__theme_template_editor_tab()
    {
        $_parameters = do_lorem_template('FORM_SCREEN_INPUT_LIST_ENTRY', array('SELECTED' => true, 'DISABLED' => false, 'CLASS' => '', 'NAME' => placeholder_id(), 'TEXT' => lorem_phrase()));

        $parameters = do_lorem_template('THEME_TEMPLATE_EDITOR_TEMPCODE_DROPDOWN', array(
            'FILE_ID' => lorem_word(),
            'PARAMETERS' => $_parameters,
            'STUB' => 'parameter',
            'LANG' => do_lang_tempcode('INSERT_PARAMETER'),
        ));

        $guids = array(array(
            'GUID_FILENAME' => lorem_word(),
            'GUID_LINE' => placeholder_number(),
            'GUID_GUID' => placeholder_id(),
            'GUID_IS_LIVE' => false,
        ));

        $related = array(placeholder_id());

        return array(
            lorem_globalise(do_lorem_template('THEME_TEMPLATE_EDITOR_TAB', array(
                'THEME' => lorem_word(),
                'FILE' => lorem_word(),
                'FILE_ID' => lorem_word(),
                'CONTENTS' => lorem_paragraph(),
                'HIGHLIGHTER_TYPE' => 'htm',
                'REVISIONS' => placeholder_table(),
                'GUIDS' => $guids,
                'RELATED' => $related,
                'LIVE_PREVIEW_URL' => placeholder_url(),
                'SCREEN_PREVIEW_URL' => placeholder_url(),

                'INCLUDE_TEMPCODE_EDITING' => true,
                'PARAMETERS' => $parameters,
                'DIRECTIVES' => new Tempcode(),
                'MISC_SYMBOLS' => new Tempcode(),
                'PROGRAMMATIC_SYMBOLS' => new Tempcode(),
                'ABSTRACTION_SYMBOLS' => new Tempcode(),
                'ARITHMETICAL_SYMBOLS' => new Tempcode(),
                'FORMATTING_SYMBOLS' => new Tempcode(),
                'LOGICAL_SYMBOLS' => new Tempcode(),

                'INCLUDE_CSS_EDITING' => false,

                'OWN_FORM' => true,
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__theme_template_editor_restore_revision()
    {
        return array(
            lorem_globalise(do_lorem_template('THEME_TEMPLATE_EDITOR_RESTORE_REVISION', array(
                'DATE' => placeholder_date(),
                'FILE' => lorem_word(),
                'REVISION_ID' => placeholder_id(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__tempcode_tester_screen()
    {
        return array(
            lorem_globalise(do_lorem_template('TEMPCODE_TESTER_SCREEN', array(
                'TITLE' => lorem_title(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__screen_previews_screen()
    {
        $templates = new Tempcode();
        $lis = new Tempcode();
        $ftemp = new Tempcode();
        $list = array();
        foreach (placeholder_array() as $v) {
            $list[] = $v;
        }
        foreach (placeholder_array() as $v) {
            $lis->attach(do_lorem_template('THEME_SCREEN_PREVIEW', array(
                'URL' => placeholder_url(),
                'COLOR' => 'green',
                'TEMPLATE' => lorem_word(),
                'LIST' => '',
            )));
        }

        $post = do_lorem_template('THEME_SCREEN_PREVIEW_WRAP', array(
            'LI' => $lis,
            'TITLE' => lorem_phrase(),
        ));

        return array(
            lorem_globalise($post, null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__template_edit_links_screen()
    {
        $parameters = array(
            'FILE' => lorem_phrase(),
            'EDIT_URL' => placeholder_url(),
            'CODENAME' => lorem_word(),
            'GUID' => placeholder_id(),
            'ID' => placeholder_random_id(),
        );

        $param_info = do_lorem_template('PARAM_INFO', array(
            'MAP' => $parameters,
        ));

        return array(
            lorem_globalise(do_lorem_template('TEMPLATE_EDIT_LINK', array(
                'PARAM_INFO' => $param_info,
                'CONTENTS' => lorem_paragraph_html(),
                'CODENAME' => lorem_word(),
                'EDIT_URL' => placeholder_url(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__template_tree_screen()
    {
        $tree_items = new Tempcode();
        foreach (placeholder_array() as $value) {
            $parameters = array(
                'FILE' => lorem_phrase(),
                'EDIT_URL' => placeholder_url(),
                'CODENAME' => lorem_word(),
                'ID' => placeholder_random_id(),
            );
            $tree_item = do_lorem_template('TEMPLATE_TREE_ITEM', $parameters);

            $tree_items->attach(do_lorem_template('TEMPLATE_TREE_ITEM_WRAP', array(
                'CONTENT' => $tree_item,
            )));
        }

        $tree_node = new Tempcode();
        $tree_node->attach(do_lorem_template('TEMPLATE_TREE_NODE', array(
            'ITEMS' => $tree_items,
        )));

        $tree = do_lorem_template('TEMPLATE_TREE', array(
            'HIDDEN' => '',
            'EDIT_URL' => placeholder_url(),
            'TREE' => $tree_node,
        ));

        return array(
            lorem_globalise(do_lorem_template('TEMPLATE_TREE_SCREEN', array(
                'TITLE' => lorem_title(),
                'TREE' => $tree,
            )), null, '', true)
        );
    }
}
