<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_upgrader
 */

/* Standalone script to extract a TAR file */

// Fixup SCRIPT_FILENAME potentially being missing
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

// Find Composr base directory, and chdir into it
global $FILE_BASE, $RELATIVE_PATH;
$FILE_BASE = (strpos(__FILE__, './') === false) ? __FILE__ : realpath(__FILE__);
$FILE_BASE = dirname($FILE_BASE);
if (!is_file($FILE_BASE . '/sources/global.php')) {
    $RELATIVE_PATH = basename($FILE_BASE);
    $FILE_BASE = dirname($FILE_BASE);
} else {
    $RELATIVE_PATH = '';
}
if (!is_file($FILE_BASE . '/sources/global.php')) {
    $FILE_BASE = $_SERVER['SCRIPT_FILENAME']; // this is with symlinks-unresolved (__FILE__ has them resolved); we need as we may want to allow zones to be symlinked into the base directory without getting path-resolved
    $FILE_BASE = dirname($FILE_BASE);
    if (!is_file($FILE_BASE . '/sources/global.php')) {
        $RELATIVE_PATH = basename($FILE_BASE);
        $FILE_BASE = dirname($FILE_BASE);
    } else {
        $RELATIVE_PATH = '';
    }
}
@chdir($FILE_BASE);

if (str_replace(array('on', 'true', 'yes'), array('1', '1', '1'), strtolower(ini_get('register_globals'))) == '1') {
    foreach ($_GET as $key => $_) {
        if ((array_key_exists($key, $GLOBALS)) && ($GLOBALS[$key] == $_GET[$key])) {
            $GLOBALS[$key] = null;
        }
    }
    foreach ($_POST as $key => $_) {
        if ((array_key_exists($key, $GLOBALS)) && ($GLOBALS[$key] == $_POST[$key])) {
            $GLOBALS[$key] = null;
        }
    }
    foreach ($_COOKIE as $key => $_) {
        if ((array_key_exists($key, $GLOBALS)) && ($GLOBALS[$key] == $_COOKIE[$key])) {
            $GLOBALS[$key] = null;
        }
    }
    foreach ($_ENV as $key => $_) {
        if ((array_key_exists($key, $GLOBALS)) && ($GLOBALS[$key] == $_ENV[$key])) {
            $GLOBALS[$key] = null;
        }
    }
    foreach ($_SERVER as $key => $_) {
        if ((array_key_exists($key, $GLOBALS)) && ($GLOBALS[$key] == $_SERVER[$key])) {
            $GLOBALS[$key] = null;
        }
    }
    if ((isset($_SESSION)) && (is_array($_SESSION))) {
        foreach ($_SESSION as $key => $_) {
            if ((array_key_exists($key, $GLOBALS)) && ($GLOBALS[$key] == $_SESSION[$key])) {
                $GLOBALS[$key] = null;
            }
        }
    }
}

$hashed_password = $_GET['hashed_password'];
global $SITE_INFO;
require_once(is_file($FILE_BASE . '/_config.php') ? $FILE_BASE . '/_config.php' : $FILE_BASE . '/info.php'); // LEGACY
if (!upgrader2_check_master_password($hashed_password)) {
    exit('Access Denied');
}

// Open TAR file
$tmp_path = $_GET['tmp_path'];
if (!file_exists($tmp_path)) {
    header('Content-type: text/plain');
    exit('Temp file has disappeared (' . $tmp_path . ')');
}
$tmp_path = dirname(dirname(__FILE__)) . '/data_custom/upgrader.cms.tmp'; // Actually for security, we will not allow it to be configurable (in case someone managed to steal the hash we can't let them extract arbitrary archives)
if (!is_file($tmp_path)) {
    $tmp_path = dirname(dirname(__FILE__)) . '/data_custom/upgrader.tar.tmp';  // LEGACY. Some old ocPortal upgraders versions overwrite upgrader2.php early, so Composr needs to support the ocPortal temporary name.
}
if (!is_file($tmp_path)) {
    exit('Could not find data_custom/upgrader.cms.tmp');
}
$myfile = fopen($tmp_path, 'rb');
flock($myfile, LOCK_SH);

$file_offset = intval($_GET['file_offset']);

$tmp_data_path = $_GET['tmp_data_path'];
if (!file_exists($tmp_data_path)) {
    header('Content-type: text/plain');
    exit('2nd temp file has disappeared (' . $tmp_data_path . ')');
}
$data = unserialize(file_get_contents($tmp_data_path));
asort($data);

// Work out what we're doing
$todo = $data['todo'];

$per_cycle = 100;

