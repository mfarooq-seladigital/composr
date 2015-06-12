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
 * @package    commandr
 */

/**
 * Hook class.
 */
class Hook_commandr_fs_database
{
    /**
     * Standard commandr_fs listing function for Commandr FS hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return ~array The final directory listing (false: failure)
     */
    public function listing($meta_dir, $meta_root_node, &$commandr_fs)
    {
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        $listing = array();
        if (count($meta_dir) < 1) {
            // We're at the top level; list the tables
            $tables = $GLOBALS['SITE_DB']->query_select('db_meta', array('DISTINCT m_table'));

            foreach ($tables as $table) {
                $table_name = $table['m_table'];

                $modification_time = mixed();
                if (substr(get_db_type(), 0, 5) == 'mysql') {
                    $_modification_time = $GLOBALS['SITE_DB']->query_value_if_there('SELECT UPDATE_TIME FROM information_schema.tables WHERE TABLE_SCHEMA=\'' . db_escape_string(get_db_site()) . '\' AND TABLE_NAME=\'' . db_escape_string(get_table_prefix() . $table_name) . '\'', false, true);
                    $modification_time = strtotime($_modification_time);
                    if ($modification_time === false) {
                        $modification_time = null;
                    }
                }

                $listing[] = array(
                    $table_name,
                    COMMANDRFS_DIR,
                    null/*don't calculate a filesize*/,
                    $modification_time,
                );
            }
        } elseif (count($meta_dir) == 1) {
            // We're in a table; list the row key combinations
            $keys = $GLOBALS['SITE_DB']->query_select('db_meta', array('m_name', 'm_type'), array('m_table' => $meta_dir[0]));
            if (count($keys) == 0) {
                return false;
            }
            $select = array();
            foreach ($keys as $key) {
                if ($key['m_type'][0] == '*') {
                    $select[] = str_replace('*', '', $key['m_name']);
                }
            }
            $rows = $GLOBALS['SITE_DB']->query_select($meta_dir[0], $select, null, '', 1000/*reasonable limit*/);
            foreach ($rows as $row) {
                $x = '';
                foreach ($select as $key) {
                    if ($key == 'id') {
                        if ($x != '') {
                            $x .= ',';
                        }
                        $x .= $this->escape_name(is_string($row[$key]) ? $row[$key] : strval($row[$key]));
                    } else {
                        if ((is_string($row[$key])) && (strpos($row[$key], ':') !== false)) {
                            continue;
                        }
                        if ($x != '') {
                            $x .= ',';
                        }
                        $x .= $key . ':' . $this->escape_name(is_string($row[$key]) ? $row[$key] : strval($row[$key]));
                    }
                }
                $listing[] = array(
                    $x,
                    COMMANDRFS_DIR,
                    null/*don't calculate a filesize*/,
                    null/*don't specify a modification time*/,
                );
            }
        } elseif (count($meta_dir) == 2) {
            // We're in a row; list the row contents :)
            $where = $this->_do_where($meta_dir[0], $meta_dir[1]);
            if ($where === false) {
                return false;
            }
            $row = $GLOBALS['SITE_DB']->query_select($meta_dir[0], array('*'), $where, '', 1, null, false, array());
            if (!array_key_exists(0, $row)) {
                return false;
            }
            $row = $row[0];
            foreach ($row as $field_name => $field_value) {
                $listing[] = array(
                    $field_name,
                    COMMANDRFS_FILE,
                    null/*don't calculate a filesize*/,
                    null/*don't specify a modification time*/,
                );
            }
        } else {
            return false; // Directory doesn't exist
        }

        return $listing;
    }

