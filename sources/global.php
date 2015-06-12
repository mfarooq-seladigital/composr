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
 * @package    core
 */

$script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_ENV['SCRIPT_NAME']) ? $_ENV['SCRIPT_NAME'] : '');
if ((strpos($script_name, '/sources/') !== false) || (strpos($script_name, '/sources_custom/') !== false)) {
    header('Content-type: text/plain');
    exit('May not be included directly');
}

/**
 * This function is a very important one when coding. It allows you to include a source code file (from root/sources/ or root/sources_custom/) through the proper channels.
 * You should remember this function, and not substitute anything else for it, as that will likely make your code unstable.
 * It is key to source code modularity in Composr.
 *
 * @param  string $codename The codename for the source module to load (or a full relative path, ending with .php; if custom checking is needed, this must be the custom version)
 * @param  boolean $light_exit Whether to cleanly fail when a source file is missing
 */
function require_code($codename, $light_exit = false)
{
    global $REQUIRED_CODE, $FILE_BASE, $SITE_INFO;
    if (isset($REQUIRED_CODE[$codename])) {
        return;
    }
    $REQUIRED_CODE[$codename] = false; // unset means no, false means in-progress, true means done

    $shorthand = (strpos($codename, '.php') === false);
    if (!$shorthand) {
        $non_custom_codename = str_replace('_custom/', '/', $codename);
        $REQUIRED_CODE[$non_custom_codename] = true;
    }

    if (strpos($codename, '..') !== false) {
        $codename = filter_naughty($codename);
    }

    static $mue = null;
    if ($mue === null) {
        $mue = function_exists('memory_get_usage');
    }
    if (($mue) && (isset($_GET['keep_show_loading'])) && ($_GET['keep_show_loading'] == '1')) {
        if (function_exists('memory_get_usage')) { // Repeated, for code quality checker; done previously, for optimisation
            $before = memory_get_usage();
        }
    }

    $worked = false;

    $path_custom = $FILE_BASE . '/' . ($shorthand ? ('sources_custom/' . $codename . '.php') : $codename);
    $path_orig = $FILE_BASE . '/' . ($shorthand ? ('sources/' . $codename . '.php') : $non_custom_codename);

    $has_orig = null;
    if (isset($GLOBALS['PERSISTENT_CACHE'])) {
        global $CODE_OVERRIDES;
        if (!isset($CODE_OVERRIDES)) {
            $CODE_OVERRIDES = persistent_cache_get('CODE_OVERRIDES');
            if ($CODE_OVERRIDES === null) {
                $CODE_OVERRIDES = array();
            }
        }
        if (isset($CODE_OVERRIDES[$codename])) {
            $has_custom = $CODE_OVERRIDES[$codename];
            $has_orig = $CODE_OVERRIDES['!' . $codename];
        } else {
            $has_custom = is_file($path_custom);
            $has_orig = is_file($path_orig);
            $CODE_OVERRIDES[$codename] = $has_custom;
            $CODE_OVERRIDES['!' . $codename] = $has_orig;
            persistent_cache_set('CODE_OVERRIDES', $CODE_OVERRIDES);
        }
    } else {
        $has_custom = is_file($path_custom);
    }

    if ((isset($SITE_INFO['safe_mode'])) && ($SITE_INFO['safe_mode'] == '1')) {
        $has_custom = false;
    }

    if (($has_custom) && ((!function_exists('in_safe_mode')) || (!in_safe_mode()) || (!is_file($path_orig)))) {
        $done_init = false;
        $init_func = 'init__' . str_replace('/', '__', str_replace('.php', '', $codename));

        if (!isset($has_orig)) {
            $has_orig = is_file($path_orig);
        }
        if (($path_custom != $path_orig) && ($has_orig)) {
            $orig = str_replace(array('?' . '>', '<' . '?php'), array('', ''), file_get_contents($path_orig));
            $a = file_get_contents($path_custom);

            if ((strpos($codename, '.php') === false) || (strpos($a, 'class Mx_') === false)/*Cannot do code rewrite for a module override that includes an Mx, because the extends needs the parent class already defined*/) {
                $functions_before = get_defined_functions();
                $classes_before = get_declared_classes();
                if (HHVM) {
                    hhvm_include($path_custom); // Include our custom
                } else {
                    include($path_custom); // Include our custom
                }
                $functions_after = get_defined_functions();
                $classes_after = get_declared_classes();
                $functions_diff = array_diff($functions_after['user'], $functions_before['user']); // Our custom defined these functions
                $classes_diff = array_diff($classes_after, $classes_before);

                $pure = true; // We will set this to false if it does not have all functions the main one has. If it does have all functions we know we should not run the original init, as it will almost certainly just have been the same code copy&pasted through.
                $overlaps = false;
                foreach ($functions_diff as $function) { // Go through override's functions and make sure original doesn't have them: rename original's to non_overridden__ equivs.
                    if (strpos($orig, 'function ' . $function . '(') !== false) { // NB: If this fails, it may be that "function\t" is in the file (you can't tell with a three-width proper tab)
                        $orig = str_replace('function ' . $function . '(', 'function non_overridden__' . $function . '(', $orig);
                        $overlaps = true;
                    } else {
                        $pure = false;
                    }
                }
                foreach ($classes_diff as $class) {
                    if (substr(strtolower($class), 0, 6) == 'module') {
                        $class = ucfirst($class);
                    }
                    if (substr(strtolower($class), 0, 4) == 'hook') {
                        $class = ucfirst($class);
                    }

                    if (strpos($orig, 'class ' . $class) !== false) {
                        $orig = str_replace('class ' . $class, 'class non_overridden__' . $class, $orig);
                        $overlaps = true;
                    } else {
                        $pure = false;
                    }
                }

                // See if we can get away with loading init function early. If we can we do a special version of it that supports fancy code modification. Our override isn't allowed to call the non-overridden init function as it won't have been loaded up by PHP in time. Instead though we will call it ourselves if it still exists (hasn't been removed by our own init function) because it likely serves a different purpose to our code-modification init function and copy&paste coding is bad.
                $doing_code_modifier_init = function_exists($init_func);
                if ($doing_code_modifier_init) {
                    $test = call_user_func_array($init_func, array($orig));
                    if (is_string($test)) {
                        $orig = $test;
                    }
                    $done_init = true;
                    if ((count($functions_diff) == 1) && (count($classes_diff) == 0)) {
                        $pure = false;
                    }
                }

                if (!$doing_code_modifier_init && !$overlaps) { // To make stack traces more helpful and help with opcode caching
                    if (HHVM) {
                        hhvm_include($path_orig);
                    } else {
                        include($path_orig);
                    }
                } else {
                    //static $log_file=NULL;if ($log_file===NULL) $log_file=fopen(get_file_base().'/log.'.strval(time()).'.txt','wb');fwrite($log_file,$path_orig."\n");      Good for debugging errors in eval'd code
                    eval($orig); // Load up modified original

                }

                if ((!$pure) && ($doing_code_modifier_init) && (function_exists('non_overridden__init__' . str_replace('/', '__', str_replace('.php', '', $codename))))) {
                    call_user_func('non_overridden__init__' . str_replace('/', '__', str_replace('.php', '', $codename)));
                }
            } else {
                // Note we load the original and then the override. This is so function_exists can be used in the overrides (as we can't support the re-definition) OR in the case of Mx_ class derivation, so that the base class is loaded first.

                if (isset($_GET['keep_show_parse_errors'])) {
                    safe_ini_set('display_errors', '0');
                    $orig = str_replace('?' . '>', '', str_replace('<' . '?php', '', file_get_contents($path_orig)));
                    if (eval($orig) === false) {
                        if ((!function_exists('fatal_exit')) || ($codename == 'failure')) {
                            critical_error('PASSON', @strval($php_errormsg) . ' [sources/' . $codename . '.php]');
                        }
                        fatal_exit(@strval($php_errormsg) . ' [sources/' . $codename . '.php]');
                    }
                } else {
                    if (HHVM) {
                        hhvm_include($path_orig);
                    } else {
                        include($path_orig);
                    }
                }
                if (isset($_GET['keep_show_parse_errors'])) {
                    safe_ini_set('display_errors', '0');
                    $orig = str_replace('?' . '>', '', str_replace('<' . '?php', '', file_get_contents($path_custom)));
                    if (eval($orig) === false) {
                        if ((!function_exists('fatal_exit')) || ($codename == 'failure')) {
                            critical_error('PASSON', @strval($php_errormsg) . ' [sources_custom/' . $codename . '.php]');
                        }
                        fatal_exit(@strval($php_errormsg) . ' [sources_custom/' . $codename . '.php]');
                    }
                } else {
                    if (HHVM) {
                        hhvm_include($path_custom);
                    } else {
                        include($path_custom);
                    }
                }
            }
        } else {
            if (isset($_GET['keep_show_parse_errors'])) {
                safe_ini_set('display_errors', '0');
                $orig = str_replace('?' . '>', '', str_replace('<' . '?php', '', file_get_contents($path_custom)));
                if (eval($orig) === false) {
                    if ((!function_exists('fatal_exit')) || ($codename == 'failure')) {
                        critical_error('PASSON', @strval($php_errormsg) . ' [sources_custom/' . $codename . '.php]');
                    }
                    fatal_exit(@strval($php_errormsg) . ' [sources_custom/' . $codename . '.php]');
                }
            } else {
                if (HHVM) {
                    hhvm_include($path_custom);
                } else {
                    include($path_custom);
                }
            }
        }

        if (($mue) && (isset($_GET['keep_show_loading'])) && ($_GET['keep_show_loading'] == '1')) {
            if (function_exists('memory_get_usage')) { // Repeated, for code quality checker; done previously, for optimisation
                print('<!-- require_code: ' . htmlentities($codename) . ' (' . number_format(memory_get_usage() - $before) . ' bytes used, now at ' . number_format(memory_get_usage()) . ') -->' . "\n");
                flush();
            }
        }

        if (!$done_init) {
            if (function_exists($init_func)) {
                call_user_func($init_func);
            }
        }

        $worked = true;
    } else {
        if (isset($_GET['keep_show_parse_errors'])) {
            $contents = @file_get_contents($path_orig);
            if ($contents !== false) {
                safe_ini_set('display_errors', '0');
                $orig = str_replace(array('?' . '>', '<' . '?php'), array('', ''), $contents);

                if (eval($orig) === false) {
                    if ((!function_exists('fatal_exit')) || ($codename == 'failure')) {
                        critical_error('PASSON', @strval($php_errormsg) . ' [sources/' . $codename . '.php]');
                    }
                    fatal_exit(@strval($php_errormsg) . ' [sources/' . $codename . '.php]');
                }

                $worked = true;
            }
        } else {
            $php_errormsg = '';
            if (HHVM) {
                @hhvm_include($path_orig);
            } else {
                @include($path_orig);
            }
            if ($php_errormsg == '') {
                $worked = true;
            }
        }

        if ($worked) {
            if (($mue) && (isset($_GET['keep_show_loading'])) && ($_GET['keep_show_loading'] == '1')) {
                if (function_exists('memory_get_usage')) { // Repeated, for code quality checker; done previously, for optimisation
                    print('<!-- require_code: ' . htmlentities($codename) . ' (' . number_format(memory_get_usage() - $before) . ' bytes used, now at ' . number_format(memory_get_usage()) . ') -->' . "\n");
                    flush();
                }
            }

            $init_func = 'init__' . str_replace(array('/', '.php'), array('__', ''), $codename);
            if (function_exists($init_func)) {
                call_user_func($init_func);
            }
        }
    }

    $REQUIRED_CODE[$codename] = true;
    if ($worked) {
        return;
    }

    if ($light_exit) {
        warn_exit(do_lang_tempcode('MISSING_SOURCE_FILE', escape_html($codename), escape_html($path_orig)));
    }
    if (!function_exists('do_lang')) {
        if ($codename == 'critical_errors') {
            exit('<!DOCTYPE html>' . "\n" . '<html lang="EN"><head><title>Critical startup error</title></head><body><h1>Composr startup error</h1><p>The Composr critical error message file, sources/critical_errors.php, could not be located. This is almost always due to an incomplete upload of the Composr system, so please check all files are uploaded correctly.</p><p>Once all Composr files are in place, Composr must actually be installed by running the installer. You must be seeing this message either because your system has become corrupt since installation, or because you have uploaded some but not all files from our manual installer package: the quick installer is easier, so you might consider using that instead.</p><p>ocProducts maintains full documentation for all procedures and tools, especially those for installation. These may be found on the <a href="http://compo.sr">Composr website</a>. If you are unable to easily solve this problem, we may be contacted from our website and can help resolve it for you.</p><hr /><p style="font-size: 0.8em">Composr is a website engine created by ocProducts.</p></body></html>');
        }
        critical_error('MISSING_SOURCE', $codename);
    }
    fatal_exit(do_lang_tempcode('MISSING_SOURCE_FILE', escape_html($codename), escape_html($path_orig)));
}

