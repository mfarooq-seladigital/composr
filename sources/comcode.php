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
 * @package    core_rich_media
 */

/**
 * Standard code module initialisation function.
 */
function init__comcode()
{
    global $COMCODE_PARSE_URLS_CHECKED;
    $COMCODE_PARSE_URLS_CHECKED = 0;

    global $OVERRIDE_SELF_ZONE;
    $OVERRIDE_SELF_ZONE = null; // This is not pretty, but needed to properly scope links for search results.

    global $LAX_COMCODE;
    /** Set whether the lax Comcode parser should be used, which is important for any Comcode not being interactively added (i.e. existing Comcode should not cause errors, even if it is poor quality).
     *
     * @global boolean $LAX_COMCODE
     */
    $LAX_COMCODE = null;

    global $VALID_COMCODE_TAGS;
    /** A list of all valid Comcode tags that we recognise.
     *
     * @global array $VALID_COMCODE_TAGS
     */
    $VALID_COMCODE_TAGS = array(
        'samp' => true, 'q' => true, 'var' => true, 'overlay' => true, 'tooltip' => true,
        'section' => true, 'section_controller' => true,
        'big_tab' => true, 'big_tab_controller' => true, 'tabs' => true, 'tab' => true,
        'carousel' => true, 'cite' => true, 'ins' => true, 'del' => true, 'dfn' => true, 'address' => true, 'acronym' => true, 'abbr' => true, 'contents' => true, 'concepts' => true, 'list' => true,
        'flash' => true, 'media_set' => true, 'media' => true, 'indent' => true, 'staff_note' => true, 'menu' => true, 'b' => true, 'i' => true, 'u' => true, 's' => true, 'sup' => true, 'sub' => true,
        'if_in_group' => true, 'title' => true, 'size' => true, 'color' => true, 'highlight' => true, 'font' => true, 'tt' => true, 'box' => true, 'img' => true,
        'url' => true, 'email' => true, 'reference' => true, 'page' => true, 'codebox' => true, 'no_parse' => true, 'code' => true, 'hide' => true,
        'quote' => true, 'block' => true, 'semihtml' => true, 'html' => true, 'concept' => true, 'thumb' => true,
        'attachment' => true, 'attachment_safe' => true, 'align' => true, 'left' => true, 'center' => true, 'right' => true,
        'snapback' => true, 'post' => true, 'topic' => true, 'include' => true, 'random' => true, 'ticker' => true, 'jumping' => true, 'surround' => true, 'pulse' => true, 'shocker' => true,
    );
    //if (addon_installed('ecommerce')) {
    $VALID_COMCODE_TAGS['currency'] = true;
    //}

    // We're not allowed to specify any of these as entities
    global $POTENTIAL_JS_NAUGHTY_ARRAY;
    $POTENTIAL_JS_NAUGHTY_ARRAY = array('d' => true, /*'a' => true, 't' => true, 'a' => true,*/
                                        'j' => true, 'a' => true, 'v' => true, 's' => true, 'c' => true, 'r' => true, 'i' => true, 'p' => true, 't' => true, 'J' => true, 'A' => true, 'V' => true, 'S' => true, 'C' => true, 'R' => true, 'I' => true, 'P' => true, 'T' => true, ' ' => true, "\t" => true, "\n" => true, "\r" => true, ':' => true, '/' => true, '*' => true, '\\' => true);
    $POTENTIAL_JS_NAUGHTY_ARRAY[chr(0)] = true;

    define('WYSIWYG_COMCODE__BUTTON', 1);
    define('WYSIWYG_COMCODE__XML_BLOCK', 2);
    define('WYSIWYG_COMCODE__XML_INLINE', 3);
    define('WYSIWYG_COMCODE__STANDOUT_BLOCK', 4);
    define('WYSIWYG_COMCODE__STANDOUT_INLINE', 5);
    define('WYSIWYG_COMCODE__HTML', 6);
}

/**
 * Make text usable inside a string inside Comcode
 *
 * @param  string $in Raw text
 * @return string Escaped text
 */
function comcode_escape($in)
{
    return str_replace('{', '\\{', str_replace('[', '\\[', str_replace('"', '\\"', str_replace('\\', '\\\\', $in))));
}

/**
 * Convert (X)HTML into Comcode
 *
 * @param  LONG_TEXT $html The HTML to be converted
 * @param  boolean $force Whether to force full conversion regardless of settings
 * @return LONG_TEXT The equivalent Comcode
 */
function html_to_comcode($html, $force = true)
{
    // First we don't allow this to be semi-html
    $html = str_replace('[', '&#091;', $html);

    require_code('comcode_from_html');

    return semihtml_to_comcode($html, $force);
}

/**
 * Get the text with all the emoticon codes replaced with the correct XHTML. Emoticons are determined by your forum system.
 * This is not used in the normal Comcode chain - it's for non-Comcode things that require emoticons (actually in reality it is used in the Comcode chain if the optimiser sees that a full parse is not needed)
 *
 * @param  string $text The text to add emoticons to (assumption: that this is XHTML)
 * @return string The XHTML with the image-substitution of emoticons
 */
function apply_emoticons($text)
{
    require_code('comcode_renderer');
    return _apply_emoticons($text);
}

