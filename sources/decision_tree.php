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
 * @package    core
 */

/*
Decision trees let you ask a series of questions, leading you through a tree of forms / inform screens.
Answers are propagated all the way through.

A decision tree consists of a map between named screen name and screen details

Each screen details has:
 - title (string) REQUIRED
 - expects_parameters (list of parameters that must be set on this screen, so that you can't have leakiness by jumping deep in). Goes back to start screen if missing
 - text (string)
 - inform (array of informational notices to give on this screen)
 - notice (array of notices to give on this screen)
 - warn (array of warnings to give on this screen)
 - hidden (map between keys and values)
 - questions (map between named question name [=parameter name] and question details)
 - next (array of tuples, each being parameter, value, and string of next screen to go OR Tempcode/string of URL) OR Tempcode/string for the URL to go to
 - previous (where the back button goes, if there is one)
 - form_method (string = GET|POST). Default is POST. Use GET if you want screens to be bookmarkable. GET only supports very simple inputs, and does not support comcode_prepend/comcode_append

Submitting the form takes you to '_<screen>' which decides where to take you to next via a value analysis, using an instant redirect
If 'next' is a string or Tempcode:
 A simple redirect happens (detects whether it is a URL or a name of a screen)
If 'next' is an array:
 A redirect is determined based upon the values submitted

If 'questions' is non-empty then 'next' must be set also. If 'questions' is empty and 'next' is set, it will be an empty form. If neither are set it will be an info-screen.

Each question details has:
 - label (string) REQUIRED
 - description (string)
 - required (boolean). Default is false
 - type (string = <field hook>). Default is short_text
 - default (string, passed to hook)
 - default_list (array, passed to hook)
 - options (string, passed to hook)
 - comcode_prepend and comcode_append

Other features
 - Actually 'inform'/'notice'/'warn' can take a 'next'-style array, in which case the messages are shown using JavaScript upon option selection
*/

/**
 * Provide multi-screen (multi-form) decision trees.
 *
 * @package        core
 */
class DecisionTree
{
    private $decision_tree;
    private $default_screen;

