<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    errorlog
 */

/**
 * Module page class.
 */
class Module_admin_errorlog
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled)
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user)
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name)
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return null to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled)
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        return array(
            '!' => array('ERROR_LOG', 'menu/adminzone/audit/errorlog'),
        );
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know metadata for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none)
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        require_lang('errorlog');

        set_helper_panel_tutorial('tut_disaster');

        $this->title = get_screen_title('ERROR_LOG');

        if (!php_function_allowed('ini_set')) {
            attach_message(do_lang_tempcode('ERROR_LOGGING_PROBABLY_BROKEN'), 'warn');
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution
     */
    public function run()
    {
        require_css('errorlog');

        // Read in errors
        if (!GOOGLE_APPENGINE) {
            if (is_readable(get_custom_file_base() . '/data_custom/errorlog.php')) {
                if (filesize(get_custom_file_base() . '/data_custom/errorlog.php') > 1024 * 1024) {
                    $myfile = fopen(get_custom_file_base() . '/data_custom/errorlog.php', 'rb');
                    flock($myfile, LOCK_SH);
                    fseek($myfile, -1024 * 500, SEEK_END);
                    $lines = explode("\n", fread($myfile, 1024 * 500));
                    flock($myfile, LOCK_UN);
                    fclose($myfile);
                    unset($lines[0]);
                    $lines[] = '...';
                } else {
                    $lines = file(get_custom_file_base() . '/data_custom/errorlog.php');
                }
            } else {
                $lines = array();
            }
            $stuff = array();
            foreach ($lines as $line) {
                $_line = trim($line);

                if (($_line != '') && (strpos($_line, '<?php') === false)) {
                    $matches = array();
                    if (preg_match('#^\[(.+?) (.+?)\] (.{1,20}):  ?(.*)#', $_line, $matches) != 0) {
                        $stuff[] = $matches;
                    }
                }
            }
        } else {
            $stuff = array();

            require_once('google/appengine/api/log/LogService.php');

            $_log_service = 'google\appengine\api\log\LogService';
            $log_service = new $_log_service;
            $options = array();
            $options['include_app_logs'] = true;
            $options['minimum_log_level'] = eval('return $log_service::LEVEL_WARNING;'); // = PHP notice
            $options['batch_size'] = 300;

            $logs = $log_service->fetch($options);
            foreach ($logs as $log) {
                $app_logs = $log->getAppLogs();
                foreach ($app_logs as $app_log) {
                    $message = $app_log->getMessage();

                    $level = $app_log->getLevel();
                    $_level = '';
                    if ($level == eval('return $log_service::LEVEL_WARNING;')) {
                        $_level = 'notice';
                    } elseif ($level == eval('return $log_service::LEVEL_ERROR;')) {
                        $_level = 'warning';
                    } elseif ($level == eval('return $log_service::LEVEL_CRITICAL;')) {
                        $_level = 'error';
                    } else {
                        continue;
                    }

                    $time = intval($app_log->getTimeUsec() / 1000000.0);

                    $stuff[] = array('', date('D-M-Y', $time), date('H:i:s', $time), $_level, $message);
                }
            }
        }

        // Put errors into table
        $start = get_param_integer('start', 0);
        $max = get_param_integer('max', 50);
        $sortables = array('date_and_time' => do_lang_tempcode('DATE_TIME'));
        $test = explode(' ', get_param_string('sort', 'date_and_time DESC', INPUT_FILTER_GET_COMPLEX), 2);
        if (count($test) == 1) {
            $test[1] = 'DESC';
        }
        list($sortable, $sort_order) = $test;
        if (((strtoupper($sort_order) != 'ASC') && (strtoupper($sort_order) != 'DESC')) || (!array_key_exists($sortable, $sortables))) {
            log_hack_attack_and_exit('ORDERBY_HACK');
        }
        if ($sort_order == 'DESC') {
            $stuff = array_reverse($stuff);
        }
        require_code('templates_results_table');
        $fields_title = results_field_title(array(do_lang_tempcode('DATE_TIME'), do_lang_tempcode('TYPE'), do_lang_tempcode('MESSAGE')), $sortables, 'sort', $sortable . ' ' . $sort_order);
        $fields = new Tempcode();
        for ($i = $start; $i < $start + $max; $i++) {
            if (!array_key_exists($i, $stuff)) {
                break;
            }

            $message = str_replace(get_file_base(), '', $stuff[$i][4]);

            $fields->attach(results_entry(array(
                $stuff[$i][1] . ' ' . $stuff[$i][2],
                $stuff[$i][3],
                $message,
            ), true));
        }
        $error = results_table(do_lang_tempcode('ERROR_LOG'), $start, 'start', $max, 'max', $i, $fields_title, $fields, $sortables, $sortable, $sort_order, 'sort', new Tempcode());

        // Read in end of permissions file
        require_all_lang();
        if (is_readable(get_custom_file_base() . '/data_custom/permission_checks.log')) {
            $myfile = @fopen(get_custom_file_base() . '/data_custom/permission_checks.log', 'rb');
            if ($myfile !== false) {
                flock($myfile, LOCK_SH);
                fseek($myfile, -40000, SEEK_END);
                $data = '';
                while (!feof($myfile)) {
                    $data .= fread($myfile, 8192);
                }
                flock($myfile, LOCK_UN);
                fclose($myfile);
                $lines = explode("\n", $data);
                if (count($lines) != 0) {
                    if (strpos($lines[0], '<' . '?php') !== false) {
                        array_shift($lines);
                    } else {
                        if (strlen($data) == 40000) {
                            $lines[0] = '...';
                        }
                    }
                }
                foreach ($lines as $i => $line) {
                    $matches = array();
                    if (preg_match('#^\s+has_privilege: (\w+)#', $line, $matches) != 0) {
                        $looked_up = do_lang('PRIVILEGE_' . $matches[1], null, null, null, null, false);
                        if ($looked_up !== null) {
                            $line = str_replace($matches[1], $looked_up, $line);
                            $lines[$i] = $line;
                        }
                    }
                }
            }

            // Put permssions into table
            $permission = implode("\n", $lines);
        } else {
            $permission = '';
        }

        $tpl = do_template('ERRORLOG_SCREEN', array('_GUID' => '9186c7beb6b722a52f39e2cbe16aded6', 'TITLE' => $this->title, 'ERROR' => $error, 'PERMISSION' => $permission));

        require_code('templates_internalise_screen');
        return internalise_own_screen($tpl);
    }
}
