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
 * @package    core_forum_drivers
 */

/**
 * Base class for WBB forum drivers.
 *
 * @package    core_forum_drivers
 */

/**
 * Forum driver class.
 *
 * @package    core_forum_drivers
 */
class Forum_driver_wbb_shared extends Forum_driver_base
{
    /**
     * Check the connected DB is valid for this forum driver.
     *
     * @return boolean Whether it is valid
     */
    public function check_db()
    {
        $test = $this->connection->query('SELECT COUNT(*) FROM ' . $this->connection->get_table_prefix() . 'users', null, null, true);
        return !is_null($test);
    }

    /**
     * Get the rows for the top given number of posters on the forum.
     *
     * @param  integer $limit The limit to the number of top posters to fetch
     * @return array The rows for the given number of top posters in the forum
     */
    public function get_top_posters($limit)
    {
        return $this->connection->query('SELECT * FROM ' . $this->connection->get_table_prefix() . 'users WHERE userid<>' . strval($this->get_guest_id()) . ' ORDER BY userposts DESC', $limit);
    }

    /**
     * Attempt to to find the member's language from their forum profile. It converts between language-identifiers using a map (lang/map.ini).
     *
     * @param  MEMBER $member The member who's language needs to be fetched
     * @return ?LANGUAGE_NAME The member's language (null: unknown)
     */
    public function forum_get_lang($member)
    {
        return null;
    }

    /**
     * Find if the login cookie contains the login name instead of the member ID.
     *
     * @return boolean Whether the login cookie contains a login name or a member ID
     */
    public function is_cookie_login_name()
    {
        return false;
    }

    /**
     * Find if login cookie is md5-hashed.
     *
     * @return boolean Whether the login cookie is md5-hashed
     */
    public function is_hashed()
    {
        return true;
    }

    /**
     * Find the member ID of the forum guest member.
     *
     * @return MEMBER The member ID of the forum guest member
     */
    public function get_guest_id()
    {
        return 0;
    }

    /**
     * Get the forums' table prefix for the database.
     *
     * @return string The forum database table prefix
     */
    public function get_drivered_table_prefix()
    {
        global $SITE_INFO;
        return 'bb' . $SITE_INFO['bb_forum_number'] . '_';
    }

    /**
     * Add the specified custom field to the forum (some forums implemented this using proper custom profile fields, others through adding a new field).
     *
     * @param  string $name The name of the new custom field
     * @param  integer $length The length of the new custom field
     * @param  BINARY $locked Whether the field is locked
     * @param  BINARY $viewable Whether the field is for viewing
     * @param  BINARY $settable Whether the field is for setting
     * @param  BINARY $required Whether the field is required
     * @return boolean Whether the custom field was created successfully
     */
    public function install_create_custom_field($name, $length, $locked = 1, $viewable = 0, $settable = 0, $required = 0)
    {
        if (!array_key_exists('bb_forum_number', $_POST)) {
            $_POST['bb_forum_number'] = ''; // for now
        }

        $name = 'cms_' . $name;
        $test = $this->connection->query('SELECT profilefieldid FROM bb' . $_POST['bb_forum_number'] . '_profilefields WHERE ' . db_string_equal_to('title', $name));
        if (!array_key_exists(0, $test)) {
            $this->connection->query('INSERT INTO bb' . $_POST['bb_forum_number'] . '_profilefields (title,description,required,hidden,maxlength,fieldsize) VALUES (\'' . db_escape_string($name) . '\',\'\',' . strval(intval($required)) . ',' . strval(1 - intval($viewable)) . ',' . strval($length) . ',' . strval($length) . ')');
            $_key = $this->connection->query('SELECT MAX(profilefieldid) AS v FROM bb' . $_POST['bb_forum_number'] . '_profilefields');
            $key = $_key[0]['v'];
            $this->connection->query('ALTER TABLE bb' . $_POST['bb_forum_number'] . '_userfields ADD field' . $key . ' TEXT', null, null, true);
            return true;
        }
        return false;
    }

    /**
     * Get an array of attributes to take in from the installer. Almost all forums require a table prefix, which the requirement there-of is defined through this function.
     * The attributes have 4 values in an array
     * - name, the name of the attribute for _config.php
     * - default, the default value (perhaps obtained through autodetection from forum config)
     * - description, a textual description of the attributes
     * - title, a textual title of the attribute
     *
     * @return array The attributes for the forum
     */
    public function install_specifics()
    {
        global $PROBED_FORUM_CONFIG;
        $a = array();
        $a['name'] = 'bb_forum_number';
        $a['default'] = array_key_exists('sql_tbl_prefix', $PROBED_FORUM_CONFIG) ? $PROBED_FORUM_CONFIG['sql_tbl_prefix'] : '1';
        $a['description'] = do_lang('MOST_DEFAULT');
        $a['title'] = do_lang('BOARD_INSTALL_NUMBER');
        return array($a);
    }

