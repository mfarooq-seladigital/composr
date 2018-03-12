<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    meta_toolkit
 */

i_solemnly_declare(I_UNDERSTAND_SQL_INJECTION | I_UNDERSTAND_XSS | I_UNDERSTAND_PATH_INJECTION);

$error_msg = new Tempcode();
if (!addon_installed__messaged('meta_toolkit', $error_msg)) {
    return $error_msg;
}

if (post_param_integer('confirm', 0) == 0) {
    $preview = 'Create files dump (TAR file)';
    $title = get_screen_title($preview, false);
    $url = get_self_url(false, false);
    return do_template('CONFIRM_SCREEN', array('TITLE' => $title, 'PREVIEW' => $preview, 'FIELDS' => form_input_hidden('confirm', '1'), 'URL' => $url));
}

disable_php_memory_limit();
if (php_function_allowed('set_time_limit')) {
    @set_time_limit(0);
}
push_db_scope_check(false);

safe_ini_set('ocproducts.xss_detect', '0');

require_code('tar');

$filename = 'composr-' . get_site_name() . '.' . date('Y-m-d') . '.tar';

header('Content-Disposition: attachment; filename="' . escape_header($filename, true) . '"');

$tar = tar_open(null, 'wb');

$max_size = get_param_integer('max_size', null);
$subpath = get_param_string('path', '', INPUT_FILTER_GET_COMPLEX);
tar_add_folder($tar, null, get_file_base() . (($subpath == '') ? '' : '/') . $subpath, $max_size, $subpath, array(), null, false, null);

tar_close($tar);

$GLOBALS['SCREEN_TEMPLATE_CALLED'] = '';
exit();