/**
 * Require code, but without looking for sources_custom overrides
 *
 * @param  string $codename The codename for the source module to load
 */
function require_code_no_override($codename)
{
    global $REQUIRED_CODE;
    if (array_key_exists($codename, $REQUIRED_CODE)) {
        return;
    }
    $REQUIRED_CODE[$codename] = true;
    require_once(get_file_base() . '/sources/' . filter_naughty($codename) . '.php');
    if (function_exists('init__' . str_replace('/', '__', $codename))) {
        call_user_func('init__' . str_replace('/', '__', $codename));
    }
}

/**
 * Find if we are running on a live Google App Engine application.
 *
 * @return boolean If it is running as a live Google App Engine application
 */
function appengine_is_live()
{
    return ((GOOGLE_APPENGINE) && (!is_writable(get_file_base() . '/index.php')));
}

/**
 * Are we currently running HTTPS.
 *
 * @return boolean If we are
 */
function tacit_https()
{
    static $tacit_https = null;
    if ($tacit_https === null) {
        $tacit_https = ((cms_srv('HTTPS') != '') && (cms_srv('HTTPS') != 'off')) || (cms_srv('HTTP_X_FORWARDED_PROTO') == 'https');
    }
    return $tacit_https;
}

/**
 * Make an object of the given class
 *
 * @param  string $class The class name
 * @param  boolean $failure_ok Whether to return NULL if there is no such class
 * @return ?object The object (null: no such class)
 */
