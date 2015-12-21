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
 * @package    core_menus
 */

require_code('resource_fs');

/**
 * Hook class.
 */
class Hook_commandr_fs_menus extends Resource_fs_base
{
    public $folder_resource_type = 'menu';
    public $file_resource_type = 'menu_item';

    /**
     * Standard Commandr-fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @return integer How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        switch ($resource_type) {
            case 'menu_item':
                return $GLOBALS['SITE_DB']->query_select_value('menu_items', 'COUNT(*)');

            case 'menu':
                return $GLOBALS['SITE_DB']->query_select_value('menu_items', 'COUNT(DISTINCT i_menu)');
        }
        return 0;
    }

    /**
     * Standard Commandr-fs function for searching for a resource by label.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @param  LONG_TEXT $label The resource label
     * @return array A list of resource IDs
     */
    public function find_resource_by_label($resource_type, $label)
    {
        switch ($resource_type) {
            case 'menu_item':
                $_ret = $GLOBALS['SITE_DB']->query_select('menu_items', array('id'), array($GLOBALS['SITE_DB']->translate_field_ref('i_caption') => $label));
                $ret = array();
                foreach ($_ret as $r) {
                    $ret[] = strval($r['id']);
                }
                return $ret;

            case 'menu':
                $ret = $GLOBALS['SITE_DB']->query_select('menu_items', array('DISTINCT i_menu'), array('i_menu' => $label));
                return collapse_1d_complexity('i_menu', $ret);
        }
        return array();
    }