    /**
     * Searches for forum auto-config at this path.
     *
     * @param  PATH $path The path in which to search
     * @return boolean Whether the forum auto-config could be found
     */
    public function install_test_load_from($path)
    {
        global $PROBED_FORUM_CONFIG;
        if (@file_exists($path . '/acp/lib/config.inc.php')) {
            $sqldb = '';
            $sqluser = '';
            $sqlpassword = '';
            $n = '';
            @include($path . '/acp/lib/config.inc.php');
            $PROBED_FORUM_CONFIG['sql_database'] = $sqldb;
            $PROBED_FORUM_CONFIG['sql_user'] = $sqluser;
            $PROBED_FORUM_CONFIG['sql_pass'] = $sqlpassword;
            $PROBED_FORUM_CONFIG['cookie_member_id'] = 'wbb_userid';
            $PROBED_FORUM_CONFIG['cookie_member_hash'] = 'wbb_userpassword';
            $PROBED_FORUM_CONFIG['board_url'] = '';
            $PROBED_FORUM_CONFIG['sql_tbl_prefix'] = $n;
            return true;
        }
        return false;
    }

    /**
     * Get an array of paths to search for config at.
     *
     * @return array The paths in which to search for the forum config
     */
    public function install_get_path_search_list()
    {
        return array(
            0 => 'forums',
            1 => 'forum',
            2 => 'boards',
            3 => 'board',
            4 => 'bb',
            5 => 'bb2',
            6 => 'upload',
            7 => 'uploads',
            8 => 'burningboard',
            9 => 'wbb',
            10 => '../forums',
            11 => '../forum',
            12 => '../boards',
            13 => '../board',
            14 => '../bb',
            15 => '../bb2',
            16 => '../upload',
            17 => '../uploads',
            18 => '../burningboard',
            19 => '../wbb');
    }

    /**
     * Get an emoticon chooser template.
     *
     * @param  string $field_name The ID of the form field the emoticon chooser adds to
     * @return Tempcode The emoticon chooser template
     */
    public function get_emoticon_chooser($field_name = 'post')
    {
        require_code('comcode_compiler');
        $emoticons = $this->connection->query_select('smilies', array('*'));
        $em = new Tempcode();
        foreach ($emoticons as $emo) {
            $code = $emo['smiliecode'];
            $em->attach(do_template('EMOTICON_CLICK_CODE', array('_GUID' => 'c016421840b36b3f70bf5da34740dfaf', 'FIELD_NAME' => $field_name, 'CODE' => $code, 'IMAGE' => apply_emoticons($code))));
        }

        return $em;
    }

    /**
     * Pin a topic.
     *
     * @param  AUTO_LINK $id The topic ID
     * @param  boolean $pin True: pin it, False: unpin it
     */
    public function pin_topic($id, $pin = true)
    {
        $this->connection->query_update('threads', array('important' => $pin ? 1 : 0), array('threadid' => $id), '', 1);
    }

    /**
     * Get a member row for the member of the given name.
     *
     * @param  SHORT_TEXT $name The member name
     * @return ?array The profile-row (null: could not find)
     */
    public function get_mrow($name)
    {
        $rows = $this->connection->query_select('users', array('*'), array('username' => $name), '', 1);
        if (!array_key_exists(0, $rows)) {
            return null;
        }
        return $rows[0];
    }

    /**
     * From a member row, get the member's member ID.
     *
     * @param  array $r The profile-row
     * @return MEMBER The member ID
     */
    public function mrow_id($r)
    {
        return $r['userid'];
    }

    /**
     * From a member row, get the member's last visit date.
     *
     * @param  array $r The profile-row
     * @return TIME The last visit date
     */
    public function mrow_lastvisit($r)
    {
        return $r['lastvisit'];
    }

    /**
     * From a member row, get the member's name.
     *
     * @param  array $r The profile-row
     * @return string The member name
     */
    public function mrow_username($r)
    {
        return $r['username'];
    }

    /**
     * From a member row, get the member's e-mail address.
     *
     * @param  array $r The profile-row
     * @return SHORT_TEXT The member e-mail address
     */
    public function mrow_email($r)
    {
        return $r['email'];
    }

