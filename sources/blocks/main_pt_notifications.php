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
 * @package    cns_forum
 */

/**
 * Block class.
 */
class Block_main_pt_notifications
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
        $info['parameters'] = array();
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
        if (get_forum_type() != 'cns') {
            return new Tempcode();
        }

        require_code('cns_general');
        require_css('cns');
        require_lang('cns');

        $block_id = get_block_id($map);

        if (!is_guest()) {
            require_code('cns_notifications');
            list($notifications, $num_unread_pps) = generate_notifications(get_member());
        } else {
            $notifications = new Tempcode();
            $num_unread_pps = 0;
        }

        return do_template('BLOCK_MAIN_PT_NOTIFICATIONS', array(
            '_GUID' => '7606c3bf73f059ec5b194bc33d881763',
            'BLOCK_ID' => $block_id,
            'NOTIFICATIONS' => $notifications,
        ));
    }
}
