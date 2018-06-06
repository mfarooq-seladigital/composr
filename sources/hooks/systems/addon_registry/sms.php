<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    sms
 */

/**
 * Hook class.
 */
class Hook_addon_registry_sms
{
    /**
     * Get a list of file permissions to set.
     *
     * @param  boolean $runtime Whether to include wildcards represented runtime-created chmoddable files
     * @return array File permissions to set
     */
    public function get_chmod_array($runtime = false)
    {
        return array();
    }

    /**
     * Get the version of Composr this addon is for.
     *
     * @return float Version number
     */
    public function get_version()
    {
        return cms_version_number();
    }

    /**
     * Get the description of the addon.
     *
     * @return string Description of the addon
     */
    public function get_description()
    {
        return 'Provides an option for the software to send SMS messages, via the commercial Clickatell web service. By default this is only used by the notifications system.';
    }

    /**
     * Get a list of tutorials that apply to this addon.
     *
     * @return array List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_notifications',
        );
    }

    /**
     * Get a mapping of dependency types.
     *
     * @return array File permissions to set
     */
    public function get_dependencies()
    {
        return array(
            'requires' => array(),
            'recommends' => array(),
            'conflicts_with' => array(),
        );
    }

    /**
     * Explicitly say which icon should be used.
     *
     * @return URLPATH Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/admin/component.svg';
    }

    /**
     * Get a list of files that belong to this addon.
     *
     * @return array List of files
     */
    public function get_file_list()
    {
        return array(
            'sources/hooks/systems/addon_registry/sms.php',
            'sources/sms.php',
            'lang/EN/sms.ini',
            'sources/hooks/systems/config/sms_password.php',
            'sources/hooks/systems/config/sms_username.php',
            'sources/hooks/systems/config/sms_low_limit.php',
            'sources/hooks/systems/config/sms_high_limit.php',
            'sources/hooks/systems/config/sms_low_trigger_limit.php',
            'sources/hooks/systems/config/sms_high_trigger_limit.php',
            'sources/hooks/systems/config/sms_api_id.php',
            'sources/hooks/systems/cns_cpf_filter/sms.php',
            'data/sms.php',
        );
    }

    /**
     * Uninstall the addon.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('sms_log');
        $GLOBALS['SITE_DB']->drop_table_if_exists('confirmed_mobiles');

        delete_privilege('use_sms');
        delete_privilege('sms_higher_limit');
        delete_privilege('sms_higher_trigger_limit');
    }

    /**
     * Install the addon.
     *
     * @param  ?integer $upgrade_from What version we're upgrading from (null: new install)
     */
    public function install($upgrade_from = null)
    {
        if ($upgrade_from === null) {
            $GLOBALS['SITE_DB']->create_table('sms_log', array(
                'id' => '*AUTO',
                's_member_id' => 'MEMBER',
                's_time' => 'TIME',
                's_trigger_ip' => 'IP',
            ));
            $GLOBALS['SITE_DB']->create_index('sms_log', 'sms_log_for', array('s_member_id', 's_time'));
            $GLOBALS['SITE_DB']->create_index('sms_log', 'sms_trigger_ip', array('s_trigger_ip'));
            add_privilege('GENERAL_SETTINGS', 'use_sms', false);
            add_privilege('GENERAL_SETTINGS', 'sms_higher_limit', false);
            add_privilege('GENERAL_SETTINGS', 'sms_higher_trigger_limit', false);

            /* Not currently implemented
            $GLOBALS['SITE_DB']->create_table('confirmed_mobiles', array(
                'm_phone_number' => '*SHORT_TEXT',
                'm_member_id' => 'MEMBER',
                'm_time' => 'TIME',
                'm_confirm_code' => 'SHORT_TEXT',
            ));
            */
            /*$GLOBALS['SITE_DB']->create_index('confirmed_mobiles', 'confirmed_numbers', array('m_confirm_code'));*/
        }
    }
}