function object_factory($class, $failure_ok = false)
{
    if (!class_exists($class)) {
        if ($failure_ok) {
            return null;
        }
        fatal_exit(escape_html('Missing class: ' . $class));
    }
    return new $class;
}

/**
 * Find whether a particular PHP function is blocked.
 *
 * @param  string $function Function name.
 * @return boolean Whether it is.
 */
function php_function_allowed($function)
{
    return (@preg_match('#(\s|,|^)' . str_replace('#', '\#', preg_quote($function)) . '(\s|$|,)#', strtolower(@ini_get('disable_functions') . ',' . ini_get('suhosin.executor.func.blacklist') . ',' . ini_get('suhosin.executor.include.blacklist') . ',' . ini_get('suhosin.executor.eval.blacklist'))) == 0);
}

/**
 * Sets the value of a configuration option, if the PHP environment allows it.
 *
 * @param  string $var Config option.
 * @param  string $value New value of option.
 * @return ~string Old value of option (false: error).
 */
function safe_ini_set($var, $value)
{
    if (!php_function_allowed('ini_set')) {
        return false;
    }

    return @ini_set($var, $value);
}

/**
 * Get the file base for your installation of Composr
 *
 * @return PATH The file base, without a trailing slash
 */
function get_file_base()
{
    global $FILE_BASE;
    return $FILE_BASE;
}

