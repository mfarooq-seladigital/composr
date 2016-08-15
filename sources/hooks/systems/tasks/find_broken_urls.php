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
 * @package    core_cleanup_tools
 */

/**
 * Hook class.
 */
class Hook_task_find_broken_urls
{
    /**
     * Run the task hook.
     *
     * @return ?array A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (null: show standard success message)
     */
    public function run()
    {
        require_lang('cleanup');

        $found = array();
        $found_404 = array();

        $checked_already = array();

        $dbs_bak = $GLOBALS['NO_DB_SCOPE_CHECK'];
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        $this->check_url_fields($checked_already, $found_404, $found);
        $this->check_comcode_fields($checked_already, $found_404, $found);
        $this->check_catalogues($checked_already, $found_404, $found);
        $this->check_comcode_pages($checked_already, $found_404, $found);

        $GLOBALS['NO_DB_SCOPE_CHECK'] = $dbs_bak;

        $ret = do_template('BROKEN_URLS', array(
            '_GUID' => '7b60d02e1b95f8d9053fb0a49f45d892',
            'FOUND' => $found,
            'FOUND_404' => $found_404,
        ));
        return array('text/html', $ret);
    }

    /**
     * Check URL fields for broken URLs.
     *
     * @param  array $checked_already Place to record what we've already checked
     * @param  array $found_404 Place to put 404 errors
     * @param  array $found Place to put file-not-found errors
     */
    private function check_url_fields(&$checked_already, &$found_404, &$found)
    {
        $skip_hooks = find_all_hooks('systems', 'non_active_urls');
        $sql = 'SELECT m_name,m_table FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'db_meta WHERE m_type LIKE \'' . db_encode_like('%URLPATH%') . '\'';
        $urlpaths = $GLOBALS['SITE_DB']->query($sql);
        foreach ($urlpaths as $urlpath) {
            if ($urlpath['m_table'] == 'hackattack') {
                continue;
            }
            if ($urlpath['m_table'] == 'url_title_cache') {
                continue;
            }
            if ($urlpath['m_table'] == 'theme_images') {
                continue;
            }
            if (array_key_exists($urlpath['m_table'], $skip_hooks)) {
                continue;
            }

            $ofs = $GLOBALS['SITE_DB']->query_select($urlpath['m_table'], array('*'));
            foreach ($ofs as $of) {
                $url = $of[$urlpath['m_name']];

                $this->check_url(
                    $url,
                    $urlpath['m_table'],
                    $urlpath['m_name'],
                    array_key_exists('id', $of) ? strval($of['id']) : (array_key_exists('name', $of) ? $of['name'] : do_lang('UNKNOWN')),
                    $checked_already,
                    $found_404,
                    $found
                );
            }
        }
    }

    /**
     * Check Comcode fields for broken URLs.
     *
     * @param  array $checked_already Place to record what we've already checked
     * @param  array $found_404 Place to put 404 errors
     * @param  array $found Place to put file-not-found errors
     */
    private function check_comcode_fields(&$checked_already, &$found_404, &$found)
    {
        global $COMCODE_BROKEN_URLS;

        global $LAX_COMCODE;
        $temp = $LAX_COMCODE;
        $LAX_COMCODE = true;

        $sql = 'SELECT m_name,m_table FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'db_meta WHERE m_type LIKE \'' . db_encode_like('%LONG\_TRANS\_\_COMCODE%') . '\'';
        $possible_comcode_fields = $GLOBALS['SITE_DB']->query($sql);
        foreach ($possible_comcode_fields as $field) {
            if ($field['m_table'] == 'seo_meta') {
                continue;
            }
            if ($field['m_table'] == 'cached_comcode_pages') {
                continue;
            }

            $ofs = $GLOBALS['SITE_DB']->query_select($field['m_table'], array('*'));
            foreach ($ofs as $of) {
                $COMCODE_BROKEN_URLS = array();

                get_translated_tempcode($field['m_table'], $of, $field['m_name']);

                if ((array_key_exists('COMCODE_BROKEN_URLS', $GLOBALS)) && (!is_null($COMCODE_BROKEN_URLS))) {
                    foreach ($COMCODE_BROKEN_URLS as $i => $_url) {
                        list($url, $spot) = $_url;
                        if (is_null($spot)) {
                            if (multi_lang_content()) {
                                $_url[$i][1] = 'translate#' . strval($i) . ' (text_original)';
                            } else {
                                $_url[$i][1] = $field['m_table'] . '#' . strval($i) . ' (' . $field['m_name'] . ')';
                            }
                        }
                    }
                }
            }
        }

        $LAX_COMCODE = $temp;
    }

