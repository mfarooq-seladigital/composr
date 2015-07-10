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
 - notices (array of notices to give on this screen)
 - warnings (array of warnings to give on this screen)
 - questions (map between named question name [=parameter name] and question details)
 - next (array of tuples, each being parameter, value, and next screen to go OR string just the name of a screen) OR Tempcode/string for the URL to go to
 - previous (where the back button goes, if there is one)
 - form_method (string = get|post). Default is post. Use get if you want screens to be bookmarkable

If 'next' is a string:
 Submitting the form takes you directly to '<next>'
If 'next' is an array:
 Submitting the form takes you to '_<screen>' which decides where to take you to next via a value analysis, using an instant redirect

If 'questions' is non-empty then 'next' must be set also. If 'questions' is empty and 'next' is set, it will be an empty form. If neither are set it will be an info-screen.

Each question details has:
 - label (string) REQUIRED
 - description (string)
 - required (boolean). Default is false
 - type (string = <field hook>). Default is short_text. Only works with hooks that send over a direct string, not complex multi-part inputs or uploads
 - default (string, passed to hook)
 - default_list (array, passed to hook)
 - options (string, passed to hook)
*/

/**
 * Provide multi-screen (multi-form) decision trees.
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
     * @return tempcode Screen output
     */
	public function run()
	{
        $GLOBALS['OUTPUT_STREAMING'] = false; // Too complex to do a pre_run for this properly

		$tree_position = get_param_string('type', 'browse');
        if ($tree_position[0] == '_') {
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
                if (is_null(either_param_string($param, null))) {
                    $url = $this->build_url($this->default_screen);
                    return redirect_screen($title, $url, do_lang_tempcode('DECISION_TREE_SCREEN_NEEDS_PARAMETER', escape_html($param)));
                }
            }
        }

        // Handle a jump?
        if ($submit) {
            $redirect_to = $this->process_input($tree_position);
            $url = $this->build_url($redirect_to);
            return redirect_screen($title, $url);
        }

        // Do this screen
        return $this->render($tree_position);
	}

    /**
     * Build out a URL to a particular decision tree screen.
     *
     * @param  ID_TEXT $target_position Tree position to go to
     * @return tempcode URL
     */
    private function build_url($target_position)
    {
        return build_url(array('page' => '_SELF', 'type' => $target_position), '_SELF', null, true);
    }

    /**
     * Process a step within the decision tree needing value checks.
     *
     * @param  ID_TEXT $tree_position Tree position coming from
     * @return ID_TEXT Tree position going to
     */
	private function process_input($tree_position)
	{
        $details = $this->decision_tree[$tree_position];

        foreach ($details['next'] as $next) {
            if (either_param_string($next[0], null) === $next[1]) {
                $redirect_to = $next[2];
                return $redirect_to;
            }
        }

        fatal_exit('Internal error - not sure where to go');
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

        $text = isset($details['text']) ? $details['text'] : '';

        // Screen messages
        if (isset($details['notices'])) {
            foreach ($details['notices'] as $notice) {
                attach_message($notice, 'inform');
            }
        }
        if (isset($details['warnings'])) {
            foreach ($details['warnings'] as $warning) {
                attach_message($warning, 'warn');
            }
        }

        if (empty($details['previous'])) {
            $back_url = mixed();
        } else {
            $back_url = $this->build_url($details['previous']);
        }

        $hidden = build_keep_post_fields();

        // What if no questions and no next? No form.
        if ((empty($details['questions'])) && (empty($details['next']))) {
            return inform_screen($title, $text, false, $back_url, $hidden);
        }

        // Form...

        require_code('fields');
        require_code('form_templates');

        $fields = new Tempcode();
        if (isset($details['questions'])) {
            $i = 0;

            foreach ($details['questions'] as $question_name => $question_details) {
                $label = $question_details['label'];
                $description = isset($question_details['description']) ? $question_details['description'] : '';

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
                $fields->attach($hook_ob->get_field_inputter($label, $description, $field, $default, true));

                $i++;
            }
        }

        $form_method = empty($details['form_method']) ? 'post' : strtolower($details['form_method']);

        if ((is_object($details['next'])) || ((is_string($details['next'])) && (looks_like_url($details['next'])))) {
            $next_url = $details['next'];
        } else {
            if (is_array($details['next'])) {
                $next_tree_position = '_' . $tree_position; // Needs complex processing
            } else {
                $next_tree_position = $details['next'];
            }
            $next_url = $this->build_url($next_tree_position);
        }

        return do_template('FORM_SCREEN', array(
            'SKIP_WEBSTANDARDS' => true,
            'TITLE' => $title,
            'HIDDEN' => $hidden,
            'FIELDS' => $fields,
            'GET' => ($form_method == 'get'),
            'URL' => $next_url,
            'SUBMIT_ICON' => 'buttons__next',
            'SUBMIT_NAME' => do_lang_tempcode('NEXT'),
            'TEXT' => $text,
            'BACK_URL' => $back_url,
            'SUPPORT_AUTOSAVE' => false,
            'TARGET' => '_self',
        ));
	}
}
