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
 * @package    users_online_block
 */

/**
 * Block class.
 */
class Block_side_users_online
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
        $info['version'] = 3;
        $info['locked'] = false;
        $info['parameters'] = array();
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
        $info['cache_on'] = 'array()';
        $info['special_cache_flags'] = CACHE_AGAINST_DEFAULT | CACHE_AGAINST_MEMBER; // Showing friends birthdays, possibly
        $info['ttl'] = 3;
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
        $count = 0;
        require_code('users2');
        $members = get_users_online(false, null, $count);
        if ($members === null) {
            return new Tempcode(); // Too many to show
        }

        if (get_forum_type() == 'cns') {
            require_code('cns_general');
            require_code('cns_members');
            require_css('cns');
        }

        $block_id = get_block_id($map);

        $online = array();
        $guests = 0;
        $_members = 0;
        $done_members = array();
        $done_ips = array();
        foreach ($members as $_member) {
            $member_id = $_member['member_id'];
            $username = $_member['cache_username'];
            $ip = $_member['ip'];

            if ((is_guest($member_id)) || ($username === null)) {
                if (!array_key_exists($ip, $done_ips)) {
                    $done_ips[$ip] = true;
                    $guests++;
                }
            } else {
                if (!array_key_exists($member_id, $done_members)) {
                    $colour = (get_forum_type() == 'cns') ? get_group_colour(cns_get_member_primary_group($member_id)) : null;
                    $done_members[$member_id] = true;
                    $url = $GLOBALS['FORUM_DRIVER']->member_profile_url($member_id, true);
                    $online[] = array(
                        'URL' => $url,
                        'USERNAME' => $username,
                        'COLOUR' => $colour,
                        'MEMBER_ID' => strval($member_id),
                        'AVATAR_URL' => $GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id),
                    );
                    $_members++;
                }
            }
        }

        $newest = new Tempcode();
        $birthdays = array();
        if (get_forum_type() == 'cns') {
            require_lang('cns');

            // Show newest member
            if (get_option('usersonline_show_newest_member') == '1') {
                $newest_member = $GLOBALS['FORUM_DB']->query_select('f_members', array('m_username', 'id'), array('m_validated' => 1), 'ORDER BY id DESC', 1);
                $username_link = $GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($newest_member[0]['id'], $newest_member[0]['m_username']);
                $newest->attach(paragraph(do_lang_tempcode('NEWEST_MEMBER_WELCOME', $username_link), 'gdgdfhrug'));
            }

            // Birthdays
            if (get_option('usersonline_show_birthdays') == '1') {
                require_code('cns_members');
                $_birthdays = cns_find_birthdays();
                foreach ($_birthdays as $_birthday) {
                    $birthday_url = build_url(array('page' => 'topics', 'type' => 'birthday', 'id' => $_birthday['username']), get_module_zone('topics'));
                    $birthdays[] = array(
                        'AGE' => array_key_exists('age', $_birthday) ? integer_format($_birthday['age']) : null,
                        'PROFILE_URL' => $GLOBALS['CNS_DRIVER']->member_profile_url($_birthday['id'], true),
                        'USERNAME' => $_birthday['username'],
                        'MEMBER_ID' => strval($_birthday['id']),
                        'BIRTHDAY_URL' => $birthday_url,
                    );
                }
            }
        }

        return do_template('BLOCK_SIDE_USERS_ONLINE', array(
            '_GUID' => 'fdfa68dff479b4ea7d517585297ea6af',
            'BLOCK_ID' => $block_id,
            'ONLINE' => $online,
            'GUESTS' => integer_format($guests),
            'MEMBERS' => integer_format($_members),
            '_GUESTS' => strval($guests),
            '_MEMBERS' => strval($_members),
            'BIRTHDAYS' => $birthdays,
            'NEWEST' => $newest,
        ));
    }
}
