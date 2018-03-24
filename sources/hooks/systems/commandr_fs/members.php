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
 * @package    core_cns
 */

/**
 * Hook class.
 */
class Hook_commandr_fs_members
{
    private $field_mapping = array(
        'id' => 'id',
        'theme' => 'm_theme',
        'avatar' => 'm_avatar_url',
        'validated' => 'm_validated',
        'timezone_offset' => 'm_timezone_offset',
        'primary_group' => 'm_primary_group',
        'signature' => 'm_signature',
        'banned' => 'm_is_perm_banned',
        'preview_posts' => 'm_preview_posts',
        'dob_day' => 'm_dob_day',
        'dob_month' => 'm_dob_month',
        'dob_year' => 'm_dob_year',
        'reveal_age' => 'm_reveal_age',
        'email' => 'm_email_address',
        'title' => 'm_title',
        'photo' => 'm_photo_url',
        'photo_thumb' => 'm_photo_thumb_url',
        'views_signatures' => 'm_views_signatures',
        'auto_monitor_contrib_content' => 'm_auto_monitor_contrib_content',
        'language' => 'm_language',
        'allow_emails' => 'm_allow_emails',
        'allow_emails_from_staff' => 'm_allow_emails_from_staff',
        'max_email_attach_size_mb' => 'm_max_email_attach_size_mb',
        'last_visit_time' => 'm_last_visit_time',
        'last_submit_time' => 'm_last_submit_time',
        'ip_address' => 'm_ip_address',
        'highlighted_name' => 'm_highlighted_name',
        'pt_allow' => 'm_pt_allow',
        'pt_rules_text' => 'm_pt_rules_text',
        'on_probation_until' => 'm_on_probation_until',
        'auto_mark_read' => 'm_auto_mark_read',
    );

