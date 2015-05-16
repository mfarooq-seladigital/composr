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
 * @package    msn
 */

/**
 * Hook class.
 */
class Hook_addon_registry_msn
{
    /**
     * Get a list of file permissions to set
     *
     * @return array File permissions to set
     */
    public function get_chmod_array()
    {
        return array();
    }

    /**
     * Get the version of Composr this addon is for
     *
     * @return float Version number
     */
    public function get_version()
    {
        return cms_version_number();
    }

    /**
     * Get the description of the addon
     *
     * @return string Description of the addon
     */
    public function get_description()
    {
        return 'Features to support multi-site-networks (networks of linked sites that usually share a common member system).';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_msn',
        );
    }

    /**
     * Get a mapping of dependency types
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
     * Explicitly say which icon should be used
     *
     * @return URLPATH Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/menu/adminzone/structure/multi_site_network.png';
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array List of files
     */
    public function get_file_list()
    {
        return array(
            'themes/default/images/icons/24x24/menu/adminzone/structure/multi_site_network.png',
            'themes/default/images/icons/48x48/menu/adminzone/structure/multi_site_network.png',
            'sources/hooks/systems/config/network_links.php',
            'sources/hooks/systems/addon_registry/msn.php',
            'sources/hooks/blocks/main_notes/msn.php',
            'themes/default/templates/BLOCK_SIDE_NETWORK.tpl',
            'themes/default/templates/NETLINK.tpl',
            'adminzone/pages/comcode/EN/netlink.txt',
            'text/netlink.txt',
            'netlink.php',
            'sources/hooks/systems/page_groupings/msn.php',
            'sources/multi_site_networks.php',
            'sources/blocks/side_network.php',
        );
    }

    /**
     * Get mapping between template names and the method of this class that can render a preview of them
     *
     * @return array The mapping
     */
    public function tpl_previews()
    {
        return array(
            'templates/BLOCK_SIDE_NETWORK.tpl' => 'block_side_network',
            'templates/NETLINK.tpl' => 'netlink'
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__block_side_network()
    {
        return array(
            lorem_globalise(do_lorem_template('BLOCK_SIDE_NETWORK', array(
                'CONTENT' => lorem_phrase(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__netlink()
    {
        $content = new Tempcode();
        $url = placeholder_url();
        foreach (placeholder_array() as $key => $value) {
            $content->attach(form_input_list_entry($url->evaluate(), false, lorem_word()));
        }

        return array(
            lorem_globalise(do_lorem_template('NETLINK', array(
                'CONTENT' => $content,
            )), null, '', true)
        );
    }
}