    /**
     * Create a decision tree handler.
     *
     * @param  array $decision_tree Decision tree structure to work from
     * @param  ID_TEXT $default_screen Name of the default screen to start from
     */
    public function __construct($decision_tree, $default_screen = 'start')
    {
        require_lang('decision_tree');

        $this->decision_tree = $decision_tree;

        $this->default_screen = $default_screen;

        // Verify the tree contains a valid structure. Either explicit errors, or implicit errors generated trying to process the data structure.
        foreach ($decision_tree as $screen_name => $screen) {
            if (substr($screen_name, 0, 1) == '_') {
                fatal_exit('Cannot start a screen name with underscore, is reserved');
            }

            if (isset($screen['next'])) {
                if (is_array($screen['next'])) {
                    foreach ($screen['next'] as $next) {
                        if (count($next) != 3) {
                            fatal_exit('Each \'next\' must be a tuple of 3 details: parameter, value, next screen to go to');
                        }
                    }
                }
            }

            $required_properties = array('title');
            if (!empty($screen['questions'])) {
                $required_properties[] = 'next'; // If has 'questions', must also have 'next'
            }
            foreach ($required_properties as $property) {
                if (empty($screen[$property])) {
                    fatal_exit($property . ' parameter required on each screen');
                }

                if (isset($screen['questions'])) {
                    foreach ($screen['questions'] as $question_name => $question) {
                        $required_properties = array('label');
                        foreach ($required_properties as $question_property) {
                            if (empty($question[$question_property])) {
                                fatal_exit($question_property . ' parameter required on each question');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Start the decision tree process, returning Tempcode for the screen currently on.
     *
     * @param  boolean $recurse Whether we are running recursively, after just processing a prior result
     * @return Tempcode Screen output
     */
    public function run($recurse = false)
    {
        $GLOBALS['OUTPUT_STREAMING'] = false; // Too complex to do a pre_run for this properly

        $tree_position = get_param_string('type', 'browse');
        if ($tree_position != '' && $tree_position[0] == '_') {
            $tree_position = substr($tree_position, 1);
            $submit = true;
        } else {
            $submit = false;
        }
        if (!isset($this->decision_tree[$tree_position])) {
            $tree_position = $this->default_screen;
        }

        $details = $this->decision_tree[$tree_position];

        $title = get_screen_title($details['title'], false);

        // Verify we can be on this screen
        if (isset($details['expects_parameters'])) {
            foreach ($details['expects_parameters'] as $param) {
                if (either_param_string($param, null) === null) {
                    $url = $this->build_url($this->default_screen);
                    return redirect_screen($title, $url, do_lang_tempcode('DECISION_TREE_SCREEN_NEEDS_PARAMETER', escape_html($param)));
                }
            }
        }

        // Handle a jump?
        if ($submit) {
            $redirect_to = mixed();
            $redirect_to = $this->process_input($tree_position);
            if (is_object($redirect_to) || looks_like_url($redirect_to)) {
                $url = $redirect_to;
            } else {
                if ($redirect_to == $tree_position) { // Looped back
                    // Do this screen
                    return $this->render($tree_position);
                }

                // Optimisation, to avoid messy POST redirect
                if (count($_POST) > 0) {
                    $_GET['type'] = $redirect_to;
                    return $this->run(true);
                }

                $url = $this->build_url($redirect_to);
            }

            if (count($_POST) > 0) {
                $post = build_keep_post_fields(null, true);
                $refresh = do_template('JS_REFRESH', array('_GUID' => '63cb29a82471b7ba7fd594eb92cc02c1', 'FORM_NAME' => 'redir_form'));

                return do_template('REDIRECT_POST_METHOD_SCREEN', array('_GUID' => 'f9f374626d7acdb0699399f970b2196a', 'REFRESH' => $refresh, 'TITLE' => $title, 'TEXT' => do_lang_tempcode('_REDIRECTING'), 'URL' => $url, 'POST' => $post));
            }

            return redirect_screen($title, $url);
        }

        // Do this screen
        return $this->render($tree_position);
    }

    /**
     * Build out a URL to a particular decision tree screen.
     *
     * @param  ID_TEXT $target_position Tree position to go to
     * @return Tempcode URL
     */
    private function build_url($target_position)
    {
        return build_url(array('page' => '_SELF', 'type' => $target_position), '_SELF', null, true);
    }

    /**
     * Render out decision tree screen.
     *
     * @param  ID_TEXT $tree_position Tree position at
     * @return Tempcode Screen output
     */
    private function render($tree_position)
    {
        $details = $this->decision_tree[$tree_position];

        $title = get_screen_title($details['title'], false);

        $text = comcode_to_tempcode(isset($details['text']) ? $details['text'] : '', null, true);

        require_javascript('core');
        $javascript = '';

        // Screen messages
        foreach (array('inform', 'notice', 'warn') as $notice_type) {
            if (isset($details[$notice_type])) {
                foreach ($details[$notice_type] as $notice_details) {
                    if (is_array($notice_details)) { // Contextual, dynamic
                        if (count($notice_details) != 3) {
                            fatal_exit('Each \'' . $notice_type . '\' must be a tuple of 3 details: parameter, value, notice');
                        }

                        list($parameter, $value, $notice) = $notice_details;

                        $_notice = comcode_to_tempcode($notice, null, true);

                        $notice_title = do_lang('DYNAMIC_NOTICE_' . $notice_type);

                        $javascript .= /** @lang JavaScript */'
                            var e=document.getElementById(\'main_form\').elements[\'' . addslashes($parameter) . '\'];
                            if (e.length === undefined) {
                                e=[e];
                            }
                            for (var i=0;i<e.length;i++) {
                                e[i].addEventListener(\'click\',function(_e) { return function() {
                                    var selected=false;
                                    if (_e.type!=\'undefined\' && _e.type==\'checkbox\') {
                                        selected=(_e.checked && _e.value==\'' . addslashes($value) . '\') || (!_e.checked && \'\'==\'' . addslashes($value) . '\');
                                    } else {
                                        selected=(_e.value==\'' . addslashes($value) . '\');
                                    }
                                    if (selected) {
                                        fauxmodal_alert(\'' . addslashes($_notice->evaluate()) . '\',null,\'' . addslashes($notice_title) . '\',true);
                                    }
                                }}(e[i]));
                            }
                        ';
                    } else { // Flat
                        $notice = $notice_details;
                        attach_message($notice, $notice_type);
                    }
                }
            }
        }

        if (empty($details['previous'])) {
            $back_url = mixed();
        } else {
            $back_url = $this->build_url($details['previous']);
        }

        // What if no questions and no next? No form.
        if ((empty($details['questions'])) && (empty($details['next']))) {
            return inform_screen($title, protect_from_escaping($text), false, $back_url, build_keep_post_fields(null, true));
        }

        // Form...

        require_code('fields');
        require_code('form_templates');

        $fields = new Tempcode();
        $hidden = new Tempcode();
        if (isset($details['questions'])) {
            $i = 0;

            $current_section = null;

            foreach ($details['questions'] as $question_name => $question_details) {
                unset($_POST['_processed__' . $question_name]);

                $label = $question_details['label'];

                if (strpos($label, ': ') !== false) {
                    list($section, $label) = explode(': ', $label, 2);
                } else {
                    $section = null;
                }

                if ($section !== null && $current_section !== $section) {
                    $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '103da055fbd879f2bfc023d83d64091d', 'TITLE' => $section)));
                }
                $current_section = $section;

                $_description = isset($question_details['description']) ? $question_details['description'] : '';
                $description = comcode_to_tempcode($_description, null, true);

                if ($_description != '') {
                    $hidden->attach(form_input_hidden('description_for__' . $question_name, $_description));
                }

                list($hook_ob, $field, $default) = $this->get_question_field_details($question_name, $question_details, $i);

                $temp = $hook_ob->get_field_inputter(protect_from_escaping(comcode_to_tempcode($label, null, true)), $description, $field, empty($default) ? null : $default, true);
                if (is_array($temp)) {
                    $field_details = $temp[0];
                    $hidden->attach($temp[1]);
                } else {
                    $field_details = $temp;
                }
                $fields->attach($field_details);

                $i++;
            }
        }

        if (isset($details['hidden'])) {
            foreach ($details['hidden'] as $key => $val) {
                $hidden->attach(form_input_hidden($key, $val));
            }
        }

        $form_method = empty($details['form_method']) ? 'POST' : $details['form_method'];

        $hidden->attach(build_keep_post_fields(null, true));

        $next_tree_position = '_' . $tree_position; // Needs complex processing
        $next_url = $this->build_url($next_tree_position);

        return do_template('FORM_SCREEN', array(
            '_GUID' => '3164d2c849259902d0e3dc8dce1ad110',
            'SKIP_WEBSTANDARDS' => true,
            'TITLE' => $title,
            'HIDDEN' => $hidden,
            'FIELDS' => $fields,
            'GET' => ($form_method == 'GET'),
            'URL' => $next_url,
            'SUBMIT_ICON' => 'buttons__next',
            'SUBMIT_NAME' => do_lang_tempcode('NEXT'),
            'TEXT' => $text,
            'BACK_URL' => $back_url,
            'SUPPORT_AUTOSAVE' => false,
            'TARGET' => '_self',
            'JAVASCRIPT' => $javascript,
        ));
    }

    /**
     * Get details of a question.
     *
     * @param  string $question_name Question field name
     * @param  array $question_details Map of details of the question
     * @param  integer $i Question number in sequence
     * @return array Tuple of field details: hook object, field details map, default field value
     */
    private function get_question_field_details($question_name, $question_details, $i)
    {
        $default = either_param_string($question_name, isset($question_details['default']) ? $question_details['default'] : '');
        $default_list = isset($question_details['default_list']) ? $question_details['default_list'] : array($default);

        $options = isset($question_details['options']) ? $question_details['options'] : null;

        $type = isset($question_details['type']) ? $question_details['type'] : 'short_text';

        $required = isset($question_details['required']) ? $question_details['required'] : false;

        $hook_ob = get_fields_hook($type);

        $field = array(
            'id' => $i,
            'cf_type' => $type,
            'cf_input_name' => $question_name,
            'cf_default' => implode('|', $default_list),
            'cf_required' => $required ? 1 : 0,
            'cf_options' => $options,
        );

        return array($hook_ob, $field, $default);
    }

    /**
     * Process a step within the decision tree, making decisions and substitions based on the past step's input.
     *
     * @param  ID_TEXT $tree_position Tree position coming from
     * @return mixed Tree position going to or Tempcode URL
     */
    private function process_input($tree_position)
    {
        $details = $this->decision_tree[$tree_position];

        // Field inputters for less-basic field inputting (only supported for POST fields)
        if ((isset($details['questions'])) && (cms_srv('REQUEST_METHOD') == 'POST')) {
            $i = 0;
            foreach ($details['questions'] as $question_name => $question_details) {
                if (post_param_integer('_processed__' . $question_name, 0) == 0) {
                    list($hook_ob, $field) = $this->get_question_field_details($question_name, $question_details, $i);

                    $backup = $_POST;
                    $_POST = array();
                    foreach ($backup as $key => $val) {
                        if (strpos($key, $question_name) !== false) {
                            $_POST[str_replace($question_name, 'field_' . strval($i), $key)] = $val;
                        }
                    }
                    $val = $hook_ob->inputted_to_field_value(false, $field);
                    $_POST = $backup;
                    if ($val !== null) {
                        $_POST[$question_name] = $val;

                        $_POST['_processed__' . $question_name] = '1';
                    }
                }

                $i++;
            }
        }

        // Comcode prepend/append (only supported for POST fields)
        if ((isset($details['questions'])) && (cms_srv('REQUEST_METHOD') == 'POST')) {
            foreach ($details['questions'] as $question_name => $question_details) {
                if (!empty($_POST[$question_name])) {
                    $val = $_POST[$question_name];

                    if (substr($val, 0, 1) != '(' || substr($val, -1) != ')') {
                        if ((!empty($question_details['comcode_prepend'])) && (substr($val, 0, strlen($question_details['comcode_prepend'])) != $question_details['comcode_prepend'])) {
                            $val = $question_details['comcode_prepend'] . $val;
                        }

                        if ((!empty($question_details['comcode_append'])) && (substr($val, -strlen($question_details['comcode_append'])) != $question_details['comcode_append'])) {
                            $val = $val . $question_details['comcode_append'];
                        }

                        $_POST[$question_name] = $val;
                    }
                }
            }
        }

        // Where to go next?
        if (isset($details['next'])) {
            if (is_array($details['next'])) {
                foreach ($details['next'] as $next) {
                    $given = either_param_string($next[0], null);
                    if ($given === $next[1]) {
                        $redirect_to = $next[2];
                        return $redirect_to;
                    }
                }
            } else {
                return $details['next'];
            }
        }

        fatal_exit('Internal error - not sure where to go');
    }
}
