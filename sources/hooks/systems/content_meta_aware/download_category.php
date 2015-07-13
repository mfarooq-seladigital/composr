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
 * @package    downloads
 */

/**
 * Hook class.
 */
class Hook_content_meta_aware_download_category
{
    /**
     * Get content type details. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
     *
     * @param  ?ID_TEXT $zone The zone to link through to (null: autodetect).
     * @return ?array Map of award content-type info (null: disabled).
     */
    public function info($zone = null)
    {
        return array(
            'supports_custom_fields' => true,

            'content_type_label' => 'downloads:DOWNLOAD_CATEGORY',

            'connection' => $GLOBALS['SITE_DB'],
            'table' => 'download_categories',
            'id_field' => 'id',
            'id_field_numeric' => true,
            'parent_category_field' => 'parent_id',
            'parent_category_meta_aware_type' => 'download_category',
            'is_category' => true,
            'is_entry' => false,
            'category_field' => 'parent_id', // For category permissions
            'category_type' => 'downloads', // For category permissions
            'parent_spec__table_name' => 'download_categories',
            'parent_spec__parent_name' => 'parent_id',
            'parent_spec__field_name' => 'id',
            'category_is_string' => false,

            'title_field' => 'category',
            'title_field_dereference' => true,
            'description_field' => 'description',
            'thumb_field' => 'rep_image',

            'view_page_link_pattern' => '_SEARCH:downloads:browse:_WILD',
            'edit_page_link_pattern' => '_SEARCH:cms_downloads:_edit_category:_WILD',
            'view_category_page_link_pattern' => '_SEARCH:downloads:browse:_WILD',
            'add_url' => (function_exists('has_submit_permission') && has_submit_permission('mid', get_member(), get_ip_address(), 'cms_downloads')) ? (get_module_zone('cms_downloads') . ':cms_downloads:add_category:parent_id=!') : null,
            'archive_url' => ((!is_null($zone)) ? $zone : get_module_zone('downloads')) . ':downloads',

            'support_url_monikers' => true,

            'views_field' => null,
            'submitter_field' => null,
            'add_time_field' => 'add_date',
            'edit_time_field' => null,
            'date_field' => 'add_date',
            'validated_field' => null,

            'seo_type_code' => 'downloads_category',

            'feedback_type_code' => null,

            'permissions_type_code' => 'downloads', // NULL if has no permissions

            'search_hook' => 'download_categories',

            'addon_name' => 'downloads',

            'cms_page' => 'cms_downloads',
            'module' => 'downloads',

            'commandr_filesystem_hook' => 'downloads',
            'commandr_filesystem__is_folder' => true,

            'rss_hook' => null,

            'actionlog_regexp' => '\w+_DOWNLOAD_CATEGORY',
        );
    }

    /**
     * Run function for content hooks. Renders a content box for an award/randomisation.
     *
     * @param  array $row The database row for the content
     * @param  ID_TEXT $zone The zone to display in
     * @param  boolean $give_context Whether to include context (i.e. say WHAT this is, not just show the actual content)
     * @param  boolean $include_breadcrumbs Whether to include breadcrumbs (if there are any)
     * @param  ?ID_TEXT $root Virtual root to use (null: none)
     * @param  boolean $attach_to_url_filter Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
     * @param  ID_TEXT $guid Overridden GUID to send to templates (blank: none)
     * @return Tempcode Results
     */
    public function run($row, $zone, $give_context = true, $include_breadcrumbs = true, $root = null, $attach_to_url_filter = false, $guid = '')
    {
        require_code('downloads');

        return render_download_category_box($row, $zone, $give_context, $include_breadcrumbs, is_null($root) ? null : intval($root), $attach_to_url_filter, $guid);
    }
}
