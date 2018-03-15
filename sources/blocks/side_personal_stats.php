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
 * @package    core
 */

/**
 * Block class.
 */
class Block_side_personal_stats
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
        $info['parameters'] = array();
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters
     * @return Tempcode The result of execution
     */
    public function run($map)
    {
        require_css('personal_stats');

        $block_id = get_block_id($map);

        $member_id = get_member();

        if (get_forum_type() == 'none') {
            return paragraph(do_lang_tempcode('NO_FORUM_INSTALLED'), 'kc6kp12z4myd48e2cf9p1nzco1ynartu', 'red-alert');
        }

        if (!is_guest($member_id)) {
            $avatar_url = '';
            if (!has_no_forum()) {
                if (get_option('show_avatar') === '1') {
                    $avatar_url = $GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id);
                }
            }

            $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id);

            require_code('global4');
            list($links, $links_ecommerce, $details, $num_unread_pps) = member_personal_links_and_details($member_id);

            return do_template('BLOCK_SIDE_PERSONAL_STATS', array(
                '_GUID' => '99f9bc3387102daaeeedf99843b0502e',
                'BLOCK_ID' => $block_id,
                'NUM_UNREAD_PTS' => strval($num_unread_pps),
                'AVATAR_URL' => $avatar_url,
                'MEMBER_ID' => strval($member_id),
                'USERNAME' => $username,
                'LINKS' => $links,
                'LINKS_ECOMMERCE' => $links_ecommerce,
                'DETAILS' => $details,
            ));
        } else {
            $title = do_lang_tempcode('NOT_LOGGED_IN');

            list($full_url, $login_url, $join_url) = get_login_url();
            return do_template('BLOCK_SIDE_PERSONAL_STATS_NO', array(
                '_GUID' => '32aade68b98dfd191f0f84c6648f7dde',
                'BLOCK_ID' => $block_id,
                'TITLE' => $title,
                'FULL_LOGIN_URL' => $full_url,
                'JOIN_URL' => $join_url,
                'LOGIN_URL' => $login_url,
            ));
        }
    }
}
