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
 * @package    banners
 */

/**
 * Block class.
 */
class Block_main_banner_wave
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled).
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
        $info['parameters'] = array('param', 'max');
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
        $info['cache_on'] = 'array(array_key_exists(\'param\',$map)?$map[\'param\']:\'\',array_key_exists(\'max\',$map)?intval($map[\'max\']):100)';
        $info['ttl'] = (get_value('no_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 5; // due to shuffle, can't cache long
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters.
     * @return Tempcode The result of execution.
     */
    public function run($map)
    {
        require_css('banners');

        if (!array_key_exists('param', $map)) {
            $map['param'] = '';
        }
        $max = array_key_exists('max', $map) ? intval($map['max']) : 100;

        require_code('banners');

        $b_type = $map['param'];
        $myquery = 'SELECT * FROM ' . get_table_prefix() . 'banners WHERE ((((the_type<>1) OR ((campaign_remaining>0) AND ((expiry_date IS NULL) or (expiry_date>' . strval(time()) . ')))) AND ' . db_string_not_equal_to('name', '') . ')) AND validated=1 AND ' . db_string_equal_to('b_type', $b_type) . ' ORDER BY name';
        $banners = $GLOBALS['SITE_DB']->query($myquery, 200/*just in case of insane amounts of data*/);
        $assemble = new Tempcode();

        if (count($banners) > $max) {
            shuffle($banners);
            $banners = array_slice($banners, 0, $max);
        }

        foreach ($banners as $i => $banner) {
            $bd = show_banner($banner['name'], $banner['b_title_text'], get_translated_tempcode('banners', $banner, 'caption'), $banner['b_direct_code'], $banner['img_url'], '', $banner['site_url'], $banner['b_type'], $banner['submitter']);
            $assemble->attach(do_template('BLOCK_MAIN_BANNER_WAVE_BWRAP', array('_GUID' => 'bbb0851f015305da014f0a55006770f5', 'TYPE' => $map['param'], 'BANNER' => $bd)));
        }

        return do_template('BLOCK_MAIN_BANNER_WAVE', array('_GUID' => '8bced3f44675de9ef0bd5f4d286aea76', 'TYPE' => $map['param'], 'ASSEMBLE' => $assemble));
    }
}