    /**
     * Get a URL to the specified member's home (control panel).
     *
     * @param  MEMBER $id The member ID
     * @return URLPATH The URL to the members home
     */
    public function member_home_url($id)
    {
        return get_forum_base_url() . '/usercp.php';
    }

    /**
     * Get the photo thumbnail URL for the specified member ID.
     *
     * @param  MEMBER $member The member ID
     * @return URLPATH The URL (blank: none)
     */
    public function get_member_photo_url($member)
    {
        return '';
    }

    /**
     * Get the avatar URL for the specified member ID.
     *
     * @param  MEMBER $member The member ID
     * @return URLPATH The URL (blank: none)
     */
    public function get_member_avatar_url($member)
    {
        $avatar = $this->connection->query_select_value_if_there('avatars', 'avatarname', array('userid' => $member));
        if ((is_null($avatar)) || ($avatar == '') || (!url_is_local($avatar))) {
            return $avatar;
        }
        return get_forum_base_url() . '/images/avatars/' . $avatar;
    }

    /**
     * Get a URL to the specified member's profile.
     *
     * @param  MEMBER $id The member ID
     * @return URLPATH The URL to the member profile
     */
    protected function _member_profile_url($id)
    {
        return get_forum_base_url() . '/profile.php?userid=' . strval($id);
    }

    /**
     * Get a URL to the registration page (for people to create member accounts).
     *
     * @return URLPATH The URL to the registration page
     */
    protected function _join_url()
    {
        return get_forum_base_url() . '/register.php';
    }

    /**
     * Get a URL to the members-online page.
     *
     * @return URLPATH The URL to the members-online page
     */
    protected function _users_online_url()
    {
        return get_forum_base_url() . '/wiw.php';
    }

    /**
     * Get a URL to send a private/personal message to the given member.
     *
     * @param  MEMBER $id The member ID
     * @return URLPATH The URL to the private/personal message page
     */
    protected function _member_pm_url($id)
    {
        return get_forum_base_url() . '/pms.php?action=newpm&userid=' . strval($id);
    }

    /**
     * Get a URL to the specified forum.
     *
     * @param  integer $id The forum ID
     * @return URLPATH The URL to the specified forum
     */
    protected function _forum_url($id)
    {
        return get_forum_base_url() . '/board.php?boardid=' . strval($id);
    }

    /**
     * Get the forum ID from a forum name.
     *
     * @param  SHORT_TEXT $forum_name The forum name
     * @return integer The forum ID
     */
    public function forum_id_from_name($forum_name)
    {
        return is_numeric($forum_name) ? intval($forum_name) : $this->connection->query_select_value_if_there('boards', 'boardid', array('title' => $forum_name));
    }

    /**
     * Get the topic ID from a topic identifier in the specified forum. It is used by comment topics, which means that the unique-topic-name assumption holds valid.
     *
     * @param  string $forum The forum name / ID
     * @param  SHORT_TEXT $topic_identifier The topic identifier
     * @return ?integer The topic ID (null: not found)
     */
    public function find_topic_id_for_topic_identifier($forum, $topic_identifier)
    {
        if (is_integer($forum)) {
            $forum_id = $forum;
        } else {
            $forum_id = $this->forum_id_from_name($forum);
        }
        return $this->connection->query_value_if_there('SELECT threadid FROM ' . $this->connection->get_table_prefix() . 'threads WHERE boardid=' . strval($forum_id) . ' AND (' . db_string_equal_to('topic', $topic_identifier) . ' OR topic LIKE \'%: #' . db_encode_like($topic_identifier) . '\')');
    }

