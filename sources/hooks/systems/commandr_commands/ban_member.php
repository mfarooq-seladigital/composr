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
class Hook_commandr_command_ban_member
{
    /**
     * Run function for Commandr hooks.
     *
     * @param  array $options The options with which the command was called
     * @param  array $parameters The parameters with which the command was called
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return ~array Array of stdcommand, stdhtml, stdout, and stderr responses (false: error)
     */
    public function run($options, $parameters, &$commandr_fs)
    {
        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('ban_member', array('h', 'u'), array(true, true)), '', '');
        } else {
            if (get_forum_type() != 'cns') {
                return array('', '', '', do_lang('NO_CNS'));
            }

            if (!array_key_exists(0, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '1', 'ban_member'));
            }

            require_code('cns_members_action');
            require_code('cns_members_action2');
            require_lang('cns');

            $member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($parameters[0]);

            if (is_null($member_id)) {
                return array('', '', '', do_lang('MEMBER_NO_EXIST'));
            }

            if ((array_key_exists('u', $options)) || (array_key_exists('unban', $options))) {
                cns_unban_member($member_id);
            } else {
                cns_ban_member($member_id);
            }
            return array('', '', do_lang('SUCCESS'), '');
        }
    }
}