    /**
     * Check catalogues for broken URLs.
     *
     * @param  array $checked_already Place to record what we've already checked
     * @param  array $found_404 Place to put 404 errors
     * @param  array $found Place to put file-not-found errors
     */
    private function check_catalogues(&$checked_already, &$found_404, &$found)
    {
        if (addon_installed('catalogues')) {
            $catalogue_fields = $GLOBALS['SITE_DB']->query_select('catalogue_fields', array('id'), array('cf_type' => 'url'));
            $or_list = '';
            foreach ($catalogue_fields as $field) {
                if ($or_list != '') {
                    $or_list .= ' OR ';
                }
                $or_list .= 'cf_id=' . strval($field['id']);
            }
            if ($or_list != '') {
                $sql = 'SELECT id,cv_value,ce_id FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'catalogue_efv_short WHERE ' . $or_list;
                $values = $GLOBALS['SITE_DB']->query($sql, null, null, false, true);
                foreach ($values as $value) {
                    $url = $value['cv_value'];

                    $this->check_url(
                        $url,
                        'catalogue_efv_short',
                        'cv_value',
                        strval($value['ce_id']),
                        $checked_already,
                        $found_404,
                        $found
                    );
                }
            }
        }
    }

    /**
     * Check Comcode pages for broken URLs.
     *
     * @param  array $checked_already Place to record what we've already checked
     * @param  array $found_404 Place to put 404 errors
     * @param  array $found Place to put file-not-found errors
     */
    private function check_comcode_pages(&$checked_already, &$found_404, &$found)
    {
        global $COMCODE_BROKEN_URLS;

        global $LAX_COMCODE;
        $temp = $LAX_COMCODE;
        $LAX_COMCODE = true;

        $zones = find_all_zones();
        foreach ($zones as $zone) {
            $pages = find_all_pages($zone, 'comcode_custom/' . get_site_default_lang(), 'txt', true) + find_all_pages($zone, 'comcode/' . get_site_default_lang(), 'txt', true);
            foreach ($pages as $page => $type) {
                $COMCODE_BROKEN_URLS = array();

                $file_path = zone_black_magic_filterer(((strpos($type, '_custom') !== false) ? get_custom_file_base() : get_file_base()) . '/' . $zone . '/pages/' . $type . '/' . $page);
                $comcode = file_get_contents($file_path);
                comcode_to_tempcode($comcode, null, true);

                if ((array_key_exists('COMCODE_BROKEN_URLS', $GLOBALS)) && (!is_null($COMCODE_BROKEN_URLS))) {
                    foreach ($COMCODE_BROKEN_URLS as $i => $_url) {
                        list($url, $spot) = $_url;
                        if (is_null($spot)) {
                            $_url[$i][1] = $zone . ':' . $page;
                        }
                    }
                }
            }
        }

        if ((array_key_exists('COMCODE_BROKEN_URLS', $GLOBALS)) && (!is_null($COMCODE_BROKEN_URLS))) {
            foreach ($COMCODE_BROKEN_URLS as $_url) {
                list($url, $spot) = $_url;

                if (!array_key_exists($url, $checked_already)) {
                    $found_404[] = array('URL' => $url, 'SPOT' => $spot);
                }
            }
        }

        $LAX_COMCODE = $temp;
    }

    /**
     * Check to see if a URL is broken.
     *
     * @param  URLPATH $url URL to check
     * @param  ID_TEXT $table Table name
     * @param  ID_TEXT $field Field name
     * @param  ID_TEXT $id ID
     * @param  array $checked_already Place to record what we've already checked
     * @param  array $found_404 Place to put 404 errors
     * @param  array $found Place to put file-not-found errors
     * @param  string $spot A textual identifier to where the content can be seen
     */
    private function check_url($url, $table, $field, $id, &$checked_already, &$found_404, &$found, $spot = '')
    {
        if (trim($url) == '') {
            return;
        }
        if (isset($checked_already[$url])) {
            return;
        }

        if ($spot == '') {
            $spot = $table . '#' . $id . ' (' . $field . ')';
        }

        if (((substr($url, 0, 8) == 'uploads/') || (substr($url, 0, 7) == 'themes/')) && (strpos($url, '?') === false)) {
            if ((!file_exists(rawurldecode($url))) && ($field != 'm_avatar_url')) {
                $found[] = array('URL' => $url, 'TABLE' => $table, 'FIELD' => $field, 'ID' => $id);
            }

        } elseif ($url != '') {
            if (url_is_local($url)) {
                if (($url[0] == '/') && (strpos(get_base_url(), '/') !== false)) {
                    $url = substr(get_base_url(), 0, strpos(get_base_url(), '/')) . '/' . $url;
                } else {
                    $url = get_base_url() . '/' . $url;
                }
            }

            $test = http_download_file($url, 0, false);
            if ((is_null($test)) && (in_array($GLOBALS['HTTP_MESSAGE'], array('404', 'could not connect to host')))) {
                $found_404[] = array('URL' => $url, 'SPOT' => $spot);
            }
        }

        $checked_already[$url] = true;
    }
}
