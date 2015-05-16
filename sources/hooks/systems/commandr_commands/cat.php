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
class Hook_commandr_command_cat
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
            return array('', do_command_help('cat', array('h'), array('l')), '', '');
        } else {
            if (!array_key_exists(0, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '1', 'cat'));
            }

            $line_numbers = array_key_exists('l', $options);

            $output = '';
            for ($i = 0; $i < count($parameters); $i++) {
                $parameters[$i] = $commandr_fs->_pwd_to_array($parameters[$i]);
                if (!$commandr_fs->_is_file($parameters[$i])) {
                    return array('', '', '', do_lang('NOT_A_FILE', integer_format($i + 1)));
                }
                $data = $commandr_fs->read_file($parameters[$i]);
                $lines = explode("\n", $data);
                foreach ($lines as $j => $line) {
                    if ($line_numbers) {
                        $output .= str_pad(strval($j + 1), strlen(strval(count($lines)))) . '  ';
                    }
                    $output .= $line . "\n";
                }
            }

            return array('', '', $output, '');
        }
    }
}
