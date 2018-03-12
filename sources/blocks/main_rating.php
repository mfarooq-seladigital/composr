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
 * @package    core_feedback_features
 */

/**
 * Block class.
 */
class Block_main_rating
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled)
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
        $info['parameters'] = array('param', 'page', 'extra_param_from', 'title');
        return $info;
    }

    /**
     * Find caching details for the block.
     *
     * @return ?array Map of cache details (cache_on and ttl) (null: block is disabled)
     */
    /*
    public function caching_environment() // We can't cache this block, because it needs to execute in order to allow commenting
    {
        $info['cache_on'] = 'array(has_privilege(get_member(),\'rate\'),array_key_exists(\'extra_param_from\',$map)?$map[\'extra_param_from\']:\'\',array_key_exists(\'param\',$map)?$map[\'param\']:\'main\',array_key_exists(\'page\',$map)?$map[\'page\']:get_page_name(),array_key_exists(\'title\',$map)?$map[\'title\']:\'\')';
        $info['ttl'] = 60 * 5;
        return $info;
    }*/

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters
     * @return Tempcode The result of execution
     */
    public function run($map)
    {
        if (!array_key_exists('param', $map)) {
            $map['param'] = 'main';
        }
        if (!array_key_exists('page', $map)) {
            $map['page'] = get_page_name();
        }

        if (array_key_exists('extra_param_from', $map)) {
            $extra = '_' . $map['extra_param_from'];
        } else {
            $extra = '';
        }

        require_code('feedback');

        $self_url = get_self_url();
        $self_title = empty($map['title']) ? $map['page'] : $map['title'];
        $id = $map['page'] . '_' . $map['param'] . $extra;
        $test_changed = post_param_string('rating_' . $id, '');
        if ($test_changed != '') {
            delete_cache_entry('main_rating');
        }
        actualise_rating(true, 'block_main_rating', $id, $self_url, $self_title);

        return get_rating_box($self_url, $self_title, 'block_main_rating', $id, true);
    }
}