    /**
     * Standard Commandr-fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array $row Resource row (not full, but does contain the ID)
     * @return ?TIME The edit date or add date, whichever is higher (null: could not find one)
     */
    protected function _get_folder_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'actionlogs WHERE ' . db_string_equal_to('param_a', $row['i_menu']) . ' AND  (' . db_string_equal_to('the_type', 'ADD_MENU') . ' OR ' . db_string_equal_to('the_type', 'EDIT_MENU') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard Commandr-fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT $filename Filename OR Resource label
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error)
     */
    public function folder_add($filename, $path, $properties)
    {
        if ($path != '') {
            return false; // Only one depth allowed for this resource type
        }

        list($properties, $label) = $this->_folder_magic_filter($filename, $path, $properties, $this->folder_resource_type);

        require_code('menus2');

        $menu = $this->_create_name_from_label($label);
        $test = $GLOBALS['SITE_DB']->query_select_value_if_there('menu_items', 'i_menu', array('i_menu' => $menu));
        if (!is_null($test)) {
            $menu .= '_' . uniqid('', true); // uniqify
        }

        $order = db_get_first_id();
        $parent = null;
        $caption = do_lang('HOME');
        $url = '_SELF:start';
        $check_permissions = 1;
        $page_only = '';
        $expanded = 1;
        $new_window = 0;
        $caption_long = '';
        $theme_image_code = '';
        $include_sitemap = 0;

        add_menu_item($menu, $order, $parent, $caption, $url, $check_permissions, $page_only, $expanded, $new_window, $caption_long, $theme_image_code, $include_sitemap);

        if ((addon_installed('commandr')) && (!running_script('install'))) {
            require_code('resource_fs');
            generate_resource_fs_moniker('menu', $menu, null, null, true);
        }

        log_it('ADD_MENU', $menu);

        $this->_resource_save_extend($this->folder_resource_type, $menu, $filename, $label, $properties);

        return $menu;
    }

    /**
     * Standard Commandr-fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT $filename Filename
     * @param  string $path The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array Details of the resource (false: error)
     */
    public function folder_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->folder_convert_filename_to_id($filename);

        $properties = array(
            'label' => $resource_id,
        );
        $this->_resource_load_extend($resource_type, $resource_id, $properties, $filename, $path);
        return $properties;
    }

    /**
     * Standard Commandr-fs edit function for resource-fs hooks. Edits the resource to the given properties.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function folder_edit($filename, $path, $properties)
    {
        list($properties, $label) = $this->_folder_magic_filter($filename, $path, $properties, $this->folder_resource_type);

        $menu = $this->_create_name_from_label($label);

        $test = $GLOBALS['SITE_DB']->query_select_value_if_there('menu_items', 'i_menu', array('i_menu' => $menu));
        if (is_null($test)) {
            return false;
        }

        $this->_resource_save_extend($this->folder_resource_type, $menu, $filename, $label, $properties);

        return $menu;
    }

    /**
     * Standard Commandr-fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @return boolean Success status
     */
    public function folder_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->folder_convert_filename_to_id($filename);

        require_code('menus2');
        delete_menu($resource_id);

        return true;
    }

    /**
     * Standard Commandr-fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array $row Resource row (not full, but does contain the ID)
     * @return ?TIME The edit date or add date, whichever is higher (null: could not find one)
     */
    protected function _get_file_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'actionlogs WHERE ' . db_string_equal_to('param_a', strval($row['id'])) . ' AND  (' . db_string_equal_to('the_type', 'ADD_MENU_ITEM') . ' OR ' . db_string_equal_to('the_type', 'EDIT_MENU_ITEM') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard Commandr-fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT $filename Filename OR Resource label
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function file_add($filename, $path, $properties)
    {
        list($category_resource_type, $category) = $this->folder_convert_filename_to_id($path);
        list($properties, $label) = $this->_file_magic_filter($filename, $path, $properties, $this->file_resource_type);

        if (is_null($category)) {
            return false; // Folder not found
        }

        require_code('menus2');

        $order = $this->_default_property_int($properties, 'order');
        $parent = $this->_default_property_int_null($properties, 'parent');
        $url = $this->_default_property_str($properties, 'url');
        $check_permissions = $this->_default_property_int($properties, 'check_permissions');
        $page_only = $this->_default_property_str($properties, 'page_only');
        $expanded = $this->_default_property_int($properties, 'expanded');
        $new_window = $this->_default_property_int($properties, 'new_window');
        $caption_long = $this->_default_property_str($properties, 'caption_long');
        $theme_image_code = $this->_default_property_str($properties, 'theme_image_code');
        $include_sitemap = $this->_default_property_int($properties, 'include_sitemap');

        $id = add_menu_item($category, $order, $parent, $label, $url, $check_permissions, $page_only, $expanded, $new_window, $caption_long, $theme_image_code, $include_sitemap);

        $this->_resource_save_extend($this->file_resource_type, strval($id), $filename, $label, $properties);

        return strval($id);
    }

    /**
     * Standard Commandr-fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT $filename Filename
     * @param  string $path The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array Details of the resource (false: error)
     */
    public function file_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        $rows = $GLOBALS['SITE_DB']->query_select('menu_items', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        $properties = array(
            'label' => $row['i_caption'],
            'order' => $row['i_order'],
            'parent' => $row['i_parent'],
            'caption_long' => $row['i_caption_long'],
            'url' => $row['i_url'],
            'check_permissions' => $row['i_check_permissions'],
            'expanded' => $row['i_expanded'],
            'new_window' => $row['i_new_window'],
            'page_only' => $row['i_page_only'],
            'theme_img_code' => $row['i_theme_img_code'],
            'include_sitemap' => $row['i_include_sitemap'],
        );
        $this->_resource_load_extend($resource_type, $resource_id, $properties, $filename, $path);
        return $properties;
    }

    /**
     * Standard Commandr-fs edit function for resource-fs hooks. Edits the resource to the given properties.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function file_edit($filename, $path, $properties)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);
        list($category_resource_type, $category) = $this->folder_convert_filename_to_id($path);
        list($properties,) = $this->_file_magic_filter($filename, $path, $properties, $this->file_resource_type);

        if (is_null($category)) {
            return false; // Folder not found
        }

        require_code('menus2');

        $label = $this->_default_property_str($properties, 'label');
        $order = $this->_default_property_int($properties, 'order');
        $parent = $this->_default_property_int_null($properties, 'parent');
        $url = $this->_default_property_str($properties, 'url');
        $check_permissions = $this->_default_property_int($properties, 'check_permissions');
        $page_only = $this->_default_property_str($properties, 'page_only');
        $expanded = $this->_default_property_int($properties, 'expanded');
        $new_window = $this->_default_property_int($properties, 'new_window');
        $caption_long = $this->_default_property_str($properties, 'caption_long');
        $theme_image_code = $this->_default_property_str($properties, 'theme_image_code');
        $include_sitemap = $this->_default_property_int($properties, 'include_sitemap');

        edit_menu_item(intval($resource_id), $category, $order, $parent, $label, $url, $check_permissions, $page_only, $expanded, $new_window, $caption_long, $theme_image_code, $include_sitemap);

        $this->_resource_save_extend($this->file_resource_type, $resource_id, $filename, $label, $properties);

        return $resource_id;
    }

    /**
     * Standard Commandr-fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @return boolean Success status
     */
    public function file_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        require_code('menus2');
        delete_menu_item(intval($resource_id));

        return true;
    }
}
