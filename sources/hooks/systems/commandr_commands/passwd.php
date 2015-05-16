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
class Hook_commandr_command_passwd
{
    /**
     * Run function for Commandr hooks.
     *
     * @param  array $options The options with which the command was called
     * @param  array $parameters The parameters with which the command was called
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return ~array                   Array of stdcommand, stdhtml, stdout, and stderr responses (false: error)
     */
    public function run($options, $parameters, &$commandr_fs)
    {
        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('passwd', array('h', 'u'), array(true)), '', '');
        } else {
            if (!array_key_exists(0, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '1', 'passwd'));
            }

            if (get_forum_type() != 'cns') {
                return array('', '', '', do_lang('NO_CNS'));
            }

            require_code('cns_members_action');
            require_code('cns_members_action2');

            if (array_key_exists('u', $options)) {
                $member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($options['u']);
            } elseif (array_key_exists('username', $options)) {
                $member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($options['username']);
            } else {
                $member_id = get_member();
            }

            $update = array();
            $update['m_password_change_code'] = '';
            $salt = $GLOBALS['CNS_DRIVER']->get_member_row_field($member_id, 'm_pass_salt');
            if (is_null($salt)) {
                return array('', '', '', do_lang('_MEMBER_NO_EXIST', array_key_exists('username', $options) ? $options['username'] : $options['u']));
            }

            if (get_value('no_password_hashing') === '1') {
                $update['m_password_compat_scheme'] = 'plain';
                $update['m_pass_salt'] = '';
                $update['m_pass_hash_salted'] = $parameters[0];
            } else {
                $update['m_password_compat_scheme'] = '';
                require_code('crypt');
                $update['m_pass_hash_salted'] = ratchet_hash($parameters[0], $salt);
            }

            $GLOBALS['SITE_DB']->query_update('f_members', $update, array('id' => $member_id), '', 1);
            return array('', '', do_lang('SUCCESS'), '');
        }
    }
}