/**
 * Convert the specified Comcode (unknown format) into a Tempcode tree. You shouldn't output the Tempcode tree to the browser, as it looks really horrible. If you are in a rare case where you need to output directly (not through templates), you should call the evaluate method on the Tempcode object, to convert it into a string.
 *
 * @param  LONG_TEXT $comcode The Comcode to convert
 * @param  ?MEMBER $source_member The member the evaluation is running as. This is a security issue, and you should only run as an administrator if you have considered where the Comcode came from carefully (null: current member)
 * @param  boolean $as_admin Whether to explicitly execute this with admin rights. There are a few rare situations where this should be done, for data you know didn't come from a member, but is being evaluated by one. Note that if this is passed false, and $source_member is an admin, it will be parsed using admin privileges anyway.
 * @param  ?integer $wrap_pos The position to conduct wordwrapping at (null: do not conduct word-wrapping)
 * @param  ?string $pass_id A special identifier that can identify this resource in a sea of our resources of this class; usually this can be ignored, but may be used to provide a binding between JavaScript in evaluated Comcode, and the surrounding environment (null: no explicit binding)
 * @param  ?object $connection The database connection to use (null: standard site connection)
 * @param  boolean $semiparse_mode Whether to parse so as to create something that would fit inside a semihtml tag. It means we generate HTML, with Comcode written into it where the tag could never be reverse-converted (e.g. a block).
 * @param  boolean $preparse_mode Whether this is being pre-parsed, to pick up errors before row insertion.
 * @param  boolean $is_all_semihtml Whether to treat this whole thing as being wrapped in semihtml, but apply normal security otherwise.
 * @param  boolean $structure_sweep Whether we are only doing this parse to find the title structure
 * @param  boolean $check_only Whether to only check the Comcode. It's best to use the check_comcode function which will in turn use this parameter.
 * @param  ?array $highlight_bits A list of words to highlight (null: none)
 * @param  ?MEMBER $on_behalf_of_member The member we are running on behalf of, with respect to how attachments are handled; we may use this members attachments that are already within this post, and our new attachments will be handed to this member (null: member evaluating)
 * @return Tempcode The Tempcode generated
 */
function comcode_to_tempcode($comcode, $source_member = null, $as_admin = false, $wrap_pos = null, $pass_id = null, $connection = null, $semiparse_mode = false, $preparse_mode = false, $is_all_semihtml = false, $structure_sweep = false, $check_only = false, $highlight_bits = null, $on_behalf_of_member = null)
{
    $matches = array();
    if (preg_match('#^\{\!([A-Z\_]+)\}$#', $comcode, $matches) != 0) {
        return do_lang_tempcode($matches[1]);
    }

    if ($semiparse_mode) {
        $wrap_pos = 100000;
    }

    $attachments = (count($_FILES) != 0);
    foreach ($_POST as $key => $value) {
        if (is_integer($key)) {
            $key = strval($key);
        }

        if (preg_match('#^hidFileID\_#i', $key) != 0) {
            $attachments = true;
        }
    }
    if ((!$attachments || ($GLOBALS['IN_MINIKERNEL_VERSION'])) && (preg_match('#^[\w\d\-\_\(\) \.,:;/"\!\?]*$#'/*NB: No apostophes allowed in here, as they get changed by escape_html and can interfere then with apply_emoticons*/, $comcode) != 0) && (strpos($comcode, '  ') === false) && (strpos($comcode, '://') === false) && (strpos($comcode, '--') === false) && (get_page_name() != 'search')) {
        if (running_script('stress_test_loader')) {
            return make_string_tempcode(escape_html($comcode));
        }
        return make_string_tempcode(apply_emoticons(escape_html($comcode)));
    }

    require_code('comcode_renderer');
    $long = (strlen($comcode) > 1000);
    if ($long) {
        cms_profile_start_for('comcode_to_tempcode/LONG');
    }
    $ret = _comcode_to_tempcode($comcode, $source_member, $as_admin, $wrap_pos, $pass_id, $connection, $semiparse_mode, $preparse_mode, $is_all_semihtml, $structure_sweep, $check_only, $highlight_bits, $on_behalf_of_member);
    if ($long) {
        cms_profile_end_for('comcode_to_tempcode/LONG', is_null($source_member) ? '' : ('owned by member #' . strval($source_member)));
    }
    return $ret;
}

/**
 * Strip out any Comcode from this "plain text". Useful for semantic text is wanted but where Comcode is used as "the next best thing" we have.
 *
 * @param  string $text Plain-text/Comcode
 * @param  boolean $for_extract Whether this is for generating an extract that does not need to be fully comprehended (i.e. favour brevity)
 * @return string Purified plain-text
 */
function strip_comcode($text, $for_extract = false)
{
    if ($text == '' || preg_match('#^[\w\d\-\_\(\) \.,:;/"\'\!\?]*$$#', $text) != 0) {
        return $text; // Optimisation
    }

    require_code('mail');
    if (function_exists('comcode_to_clean_text')) {// For benefit of installer, which disables mail.php
        $text = comcode_to_clean_text($text, $for_extract);
    }

    if (strpos($text, '[') !== false) {
        global $VALID_COMCODE_TAGS;
        foreach (array_keys($VALID_COMCODE_TAGS) as $tag) {
            if ($tag == 'i') {
                $text = preg_replace('#\[/?' . $tag . '\]#', '', $text);
            } else {
                $text = preg_replace('#\[/?' . $tag . '[^\]]*\]#', '', $text);
            }
        }
    }

    if (strpos($text, '&') !== false) {
        $text = str_replace(array('&hellip;', '&middot;', '&ndash;', '&mdash;'), array('...', '-', '-', '-'), $text);
    }

    return $text;
}