/**
 * Get the file base for your installation of Composr.  For a shared install, or a GAE-install, this is different to the file-base.
 *
 * @return PATH The file base, without a trailing slash
 */
function get_custom_file_base()
{
    global $FILE_BASE, $SITE_INFO;
    if (!empty($SITE_INFO['custom_file_base'])) {
        return $SITE_INFO['custom_file_base'];
    }
    if (!empty($SITE_INFO['custom_file_base_stub'])) {
        require_code('shared_installs');
        $u = current_share_user();
        if (!is_null($u)) {
            return $SITE_INFO['custom_file_base_stub'] . '/' . $u;
        }
    }
    return $FILE_BASE;
}

/**
 * Get the parameter put into it, with no changes. If it detects that the parameter is naughty (i.e malicious, and probably from a hacker), it will log the hack-attack and output an error message.
 * This function is designed to be called on parameters that will be embedded in a path, and defines malicious as trying to reach a parent directory using '..'. All file paths in Composr should be absolute
 *
 * @param  string $in String to test
 * @param  boolean $preg Whether to just filter out the naughtyness
 * @return string Same as input string
 */
function filter_naughty($in, $preg = false)
{
    if (strpos($in, "\0") !== false) {
        log_hack_attack_and_exit('PATH_HACK');
    }

    if (strpos($in, '..') !== false) {
        if ($preg) {
            return str_replace('.', '', $in);
        }

        $in = str_replace('...', '', $in);
        if (strpos($in, '..') !== false) {
            log_hack_attack_and_exit('PATH_HACK');
        }
        warn_exit(do_lang_tempcode('INVALID_URL'));
    }
    return $in;
}

