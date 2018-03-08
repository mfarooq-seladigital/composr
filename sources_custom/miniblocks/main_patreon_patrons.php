<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    composr_homesite
 */

require_code('patreon');
$level = isset($map['level']) ? intval($map['level']) : 30;
$patreon_patrons = get_patreon_patrons_on_minimum_level($level);
$_patreon_patrons = array();
foreach ($patreon_patrons as $patron) {
    $_patreon_patrons[] = array(
        'NAME' => $patron['name'],
        'USERNAME' => $patron['username'],
        'MONTHLY' => strval($patron['monthly']),
    );
}

$tpl = do_template('BLOCK_MAIN_PATREON_PATRONS', array('_GUID' => '8b7ed8319aa6ec0e6bc0e8b5e1fede4d', 'PATREON_PATRONS' => $_patreon_patrons));
$tpl->evaluate_echo();