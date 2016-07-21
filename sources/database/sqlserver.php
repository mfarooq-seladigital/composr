<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: (mssql|sqlsrv)\_.+*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_database_drivers
 */

/*
Use the Enterprise Manager to get things set up.
You need to go into your server properties and turn the security to "SQL Server and Windows"
*/

/**
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__database__sqlserver()
{
    safe_ini_set('mssql.textlimit', '300000');
    safe_ini_set('mssql.textsize', '300000');
}

/**
 * Database Driver.
 *
 * @package    core_database_drivers
 */
class Database_Static_sqlserver extends DatabaseDriver
{
    public $cache_db = array();

    /**
     * Get the default user for making db connections (used by the installer as a default).
     *
     * @return string The default user for db connections
     */
    public function default_user()
    {
        return 'sa';
    }

    /**
     * Get the default password for making db connections (used by the installer as a default).
     *
     * @return string The default password for db connections
     */
    public function default_password()
    {
        return '';
    }

    /**
     * Get a database connection. This function shouldn't be used by you, as a connection to the database is established automatically.
     *
     * @param  boolean $persistent Whether to create a persistent connection
     * @param  string $db_name The database name
     * @param  string $db_host The database host (the server)
     * @param  string $db_user The database connection username
     * @param  string $db_password The database connection password
     * @param  boolean $fail_ok Whether to on error echo an error and return with a null, rather than giving a critical error
     * @return ?array A database connection (null: failed)
     */
    public function get_connection($persistent, $db_name, $db_host, $db_user, $db_password, $fail_ok = false)
    {
        // Potential caching
        if (isset($this->cache_db[$db_name][$db_host])) {
            return $this->cache_db[$db_name][$db_host];
        }

        if ((!function_exists('sqlsrv_connect')) && (!function_exists('mssql_pconnect'))) {
            $error = 'The sqlserver PHP extension not installed (anymore?). You need to contact the system administrator of this server.';
            if ($fail_ok) {
                echo $error;
                return null;
            }
            critical_error('PASSON', $error);
        }

        if (function_exists('sqlsrv_connect')) {
            if ($db_host == '127.0.0.1' || $db_host == 'localhost') {
                $db_host = '(local)';
            }
            $connection = @sqlsrv_connect($db_host, ($db_user == '') ? array('Database' => $db_name) : array('UID' => $db_user, 'PWD' => $db_password, 'Database' => $db_name));
        } else {
            $connection = $persistent ? @mssql_pconnect($db_host, $db_user, $db_password) : @mssql_connect($db_host, $db_user, $db_password);
        }
        if ($connection === false) {
            $error = 'Could not connect to database-server (' . @strval($php_errormsg) . ')';
            if ($fail_ok) {
                echo $error;
                return null;
            }
            critical_error('PASSON', $error); //warn_exit(do_lang_tempcode('CONNECT_DB_ERROR'));
        }
        if (!function_exists('sqlsrv_connect')) {
            if (!mssql_select_db($db_name, $connection)) {
                $error = 'Could not connect to database (' . mssql_get_last_message() . ')';
                if ($fail_ok) {
                    echo $error;
                    return null;
                }
                critical_error('PASSON', $error); //warn_exit(do_lang_tempcode('CONNECT_ERROR'));
            }
        }

        $this->cache_db[$db_name][$db_host] = $connection;
        return $connection;
    }

    /**
     * Encode an SQL statement fragment for a conditional to see if two strings are equal.
     *
     * @param  ID_TEXT $attribute The attribute
     * @param  string $compare The comparison
     * @return string The SQL
     */
    public function string_equal_to($attribute, $compare)
    {
        return $attribute . " LIKE '" . $this->escape_string($compare) . "'";
    }

    /**
     * Encode a LIKE string comparision fragement for the database system. The pattern is a mixture of characters and ? and % wildcard symbols.
     *
     * @param  string $pattern The pattern
     * @return string The encoded pattern
     */
    public function encode_like($pattern)
    {
        return $this->escape_string(str_replace('%', '*', $pattern));
    }

