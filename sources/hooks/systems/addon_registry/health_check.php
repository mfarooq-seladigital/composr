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
 * @package    health_check
 */

/**
 * Hook class.
 */
class Hook_addon_registry_health_check
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
     * Get the addon category.
     *
     * @return string The category
     */
    public function get_category()
    {
        return 'New Features';
    }

    /**
     * Get the addon author.
     *
     * @return string The author
     */
    public function get_author()
    {
        return 'Chris Graham';
    }

    /**
     * Find other authors.
     *
     * @return array A list of co-authors that should be attributed
     */
    public function get_copyright_attribution()
    {
        return array();
    }

    /**
     * Get the addon licence (one-line summary only).
     *
     * @return string The licence
     */
    public function get_licence()
    {
        return 'Licensed on the same terms as Composr';
    }

    /**
     * Get the description of the addon.
     *
     * @return string Description of the addon
     */
    public function get_description()
    {
        return 'The Health Check addon automatically finds problems on your website and server.';
    }

    /**
     * Get a list of tutorials that apply to this addon.
     *
     * @return array List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_website_health',
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
            'conflicts_with' => array()
        );
    }

    /**
     * Explicitly say which icon should be used.
     *
     * @return URLPATH Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/menu/adminzone/tools/health_check.svg';
    }

    /**
     * Get a list of files that belong to this addon.
     *
     * @return array List of files
     */
    public function get_file_list()
    {
        return array(
            'sources/hooks/systems/addon_registry/health_check.php',
            'lang/EN/health_check.ini',
            'sources/hooks/systems/health_checks/.htaccess',
            'sources/hooks/systems/health_checks/index.html',
            'sources/hooks/systems/health_checks/install_env.php',
            'sources/hooks/systems/health_checks/install_env_php_lock_down.php',
            'sources/hooks/systems/health_checks/install_env_php_ext.php',
            'sources/hooks/systems/health_checks/cron.php',
            'sources/hooks/systems/health_checks/domains.php',
            'sources/hooks/systems/health_checks/email.php',
            'sources/hooks/systems/health_checks/email_newsletter.php',
            'sources/hooks/systems/health_checks/integrity.php',
            'sources/hooks/systems/health_checks/marketing.php',
            'sources/hooks/systems/health_checks/marketing_seo.php',
            'sources/hooks/systems/health_checks/marketing_seo_robotstxt.php',
            'sources/hooks/systems/health_checks/mistakes_build.php',
            'sources/hooks/systems/health_checks/mistakes_deploy.php',
            'sources/hooks/systems/health_checks/mistakes_user_ux.php',
            'sources/hooks/systems/health_checks/network.php',
            'sources/hooks/systems/health_checks/performance.php',
            'sources/hooks/systems/health_checks/performance_bloat.php',
            'sources/hooks/systems/health_checks/performance_server.php',
            'sources/hooks/systems/health_checks/security.php',
            'sources/hooks/systems/health_checks/security_hackattack.php',
            'sources/hooks/systems/health_checks/security_ssl.php',
            'sources/hooks/systems/health_checks/stability.php',
            'sources/hooks/systems/health_checks/upkeep.php',
            'sources/hooks/systems/health_checks/upkeep_backups.php',
            'themes/default/images/icons/menu/adminzone/tools/health_check.svg',
            'sources/health_check.php',
            'sources/hooks/systems/config/hc_cpu_normative_threshold.php',
            'sources/hooks/systems/config/hc_database_threshold.php',
            'sources/hooks/systems/config/hc_io_mbs.php',
            'sources/hooks/systems/config/hc_admin_stale_threshold.php',
            'sources/hooks/systems/config/hc_compound_requests_per_second_threshold.php',
            'sources/hooks/systems/config/hc_compound_requests_window_size.php',
            'sources/hooks/systems/config/hc_cpu_pct_threshold.php',
            'sources/hooks/systems/config/hc_cron_threshold.php',
            'sources/hooks/systems/config/hc_disk_space_threshold.php',
            'sources/hooks/systems/config/hc_error_log_day_flood_threshold.php',
            'sources/hooks/systems/config/hc_google_safe_browsing_api_enabled.php',
            'sources/hooks/systems/config/hc_io_pct_threshold.php',
            'sources/hooks/systems/config/hc_mail_address.php',
            'sources/hooks/systems/config/hc_mail_password.php',
            'sources/hooks/systems/config/hc_mail_server.php',
            'sources/hooks/systems/config/hc_mail_server_port.php',
            'sources/hooks/systems/config/hc_mail_server_type.php',
            'sources/hooks/systems/config/hc_mail_username.php',
            'sources/hooks/systems/config/hc_mail_wait_time.php',
            'sources/hooks/systems/config/hc_page_size_threshold.php',
            'sources/hooks/systems/config/hc_page_speed_threshold.php',
            'sources/hooks/systems/config/hc_process_hang_threshold.php',
            'sources/hooks/systems/config/hc_processes_to_monitor.php',
            'sources/hooks/systems/config/hc_ram_threshold.php',
            'sources/hooks/systems/config/hc_requests_per_second_threshold.php',
            'sources/hooks/systems/config/hc_requests_window_size.php',
            'sources/hooks/systems/config/hc_scan_page_links.php',
            'sources/hooks/systems/config/hc_transfer_latency_threshold.php',
            'sources/hooks/systems/config/hc_uptime_threshold.php',
            'sources/hooks/systems/config/hc_cron_notify_regardless.php',
            'sources/hooks/systems/config/hc_cron_regularity.php',
            'sources/hooks/systems/config/hc_cron_sections_to_run.php',
            'sources/hooks/systems/config/hc_is_test_site.php',
            'sources/hooks/systems/config/hc_transfer_speed_threshold.php',
            'sources/hooks/systems/cron/_health_check.php',
            'sources/hooks/systems/notifications/health_check.php',
            'themes/default/templates/HEALTH_CHECK_RESULTS.tpl',
            'themes/default/templates/HEALTH_CHECK_SCREEN.tpl',
            'data/health_check.php',
            'adminzone/pages/modules/admin_health_check.php',
            'sources/hooks/systems/page_groupings/health_check.php',
            'sources/hooks/systems/commandr_commands/health_check.php',
        );
    }

    /**
     * Get mapping between template names and the method of this class that can render a preview of them.
     *
     * @return array The mapping
     */
    public function tpl_previews()
    {
        return array(
            'templates/HEALTH_CHECK_RESULTS.tpl' => 'health_check_screen',
            'templates/HEALTH_CHECK_SCREEN.tpl' => 'health_check_screen',
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__health_check_screen()
    {
        $categories = array(
            lorem_phrase() => array(
                'SECTIONS' => array(
                    lorem_phrase() . ' 1' => array(
                        'RESULTS' => array(
                            array(
                                'RESULT' => 'PASS',
                                'MESSAGE' => lorem_sentence_html(),
                            ),
                            array(
                                'RESULT' => 'MANUAL',
                                'MESSAGE' => lorem_sentence_html(),
                            ),
                        ),
                        'NUM_FAILS' => placeholder_number(),
                        'NUM_PASSES' => placeholder_number(),
                        'NUM_SKIPPED' => placeholder_number(),
                        'NUM_MANUAL' => placeholder_number(),
                        '_NUM_FAILS' => '1',
                        '_NUM_PASSES' => '1',
                        '_NUM_SKIPPED' => '1',
                        '_NUM_MANUAL' => '1',
                    ),
                    lorem_phrase() . ' 2' => array(
                        'RESULTS' => array(
                            array(
                                'RESULT' => 'FAIL',
                                'MESSAGE' => lorem_sentence_html(),
                            ),
                        ),
                        'NUM_FAILS' => placeholder_number(),
                        'NUM_PASSES' => placeholder_number(),
                        'NUM_SKIPPED' => placeholder_number(),
                        'NUM_MANUAL' => placeholder_number(),
                        '_NUM_FAILS' => '1',
                        '_NUM_PASSES' => '1',
                        '_NUM_SKIPPED' => '1',
                        '_NUM_MANUAL' => '1',
                    ),
                    lorem_phrase() . ' 3' => array(
                        'RESULTS' => array(
                            array(
                                'RESULT' => 'SKIP',
                                'MESSAGE' => lorem_sentence_html(),
                            ),
                        ),
                        'NUM_FAILS' => placeholder_number(),
                        'NUM_PASSES' => placeholder_number(),
                        'NUM_SKIPPED' => placeholder_number(),
                        'NUM_MANUAL' => placeholder_number(),
                        '_NUM_FAILS' => '1',
                        '_NUM_PASSES' => '1',
                        '_NUM_SKIPPED' => '1',
                        '_NUM_MANUAL' => '1',
                    ),
                ),
            ),
        );
        $results = do_lorem_template('HEALTH_CHECK_RESULTS', array('CATEGORIES' => $categories));

        return array(
            lorem_globalise(do_lorem_template('HEALTH_CHECK_SCREEN', array(
                'TITLE' => lorem_title(),
                'SECTIONS' => placeholder_options(),
                'PASSES' => true,
                'SKIPS' => true,
                'MANUAL_CHECKS' => true,
                'RESULTS' => $results,
            )), null, '', true)
        );
    }
}
