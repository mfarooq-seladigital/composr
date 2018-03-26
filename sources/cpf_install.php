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
 * @package    core
 */

/* Also see the cns_make_predefined_content_field function, which makes fields that are not integrated with anything */

/**
 * Remove CPF fields for GPS.
 * Assumes Conversr.
 */
function uninstall_gps_fields()
{
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('latitude');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('longitude');
}

/**
 * Create CPF fields for GPS.
 * Assumes Conversr.
 */
function install_gps_fields()
{
    require_lang('cns');

    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('latitude', 20, 0, 0, 1, 0, '', 'float');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('longitude', 20, 0, 0, 1, 0, '', 'float');
}

/**
 * Remove CPF fields for names.
 * Assumes Conversr.
 */
function uninstall_name_fields()
{
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('firstname');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('lastname');
}

/**
 * Create CPF fields for names.
 * Assumes Conversr.
 */
function install_name_fields()
{
    require_lang('cns');

    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('firstname', 35, 0, 0, 1, 0, '', 'short_text');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('lastname', 35, 0, 0, 1, 0, '', 'short_text');
}

/**
 * Remove CPF fields for address.
 * Assumes Conversr.
 */
function uninstall_address_fields()
{
    // Billing address
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('billing_street_address');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('billing_city');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('billing_county');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('billing_state');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('billing_post_code');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('billing_country');

    // Regular address (is also re-used for shipping)
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('street_address');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('city');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('county');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('state');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('post_code');
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('country');
}

/**
 * Create CPF fields for address.
 * Assumes Conversr.
 */
function install_address_fields()
{
    require_lang('cns');

    // Billing address...

    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('billing_street_address', 500, 0, 0, 1, 0, '', 'long_text');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('billing_city', 40, 0, 0, 1, 0, '', 'short_text');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('billing_county', 40, 0, 0, 1, 0, '', 'short_text');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('billing_state', 100, 0, 0, 1, 0, '', 'state');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('billing_post_code', 20, 0, 0, 1, 0, '', 'short_text');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('billing_country', 5, 0, 0, 1, 0, '', 'country');

    // Regular address (is also re-used for shipping)...

    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('street_address', 500, 0, 0, 1, 0, '', 'long_text');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('city', 40, 0, 0, 1, 0, '', 'short_text');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('county', 40, 0, 0, 1, 0, '', 'short_text');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('state', 100, 0, 0, 1, 0, '', 'state');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('post_code', 20, 0, 0, 1, 0, '', 'short_text');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('country', 5, 0, 0, 1, 0, '', 'country');
}

/**
 * Remove CPF field for mobile phone.
 * Assumes Conversr.
 */
function uninstall_mobile_phone_field()
{
    $GLOBALS['FORUM_DRIVER']->install_delete_custom_field('mobile_phone_number');
}

/**
 * Create CPF field for mobile phone.
 * Assumes Conversr.
 */
function install_mobile_phone_field()
{
    require_lang('cns_special_cpf');
    $GLOBALS['FORUM_DRIVER']->install_create_custom_field('mobile_phone_number', 30, 0, 0, 1, 0, do_lang('SPECIAL_CPF__cms_mobile_phone_number_DESCRIPTION'), 'short_text', 0, null, '', 'icons/contact_methods/telephone');
}