    /**
     * Find whether full-text-search is present
     *
     * @param  array $connection A DB connection
     * @return boolean Whether it is
     */
    public function has_full_text($connection)
    {
        global $SITE_INFO;
        if ((!empty($SITE_INFO['skip_fulltext_sqlserver'])) && ($SITE_INFO['skip_fulltext_sqlserver'] == '1')) {
            return false;
        }
        return true;
    }

    /**
     * Assemble part of a WHERE clause for doing full-text search
     *
     * @param  string $content Our match string (assumes "?" has been stripped already)
     * @param  boolean $boolean Whether to do a boolean full text search
     * @return string Part of a WHERE clause for doing full-text search
     */
    public function full_text_assemble($content, $boolean)
    {
        $content = str_replace('"', '', $content);
        return 'CONTAINS ((?),\'' . $this->escape_string($content) . '\')';
    }

    /**
     * Escape a string so it may be inserted into a query. If SQL statements are being built up and passed using db_query then it is essential that this is used for security reasons. Otherwise, the abstraction layer deals with the situation.
     *
     * @param  string $string The string
     * @return string The escaped string
     */
    public function escape_string($string)
    {
        $string = fix_bad_unicode($string);

        return str_replace("'", "''", $string);
    }

    /**
     * This function is a very basic query executor. It shouldn't usually be used by you, as there are abstracted versions available.
     *
     * @param  string $query The complete SQL query
     * @param  array $connection A DB connection
     * @param  ?integer $max The maximum number of rows to affect (null: no limit)
     * @param  ?integer $start The start row to affect (null: no specification)
     * @param  boolean $fail_ok Whether to output an error on failure
     * @param  boolean $get_insert_id Whether to get the autoincrement ID created for an insert query
     * @return ?mixed The results (null: no results), or the insert ID
     */
    public function query($query, $connection, $max = null, $start = null, $fail_ok = false, $get_insert_id = false)
    {
        if (!is_null($max)) {
            if (is_null($start)) {
                $max += $start;
            }

            if ((strtoupper(substr($query, 0, 7)) == 'SELECT ') || (strtoupper(substr($query, 0, 8)) == '(SELECT ')) { // Unfortunately we can't apply to DELETE FROM and update :(. But its not too important, LIMIT'ing them was unnecessarily anyway
                $query = 'SELECT TOP ' . strval(intval($max)) . substr($query, 6);
            }
        }

        push_suppress_error_death(true);
        if (function_exists('sqlsrv_query')) {
            $results = sqlsrv_query($connection, $query, array(), array('Scrollable' => 'static'));
        } else {
            $results = mssql_query($query, $connection);
        }
        pop_suppress_error_death();
        if (($results === false) && (strtoupper(substr($query, 0, 12)) == 'INSERT INTO ') && (strpos($query, '(id, ') !== false)) {
            $pos = strpos($query, '(');
            $table_name = substr($query, 12, $pos - 13);
            if (function_exists('sqlsrv_query')) {
                @sqlsrv_query($connection, 'SET IDENTITY_INSERT ' . $table_name . ' ON');
            } else {
                @mssql_query('SET IDENTITY_INSERT ' . $table_name . ' ON', $connection);
            }
        }
        if (!is_null($start)) {
            if (function_exists('sqlsrv_fetch_array')) {
                sqlsrv_fetch($results, SQLSRV_SCROLL_ABSOLUTE, $start - 1);
            } else {
                @mssql_data_seek($results, $start);
            }
        }
        if ((($results === false) || ((strtoupper(substr($query, 0, 7)) == 'SELECT ') || (strtoupper(substr($query, 0, 8)) == '(SELECT ')) && ($results === true)) && (!$fail_ok)) {
            if (function_exists('sqlsrv_errors')) {
                $err = serialize(sqlsrv_errors());
            } else {
                $_error_msg = array_pop($GLOBALS['ATTACHED_MESSAGES_RAW']);
                if (is_null($_error_msg)) {
                    $error_msg = make_string_tempcode('?');
                } else {
                    $error_msg = $_error_msg[0];
                }
                $err = mssql_get_last_message() . '/' . $error_msg->evaluate();
                if (function_exists('ocp_mark_as_escaped')) {
                    ocp_mark_as_escaped($err);
                }
            }
            if ((!running_script('upgrader')) && (!get_mass_import_mode())) {
                if (!function_exists('do_lang') || is_null(do_lang('QUERY_FAILED', null, null, null, null, false))) {
                    $this->failed_query_exit(htmlentities('Query failed: ' . $query . ' : ' . $err));
                }

                $this->failed_query_exit(do_lang_tempcode('QUERY_FAILED', escape_html($query), ($err)));
            } else {
                $this->failed_query_echo(htmlentities('Database query failed: ' . $query . ' [') . ($err) . htmlentities(']'));
                return null;
            }
        }

        if ((strtoupper(substr($query, 0, 7)) == 'SELECT ') || (strtoupper(substr($query, 0, 8)) == '(SELECT ') && ($results !== false) && ($results !== true)) {
            return $this->get_query_rows($results);
        }

        if ($get_insert_id) {
            if (strtoupper(substr($query, 0, 7)) == 'UPDATE ') {
                return null;
            }

            $pos = strpos($query, '(');
            $table_name = substr($query, 12, $pos - 13);
            if (function_exists('sqlsrv_query')) {
                $res2 = sqlsrv_query($connection, 'SELECT MAX(IDENTITYCOL) AS v FROM ' . $table_name);
                $ar2 = sqlsrv_fetch_array($res2, SQLSRV_FETCH_ASSOC);
            } else {
                $res2 = mssql_query('SELECT MAX(IDENTITYCOL) AS v FROM ' . $table_name, $connection);
                $ar2 = mssql_fetch_array($res2);
            }
            return $ar2['v'];
        }

        return null;
    }

