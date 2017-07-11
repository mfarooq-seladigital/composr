<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    testing_platform
 */

/**
 * Composr test case class (unit testing).
 */
class cloudflare_ip_range_sync_test_set extends cms_test_case
{
    public function testInSync()
    {
        $current = trim(http_get_contents('https://www.cloudflare.com/ips-v4')) . "\n" . trim(http_get_contents('https://www.cloudflare.com/ips-v6'));

        $c = file_get_contents(get_file_base() . '/sources/global.php');
        $matches = array();
        preg_match('#\$ip_cidr_trusts = \'([^\']*)\';#', $c, $matches);
        $in_code = str_replace(',', "\n", $matches[1]);

        $this->assertTrue($in_code == $current);
    }
}