    /**
     * Makes a post in the specified forum, in the specified topic according to the given specifications. If the topic doesn't exist, it is created along with a spacer-post.
     * Spacer posts exist in order to allow staff to delete the first true post in a topic. Without spacers, this would not be possible with most forum systems. They also serve to provide meta information on the topic that cannot be encoded in the title (such as a link to the content being commented upon).
     *
     * @param  SHORT_TEXT $forum_name The forum name
     * @param  SHORT_TEXT $topic_identifier The topic identifier (usually <content-type>_<content-id>)
     * @param  MEMBER $member The member ID
     * @param  LONG_TEXT $post_title The post title
     * @param  LONG_TEXT $post The post content in Comcode format
     * @param  string $content_title The topic title; must be same as content title if this is for a comment topic
     * @param  string $topic_identifier_encapsulation_prefix This is put together with the topic identifier to make a more-human-readable topic title or topic description (hopefully the latter and a $content_title title, but only if the forum supports descriptions)
     * @param  ?URLPATH $content_url URL to the content (null: do not make spacer post)
     * @param  ?TIME $time The post time (null: use current time)
     * @param  ?IP $ip The post IP address (null: use current members IP address)
     * @param  ?BINARY $validated Whether the post is validated (null: unknown, find whether it needs to be marked unvalidated initially). This only works with the Conversr driver.
     * @param  ?BINARY $topic_validated Whether the topic is validated (null: unknown, find whether it needs to be marked unvalidated initially). This only works with the Conversr driver.
     * @param  boolean $skip_post_checks Whether to skip post checks
     * @param  SHORT_TEXT $poster_name_if_guest The name of the poster
     * @param  ?AUTO_LINK $parent_id ID of post being replied to (null: N/A)
     * @param  boolean $staff_only Whether the reply is only visible to staff
     * @return array Topic ID (may be NULL), and whether a hidden post has been made
     */
    public function make_post_forum_topic($forum_name, $topic_identifier, $member, $post_title, $post, $content_title, $topic_identifier_encapsulation_prefix, $content_url = null, $time = null, $ip = null, $validated = null, $topic_validated = 1, $skip_post_checks = false, $poster_name_if_guest = '', $parent_id = null, $staff_only = false)
    {
        if (is_null($time)) {
            $time = time();
        }
        if (is_null($ip)) {
            $ip = get_ip_address();
        }
        $forum_id = $this->forum_id_from_name($forum_name);
        if (is_null($forum_id)) {
            warn_exit(do_lang_tempcode('MISSING_FORUM', escape_html($forum_name)));
        }
        $username = $this->get_username($member);
        $topic_id = $this->find_topic_id_for_topic_identifier($forum_name, $topic_identifier);
        $is_new = is_null($topic_id);
        if ($is_new) {
            $topic_id = $this->connection->query_insert('threads', array('topic' => $content_title . ', ' . $topic_identifier_encapsulation_prefix . ': #' . $topic_identifier, 'starttime' => $time, 'boardid' => $forum_id, 'closed' => 0, 'starter' => $username, 'starterid' => $member, 'lastposter' => $username, 'lastposttime' => $time, 'visible' => 1), true);
            $home_link = hyperlink($content_url, $content_title, false, true);
            $this->connection->query_insert('posts', array('threadid' => $topic_id, 'username' => do_lang('SYSTEM', '', '', '', get_site_default_lang()), 'userid' => 0, 'posttopic' => '', 'posttime' => $time, 'message' => do_lang('SPACER_POST', $home_link->evaluate(), '', '', get_site_default_lang()), 'allowsmilies' => 1, 'ipaddress' => '127.0.0.1', 'visible' => 1));
            $this->connection->query('UPDATE ' . $this->connection->get_table_prefix() . 'boards SET threadcount=(threadcount+1), postcount=(postcount+1) WHERE boardid=' . strval($forum_id), 1);
        }

        $GLOBALS['LAST_TOPIC_ID'] = $topic_id;
        $GLOBALS['LAST_TOPIC_IS_NEW'] = $is_new;

        if ($post == '') {
            return array($topic_id, false);
        }

        $this->connection->query_insert('posts', array('threadid' => $topic_id, 'username' => $username, 'userid' => $member, 'posttopic' => $post_title, 'posttime' => $time, 'message' => $post, 'allowsmilies' => 1, 'ipaddress' => $ip, 'visible' => 1));
        $this->connection->query('UPDATE ' . $this->connection->get_table_prefix() . 'boards SET lastthreadid=' . strval($topic_id) . ', postcount=(postcount+1), lastposttime=' . strval($time) . ', lastposterid=' . strval($member) . ', lastposter=\'' . db_escape_string($username) . '\' WHERE boardid=\'' . strval($forum_id) . '\'', 1);
        $this->connection->query('UPDATE ' . $this->connection->get_table_prefix() . 'threads SET replycount=(replycount+1), lastposttime=' . strval($time) . ', lastposterid=' . strval($member) . ', lastposter=\'' . db_escape_string($username) . '\' WHERE threadid=' . strval($topic_id), 1);

        return array($topic_id, false);
    }

