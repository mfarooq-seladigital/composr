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
 * @package    catalogues
 */

/**
 * Hook class.
 */
class Hook_symbol_CATALOGUE_ENTRY_FIELD_VALUE
{
    /**
     * Run function for symbol hooks. Searches for tasks to perform.
     *
     * @param  array $param Symbol parameters
     * @return string Result
     */
    public function run($param)
    {
        $value = '';
        if ((isset($param[1])) && ($param[0] != '')) {
            $map = null;

            $entry_id = intval($param[0]);
            $field_id = intval($param[1]); // nth field in catalogue

            global $CATALOGUE_MAPPER_SYMBOL_CACHE;
            if (!isset($CATALOGUE_MAPPER_SYMBOL_CACHE)) {
                $CATALOGUE_MAPPER_SYMBOL_CACHE = array();
            }
            if (isset($CATALOGUE_MAPPER_SYMBOL_CACHE[$entry_id])) {
                $map = $CATALOGUE_MAPPER_SYMBOL_CACHE[$entry_id];
            } else {
                require_code('catalogues');
                $entry = $GLOBALS['SITE_DB']->query_select('catalogue_entries', array('*'), array('id' => $entry_id), '', 1);
                if (isset($entry[0])) {
                    $catalogue_name = $entry[0]['c_name'];
                    $catalogue = load_catalogue_row($catalogue_name, true);
                    if ($catalogue !== null) {
                        $tpl_set = $catalogue_name;
                        $map = get_catalogue_entry_map($entry[0], array('c_display_type' => C_DT_FIELDMAPS) + $catalogue, 'PAGE', $tpl_set, null, null/*Actually we'll load all so we can cache all,array($field_id)*/);

                        $CATALOGUE_MAPPER_SYMBOL_CACHE[$entry_id] = $map;
                    }
                }
            }

            if ($map !== null) {
                if (isset($map['FIELD_' . strval($field_id)])) {
                    $value = $map['FIELD_' . strval($field_id)];
                } elseif (isset($map['_FIELD_' . strval($field_id)])) {
                    $value = $map['_FIELD_' . strval($field_id)];
                }
            }

            if (is_object($value)) {
                $value = $value->evaluate();
            }
        }
        return $value;
    }
}