// Do the extraction
foreach ($todo as $i => $_target_file) {
    list($target_file, , $offset, $length,) = $_target_file;

    if ($target_file == 'data/upgrader2.php') {
        if ($file_offset + $per_cycle < count($todo)) {
            continue; // Only extract on last step, to avoid possible transitionary bugs between versions of this file (this is the file running and refreshing now, i.e this file!)
        }
    } else {
        if ($i < $file_offset) {
            continue;
        }
        if ($i > $file_offset + $per_cycle) {
            break;
        }
    }

    // Make any needed directories
    @mkdir($FILE_BASE . '/' . dirname($target_file), 0777, true);

    // Copy in the data
    fseek($myfile, $offset);
    $myfile2 = @fopen($FILE_BASE . '/' . $target_file, 'wb');
    if ($myfile2 === false) {
        header('Content-type: text/plain');
        exit('Filesystem permission error when trying to extract ' . $target_file . '. Maybe you needed to give FTP details when logging in?');
    }
    flock($myfile2, LOCK_EX);
    while ($length > 0) {
        $amount_to_read = min(1024, $length);
        $data_read = fread($myfile, $amount_to_read);
        fwrite($myfile2, $data_read);
        $length -= $amount_to_read;
    }
    flock($myfile2, LOCK_UN);
    fclose($myfile2);
    @chmod($FILE_BASE . '/' . $target_file, 0644);
}
flock($myfile, LOCK_UN);
fclose($myfile);

// Show HTML
$next_offset_url = '';
if ($file_offset + $per_cycle < count($todo)) {
    $next_offset_url = 'upgrader2.php?';
    foreach ($_GET as $key => $val) {
        if (get_magic_quotes_gpc()) {
            $val = stripslashes($val);
        }

        if ($key != 'file_offset') {
            $next_offset_url .= urlencode($key) . '=' . urlencode($val) . '&';
        }
    }
    $next_offset_url .= 'file_offset=' . urlencode(strval($file_offset + $per_cycle));
    $next_offset_url .= '#progress';
}
up2_do_header($next_offset_url);
echo '<ol>';
foreach ($todo as $i => $target_file) {
    echo '<li>';
    echo '<input id="file_' . strval($i) . '" name="file_' . strval($i) . '" type="checkbox" value="1" disabled="disabled"' . (($i < $file_offset + $per_cycle) ? ' checked="checked"' : '') . ' /> <label for="file_' . strval($i) . '">' . htmlentities($target_file[0]) . '</label>';
    if ($i == $file_offset) {
        echo '<a id="progress"></a>';
    }
    echo '</li>';
}
echo '</ol>';
if ($next_offset_url == '') {
    echo '<p><strong>' . htmlentities($_GET['done']) . '!</strong></p>';
    unlink($tmp_path);
    unlink($tmp_data_path);
} else {
    echo '<p><img alt="" src="../themes/default/images/loading.gif" /></p>';
}
echo '<script>// <![CDATA[
    window.setTimeout(function() {
        window.scrollTo(0,document.getElementById("file_' . strval(min(count($todo) - 1, $file_offset + $per_cycle)) . '").offsetTop-50);
    },200);
//]]></script>';
if ($next_offset_url != '') {
    echo '<hr /><p>Continuing in 3 seconds. If you have meta-refresh disabled, <a href="' . htmlentities($next_offset_url) . '">force continue</a>.</p>';
}
up2_do_footer();

/**
 * Output the upgrader page header.
 *
 * @param URLPATH $refresh_url URL to go to next (blank: done)
 */
function up2_do_header($refresh_url = '')
{
    $_refresh_url = htmlentities($refresh_url);
    echo <<<END
<!DOCTYPE html>
    <html lang="EN">
    <head>
        <title>Extracting files</title>
        <link rel="icon" href="http://compo.sr/favicon.ico" type="image/x-icon" />
END;
    if ($refresh_url != '') {
        echo <<<END
        <meta http-equiv="refresh" content="3;url={$_refresh_url}" />
END;
    }
    echo <<<END
        <style>/*<![CDATA[*/
END;
    global $FILE_BASE;
    @print(preg_replace('#/\*\s*\*/\s*#', '', str_replace('url(\'\')', 'none', str_replace('url("")', 'none', preg_replace('#\{\$[^\}]*\}#', '', preg_replace('#\{\$\?,\{\$MOBILE\},([^,]+),([^,]+)\}#', '$2', file_get_contents($GLOBALS['FILE_BASE'] . '/themes/default/css/global.css')))))));
    echo <<<END
            .screen_title { text-decoration: underline; display: block; background: url('../themes/default/images/icons/48x48/menu/_generic_admin/tool.png') top left no-repeat; min-height: 42px; padding: 10px 0 0 60px; }
            .button_screen { padding: 0.5em 0.3em !important; }
            a[target="_blank"], a[onclick$="window.open"] { padding-right: 0; }
        /*]]>*/</style>

        <meta name="robots" content="noindex, nofollow" />
    </head>
    <body class="website_body"><div class="global_middle">
END;
}

/**
 * Output the upgrader page footer.
 */
function up2_do_footer()
{
    echo <<<END
    </div></body>
</html>
END;
}

/**
 * Check the given master password is valid.
 *
 * @param  SHORT_TEXT $password_given_hashed Given master password
 * @return boolean Whether it is valid
 */
function upgrader2_check_master_password($password_given_hashed)
{
    global $FILE_BASE;
    require_once($FILE_BASE . '/sources/crypt_master.php');
    return check_master_password_from_hash($password_given_hashed);
}