    /**
     * Get an array of maps for the topic in the given forum.
     *
     * @param  integer $topic_id The topic ID
     * @param  integer $count The comment count will be returned here by reference
     * @param  integer $max Maximum comments to returned
     * @param  integer $start Comment to start at
     * @param  boolean $mark_read Whether to mark the topic read (ignored for this forum driver)
     * @param  boolean $reverse Whether to show in reverse
     * @return mixed The array of maps (Each map is: title, message, member, date) (-1 for no such forum, -2 for no such topic)
     */
    public function get_forum_topic_posts($topic_id, &$count, $max = 100, $start = 0, $mark_read = true, $reverse = false)
    {
        if (is_null($topic_id)) {
            return (-2);
        }
        $order = $reverse ? 'posttime DESC' : 'posttime';
        $rows = $this->connection->query('SELECT * FROM ' . $this->connection->get_table_prefix() . 'posts WHERE threadid=' . strval($topic_id) . ' AND message NOT LIKE \'' . db_encode_like(substr(do_lang('SPACER_POST', '', '', '', get_site_default_lang()), 0, 20) . '%') . '\' ORDER BY ' . $order, $max, $start);
        $count = $this->connection->query_value_if_there('SELECT COUNT(*) FROM ' . $this->connection->get_table_prefix() . 'posts WHERE threadid=' . strval($topic_id) . ' AND message NOT LIKE \'' . db_encode_like(substr(do_lang('SPACER_POST', '', '', '', get_site_default_lang()), 0, 20) . '%') . '\'');
        $out = array();
        foreach ($rows as $myrow) {
            $temp = array();
            $temp['title'] = $myrow['posttopic'];
            if (is_null($temp['title'])) {
                $temp['title'] = '';
            }
            global $LAX_COMCODE;
            $temp2 = $LAX_COMCODE;
            $LAX_COMCODE = true;
            $temp['message'] = comcode_to_tempcode($myrow['message'], $myrow['userid']);
            $LAX_COMCODE = $temp2;
            $temp['member'] = $myrow['userid'];
            $temp['date'] = $myrow['posttime'];

            $out[] = $temp;
        }

        return $out;
    }

    /**
     * Get a URL to the specified topic ID. Most forums don't require the second parameter, but some do, so it is required in the interface.
     *
     * @param  integer $id The topic ID
     * @param  string $forum The forum ID
     * @return URLPATH The URL to the topic
     */
    public function topic_url($id, $forum)
    {
        return get_forum_base_url() . '/thread.php?threadid=' . strval($id);
    }

    /**
     * Get a URL to the specified post ID.
     *
     * @param  integer $id The post ID
     * @param  string $forum The forum ID
     * @return URLPATH The URL to the post
     */
    public function post_url($id, $forum)
    {
        return get_forum_base_url() . '/thread.php?postid=' . strval($id) . '#post' . strval($id);
    }

    /**
     * Get an array of topics in the given forum. Each topic is an array with the following attributes:
     * - id, the topic ID
     * - title, the topic title
     * - lastusername, the username of the last poster
     * - lasttime, the timestamp of the last reply
     * - closed, a Boolean for whether the topic is currently closed or not
     * - firsttitle, the title of the first post
     * - firstpost, the first post (only set if $show_first_posts was true)
     *
     * @param  mixed $name The forum name or an array of forum IDs
     * @param  integer $limit The limit
     * @param  integer $start The start position
     * @param  integer $max_rows The total rows (not a parameter: returns by reference)
     * @param  SHORT_TEXT $filter_topic_title The topic title filter
     * @param  boolean $show_first_posts Whether to show the first posts
     * @param  string $date_key The date key to sort by
     * @set    lasttime firsttime
     * @param  boolean $hot Whether to limit to hot topics
     * @param  SHORT_TEXT $filter_topic_description The topic description filter
     * @return ?array The array of topics (null: error)
     */
    public function show_forum_topics($name, $limit, $start, &$max_rows, $filter_topic_title = '', $show_first_posts = false, $date_key = 'lasttime', $hot = false, $filter_topic_description = '')
    {
        if (is_integer($name)) {
            $id_list = 'boardid=' . strval($name);
        } elseif (!is_array($name)) {
            $id = $this->forum_id_from_name($name);
            if (is_null($id)) {
                return null;
            }
            $id_list = 'boardid=' . strval($id);
        } else {
            $id_list = '';
            foreach (array_keys($name) as $id) {
                if ($id_list != '') {
                    $id_list .= ' OR ';
                }
                $id_list .= 'boardid=' . strval($id);
            }
            if ($id_list == '') {
                return null;
            }
        }

        $topic_filter = ($filter_topic_title != '') ? ('AND topic LIKE \'' . db_encode_like($filter_topic_title) . '\'') : '';
        $rows = $this->connection->query('SELECT * FROM ' . $this->connection->get_table_prefix() . 'threads WHERE (' . $id_list . ') ' . $topic_filter . ' ORDER BY ' . (($date_key == 'lasttime') ? 'lastposttime' : 'starttime') . ' DESC', $limit, $start);
        $max_rows = $this->connection->query_value_if_there('SELECT COUNT(*) FROM ' . $this->connection->get_table_prefix() . 'threads WHERE (' . $id_list . ') ' . $topic_filter);
        $out = array();
        foreach ($rows as $i => $r) {
            $out[$i] = array();
            $out[$i]['id'] = $r['threadid'];
            $out[$i]['num'] = $r['replycount'] + 1;
            $out[$i]['title'] = $r['topic'];
            $out[$i]['description'] = $r['topic'];
            $out[$i]['firstusername'] = $r['starter'];
            $out[$i]['lastusername'] = $r['lastposter'];
            $out[$i]['firsttime'] = $r['starttime'];
            $out[$i]['lasttime'] = $r['lastposttime'];
            $out[$i]['closed'] = ($r['closed'] == 1);
            $fp_rows = $this->connection->query('SELECT posttopic,message,userid FROM ' . $this->connection->get_table_prefix() . 'posts WHERE message NOT LIKE \'' . db_encode_like(do_lang('SPACER_POST', '', '', '', get_site_default_lang()) . '%') . '\' AND threadid=' . strval($out[$i]['id']) . ' ORDER BY posttime', 1);
            if (!array_key_exists(0, $fp_rows)) {
                unset($out[$i]);
                continue;
            }
            $out[$i]['firsttitle'] = $fp_rows[0]['posttopic'];
            if ($show_first_posts) {
                global $LAX_COMCODE;
                $temp = $LAX_COMCODE;
                $LAX_COMCODE = true;
                $out[$i]['firstpost'] = comcode_to_tempcode($fp_rows[0]['message'], $fp_rows[0]['userid']);
                $LAX_COMCODE = $temp;
            }
        }
        if (count($out) != 0) {
            return $out;
        }
        return null;
    }

