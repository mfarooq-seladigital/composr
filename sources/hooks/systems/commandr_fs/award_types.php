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
 * @package    awards
 */

require_code('resource_fs');

/**
 * Hook class.
 */
class Hook_commandr_fs_award_types extends Resource_fs_base
{
    public $file_resource_type = 'award_type';

    /**
     * Standard commandr_fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @return integer How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        return $GLOBALS['SITE_DB']->query_select_value('award_types', 'COUNT(*)');
    }

    /**
     * Standard commandr_fs function for searching for a resource by label.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @param  LONG_TEXT $label The resource label
     * @return array A list of resource IDs
     */
    public function find_resource_by_label($resource_type, $label)
    {
        $_ret = $GLOBALS['SITE_DB']->query_select('award_types', array('id'), array($GLOBALS['SITE_DB']->translate_field_ref('a_title') => $label));
        $ret = array();
        foreach ($_ret as $r) {
            $ret[] = strval($r['id']);
        }
        return $ret;
    }

    /**
     * Standard commandr_fs introspection function.
     *
     * @return array The properties available for the resource type
     */
    protected function _enumerate_file_properties()
    {
        return array(
            'description' => 'LONG_TRANS',
            'points' => 'INTEGER',
            'resource_type' => 'ID_TEXT',
            'hide_awardee' => 'BINARY',
            'update_time_hours' => 'INTEGER',
        );
    }

    /**
     * Standard commandr_fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array $row Resource row (not full, but does contain the ID)
     * @return ?TIME The edit date or add date, whichever is higher (null: could not find one)
     */
    protected function _get_file_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('param_a', strval($row['id'])) . ' AND  (' . db_string_equal_to('the_type', 'ADD_AWARD_TYPE') . ' OR ' . db_string_equal_to('the_type', 'EDIT_AWARD_TYPE') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard commandr_fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT $filename Filename OR Resource label
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function file_add($filename, $path, $properties)
    {
        list($properties, $label) = $this->_file_magic_filter($filename, $path, $properties);

        require_code('awards2');

        $description = $this->_default_property_str($properties, 'description');
        $points = $this->_default_property_int($properties, 'points');
        $resource_type = $this->_default_property_str($properties, 'resource_type');
        if ($resource_type == '') {
            $resource_type = 'download';
        }
        $hide_awardee = $this->_default_property_int($properties, 'hide_awardee');
        $update_time_hours = $this->_default_property_int($properties, 'update_time_hours');

        $id = add_award_type($label, $description, $points, $resource_type, $hide_awardee, $update_time_hours);
        return strval($id);
    }

    /**
     * Standard commandr_fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT $filename Filename
     * @param  string $path The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array Details of the resource (false: error)
     */
    public function file_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        $rows = $GLOBALS['SITE_DB']->query_select('award_types', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        return array(
            'label' => $row['a_title'],
            'description' => $row['a_description'],
            'points' => $row['a_points'],
            'resource_type' => $row['a_content_type'],
            'hide_awardee' => $row['a_hide_awardee'],
            'update_time_hours' => $row['a_update_time_hours'],
        );
    }

    /**
     * Standard commandr_fs edit function for resource-fs hooks. Edits the resource to the given properties.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function file_edit($filename, $path, $properties)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);
        list($properties,) = $this->_file_magic_filter($filename, $path, $properties);

        require_code('awards2');

        $label = $this->_default_property_str($properties, 'label');
        $description = $this->_default_property_str($properties, 'description');
        $points = $this->_default_property_int($properties, 'points');
        $resource_type = $this->_default_property_str($properties, 'resource_type');
        if ($resource_type == '') {
            $resource_type = 'download';
        }
        $hide_awardee = $this->_default_property_int($properties, 'hide_awardee');
        $update_time_hours = $this->_default_property_int($properties, 'update_time_hours');

        edit_award_type(intval($resource_id), $label, $description, $points, $resource_type, $hide_awardee, $update_time_hours);

        return $resource_id;
    }

    /**
     * Standard commandr_fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @return boolean Success status
     */
    public function file_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        require_code('awards2');
        delete_award_type(intval($resource_id));

        return true;
    }
}