    /**
     * Standard commandr_fs directory creation function for Commandr FS hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $new_dir_name The new directory name
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return boolean Success?
     */
    public function make_directory($meta_dir, $meta_root_node, $new_dir_name, &$commandr_fs)
    {
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        if (count($meta_dir) < 1) {
            return false;
        } elseif (count($meta_dir) == 1) {
            // We're in a field, and adding a new row
            $where = $this->_do_where($meta_dir[0], $new_dir_name);
            $fields = $GLOBALS['SITE_DB']->query_select('db_meta', array('m_name', 'm_type'), array('m_table' => $meta_dir[0]));
            $value = mixed();
            foreach ($fields as $field) {
                $field['m_type'] = str_replace('?', '', str_replace('*', '', $field['m_type']));
                if (!array_key_exists($field['m_name'], $where)) {
                    if (in_array($field['m_type'], array('AUTO', 'MEMBER', 'INTEGER', 'UINTEGER', 'MEMBER', 'SHORT_INTEGER', 'AUTO_LINK', 'BINARY', 'GROUP', 'TIME'))) {
                        $value = 0;
                    } elseif ($field['m_type'] == 'REAL') {
                        $value = 0.0;
                    } else {
                        $value = '';
                    }
                    $where[$field['m_name']] = $this->unescape_name($value);
                }
            }
            $GLOBALS['SITE_DB']->query_insert($meta_dir[0], $where);
        } else {
            return false; // Directories aren't allowed to be added anywhere else
        }

        return true;
    }

    /**
     * Standard commandr_fs directory removal function for Commandr FS hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $dir_name The directory name
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return boolean Success?
     */
    public function remove_directory($meta_dir, $meta_root_node, $dir_name, &$commandr_fs)
    {
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        if (count($meta_dir) < 1) {
            // We're at the top level, and removing a table
            $GLOBALS['SITE_DB']->drop_table_if_exists($dir_name);
        } elseif (count($meta_dir) == 1) {
            // We're in a field, and deleting a row
            $where = $this->_do_where($meta_dir[0], $dir_name);
            $GLOBALS['SITE_DB']->query_delete($meta_dir[0], $where, '', 1);
        } else {
            return false; // Directories aren't allowed to be removed anywhere else
        }

        return true;
    }

    /**
     * Standard commandr_fs file removal function for Commandr FS hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $file_name The file name
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return boolean Success?
     */
    public function remove_file($meta_dir, $meta_root_node, $file_name, &$commandr_fs)
    {
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        if (count($meta_dir) == 2) {
            // We're in a row, and deleting a field entry for this row
            $where = $this->_do_where($meta_dir[0], $meta_dir[1]);
            $test = $GLOBALS['SITE_DB']->query_select_value_if_there('db_meta', 'm_type', array('m_table' => $meta_dir[0], 'm_name' => $file_name));
            if (is_null($test)) {
                return false;
            }
            $test = str_replace('?', '', str_replace('*', '', $test));
            if (in_array($test, array('AUTO', 'MEMBER', 'INTEGER', 'UINTEGER', 'MEMBER', 'SHORT_INTEGER', 'AUTO_LINK', 'BINARY', 'GROUP', 'TIME'))) {
                $GLOBALS['SITE_DB']->query_update($meta_dir[0], array($file_name => 0), $where);
            } elseif ($test == 'REAL') {
                $GLOBALS['SITE_DB']->query_update($meta_dir[0], array($file_name => 0.0), $where);
            } else {
                $GLOBALS['SITE_DB']->query_update($meta_dir[0], array($file_name => ''), $where);
            }
        } else {
            return false; // Files shouldn't even exist anywhere else!
        }

        return true;
    }

    /**
     * Standard commandr_fs file reading function for Commandr FS hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $file_name The file name
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return ~string The file contents (false: failure)
     */
    public function read_file($meta_dir, $meta_root_node, $file_name, &$commandr_fs)
    {
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        if (count($meta_dir) == 2) {
            // We're in a row, and reading a field entry for this row
            $where = $this->_do_where($meta_dir[0], $meta_dir[1]);
            $test = $GLOBALS['SITE_DB']->query_select_value_if_there('db_meta', 'm_type', array('m_table' => $meta_dir[0], 'm_name' => $this->unescape_name($file_name)));
            if (is_null($test)) {
                return false;
            }
            $output = $GLOBALS['SITE_DB']->query_select($meta_dir[0], array($file_name), $where);
            if (!array_key_exists(0, $output)) {
                return false;
            }
            return is_null($output[0][$file_name]) ? '' : strval($output[0][$file_name]);
        } else {
            return false; // Files shouldn't even exist anywhere else!
        }
    }