    /**
     * This is the opposite of the get_next_member function.
     *
     * @param  MEMBER $member The member ID to decrement
     * @return ?MEMBER The previous member ID (null: no previous member)
     */
    public function get_previous_member($member)
    {
        $tempid = $this->connection->query_value_if_there('SELECT userid FROM ' . $this->connection->get_table_prefix() . 'users WHERE userid<' . strval($member) . ' AND userid<>\'0\' ORDER BY userid DESC');
        return $tempid;
    }

    /**
     * Get the member ID of the next member after the given one, or NULL.
     * It cannot be assumed there are no gaps in member IDs, as members may be deleted.
     *
     * @param  MEMBER $member The member ID to increment
     * @return ?MEMBER The next member ID (null: no next member)
     */
    public function get_next_member($member)
    {
        $tempid = $this->connection->query_value_if_there('SELECT userid FROM ' . $this->connection->get_table_prefix() . 'users WHERE userid>' . strval($member) . ' ORDER BY userid');
        return $tempid;
    }

    /**
     * Try to find a member with the given IP address
     *
     * @param  IP $ip The IP address
     * @return array The distinct rows found
     */
    public function probe_ip($ip)
    {
        return $this->connection->query_select('posts', array('DISTINCT userid AS id'), array('ipaddress' => $ip));
    }

    /**
     * Get the name relating to the specified member ID.
     * If this returns NULL, then the member has been deleted. Always take potential NULL output into account.
     *
     * @param  MEMBER $member The member ID
     * @return ?SHORT_TEXT The member name (null: member deleted)
     */
    protected function _get_username($member)
    {
        if ($member == $this->get_guest_id()) {
            return do_lang('GUEST');
        }
        return $this->get_member_row_field($member, 'username');
    }

    /**
     * Get the e-mail address for the specified member ID.
     *
     * @param  MEMBER $member The member ID
     * @return SHORT_TEXT The e-mail address
     */
    protected function _get_member_email_address($member)
    {
        return $this->get_member_row_field($member, 'email');
    }

    /**
     * Find if this member may have e-mails sent to them
     *
     * @param  MEMBER $member The member ID
     * @return boolean Whether the member may have e-mails sent to them
     */
    public function get_member_email_allowed($member)
    {
        $v = $this->get_member_row_field($member, 'emailnotify');
        if ($v == 1) {
            return true;
        }
        return false;
    }

    /**
     * Get the timestamp of a member's join date.
     *
     * @param  MEMBER $member The member ID
     * @return TIME The timestamp
     */
    public function get_member_join_timestamp($member)
    {
        return $this->get_member_row_field($member, 'regdate');
    }