/**
 * This function is similar to filter_naughty, except it requires the parameter to be strictly alphanumeric. It is intended for use on text that will be put into an eval.
 *
 * @param  string $in String to test
 * @param  boolean $preg Whether to just filter out the naughtyness
 * @return string Same as input string
 */
function filter_naughty_harsh($in, $preg = false)
{
    if (preg_match('#^[\w\-]*$#', $in) != 0) {
        return $in;
    }
    if (preg_match('#^[\w\-]*/#', $in) != 0) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE')); // Probably a relative URL underneath an SEO URL, should not really happen
    }

    if ($preg) {
        return preg_replace('#[^\w\-]#', '', $in);
    }
    log_hack_attack_and_exit('EVAL_HACK', $in);
    return ''; // trick to make Zend happy
}

/**
 * Include some PHP code, compiling to HHVM's hack, for type strictness (uses Composr phpdoc comments).
 *
 * @param  PATH $path Include path
 * @return ?mixed Code return code (null: actual NULL)
 */
function hhvm_include($path)
{
    return include($path); // Disable this line to enable the fancy Hack support. We don't maintain this 100%, but it is a great performance option.

    /*//if (!is_file($path.'.hh'))  // Leave this commented when debugging
    {
        if ($path==get_file_base().'/sources/php.php') return include($path);
        if ($path==get_file_base().'/sources/type_sanitisation.php') return include($path);
        if (strpos($path,'_custom')!==false) return include($path);

        require_code('php');
        $path=substr($path,strlen(get_file_base())+1);
        $new_code=convert_from_php_to_hhvm_hack($path);
        file_put_contents($path.'.hh',$new_code);
    }
    return include($path.'.hh');*/
}

// Useful for basic profiling
global $PAGE_START_TIME;
$PAGE_START_TIME = microtime(true);

// Unregister globals (sanitisation)
if (str_replace(array('on', 'true', 'yes'), array('1', '1', '1'), strtolower(ini_get('register_globals'))) == '1') {
    foreach (array('_GET', '_POST', '_COOKIE', '_ENV', '_SERVER', '_SESSION') as $superglobal) {
        if ((isset($GLOBALS[$superglobal])) && (is_array($GLOBALS[$superglobal]))) {
            foreach ($GLOBALS[$superglobal] as $key => $_) {
                if ((array_key_exists($key, $GLOBALS)) && ($GLOBALS[$key] == $GLOBALS[$superglobal][$key])) {
                    $GLOBALS[$key] = null;
                }
            }
        }
    }
}

// Are we in a special version of PHP?
define('HHVM', strpos(PHP_VERSION, 'hiphop') !== false);
define('GOOGLE_APPENGINE', isset($_SERVER['APPLICATION_ID']));