    /**
     * Standard Commandr-fs listing function for commandr_fs hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return ~array The final directory listing (false: failure)
     */
    public function listing($meta_dir, $meta_root_node, &$commandr_fs)
    {
        if (get_forum_type() != 'cns') {
            return false;
        }

        $listing = array();
        if (count($meta_dir) < 1) {
            // We're listing the users
            $cnt = $GLOBALS['FORUM_DB']->query_select_value('f_members', 'COUNT(*)');
            if ($cnt > 1000) {
                return false; // Too much to process
            }

            $users = $GLOBALS['FORUM_DB']->query_select('f_members', array('id', 'm_username', 'm_join_time'));
            foreach ($users as $user) {
                $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'actionlogs WHERE ' . db_string_equal_to('param_a', strval($user['id'])) . ' AND  (' . db_string_equal_to('the_type', 'EDIT_EDIT_MEMBER_PROFILE') . ')';
                $modification_time = $GLOBALS['FORUM_DB']->query_value_if_there($query);
                if (is_null($modification_time)) {
                    $modification_time = $user['m_join_time'];
                }

                $listing[] = array(
                    $user['m_username'],
                    COMMANDR_FS_DIR,
                    null/*don't calculate a filesize*/,
                    $modification_time,
                );
            }
        } elseif (count($meta_dir) == 1) {
            // We're listing the profile fields and custom profile fields of the specified member
            $username = $meta_dir[0];
            $_member_data = $GLOBALS['FORUM_DB']->query_select('f_members', array('*'), array('m_username' => $username), '', 1);
            if (!array_key_exists(0, $_member_data)) {
                return false;
            }
            $member_data = $_member_data[0];

            $listing = array();
            foreach ($this->field_mapping as $prop => $field) {
                $listing[] = array(
                    $prop,
                    COMMANDR_FS_FILE,
                    strlen(@strval($member_data[$field])),
                    $member_data['m_join_time'],
                );
            }
            $listing[] = array(
                'groups',
                COMMANDR_FS_DIR,
                null/*don't calculate a filesize*/,
                $member_data['m_join_time'],
            );

            // Custom profile fields
            $_member_custom_fields = $GLOBALS['FORUM_DB']->query_select('f_member_custom_fields', array('*'), array('mf_member_id' => $member_data['id']), '', 1);
            if (!array_key_exists(0, $_member_custom_fields)) {
                return false;
            }
            $member_custom_fields = $_member_custom_fields[0];

            foreach (array_keys($member_custom_fields) as $_i) {
                if (preg_match('#^field_#', $_i) == 0) {
                    continue;
                }

                $i = intval(substr($_i, strlen('field_')));

                $_cpf_name = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_custom_fields', 'cf_name', array('id' => $i));
                if ($_cpf_name === null) {
                    continue; // Corrupt data
                }

                $cpf_name = get_translated_text($_cpf_name);
                if (preg_match('#^[\w\s]*$#', $cpf_name) == 0) {
                    $cpf_name = $_i;
                }

                $cpf_value = $member_custom_fields['field_' . strval($i)];

                $listing[] = array(
                    $cpf_name,
                    COMMANDR_FS_FILE,
                    @strlen(strval($cpf_value)),
                    $member_data['m_join_time'],
                );
            }
        } elseif (count($meta_dir) == 2) {
            if ($meta_dir[1] != 'groups') {
                return false;
            }

            // List the member's usergroups
            $groups = $GLOBALS['FORUM_DRIVER']->get_members_groups($GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]));
            $group_names = $GLOBALS['FORUM_DRIVER']->get_usergroup_list();
            foreach ($groups as $group) {
                if (array_key_exists($group, $group_names)) {
                    $listing[] = array(
                        $group_names[$group],
                        COMMANDR_FS_FILE,
                        0,
                        null,
                    );
                }
            }
        } else {
            return false; // Directory doesn't exist
        }

        return $listing;
    }

    /**
     * Standard Commandr-fs directory creation function for commandr_fs hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $new_dir_name The new directory name
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return boolean Success?
     */
    public function make_directory($meta_dir, $meta_root_node, $new_dir_name, &$commandr_fs)
    {
        if (get_forum_type() != 'cns') {
            return false;
        }

        if (count($meta_dir) < 1) {
            // We're at the top level, and adding a new member
            require_code('cns_members_action');
            require_code('cns_members_action2');
            cns_make_member($new_dir_name, 'commandr', '', null, null, null, null, array(), null, null, 1, null, null, '', '', '', 0, 1, 1, '', '', '', 1, 1, null, 1, 1, null, '', false);
        } else {
            return false; // Directories aren't allowed to be added anywhere else
        }

        return true;
    }

    /**
     * Standard Commandr-fs directory removal function for commandr_fs hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $dir_name The directory name
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return boolean Success?
     */
    public function remove_directory($meta_dir, $meta_root_node, $dir_name, &$commandr_fs)
    {
        if (get_forum_type() != 'cns') {
            return false;
        }

        if (count($meta_dir) < 1) {
            // We're at the top level, and removing a member
            require_code('cns_members_action');
            require_code('cns_members_action2');
            cns_delete_member($GLOBALS['FORUM_DRIVER']->get_member_from_username($dir_name));
        } else {
            return false; // Directories aren't allowed to be removed anywhere else
        }

        return true;
    }

    /**
     * Standard Commandr-fs file removal function for commandr_fs hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $file_name The file name
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return boolean Success?
     */
    public function remove_file($meta_dir, $meta_root_node, $file_name, &$commandr_fs)
    {
        if (get_forum_type() != 'cns') {
            return false;
        }

        if (count($meta_dir) == 1) {
            // We're in a member's directory, and deleting one of their profile fields
            if (array_key_exists($file_name, $this->field_mapping)) {
                return false; // Can't delete a hard-coded (non-custom) profile field
            }

            require_code('cns_members_action');
            require_code('cns_members_action2');

            $field_id = $this->get_field_id_for($file_name);

            return false; // This is too dangerous, probably not what the user wants!

            //cns_delete_custom_field($field_id);
        } elseif (count($meta_dir) == 2) {
            if ($meta_dir[1] != 'groups') {
                return false;
            }

            // We're in a member's usergroup directory, and removing them from a usergroup
            require_code('cns_groups_action');
            require_code('cns_groups_action2');
            $groups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list();
            $group_id = array_search($file_name, $groups);
            if ($group_id !== false) {
                cns_member_leave_group($group_id, $GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]));
            } else {
                return false;
            }
        } else {
            return false; // Files shouldn't even exist anywhere else!
        }

        return true;
    }

    /**
     * Standard Commandr-fs file reading function for commandr_fs hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $file_name The file name
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return ~string The file contents (false: failure)
     */
    public function read_file($meta_dir, $meta_root_node, $file_name, &$commandr_fs)
    {
        if (get_forum_type() != 'cns') {
            return false;
        }

        if (count($meta_dir) == 1) {
            // We're in a member's directory, and reading one of their profile fields
            if (array_key_exists($file_name, $this->field_mapping)) {
                return @strval($GLOBALS['FORUM_DB']->query_select_value_if_there('f_members', $this->field_mapping[$file_name], array('id' => $GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]))));
            }

            require_code('cns_members');
            require_code('cns_members_action');
            require_code('cns_members_action2');

            $cpf_id = $this->get_field_id_for($file_name);

            $custom_fields = cns_get_all_custom_fields_match_member($GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]));
            foreach ($custom_fields as $custom_field) {
                if ($custom_field['FIELD_ID'] == strval($cpf_id)) {
                    return $custom_field['RAW'];
                }
            }

            return false;
        } elseif (count($meta_dir) == 2) {
            if ($meta_dir[1] != 'groups') {
                return false;
            }

            // We're in a member's usergroup directory, and all files should contain '1' :)
            return '1';
        }

        return false; // Files shouldn't even exist anywhere else!
    }

    /**
     * Standard Commandr-fs file writing function for commandr_fs hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $file_name The file name
     * @param  string $contents The new file contents
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return boolean Success?
     */
    public function write_file($meta_dir, $meta_root_node, $file_name, $contents, &$commandr_fs)
    {
        if (get_forum_type() != 'cns') {
            return false;
        }

        if (count($meta_dir) == 1) {
            // We're in a member's directory, and writing one of their profile fields
            if (array_key_exists($file_name, $this->field_mapping)) {
                $GLOBALS['FORUM_DB']->query_update('f_members', array($this->field_mapping[$file_name] => $contents), array('id' => $GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0])), '', 1);
                return true;
            }

            require_code('cns_members_action');
            require_code('cns_members_action2');

            $field_id = $this->get_field_id_for($file_name, true);
            if (is_null($field_id)) {
                $field_id = cns_make_custom_field($file_name);
            }

            cns_set_custom_field($GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]), $field_id, $contents);
        } elseif (count($meta_dir) == 2) {
            if ($meta_dir[1] != 'groups') {
                return false;
            }

            // We're in a member's usergroup directory, and writing one of their usergroup associations
            require_code('cns_groups_action');
            require_code('cns_groups_action2');
            $groups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list();
            $group_id = array_search($file_name, $groups);
            if ($group_id !== false) {
                cns_add_member_to_group($GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]), $group_id, 1);
            } else {
                return false;
            }
        } else {
            return false; // Group files can't be written, and other files shouldn't even exist anywhere else!
        }

        return true;
    }

    /**
     * Get the field ID of a CPF from a filename.
     *
     * @param  string $file_name Filename
     * @param  boolean $missing_ok If the field may be missing
     * @return ?AUTO_LINK CPF ID (null: none)
     */
    protected function get_field_id_for($file_name, $missing_ok = false)
    {
        $matches = array();
        if (preg_match('#^field_(\d+)#', $file_name, $matches) != 0) {
            return intval($matches[1]);
        }

        $where = array($GLOBALS['FORUM_DB']->translate_field_ref('cf_name') => $file_name);

        if ($missing_ok) {
            $field_id = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_custom_fields', 'id', $where);
        } else {
            $field_id = $GLOBALS['FORUM_DB']->query_select_value('f_custom_fields', 'id', $where);
        }

        return $field_id;
    }
}