    /**
     * Find all members with a name matching the given SQL LIKE string.
     *
     * @param  string $pattern The pattern
     * @param  ?integer $limit Maximum number to return (limits to the most recent active) (null: no limit)
     * @return ?array The array of matched members (null: none found)
     */
    public function get_matching_members($pattern, $limit = null)
    {
        $rows = $this->connection->query('SELECT * FROM ' . $this->connection->get_table_prefix() . 'users WHERE username LIKE \'' . db_encode_like($pattern) . '\' AND userid<>' . strval($this->get_guest_id()) . ' ORDER BY lastactivity DESC', $limit);
        sort_maps_by($rows, 'username');
        return $rows;
    }

    /**
     * Get the given member's post count.
     *
     * @param  MEMBER $member The member ID
     * @return integer The post count
     */
    public function get_post_count($member)
    {
        $c = $this->get_member_row_field($member, 'userposts');
        if (is_null($c)) {
            return 0;
        }
        return $c;
    }

    /**
     * Get the given member's topic count.
     *
     * @param  MEMBER $member The member ID
     * @return integer The topic count
     */
    public function get_topic_count($member)
    {
        return $this->connection->query_select_value('threads', 'COUNT(*)', array('starterid' => $member));
    }

    /**
     * Find the base URL to the emoticons.
     *
     * @return URLPATH The base URL
     */
    public function get_emo_dir()
    {
        return get_forum_base_url() . '/';
    }

    /**
     * Get a map between emoticon codes and templates representing the HTML-image-code for this emoticon. The emoticons presented of course depend on the forum involved.
     *
     * @return array The map
     */
    public function find_emoticons()
    {
        if (!is_null($this->EMOTICON_CACHE)) {
            return $this->EMOTICON_CACHE;
        }
        $rows = $this->connection->query_select('smilies', array('*'));
        $this->EMOTICON_CACHE = array();
        foreach ($rows as $myrow) {
            $src = str_replace('{imagefolder}' . '/', 'images/', $myrow['smiliepath']);
            if (url_is_local($src)) {
                $src = $this->get_emo_dir() . $src;
            }
            $this->EMOTICON_CACHE[$myrow['smiliecode']] = array('EMOTICON_IMG_CODE_DIR', $src, $myrow['smiliecode']);
        }
        uksort($this->EMOTICON_CACHE, 'strlen_sort');
        $this->EMOTICON_CACHE = array_reverse($this->EMOTICON_CACHE);
        return $this->EMOTICON_CACHE;
    }

    /**
     * Get the number of members currently online on the forums.
     *
     * @return integer The number of members
     */
    public function get_num_users_forums()
    {
        return $this->connection->query_value_if_there('SELECT COUNT(DISTINCT userid) FROM ' . $this->connection->get_table_prefix() . 'sessions WHERE lastactivity>' . strval(time() - 60 * intval(get_option('users_online_time'))));
    }

    /**
     * Get the number of members registered on the forum.
     *
     * @return integer The number of members
     */
    public function get_members()
    {
        return $this->connection->query_select_value('users', 'COUNT(*)');
    }

    /**
     * Get the total topics ever made on the forum.
     *
     * @return integer The number of topics
     */
    public function get_topics()
    {
        return $this->connection->query_select_value('threads', 'COUNT(*)');
    }

    /**
     * Get the total posts ever made on the forum.
     *
     * @return integer The number of posts
     */
    public function get_num_forum_posts()
    {
        return $this->connection->query_select_value('posts', 'COUNT(*)');
    }

    /**
     * Get the number of new forum posts.
     *
     * @return integer The number of posts
     */
    protected function _get_num_new_forum_posts()
    {
        return $this->connection->query_value_if_there('SELECT COUNT(*) FROM ' . $this->connection->get_table_prefix() . 'posts WHERE posttime>' . strval(time() - 60 * 60 * 24));
    }

    /**
     * Set a custom profile fields value. It should not be called directly.
     *
     * @param  MEMBER $member The member ID
     * @param  string $field The field name
     * @param  string $value The value
     */
    public function set_custom_field($member, $field, $value)
    {
        $id = $this->connection->query_select_value_if_there('profilefields', 'profilefieldid', array('title' => 'cms_' . $field));
        if (is_null($id)) {
            return;
        }
        $this->connection->query_update('userfields', array('field' . strval($id) => $value), array('userid' => $member), '', 1);
    }