// Sanitise the PHP environment some more
safe_ini_set('track_errors', '1'); // so $php_errormsg is available
if (!GOOGLE_APPENGINE) {
    safe_ini_set('include_path', '');
    safe_ini_set('allow_url_fopen', '0');
}
safe_ini_set('suhosin.executor.disable_emodifier', '1'); // Extra security if suhosin is available
safe_ini_set('suhosin.executor.multiheader', '1'); // Extra security if suhosin is available
safe_ini_set('suhosin.executor.disable_eval', '0');
safe_ini_set('suhosin.executor.eval.whitelist', '');
safe_ini_set('suhosin.executor.func.whitelist', '');
safe_ini_set('auto_detect_line_endings', '0');
safe_ini_set('default_socket_timeout', '60');
if (function_exists('set_magic_quotes_runtime')) {
    @set_magic_quotes_runtime(0); // @'d because it's deprecated and PHP 5.3 may give an error
}
safe_ini_set('html_errors', '1');
safe_ini_set('docref_root', 'http://www.php.net/manual/en/');
safe_ini_set('docref_ext', '.php');

// Get ready for some global variables
global $REQUIRED_CODE, $CURRENT_SHARE_USER, $PURE_POST, $NO_QUERY_LIMIT, $NO_QUERY_LIMIT, $IN_MINIKERNEL_VERSION;
/** Details of what code files have been loaded up.
 *
 * @global array $REQUIRED_CODE
 */
$REQUIRED_CODE = array();
/** If running on a shared-install, this is the identifying name of the site that is being called up.
 *
 * @global ?ID_TEXT $CURRENT_SHARE_USER
 */
if ((!isset($CURRENT_SHARE_USER)) || (isset($_SERVER['REQUEST_METHOD']))) {
    $CURRENT_SHARE_USER = null;
}
/** A copy of the POST parameters, as passed initially to PHP (needed for hash checks with some IPN systems).
 *
 * @global array $PURE_POST
 */
$PURE_POST = $_POST;
$NO_QUERY_LIMIT = false;
$IN_MINIKERNEL_VERSION = false;

// Critical error reporting system
global $FILE_BASE;
if (is_file($FILE_BASE . '/sources_custom/critical_errors.php')) {
    require($FILE_BASE . '/sources_custom/critical_errors.php');
} else {
    $php_errormsg = '';
    @include($FILE_BASE . '/sources/critical_errors.php');
    if ($php_errormsg != '') {
        exit('<!DOCTYPE html>' . "\n" . '<html lang="EN"><head><title>Critical startup error</title></head><body><h1>Composr startup error</h1><p>The third most basic Composr startup file, sources/critical_errors.php, could not be located. This is almost always due to an incomplete upload of the Composr system, so please check all files are uploaded correctly.</p><p>Once all Composr files are in place, Composr must actually be installed by running the installer. You must be seeing this message either because your system has become corrupt since installation, or because you have uploaded some but not all files from our manual installer package: the quick installer is easier, so you might consider using that instead.</p><p>ocProducts maintains full documentation for all procedures and tools, especially those for installation. These may be found on the <a href="http://compo.sr">Composr website</a>. If you are unable to easily solve this problem, we may be contacted from our website and can help resolve it for you.</p><hr /><p style="font-size: 0.8em">Composr is a website engine created by ocProducts.</p></body></html>');
    }
}

// Load up config file
global $SITE_INFO;
/** Site base configuration settings.
 *
 * @global array $SITE_INFO
 */
$SITE_INFO = array();
@include($FILE_BASE . '/_config.php');
if (count($SITE_INFO) == 0) {
    if ((!is_file($FILE_BASE . '/_config.php')) || (filesize($FILE_BASE . '/_config.php') == 0)) {
        critical_error('INFO.PHP');
    }
    critical_error('INFO.PHP_CORRUPTED');
}

get_custom_file_base(); // Make sure $CURRENT_SHARE_USER is set if it is a shared site, so we can use CURRENT_SHARE_USER as an indicator of it being one.

// Pass on to next bootstrap level
if (GOOGLE_APPENGINE) {
    require_code('google_appengine');
}
require_code('global2');
