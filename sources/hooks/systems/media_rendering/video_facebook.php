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
 * Hook class.
 */
class Hook_media_rendering_video_facebook extends Media_renderer_with_fallback
{
    /**
     * Get the label for this media rendering type.
     *
     * @return string The label
     */
    public function get_type_label()
    {
        require_lang('comcode');
        return do_lang('MEDIA_TYPE_' . preg_replace('#^Hook_media_rendering_#', '', __CLASS__));
    }

    /**
     * Find the media types this hook serves.
     *
     * @return integer The media type(s), as a bitmask
     */
    public function get_media_type()
    {
        return MEDIA_TYPE_VIDEO;
    }

    /**
     * See if we can recognise this mime type.
     *
     * @param  ID_TEXT $mime_type The mime type
     * @return integer Recognition precedence
     */
    public function recognises_mime_type($mime_type)
    {
        return MEDIA_RECOG_PRECEDENCE_NONE;
    }

    /**
     * See if we can recognise this URL pattern.
     *
     * @param  URLPATH $url URL to pattern match
     * @return integer Recognition precedence
     */
    public function recognises_url($url)
    {
        // (Also see patterns defined in render)

        if (preg_match('#^https?://www\.facebook\.com/video/video\.php\?v=(\w+)#', $url) != 0) {
            return MEDIA_RECOG_PRECEDENCE_HIGH;
        }
        if (preg_match('#^https?://www\.facebook\.com/video\.php\?v=(\w+)#', $url) != 0) {
            return MEDIA_RECOG_PRECEDENCE_HIGH;
        }
        if (preg_match('#^https?://www\.facebook\.com/.*/videos/(.*/)?(\d+)/#', $url) != 0) {
            return MEDIA_RECOG_PRECEDENCE_HIGH;
        }
        if (preg_match('#^https?://www\.facebook\.com/photo\.php\?v=(\w+)#', $url) != 0) {
            return MEDIA_RECOG_PRECEDENCE_HIGH;
        }
        return MEDIA_RECOG_PRECEDENCE_NONE;
    }

    /**
     * If we can handle this URL, get the thumbnail URL.
     *
     * @param  URLPATH $src_url Video URL
     * @return ?string The thumbnail URL (null: no match).
     */
    public function get_video_thumbnail($src_url)
    {
        if ($this->recognises_url($src_url)) {
            $contents = http_download_file($src_url);

            $matches = array();
            if (preg_match('#addVariable\("thumb_url", "([^"]*)"\);#', $contents, $matches) != 0) {
                return rawurldecode(str_replace('\u0025', '%', $matches[1]));
            }
        }
        return null;
    }

    /**
     * Provide code to display what is at the URL, in the most appropriate way.
     *
     * @param  mixed $url URL to render
     * @param  mixed $url_safe URL to render (no sessions etc)
     * @param  array $attributes Attributes (e.g. width, height, length)
     * @param  boolean $as_admin Whether there are admin privileges, to render dangerous media types
     * @param  ?MEMBER $source_member Member to run as (null: current member)
     * @return Tempcode Rendered version
     */
    public function render($url, $url_safe, $attributes, $as_admin = false, $source_member = null)
    {
        $ret = $this->fallback_render($url, $url_safe, $attributes, $as_admin, $source_member, $url);
        if ($ret !== null) {
            return $ret;
        }

        if (is_object($url)) {
            $url = $url->evaluate();
        }

        $matches = array();
        if (preg_match('#^https?://www\.facebook\.com/video/video\.php\?v=(\w+)#', $url, $matches) != 0) {
            $attributes['remote_id'] = $matches[1];
        }
        if (preg_match('#^https?://www\.facebook\.com/video\.php\?v=(\w+)#', $url, $matches) != 0) {
            $attributes['remote_id'] = $matches[1];
        }
        if (preg_match('#^https?://www\.facebook\.com/.*/videos/(.*/)?(\d+)/#', $url, $matches) != 0) {
            $attributes['remote_id'] = $matches[2];
        }
        if (preg_match('#^https?://www\.facebook\.com/photo\.php\?v=(\w+)#', $url, $matches) != 0) {
            $attributes['remote_id'] = $matches[1];
        }

        return do_template('MEDIA_VIDEO_FACEBOOK', array('_GUID' => 'f9ba7e3b94d421791233cf3a34508ed7', 'HOOK' => 'video_facebook') + _create_media_template_parameters($url, $attributes, $as_admin, $source_member));
    }
}
