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
 * @package    commandr
 */

/**
 * Hook class.
 */
class Hook_commandr_command_db_table_sizes
{
    /**
     * Run function for Commandr hooks.
     *
     * @param  array $options The options with which the command was called
     * @param  array $parameters The parameters with which the command was called
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return array Array of stdcommand, stdhtml, stdout, and stderr responses
     */
    public function run($options, $parameters, &$commandr_fs)
    {
        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('db_table_sizes', array('h'), array(true, true)), '', '');
        } else {
            require_code('files');

            if ((strpos(get_db_type(), 'mysql') === false) && (strpos(get_db_type(), 'sqlserver') === false) && (get_db_type() != 'postgresql')) {
                warn_exit(do_lang_tempcode('NOT_SUPPORTED_ON_DB'));
            }

            $out = '<div class="box box___commandr_box inline_block"><div class="box_inner"><div class="website_body">'; // XHTMLXHTML

            $db = $GLOBALS['SITE_DB'];
            require_code('files');

            if (strpos(get_db_type(), 'mysql') !== false) {
                $sql = 'SHOW TABLE STATUS WHERE Name LIKE \'' . db_encode_like($db->get_table_prefix() . '%') . '\'';
                $results = $db->query($sql);
                $sizes = list_to_map('Name', $results);
                foreach ($sizes as $key => $vals) {
                    $sizes[$key] = $vals['Data_length'] + $vals['Index_length'] - $vals['Data_free'];
                }
            } elseif (strpos(get_db_type(), 'sqlserver') !== false) {
                $sql = '
                    SELECT t.NAME AS tablename, SUM(a.used_pages) * 8 * 1024 AS usedpace
                    FROM sys.tables t JOIN sys.indexes i ON t.OBJECT_ID = i.object_id JOIN sys.partitions p ON i.object_id = p.OBJECT_ID AND i.index_id = p.index_id JOIN sys.allocation_units a ON p.partition_id = a.container_id LEFT JOIN sys.schemas s ON t.schema_id = s.schema_id
                    WHERE t.NAME NOT LIKE \'dt%\' AND t.is_ms_shipped = 0 AND i.OBJECT_ID > 255
                    GROUP BY t.Name, s.Name, p.Rows';
                $results = $db->query($sql);
                $sizes = collapse_2d_complexity('tablename', 'usedpace', $results);
            } elseif (get_db_type() == 'postgresql') {
                $sql = 'SELECT relname,(pg_total_relation_size(relid)-pg_relation_size(relid)) AS size FROM pg_catalog.pg_statio_user_tables WHERE relname LIKE \'' . db_encode_like($db->get_table_prefix() . '%') . '\'';
                $results = $db->query($sql);
                $sizes = collapse_2d_complexity('relname', 'size', $results);
            }

            asort($sizes);
            $out .= '<table class="results_table"><thead><tr><th>' . do_lang('NAME') . '</th><th>' . do_lang('SIZE') . '</th></tr></thead>';
            $out .= '<tbody>';
            foreach ($sizes as $key => $val) {
                $out .= '<tr><td>' . escape_html(preg_replace('#^' . preg_quote(get_table_prefix(), '#') . '#', '', $key)) . '</td><td>' . escape_html(clean_file_size($val)) . '</td></tr>';
            }
            $out .= '</tbody>';
            $out .= '</table>';

            if (count($parameters) != 0) {
                foreach ($parameters as $p) {
                    if (substr($p, 0, strlen(get_table_prefix())) == get_table_prefix()) {
                        $p = substr($p, strlen(get_table_prefix()));
                    }
                    $out .= '<h2>' . escape_html($p) . '</h2>';
                    if (array_key_exists(get_table_prefix() . $p, $sizes)) {
                        $num_rows = $db->query_select_value($p, 'COUNT(*)');
                        if ($num_rows > 0) {
                            $row = $db->query_select($p, array('*'), null, '', 1, mt_rand(0, $num_rows - 1));
                            $out .= '<table class="results_table"><tbody>';
                            $val = mixed();
                            foreach ($row[0] as $key => $val) {
                                if (!is_string($val)) {
                                    $val = strval($val);
                                }
                                $out .= '<tr><td>' . escape_html($key) . '</td><td>' . escape_html($val) . '</td></tr>';
                            }
                            $out .= '</tbody></table>';
                        } else {
                            $out .= '<p>' . do_lang('NONE') . '</p>';
                        }
                    } else {
                        $out .= '<p>' . do_lang('UNKNOWN') . '</p>';
                    }
                }
            }

            $out .= '</div></div></div>';

            return array('', $out, '', '');
        }
    }
}
