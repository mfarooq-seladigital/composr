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
 * @package    core_fields
 */

/**
 * Hook class.
 */
class Hook_fields_integer
{
    // ==============
    // Module: search
    // ==============

    /**
     * Get special Tempcode for inputting this field.
     *
     * @param  array $field The field details
     * @return ?array Specially encoded input detail rows (null: nothing special)
     */
    public function get_search_inputter($field)
    {
        $type = '_INTEGER';
        $extra = '';
        $display = get_translated_text($field['cf_name']);

        $range_search = (option_value_from_field_array($field, 'range_search', 'off') == 'on');
        if ($range_search) {
            $type .= '_RANGE';
            $special = get_param_string('option_' . strval($field['id']) . '_from', '') . ';' . get_param_string('option_' . strval($field['id']) . '_to', '');
        } else {
            $special = get_param_string('option_' . strval($field['id']), '');
        }

        return array('NAME' => strval($field['id']) . $extra, 'DISPLAY' => $display, 'TYPE' => $type, 'SPECIAL' => $special);
    }

    /**
     * Get special SQL from POSTed parameters for this field.
     *
     * @param  array $field The field details
     * @param  integer $i We're processing for the ith row
     * @return ?array Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (null: nothing special)
     */
    public function inputted_to_sql_for_search($field, $i)
    {
        $range_search = (option_value_from_field_array($field, 'range_search', 'off') == 'on');
        if ($range_search) {
            return null;
        }

        return exact_match_sql($field, $i, 'integer');
    }

    // ===================
    // Backend: fields API
    // ===================

    /**
     * Get some info bits relating to our field type, that helps us look it up / set defaults.
     *
     * @param  ?array $field The field details (null: new field)
     * @param  ?boolean $required Whether a default value cannot be blank (null: don't "lock in" a new default value)
     * @param  ?string $default The given default value as a string (null: don't "lock in" a new default value)
     * @return array Tuple of details (row-type,default-value-to-use,db row-type)
     */
    public function get_field_value_row_bits($field, $required = null, $default = null)
    {
        if (($default !== null) && (strtoupper($default) == 'AUTO_INCREMENT') && (!is_null($field)) && (!is_null($field['id']))) { // We need to calculate a default even if not required, because the defaults are programmatic
            $default = is_null($field) ? '0' : $this->get_field_auto_increment($field['id'], intval($default));
    	} else {
            if ($required !== null) {
                if (($required) && ($default == '')) {
                    $default = '0';
                }
            }
        }
        return array('integer_unescaped', $default, 'integer');
    }

    /**
     * Convert a field value to something renderable.
     *
     * @param  array $field The field details
     * @param  mixed $ev The raw value
     * @return mixed Rendered field (tempcode or string)
     */
    public function render_field_value($field, $ev)
    {
        if (is_object($ev)) {
            if ($ev->evaluate() == do_lang('NA_EM')) {
                return '';
            }

            return $ev;
        }

        if ($ev == '') {
            return '';
        }

        if (($GLOBALS['XSS_DETECT']) && (ocp_is_escaped($ev))) {
            ocp_mark_as_escaped($ev);
        }
        return $ev;
    }

    // ======================
    // Frontend: fields input
    // ======================

    /**
     * Get form inputter.
     *
     * @param  string $_cf_name The field name
     * @param  string $_cf_description The field description
     * @param  array $field The field details
     * @param  ?string $actual_value The actual current value of the field (null: none)
     * @param  boolean $new Whether this is for a new entry
     * @return ?tempcode The Tempcode for the input field (null: skip the field - it's not input)
     */
    public function get_field_inputter($_cf_name, $_cf_description, $field, $actual_value, $new)
    {
        if ($new && strtoupper($field['cf_default']) == 'AUTO_INCREMENT') {
            return null;
        }

        if ($actual_value === do_lang('NA')) {
            $actual_value = null;
        }

        return form_input_integer($_cf_name, $_cf_description, 'field_' . strval($field['id']), (is_null($actual_value) || ($actual_value === '')) ? null : intval($actual_value), $field['cf_required'] == 1);
    }

    /**
     * Find the posted value from the get_field_inputter field
     *
     * @param  boolean $editing Whether we were editing (because on edit, it could be a fractional edit)
     * @param  array $field The field details
     * @param  ?string $upload_dir Where the files will be uploaded to (null: do not store an upload, return NULL if we would need to do so)
     * @param  ?array $old_value Former value of field (null: none)
     * @return ?string The value (null: could not process)
     */
    public function inputted_to_field_value($editing, $field, $upload_dir = 'uploads/catalogues', $old_value = null)
    {
        $id = $field['id'];
        $tmp_name = 'field_' . strval($id);
        if (!$editing && strtoupper($field['cf_default']) == 'AUTO_INCREMENT') {
            return $this->get_field_auto_increment($id, $field['cf_default']);
        }
        $ret = post_param_string($tmp_name, $editing ? STRING_MAGIC_NULL : '');
        if ($ret != STRING_MAGIC_NULL && $ret != '') {
            $ret = str_pad($ret, 10, '0', STR_PAD_LEFT);
        }
        return $ret;
    }

    /**
     * Get a fresh value for an auto_increment valued field.
     *
     * @param  AUTO_LINK $field_id The field ID
     * @param  string $default The field default
     * @return ?string The value (null: could not process)
     */
    public function get_field_auto_increment($field_id, $default = '')
    {
        // Get most recent value, to start with- we will iterate forward on it
        $value = $GLOBALS['SITE_DB']->query_select_value_if_there('catalogue_efv_integer', 'cv_value', array('cf_id' => $field_id), 'ORDER BY ce_id DESC');
        if (is_null($value)) {
            $value = strval(intval($default) - 1);
        }

        $test = null;
        do {
            $value = strval(intval($value) + 1);

            $test = $GLOBALS['SITE_DB']->query_select_value_if_there('catalogue_efv_integer', 'ce_id', array('cv_value' => $value, 'cf_id' => $field_id));
        } while (!is_null($test));

        return $value;
    }
}