    /**
     * Get the rows returned from a SELECT query.
     *
     * @param  resource $results The query result pointer
     * @param  ?integer $start Whether to start reading from (null: irrelevant for this forum driver)
     * @return array A list of row maps
     */
    public function get_query_rows($results, $start = null)
    {
        $out = array();

        if (!function_exists('sqlsrv_num_fields')) {
            $num_fields = mssql_num_fields($results);
            $types = array();
            $names = array();
            for ($x = 1; $x <= $num_fields; $x++) {
                $types[$x - 1] = mssql_field_type($results, $x - 1);
                $names[$x - 1] = strtolower(mssql_field_name($results, $x - 1));
            }

            $i = 0;
            while (($row = mssql_fetch_row($results)) !== false) {
                $j = 0;
                $newrow = array();
                foreach ($row as $v) {
                    $type = strtoupper($types[$j]);
                    $name = $names[$j];

                    if (($type == 'SMALLINT') || ($type == 'INT') || ($type == 'INTEGER') || ($type == 'UINTEGER') || ($type == 'BYTE') || ($type == 'COUNTER')) {
                        if (!is_null($v)) {
                            $newrow[$name] = intval($v);
                        } else {
                            $newrow[$name] = null;
                        }
                    } else {
                        if ($v == ' ') {
                            $v = '';
                        }
                        $newrow[$name] = $v;
                    }

                    $j++;
                }

                $out[] = $newrow;

                $i++;
            }
        } else {
            if (function_exists('sqlsrv_fetch_array')) {
                while (($row = sqlsrv_fetch_array($results, SQLSRV_FETCH_ASSOC)) !== null) {
                    $out[] = $row;
                }
            } else {
                while (($row = mssql_fetch_row($results)) !== false) {
                    $out[] = $row;
                }
            }
        }

        if (function_exists('sqlsrv_free_stmt')) {
            sqlsrv_free_stmt($results);
        } else {
            mssql_free_result($results);
        }
        return $out;
    }

    /**
     * Get a map of Composr field types, to actual database types.
     *
     * @return array The map
     */
    public function get_type_remap()
    {
        $type_remap = array(
            'AUTO' => 'integer identity',
            'AUTO_LINK' => 'integer',
            'INTEGER' => 'integer',
            'UINTEGER' => 'bigint',
            'SHORT_INTEGER' => 'smallint',
            'REAL' => 'real',
            'BINARY' => 'smallint',
            'MEMBER' => 'integer',
            'GROUP' => 'integer',
            'TIME' => 'integer',
            'LONG_TRANS' => 'integer',
            'SHORT_TRANS' => 'integer',
            'LONG_TRANS__COMCODE' => 'integer',
            'SHORT_TRANS__COMCODE' => 'integer',
            'SHORT_TEXT' => 'varchar(255)',
            'LONG_TEXT' => 'text',
            'ID_TEXT' => 'varchar(80)',
            'MINIID_TEXT' => 'varchar(40)',
            'IP' => 'varchar(40)',
            'LANGUAGE_NAME' => 'varchar(5)',
            'URLPATH' => 'varchar(255)',
        );
        return $type_remap;
    }