    /**
     * Get custom profile fields values for all 'cms_' prefixed keys.
     *
     * @param  MEMBER $member The member ID
     * @return ?array A map of the custom profile fields, key_suffix=>value (null: no fields)
     */
    public function get_custom_fields($member)
    {
        $rows = $this->connection->query('SELECT profilefieldid,title FROM ' . $this->connection->get_table_prefix() . 'profilefields WHERE title LIKE \'' . db_encode_like('cms_%') . '\'');
        $values = $this->connection->query_select('userfields', array('*'), array('userid' => $member), '', 1);
        if (!array_key_exists(0, $values)) {
            return null;
        }

        $out = array();
        foreach ($rows as $row) {
            $title = substr($row['title'], 4);
            $out[$title] = $values[0]['field' . strval($row['profilefieldid'])];
        }
        return $out;
    }

    /**
     * Get a member ID from the given member's username.
     *
     * @param  SHORT_TEXT $name The member name
     * @return MEMBER The member ID
     */
    public function get_member_from_username($name)
    {
        return $this->connection->query_select_value_if_there('users', 'userid', array('username' => $name));
    }

    /**
     * Find if the given member ID and password is valid. If username is NULL, then the member ID is used instead.
     * All authorisation, cookies, and form-logins, are passed through this function.
     * Some forums do cookie logins differently, so a Boolean is passed in to indicate whether it is a cookie login.
     *
     * @param  ?SHORT_TEXT $username The member username (null: don't use this in the authentication - but look it up using the ID if needed)
     * @param  MEMBER $memberid The member ID
     * @param  SHORT_TEXT $password_hashed The md5-hashed password
     * @param  string $password_raw The raw password
     * @param  boolean $cookie_login Whether this is a cookie login
     * @return array A map of 'id' and 'error'. If 'id' is NULL, an error occurred and 'error' is set
     */
    public function forum_authorise_login($username, $memberid, $password_hashed, $password_raw, $cookie_login = false)
    {
        $out = array();
        $out['id'] = null;

        if (is_null($memberid)) {
            $rows = $this->connection->query_select('users', array('*'), array('username' => $username), '', 1);
            if (array_key_exists(0, $rows)) {
                $this->MEMBER_ROWS_CACHED[$rows[0]['userid']] = $rows[0];
            }
        } else {
            $rows = array();
            $rows[0] = $this->get_member_row($memberid);
        }

        if (!array_key_exists(0, $rows) || $rows[0] === null) { // All hands to lifeboats
            $out['error'] = (do_lang_tempcode('_MEMBER_NO_EXIST', $username));
            return $out;
        }
        $row = $rows[0];
        if ($this->is_banned($row['userid'])) { // All hands to the guns
            $out['error'] = (do_lang_tempcode('MEMBER_BANNED'));
            return $out;
        }
        if ($row['password'] != $password_hashed) {
            $out['error'] = (do_lang_tempcode('MEMBER_BAD_PASSWORD'));
            return $out;
        }

        require_code('users_active_actions');
        cms_eatcookie('cookiehash');

        $out['id'] = $row['userid'];
        return $out;
    }

    /**
     * Get a first known IP address of the given member.
     *
     * @param  MEMBER $member The member ID
     * @return IP The IP address
     */
    public function get_member_ip($member)
    {
        return $this->get_member_row_field($member, 'ipaddress');
    }

    /**
     * Gets a whole member row from the database.
     *
     * @param  MEMBER $member The member ID
     * @return ?array The member row (null: no such member)
     */
    public function get_member_row($member)
    {
        if (array_key_exists($member, $this->MEMBER_ROWS_CACHED)) {
            return $this->MEMBER_ROWS_CACHED[$member];
        }

        $rows = $this->connection->query_select('users', array('*'), array('userid' => $member), '', 1);
        if ($member == $this->get_guest_id()) {
            $rows[0]['username'] = do_lang('GUEST');
            $rows[0]['email'] = null;
            $rows[0]['avatar'] = '';
            $rows[0]['emailnotify'] = 0;
            $rows[0]['regdate'] = time();
            $rows[0]['userposts'] = 0;
            $rows[0]['groupid'] = $this->_get_guest_group();
            $rows[0]['userid'] = $this->get_guest_id();
            $rows[0]['styleid'] = null;
        }
        if (!array_key_exists(0, $rows)) {
            return null;
        }
        $this->MEMBER_ROWS_CACHED[$member] = $rows[0];
        return $this->MEMBER_ROWS_CACHED[$member];
    }

    /**
     * Gets a named field of a member row from the database.
     *
     * @param  MEMBER $member The member ID
     * @param  string $field The field identifier
     * @return mixed The field
     */
    public function get_member_row_field($member, $field)
    {
        $row = $this->get_member_row($member);
        return is_null($row) ? null : $row[$field];
    }
}