    /**
     * Standard commandr_fs file writing function for Commandr FS hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $file_name The file name
     * @param  mixed $contents The new file contents (string or integer)
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return boolean Success?
     */
    public function write_file($meta_dir, $meta_root_node, $file_name, $contents, &$commandr_fs)
    {
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        if (count($meta_dir) == 2) {
            // We're in a row, and writing a field entry for this row
            $where = $this->_do_where($meta_dir[0], $meta_dir[1]);
            $test = $GLOBALS['SITE_DB']->query_select_value_if_there('db_meta', 'm_type', array('m_table' => $meta_dir[0], 'm_name' => $this->unescape_name($file_name)));
            if (is_null($test)) {
                return false;
            }
            $accepts_null = (strpos($test, '?') !== false);
            $test = str_replace('?', '', str_replace('*', '', $test));
            $update = array();
            if (in_array($test, array('AUTO', 'MEMBER', 'INTEGER', 'UINTEGER', 'MEMBER', 'SHORT_INTEGER', 'AUTO_LINK', 'BINARY', 'GROUP', 'TIME'))) {
                $update[$this->unescape_name($file_name)] = ($contents == '') ? null : intval($contents);
                if ((is_null($update[$this->unescape_name($file_name)])) && (!$accepts_null)) {
                    $update[$this->unescape_name($file_name)] = 0;
                }
                $GLOBALS['SITE_DB']->query_update($meta_dir[0], $update, $where, '', 1);
            } elseif ($test == 'REAL') {
                $update[$this->unescape_name($file_name)] = ($contents == '') ? null : floatval($contents);
                if ((is_null($update[$this->unescape_name($file_name)])) && (!$accepts_null)) {
                    $update[$this->unescape_name($file_name)] = 0.0;
                }
                $GLOBALS['SITE_DB']->query_update($meta_dir[0], $update, $where, '', 1);
            } else {
                $update[$this->unescape_name($file_name)] = $contents;
                $GLOBALS['SITE_DB']->query_update($meta_dir[0], $update, $where, '', 1);
            }
        } else {
            return false; // Files shouldn't even exist anywhere else!
        }

        return true;
    }

    /**
     * Take a provided key-value map from the path and generate a DB query WHERE map array.
     *
     * @param  string $table_name Database table name
     * @param  string $keys Key-value map ("key:value,key2:value2")
     * @return ~array WHERE map array (false: if an invalid key was referenced)
     */
    protected function _do_where($table_name, $keys)
    {
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        $db_keys = $GLOBALS['SITE_DB']->query_select('db_meta', array('*'), array('m_table' => $table_name));
        $_db_keys = array();
        foreach ($db_keys as $db_key) {
            $_db_keys[$db_key['m_name']] = str_replace('?', '', str_replace('*', '', $db_key['m_type']));
        }

        $where = array();
        $pairs = explode(',', $keys);
        foreach ($pairs as $_pair) {
            if (strpos($_pair, ':') === false) {
                $pair = array('id', $_pair);
            } else {
                $pair = explode(':', $_pair, 2);
            }
            if ((array_key_exists($pair[0], $_db_keys)) && (array_key_exists(1, $pair))) {
                if (in_array($_db_keys[$pair[0]], array('AUTO', 'MEMBER', 'INTEGER', 'UINTEGER', 'MEMBER', 'SHORT_INTEGER', 'AUTO_LINK', 'BINARY', 'GROUP', 'TIME'))) {
                    $pair[1] = intval($pair[1]);
                } elseif ($_db_keys[$pair[0]] == 'REAL') {
                    $pair[1] = floatval($pair[1]);
                } else {
                    $pair[1] = $this->unescape_name($pair[1]);
                }
                $where[$pair[0]] = $pair[1];
            } elseif (array_key_exists($pair[0], $_db_keys)) {
                $where[$pair[0]] = null;
            } else {
                return false; // Invalid key
            }
        }

        return $where;
    }

    /**
     * Escape a value for use in a filesystem path.
     *
     * @param  string $in Value to escape (original value)
     * @return string Escaped value
     */
    public function escape_name($in)
    {
        return str_replace(array(':', ',', '/'), array('!colon!', '!comma!', '!slash!'), $in);
    }

    /**
     * Unescape a value from a filesystem path back to the original.
     *
     * @param  string $in Escaped value
     * @return string Original value
     */
    public function unescape_name($in)
    {
        return str_replace(array('!colon!', '!comma!', '!slash!'), array(':', ',', '/'), $in);
    }
}