    /**
     * Create a new table.
     *
     * @param  ID_TEXT $table_name The table name
     * @param  array $fields A map of field names to Composr field types (with *#? encodings)
     * @param  array $connection The DB connection to make on
     */
    public function create_table($table_name, $fields, $connection)
    {
        $type_remap = $this->get_type_remap();

        $_fields = '';
        $keys = '';
        foreach ($fields as $name => $type) {
            if ($type[0] == '*') { // Is a key
                $type = substr($type, 1);
                if ($keys != '') {
                    $keys .= ', ';
                }
                $keys .= $name;
            }

            if ($type[0] == '?') { // Is perhaps null
                $type = substr($type, 1);
                $perhaps_null = 'NULL';
            } else {
                $perhaps_null = 'NOT NULL';
            }

            $type = isset($type_remap[$type]) ? $type_remap[$type] : $type;

            $_fields .= '    ' . $name . ' ' . $type;
            if (substr($name, -13) == '__text_parsed') {
                $_fields .= ' DEFAULT \'\'';
            } elseif (substr($name, -13) == '__source_user') {
                $_fields .= ' DEFAULT ' . strval(db_get_first_id());
            }
            $_fields .= ' ' . $perhaps_null . ',' . "\n";
        }

        $query = 'CREATE TABLE ' . $table_name . ' (
          ' . $_fields . '
          PRIMARY KEY (' . $keys . ')
        )';
        $this->query($query, $connection, null, null);
    }

    /**
     * Create a table index.
     *
     * @param  ID_TEXT $table_name The name of the table to create the index on
     * @param  ID_TEXT $index_name The index name (not really important at all)
     * @param  string $_fields Part of the SQL query: a comma-separated list of fields to use on the index
     * @param  array $connection The DB connection to make on
     * @param  ID_TEXT $unique_key_field The name of the unique key field for the table
     */
    public function create_index($table_name, $index_name, $_fields, $connection, $unique_key_field = 'id')
    {
        if ($index_name[0] == '#') {
            if ($this->has_full_text($connection)) {
                $index_name = substr($index_name, 1);
                $unique_index_name = 'index' . $index_name . '_' . strval(mt_rand(0, mt_getrandmax()));
                $this->query('CREATE UNIQUE INDEX ' . $unique_index_name . ' ON ' . $table_name . '(' . $unique_key_field . ')', $connection);
                $this->query('CREATE FULLTEXT CATALOG ft AS DEFAULT', $connection, null, null, true); // Might already exist
                $this->query('CREATE FULLTEXT INDEX ON ' . $table_name . '(' . $_fields . ') KEY INDEX ' . $unique_index_name, $connection, null, null, true);
            }
            return;
        }
        $this->query('CREATE INDEX index' . $index_name . '_' . strval(mt_rand(0, mt_getrandmax())) . ' ON ' . $table_name . '(' . $_fields . ')', $connection);
    }

    /**
     * Change the primary key of a table.
     *
     * @param  ID_TEXT $table_name The name of the table to create the index on
     * @param  array $new_key A list of fields to put in the new key
     * @param  array $connection The DB connection to make on
     */
    public function change_primary_key($table_name, $new_key, $connection)
    {
        $this->query('ALTER TABLE ' . $table_name . ' DROP PRIMARY KEY', $connection);
        $this->query('ALTER TABLE ' . $table_name . ' ADD PRIMARY KEY (' . implode(',', $new_key) . ')', $connection);
    }

    /**
     * Close the database connections. We don't really need to close them (will close at exit), just disassociate so we can refresh them.
     */
    public function close_connections()
    {
        $this->cache_db = array();
    }
}
