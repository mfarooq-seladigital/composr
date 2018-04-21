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
 * @package    downloads
 */

/**
 * Hook class.
 */
class Hook_reorganise_uploads_downloads
{
    /**
     * Run function for reorganise_uploads hooks.
     */
    public function run()
    {
        if (!addon_installed('downloads')) {
            return;
        }

        require_code('downloads2');
        reorganise_uploads__download_categories(array(), true);
        reorganise_uploads__downloads(array(), true);
    }
}