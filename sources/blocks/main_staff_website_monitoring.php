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
 * @package    core_adminzone_dashboard
 */

/*NO_API_CHECK*/

/**
 * Block class.
 */
class Block_main_staff_website_monitoring
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Jack Franklin';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 3;
        $info['locked'] = false;
        $info['parameters'] = array();
        $info['update_require_upgrade'] = true;
        return $info;
    }

    /**
     * Find caching details for the block.
     *
     * @return ?array Map of cache details (cache_on and ttl) (null: block is disabled).
     */
    public function caching_environment()
    {
        $info = array();
        $info['cache_on'] = '(count($_POST)>0)?null:array()'; // No cache on POST as this is when we save text data
        $info['ttl'] = (get_value('no_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60 * 5;
        return $info;
    }

    /**
     * Uninstall the block.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('staff_website_monitoring');
    }

    /**
     * Install the block.
     *
     * @param  ?integer $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        if (($upgrade_from === null) || ($upgrade_from < 2)) {
            $GLOBALS['SITE_DB']->create_table('staff_website_monitoring', array(
                'id' => '*AUTO',
                'site_url' => 'URLPATH',
                'site_name' => 'SHORT_TEXT',
            ));

            $GLOBALS['SITE_DB']->query_insert('staff_website_monitoring', array(
                'site_url' => get_base_url(),
                'site_name' => get_site_name(),
            ));
        }
    }

    /**
     * Function to find Alexa details of the site.
     *
     * @param  string $url The URL of the site which you want to find out information on.)
     * @return array Returns a triple array with the rank, the amount of links, and the speed of the site.
     */
    public function getAlexaRank($url)
    {
        require_lang('staff_checklist');

        require_code('files');
        $p = array();
        $result = http_download_file('http://data.alexa.com/data?cli=10&dat=s&url=' . $url, null, false, false, 'Composr', null, null, null, null, null, null, null, null, 1.0);
        if (preg_match('#<POPULARITY [^<>]*TEXT="([0-9]+){1,}"#si', $result, $p) != 0) {
            $rank = integer_format(intval($p[1]));
        } else {
            $rank = do_lang('NA');
        }
        if (preg_match('#<LINKSIN [^<>]*NUM="([0-9]+){1,}"#si', $result, $p) != 0) {
            $links = integer_format(intval($p[1]));
        } else {
            $links = '0';
        }
        if (preg_match('#<SPEED [^<>]*PCT="([0-9]+){1,}"#si', $result, $p) != 0) {
            $speed = 'Top ' . integer_format(100 - intval($p[1])) . '%';
        } else {
            $speed = '?';
        }

        // we would like, but cannot get (without an API key)...
        /*
            time on site
            reach (as a percentage)
            page views
            audience (i.e. what country views the site most)
         */

        return array($rank, $links, $speed);
    }

    //convert a string to a 32-bit integer
    public function StrToNum($str, $check, $magic)
    {
        $int_32_unit = 4294967296.0;  // 2^32

        $length = strlen($str);
        for ($i = 0; $i < $length; $i++) {
            $check *= $magic;
            //If the float is beyond the boundaries of integer (usually +/- 2.15e+9=2^31),
            //  the result of converting to integer is undefined
            //  refer to http://php.net/manual/en/language.types.integer.php
            if ((is_integer($check) && floatval($check) >= $int_32_unit) ||
                (is_float($check) && $check >= $int_32_unit)
            ) {
                $check = ($check - $int_32_unit * intval($check / $int_32_unit));
                //if the check less than -2^31
                $check = ($check < -2147483648.0) ? ($check + $int_32_unit) : $check;
                if (is_float($check)) {
                    $check = intval($check);
                }
            }
            $check += ord($str[$i]);
        }
        return is_integer($check) ? $check : intval($check);
    }

    //genearate a hash for a url
    public function HashURL($string)
    {
        $check1 = $this->StrToNum($string, 0x1505, 0x21);
        $check2 = $this->StrToNum($string, 0, 0x1003F);

        $check1 = $check1 >> 2;
        $check1 = (($check1 >> 4) & 0x3FFFFC0) | ($check1 & 0x3F);
        $check1 = (($check1 >> 4) & 0x3FFC00) | ($check1 & 0x3FF);
        $check1 = (($check1 >> 4) & 0x3C000) | ($check1 & 0x3FFF);

        $t1 = (((($check1 & 0x3C0) << 4) | ($check1 & 0x3C)) << 2) | ($check2 & 0xF0F);
        $t2 = @(((($check1 & 0xFFFFC000) << 4) | ($check1 & 0x3C00)) << 0xA) | ($check2 & 0xF0F0000);

        return ($t1 | $t2);
    }

    //generate a checksum for the hash string
    public function CheckHash($hashnum)
    {
        $check_byte = 0;
        $flag = 0;

        $hashstr = sprintf('%u', $hashnum);
        $length = strlen($hashstr);

        for ($i = $length - 1; $i >= 0; $i--) {
            $re = intval($hashstr[$i]);
            if (1 === ($flag % 2)) {
                $re += $re;
                $re = intval($re / 10) + ($re % 10);
            }
            $check_byte += $re;
            $flag++;
        }

        $check_byte = $check_byte % 10;
        if (0 !== $check_byte) {
            $check_byte = 10 - $check_byte;
            if (1 === ($flag % 2)) {
                if (1 === ($check_byte % 2)) {
                    $check_byte += 9;
                }

                $check_byte = $check_byte >> 1;
            }
        }

        return '7' . strval($check_byte) . $hashstr;
    }

    //return the pagerank checksum hash
    public function getch($url)
    {
        return $this->CheckHash($this->HashURL($url));
    }

    //return the pagerank figure
    public function getpr($url)
    {
        $ch = $this->getch($url);
        $errno = '0';
        $errstr = '';
        $data = http_download_file('http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=' . $ch . '&features=Rank&q=info:' . $url, null, false, false, 'Composr', null, null, null, null, null, null, null, null, 1.0);
        if ($data === null) {
            return '';
        }
        $pos = strpos($data, "Rank_");
        if ($pos === false) {
        } else {
            $pr = substr($data, $pos + 9);
            $pr = trim($pr);
            $pr = str_replace("\n", '', $pr);
            return $pr;
        }
        return null;
    }

    //return the pagerank figure
    public function getPageRank($url)
    {
        if (preg_match('/^(https?:\/\/)?([^\/]+)/i', $url) == 0) {
            $url = 'http://' . $url;
        }
        $pr = $this->getpr($url);
        return $pr;
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters.
     * @return Tempcode The result of execution.
     */
    public function run($map)
    {
        $block_id = get_block_id($map);

        define('GOOGLE_MAGIC', 0xE6359A60);

        $links = post_param_string('website_monitoring_list_edit', null);
        if ($links !== null) {
            $GLOBALS['SITE_DB']->query_delete('staff_website_monitoring');
            $items = explode("\n", $links);
            foreach ($items as $i) {
                $q = trim($i);
                if (!empty($q)) {
                    $bits = explode('=', $q);
                    if (count($bits) >= 2) {
                        $last_bit = array_pop($bits);
                        $bits = array(implode('=', $bits), $last_bit);
                        $link = $bits[0];
                        $site_name = $bits[1];
                    } else {
                        $link = $q;

                        require_code('files2');
                        $meta_details = get_webpage_meta_details($link);
                        $site_name = $meta_details['t_title'];
                        if ($site_name == '') {
                            $site_name = $link;
                        }
                    }
                    $GLOBALS['SITE_DB']->query_insert('staff_website_monitoring', array('site_name' => $site_name, 'site_url' => fixup_protocolless_urls($link)));
                }
            }

            decache('main_staff_website_monitoring');

            log_it('SITE_WATCHLIST');
        }

        $rows = $GLOBALS['SITE_DB']->query_select('staff_website_monitoring');

        $sites_being_watched = array();
        $grid_data = array();
        if (count($rows) > 0) {
            foreach ($rows as $r) {
                $alex = $this->getAlexaRank(($r['site_url']));
                $sites_being_watched[$r['site_url']] = $r['site_name'];
                $google_ranking = integer_format(intval($this->getPageRank($r['site_url'])));
                $alexa_ranking = $alex[0];
                $alexa_traffic = $alex[1];

                $grid_data[] = array(
                    'URL' => $r['site_url'],
                    'GOOGLE_RANKING' => $google_ranking,
                    'ALEXA_RANKING' => $alexa_ranking,
                    'ALEXA_TRAFFIC' => $alexa_traffic,
                    'SITE_NAME' => $r['site_name'],
                );
            }
        }

        $map_comcode = get_block_ajax_submit_map($map);
        return do_template('BLOCK_MAIN_STAFF_WEBSITE_MONITORING', array(
            '_GUID' => '0abf65878c508bf133836589a8cc45da',
            'BLOCK_ID' => $block_id,
            'URL' => get_self_url(),
            'BLOCK_NAME' => 'main_staff_website_monitoring',
            'MAP' => $map_comcode,
            'SITES_BEING_WATCHED' => $sites_being_watched,
            'GRID_DATA' => $grid_data,
        ));
    }
}
