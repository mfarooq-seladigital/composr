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
 * @package    cns_forum
 */

/**
 * Block class.
 */
class Block_main_cns_involved_topics
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled)
     */
    public function info()
    {
        if (get_forum_type() != 'cns') {
            return null;
        }

        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array('member_id', 'max', 'start', 'check');
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
        $error_msg = new Tempcode();
        if (!addon_installed__autoinstall('cns_forum', $error_msg)) {
            return $error_msg;
        }

        if (get_forum_type() != 'cns') {
            return paragraph(do_lang_tempcode('NO_CNS'), '3wdm0cx063l7qs0mtk5qhq1kwczp8q5e', 'red-alert');
        }

        $block_id = get_block_id($map);

        $check_perms = array_key_exists('check', $map) ? ($map['check'] == '1') : true;

        $member_id_of = array_key_exists('member_id', $map) ? intval($map['member_id']) : get_member();
        $max = get_param_integer($block_id . '_max', array_key_exists('max', $map) ? intval($map['max']) : 10);
        $start = get_param_integer($block_id . '_start', array_key_exists('start', $map) ? intval($map['start']) : 0);

        require_code('cns_topics');
        require_code('cns_general');
        require_lang('cns');
        require_code('cns_forumview');

        $topics = new Tempcode();

        $forum1 = null;//$GLOBALS['FORUM_DRIVER']->forum_id_from_name(get_option('comments_forum_name'));
        $tf = get_option('ticket_forum_name', true);
        if ($tf !== null) {
            $forum2 = $GLOBALS['FORUM_DRIVER']->forum_id_from_name($tf);
        } else {
            $forum2 = null;
        }
        $where_more = '';
        /*
        Actually including this just slows down the COUNT part of the query due to lack of indexability
        if ($forum1 !== null) {
            $where_more .= ' AND p_cache_forum_id<>' . strval($forum1);
        }
        if ($forum2 !== null) {
            $where_more .= ' AND p_cache_forum_id<>' . strval($forum2);
        }
        */
        $rows = $GLOBALS['FORUM_DB']->query('SELECT DISTINCT p_topic_id,p_time FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE p_poster=' . strval($member_id_of) . $where_more . ' ORDER BY p_time DESC', $max, $start, false, true);
        if (count($rows) != 0) {
            if (get_bot_type() === null) {
                $max_rows = $GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(DISTINCT p_topic_id) FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE p_poster=' . strval($member_id_of) . $where_more, false, true);
            } else {
                $max_rows = count($rows); // We don't want bots hogging resources on somewhere they don't need to dig into
            }

            $moderator_actions = '';
            $has_topic_marking = has_delete_permission('mid', get_member(), $member_id_of, 'topics');
            if ($has_topic_marking) {
                $moderator_actions .= '<option value="delete_topics_and_posts">' . do_lang('DELETE_TOPICS_AND_POSTS') . '</option>';
            }

            $extra_join_sql = '';
            $where_sup = '';
            if ((!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) && ($check_perms)) {
                $extra_join_sql .= get_permission_join_clause('forum', 't_forum_id', 'a', 'ma', 't');
                $where_sup .= get_permission_where_clause(get_member(), get_permission_where_clause_groups(get_member()));
            }

            $where = '';
            foreach ($rows as $row) {
                if ($where != '') {
                    $where .= ' OR ';
                }
                $where .= 't.id=' . strval($row['p_topic_id']);
            }
            $query = 'SELECT t.*,l_time';
            if (multi_lang_content()) {
                $query .= ',t_cache_first_post AS p_post';
            } else {
                $query .= ',p_post,p_post__text_parsed,p_post__source_user';
            }
            $query .= ' FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics t LEFT JOIN ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_read_logs l ON t.id=l.l_topic_id AND l.l_member_id=' . strval(get_member());
            if (!multi_lang_content()) {
                $query .= ' LEFT JOIN ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts p2 ON p2.id=t.t_cache_first_post_id';
            }
            $query .= $extra_join_sql;
            $query .= ' WHERE ' . $where . $where_sup;
            if (multi_lang_content()) {
                $topic_rows = $GLOBALS['FORUM_DB']->query($query, null, 0, false, true, array('t_cache_first_post' => 'LONG_TRANS__COMCODE'));
            } else {
                $topic_rows = $GLOBALS['FORUM_DB']->query($query, null, 0, false, true);
            }
            $topic_rows_map = array();
            foreach ($topic_rows as $topic_row) {
                $topic_rows_map[$topic_row['id']] = $topic_row;
            }
            $hot_topic_definition = intval(get_option('hot_topic_definition'));
            foreach ($rows as $row) {
                if (array_key_exists($row['p_topic_id'], $topic_rows_map)) {
                    $topics->attach(cns_render_topic(cns_get_topic_array($topic_rows_map[$row['p_topic_id']], get_member(), $hot_topic_definition, true), $has_topic_marking));
                }
            }
            if (!$topics->is_empty()) {
                $action_url = build_url(array('page' => 'topics'), get_module_zone('topics'), array(), false, true);

                $forum_name = do_lang_tempcode('TOPICS_PARTICIPATED_IN', escape_html(integer_format($start + 1)) . '-' . integer_format($start + $max));
                $marker = '';
                $breadcrumbs = new Tempcode();
                require_code('templates_pagination');
                $pagination = pagination(do_lang_tempcode('FORUM_TOPICS'), $start, $block_id . '_start', $max, $block_id . '_max', $max_rows, false, 5, null);
                $topics = do_template('CNS_FORUM_TOPIC_WRAPPER', array(
                    '_GUID' => '8723270b128b4eea47ab3c756b342e14',
                    'ORDER' => '',
                    'MAX' => '15',
                    'MAY_CHANGE_MAX' => false,
                    'BREADCRUMBS' => $breadcrumbs,
                    'ACTION_URL' => $action_url,
                    'BUTTONS' => '',
                    'STARTER_TITLE' => '',
                    'MARKER' => $marker,
                    'FORUM_NAME' => $forum_name,
                    'TOPICS' => $topics,
                    'PAGINATION' => $pagination,
                    'MODERATOR_ACTIONS' => $moderator_actions,
                ));
            }
        }

        return do_template('BLOCK_MAIN_CNS_INVOLVED_TOPICS', array(
            '_GUID' => '3f1025f5d3391d43afbdfa292721aa09',
            'BLOCK_ID' => $block_id,
            'BLOCK_PARAMS' => block_params_arr_to_str(array('block_id' => $block_id) + $map),
            'TOPICS' => $topics,

            'START' => strval($start),
            'MAX' => strval($max),
            'START_PARAM' => $block_id . '_start',
            'MAX_PARAM' => $block_id . '_max',
            'EXTRA_GET_PARAMS' => (get_param_integer($block_id . '_max', null) === null) ? null : ('&' . $block_id . '_max=' . urlencode(strval($max))),
        ));
    }
}
