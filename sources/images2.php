<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: shell_exec*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * (Helper for ensure_thumbnail).
 *
 * @param  URLPATH $full_url The full URL to the image which will-be/is thumbnailed
 * @param  URLPATH $thumb_url The URL to the thumbnail (blank: no thumbnail yet)
 * @param  ID_TEXT $thumb_dir The directory, relative to the Composr install's uploads directory, where the thumbnails are stored. MINUS "_thumbs"
 * @param  ID_TEXT $table The name of the table that is storing what we are doing the thumbnail for
 * @param  AUTO_LINK $id The ID of the table record that is storing what we are doing the thumbnail for
 * @param  ID_TEXT $thumb_field_name The name of the table field where thumbnails are saved
 * @param  ?integer $thumb_width The thumbnail width to use (null: default)
 * @param  boolean $only_make_smaller Whether to apply a 'never make the image bigger' rule for thumbnail creation (would affect very small images)
 * @return URLPATH The URL to the thumbnail
 */
function _ensure_thumbnail($full_url, $thumb_url, $thumb_dir, $table, $id, $thumb_field_name = 'thumb_url', $thumb_width = null, $only_make_smaller = false)
{
    if ($thumb_width === null) {
        $thumb_width = intval(get_option('thumb_width'));
    }

    $is_vector = is_image($full_url, IMAGE_CRITERIA_VECTOR);

    if ($is_vector) {
        $thumb_url = $full_url;
    } else {
        require_code('urls2');
        list($thumb_path, $thumb_url) = find_unique_path('uploads/' . $thumb_dir . '_thumbs', rawurldecode(basename($full_url)), true);
    }

    // Update database
    if ((substr($table, 0, 2) == 'f_') && (get_forum_type() == 'cns')) {
        $GLOBALS['FORUM_DB']->query_update($table, array($thumb_field_name => $thumb_url), array('id' => $id), '', 1);
    } else {
        $GLOBALS['SITE_DB']->query_update($table, array($thumb_field_name => $thumb_url), array('id' => $id), '', 1);
    }

    if (!$is_vector) {
        // Do thumbnail conversion
        if (is_video($full_url, true)) {
            require_code('galleries2');
            create_video_thumb($full_url, $thumb_path);
        } else {
            $thumb_url = convert_image($full_url, $thumb_path, -1, -1, intval($thumb_width), false);
        }
    }

    // Return
    if (url_is_local($thumb_url)) {
        $thumb_url = get_custom_base_url() . '/' . $thumb_url;
    }
    return $thumb_url;
}

/**
 * Resize an image to the specified size, but retain the aspect ratio. Has some advanced thumbnailing options.
 * This function works as a higher-level front-end to _convert_image. It doesn't deal in direct filepaths and error responses, it tries it's best and with additional higher level functionality.
 * See tut_tempcode.txt's explanation of the {$THUMBNAIL,...} symbol for a more detailed explanation.
 *
 * @param  URLPATH $orig_url URL to generate thumbnail from
 * @param  ?string $dimensions A dimension string, may be a number, or 2 numbers separated by "x" (null: default thumbnail size)
 * @param  PATH $output_dir Output directory
 * @param  ?string $filename Core filename to use within the overall output filename (null: auto-generate using $orig_url)
 * @param  ?URLPATH $fallback_image Fallback URL if we fail (null: use $orig_url)
 * @param  string $algorithm Algorithm to use
 * @set box width height crop pad pad_horiz_crop_horiz pad_vert_crop_vert
 * @param  string $where
 * @set start end both start_if_vertical start_if_horizontal end_if_vertical end_if_horizontal
 * @param  ?string $background Background colour to use for padding, RGB/RGBA style and the "#" may be omitted -- or 'none' (null: choose the average colour in the image)
 * @param  boolean $only_make_smaller Only ever make the output smaller than the source image, no blowing small images up
 * @return URLPATH Generated thumbnail
 *
 * @ignore
 */
function convert_image_plus($orig_url, $dimensions = null, $output_dir = 'uploads/auto_thumbs', $filename = null, $fallback_image = null, $algorithm = 'box', $where = 'both', $background = null, $only_make_smaller = false)
{
    cms_profile_start_for('convert_image_plus');

    disable_php_memory_limit();

    if (url_is_local($orig_url)) {
        $orig_url = get_custom_base_url() . '/' . $orig_url;
    }

    if ($dimensions === null) {
        $dimensions = get_option('thumb_width');
    }
    $exp_dimensions = explode('x', $dimensions);
    if (!is_numeric($exp_dimensions[0])) {
        $exp_dimensions[0] = '-1';
    }
    if (count($exp_dimensions) == 1) {
        $exp_dimensions[1] = '-1';
    } else {
        if (is_numeric($exp_dimensions[1])) {
            if ($exp_dimensions[1] == '0') {
                $exp_dimensions[1] = '1';
            }
        } else {
            $exp_dimensions[1] = '-1';
        }
    }

    if ($filename === null) {
        $ext = get_file_extension($orig_url);
        if (!is_image('example.' . $ext, IMAGE_CRITERIA_WEBSAFE, true)) {
            $ext = 'png';
        }
        $filename = url_to_filename($orig_url);
        if (substr($filename, -4) != '.' . $ext) {
            $filename .= '.' . $ext;
        }
    }

    if ($fallback_image === null) {
        $fallback_image = $orig_url;
    }

    $file_prefix = '/' . $output_dir . '/thumb__' . $dimensions . '__' . $algorithm . '__' . $where;
    if ($background !== null) {
        $file_prefix .= '__' . str_replace('#', '', $background);
    }
    $save_path = get_custom_file_base() . $file_prefix . '__' . $filename;
    $thumbnail_url = get_custom_base_url() . $file_prefix . '__' . rawurlencode($filename);

    // Only bother calculating the image if we've not already made one with these options
    if (is_file($save_path)) {
        cms_profile_end_for('convert_image_plus', $orig_url);
        return $thumbnail_url;
    }

    // Can't operate without GD
    if (!function_exists('imagetypes')) {
        cms_profile_end_for('convert_image_plus', $orig_url);
        return $fallback_image;
    }

    // Branch based on the type of thumbnail we're making
    switch ($algorithm) {
        case 'crop':
        case 'pad':
        case 'pad_horiz_crop_horiz':
        case 'pad_vert_crop_vert':
            // We need to shrink a bit and crop/pad...

            require_code('files');

            // Find dimensions of the source
            $sizes = cms_getimagesize($orig_url);
            if ($sizes === false) {
                cms_profile_end_for('convert_image_plus', $orig_url);
                return $fallback_image;
            }
            list($source_x, $source_y) = $sizes;

            // Work out aspect ratios
            $source_aspect = floatval($source_x) / floatval($source_y);
            $destination_aspect = floatval($exp_dimensions[0]) / floatval($exp_dimensions[1]);

            // NB: We will test the scaled sizes, rather than the ratios directly, so that differences too small to affect the integer dimensions will be tolerated.

            // We only need to crop/pad if the aspect ratio differs from what we want
            if ($source_aspect > $destination_aspect) {
                // The image is wider than the output

                if (($algorithm == 'crop') || ($algorithm == 'pad_horiz_crop_horiz')) {
                    // Is it too wide, requiring cropping?
                    $scale_to = floatval($source_y) / floatval($exp_dimensions[1]);
                    $will_modify_image = intval(round(floatval($source_x) / $scale_to)) != intval($exp_dimensions[0]);
                } else {
                    // Is the image too short, requiring padding?
                    $scale_to = floatval($source_x) / floatval($exp_dimensions[0]);
                    $will_modify_image = intval(round(floatval($source_y) / $scale_to)) != intval($exp_dimensions[1]);
                }
            } elseif ($source_aspect < $destination_aspect) {
                // The image is taller than the output

                if (($algorithm == 'crop') || ($algorithm == 'pad_vert_crop_vert')) {
                    // Is it too tall, requiring cropping?
                    $scale_to = floatval($source_x) / floatval($exp_dimensions[0]);
                    $will_modify_image = intval(round(floatval($source_y) / $scale_to)) != intval($exp_dimensions[1]);
                } else {
                    // Is the image too narrow, requiring padding?
                    $scale_to = floatval($source_y) / floatval($exp_dimensions[1]);
                    $will_modify_image = intval(round(floatval($source_x) / $scale_to)) != intval($exp_dimensions[0]);
                }
            } else {
                // They're the same, within the tolerances of floating point arithmentic. Just scale it.
                $will_modify_image = false;
            }

            // We have a special case here, since we can "pad" an image with nothing, i.e. shrink it to fit within the output dimensions and just leave the output file potentially less wide/high than those. This means we don't need to modify the image contents either, just scale it
            if (($algorithm == 'pad' || $algorithm == 'pad_horiz_crop_horiz' || $algorithm == 'pad_vert_crop_vert') && ($where == 'both') && ($background === 'none')) {
                $will_modify_image = false;
            }

            // Now do the cropping, padding and scaling
            if ($will_modify_image) {
                $thumbnail_url = @_convert_image($orig_url, $save_path, intval($exp_dimensions[0]), intval($exp_dimensions[1]), -1, false, null, false, $only_make_smaller, array('type' => $algorithm, 'background' => $background, 'where' => $where, 'scale_to' => $scale_to));
            } else {
                // Just resize
                $thumbnail_url = @_convert_image($orig_url, $save_path, intval($exp_dimensions[0]), intval($exp_dimensions[1]), -1, false, null, false, $only_make_smaller);
            }

        case 'width':
        case 'height':
            // We just need to scale to the given dimension
            $thumbnail_url = @_convert_image($orig_url, $save_path, ($algorithm == 'width') ? intval($exp_dimensions[0]) : -1, ($algorithm == 'height') ? intval($exp_dimensions[1]) : -1, -1, false, null, false, $only_make_smaller);
            break;

        case 'box':
        default:
            // We just need to scale to the given dimension
            $thumbnail_url = @_convert_image($orig_url, $save_path, -1, -1, intval($exp_dimensions[0]), false, null, false, $only_make_smaller);
            break;
    }

    cms_profile_end_for('convert_image_plus', $orig_url);

    return $thumbnail_url;
}

/**
 * (Helper for convert_image / convert_image_plus).
 *
 * @param  URLPATH $from The URL to the image to resize. May be either relative or absolute
 * @param  PATH $to The file path (including filename) to where the resized image will be saved. May be changed by reference if it cannot save an image there for some reason
 * @param  integer $width The maximum width we want our new image to be (-1 means "don't factor this in")
 * @param  integer $height The maximum height we want our new image to be (-1 means "don't factor this in")
 * @param  integer $box_width This is only considered if both $width and $height are -1. If set, it will fit the image to a box of this dimension (suited for resizing both landscape and portraits fairly)
 * @param  boolean $exit_on_error Whether to exit Composr if an error occurs
 * @param  ?string $ext2 The file extension representing the file type to save with (null: same as our input file)
 * @param  boolean $using_path Whether $from was in fact a path, not a URL
 * @param  boolean $only_make_smaller Whether to apply a 'never make the image bigger' rule for thumbnail creation (would affect very small images)
 * @param  ?array $thumb_options This optional parameter allows us to specify cropping or padding for the image. See comments in the function. (null: no details passed)
 * @return URLPATH The thumbnail URL (blank: URL is outside of base URL)
 *
 * @ignore
 */
function _convert_image($from, &$to, $width, $height, $box_width = -1, $exit_on_error = true, $ext2 = null, $using_path = false, $only_make_smaller = false, $thumb_options = null)
{
    disable_php_memory_limit();

    if (!file_exists(dirname($to))) {
        require_code('files2');
        make_missing_directory(dirname($to));
    }

    // Load
    $ext = get_file_extension($from);
    if ($using_path) {
        if (!check_memory_limit_for($from, $exit_on_error)) {
            return $from;
        }
        if ($ext == 'svg') { // SVG is pass-through
            return $from;
        }
        $from_file = @file_get_contents($from);
        $exif = function_exists('exif_read_data') ? @exif_read_data($from) : false;
    } else {
        $file_path_stub = convert_url_to_path($from);
        if (!is_null($file_path_stub)) {
            if (!check_memory_limit_for($file_path_stub, $exit_on_error)) {
                return $from;
            }
            if ($ext == 'svg') { // SVG is pass-through
                return $from;
            }
            $from_file = @file_get_contents($file_path_stub);
            $exif = function_exists('exif_read_data') ? @exif_read_data($file_path_stub) : false;
        } else {
            $from_file = http_download_file($from, 1024 * 1024 * 20/*reasonable limit*/, false);
            if (is_null($from_file)) {
                $from_file = false;
                $exif = false;
            } else {
                if (!file_exists(dirname($to))) {
                    if (@mkdir(dirname($to), 0777)) {
                        fix_permissions(dirname($to));
                        sync_file(dirname($to));
                    } else {
                        intelligent_write_error(dirname($to));
                    }
                }

                $myfile = fopen($to, 'wb');
                fwrite($myfile, $from_file);
                fclose($myfile);
                fix_permissions($to);
                sync_file($to);
                $exif = function_exists('exif_read_data') ? @exif_read_data($to) : false;
                if ($ext == 'svg') { // SVG is pass-through
                    return $from;
                }
            }
        }
    }
    if ($from_file === false) {
        if ($exit_on_error) {
            warn_exit(do_lang_tempcode('CANNOT_ACCESS_URL', escape_html($from)));
        }
        require_code('site');
        if (get_value('no_cannot_access_url_messages') !== '1') {
            attach_message(do_lang_tempcode('CANNOT_ACCESS_URL', escape_html($from)), 'warn');
        }
        return $from;
    }

    $source = @imagecreatefromstring($from_file);
    if ($source === false) {
        if ($exit_on_error) {
            warn_exit(do_lang_tempcode('CORRUPT_FILE', escape_html($from)));
        }
        require_code('site');
        attach_message(do_lang_tempcode('CORRUPT_FILE', escape_html($from)), 'warn');
        return $from;
    }

    list($source, $reorientated) = adjust_pic_orientation($source, $exif);
    if ((!is_null($thumb_options)) || (!$only_make_smaller)) {
        unset($from_file);
    }

    //$source = remove_white_edges($source);    Not currently enabled, as PHP seems to have problems with alpha transparency reading

    // Derive actual width x height, for the given maximum box (maintain aspect ratio)
    $sx = imagesx($source);
    $sy = imagesy($source);

    // Fix bad parameters
    if ($width == 0) {
        $width = 1;
    }
    if ($height == 0) {
        $height = 1;
    }

    $red = null;

    if (is_null($thumb_options)) {
        // Simpler algorithm

        // If we're not sure if this is gonna stretch to fit a width or stretch to fit a height
        if (($width == -1) && ($height == -1)) {
            if ($sx > $sy) {
                $width = $box_width;
            } else {
                $height = $box_width;
            }
        }

        if (($width != -1) && ($height != -1)) {
            if ((floatval($sx) / floatval($width)) > (floatval($sy) / floatval($height))) {
                $_width = $width;
                $_height = intval($sy * ($width / $sx));
            } else {
                $_height = $height;
                $_width = intval($sx * ($height / $sy));
            }
        } elseif ($height == -1) {
            $_width = $width;
            $_height = intval($width / ($sx / $sy));
        } elseif ($width == -1) {
            $_height = $height;
            $_width = intval($height / ($sy / $sx));
        }
        if (($_width > $sx) && ($only_make_smaller)) {
            $_width = $sx;
            $_height = $sy;

            if (!$reorientated) {
                // We can just escape, nothing to do

                imagedestroy($source);

                if (($using_path) && ($from == $to)) {
                    return $from;
                }

                if ($using_path) {
                    copy($from, $to);
                } else {
                    $_to = @fopen($to, 'wb') or intelligent_write_error($to);
                    fwrite($_to, $from_file);
                    fclose($_to);
                }
                fix_permissions($to);
                sync_file($to);
                return _image_path_to_url($to);
            }
        }
        if ($_width < 1) {
            $_width = 1;
        }
        if ($_height < 1) {
            $_height = 1;
        }

        // Pad out options for imagecopyresized
        // $dst_im,$src_im,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h
        $dest_x = 0;
        $dest_y = 0;
        $source_x = 0;
        $source_y = 0;
    } else {
        // The ability to crop (ie. window-off a section of the image), and pad (ie. provide a background around the image).
        // We keep this separate to the above code because the algorithm is more complex.
        // For documentation of the $thumb_options see the tut_tempcode.txt's description of the {$THUMBNAIL,...} symbol.

        // Grab the dimensions we would get if we didn't crop or scale
        $wrong_x = intval(round(floatval($sx) / $thumb_options['scale_to']));
        $wrong_y = intval(round(floatval($sy) / $thumb_options['scale_to']));

        // Handle cropping here
        if (($thumb_options['type'] == 'crop') || (($thumb_options['type'] == 'pad_horiz_crop_horiz') && ($wrong_x > $width)) || (($thumb_options['type'] == 'pad_vert_crop_vert') && ($wrong_y > $height))) {
            // See which direction we're cropping in
            if (intval(round(floatval($sx) / $thumb_options['scale_to'])) != $width) {
                $crop_direction = 'x';
            } else {
                $crop_direction = 'y';
            }
            // We definitely have to crop, since $thumb_options only tells us to crop if it has to. Thus we know we're going to fill the output image.
            // The only question is with what part of the source image?

            // Get the amount we'll lose from the source
            if ($crop_direction == 'x') {
                $crop_off = intval(($sx - ($width * $thumb_options['scale_to'])));
            } elseif ($crop_direction == 'y') {
                $crop_off = intval(($sy - ($height * $thumb_options['scale_to'])));
            }

            // Now we see how much to chop off the start (we don't care about the end, as this will be handled by using an appropriate window size)
            $displacement = 0;
            if (($thumb_options['where'] == 'start') || (($thumb_options['where'] == 'start_if_vertical') && ($crop_direction == 'y')) || (($thumb_options['where'] == 'start_if_horizontal') && ($crop_direction == 'x'))) {
                $displacement = 0;
            } elseif (($thumb_options['where'] == 'end') || (($thumb_options['where'] == 'end_if_vertical') && ($crop_direction == 'y')) || (($thumb_options['where'] == 'end_if_horizontal') && ($crop_direction == 'x'))) {
                $displacement = intval(floatval($crop_off));
            } else {
                $displacement = intval(floatval($crop_off) / 2.0);
            }

            // Now we convert this to the right x and y start locations for the window
            $source_x = ($crop_direction == 'x') ? $displacement : 0;
            $source_y = ($crop_direction == 'y') ? $displacement : 0;

            // Now we set the width and height of our window, which will be scaled versions of the width and height of the output
            $sx = intval(($width * $thumb_options['scale_to']));
            $sy = intval(($height * $thumb_options['scale_to']));

            // We start at the origin of our output
            $dest_x = 0;
            $dest_y = 0;

            // and it is always the full size it can be (or else we'd be cropping too much)
            $_width = $width;
            $_height = $height;

        // Handle padding here
        } elseif ($thumb_options['type'] == 'pad' || (($thumb_options['type'] == 'pad_horiz_crop_horiz') && ($wrong_x < $width)) || (($thumb_options['type'] == 'pad_vert_crop_vert') && ($wrong_y < $height))) {
            // We definitely need to pad some excess space because otherwise $thumb_options would not call us.
            // Thus we need a background (can be transparent). Let's see if we've been given one.
            if (array_key_exists('background', $thumb_options) && !is_null($thumb_options['background'])) {
                if (substr($thumb_options['background'], 0, 1) == '#') {
                    $thumb_options['background'] = substr($thumb_options['background'], 1);
                }

                // We've been given a background, let's find out what it is
                if (strlen($thumb_options['background']) == 8) {
                    // We've got an alpha channel
                    $using_alpha = true;
                    $red_str = substr($thumb_options['background'], 0, 2);
                    $green_str = substr($thumb_options['background'], 2, 2);
                    $blue_str = substr($thumb_options['background'], 4, 2);
                    $alpha_str = substr($thumb_options['background'], 6, 2);
                } else {
                    // We've not got an alpha channel
                    $using_alpha = false;
                    $red_str = substr($thumb_options['background'], 0, 2);
                    $green_str = substr($thumb_options['background'], 2, 2);
                    $blue_str = substr($thumb_options['background'], 4, 2);
                }
                $red = intval($red_str, 16);
                $green = intval($green_str, 16);
                $blue = intval($blue_str, 16);
                if ($using_alpha) {
                    $alpha = intval($alpha_str, 16);
                }
            } else {
                // We've not got a background, so let's find a representative color for the image by resampling the whole thing to 1 pixel.
                $temp_img = imagecreatetruecolor(1, 1); // Make an image to map on to
                imagecopyresampled($temp_img, $source, 0, 0, 0, 0, 1, 1, $sx, $sy); // Map the source image on to the 1x1 image
                $rgb_index = imagecolorat($temp_img, 0, 0); // Grab the color index of the single pixel
                $rgb_array = imagecolorsforindex($temp_img, $rgb_index); // Get the channels for it
                $red = $rgb_array['red']; // Grab the red
                $green = $rgb_array['green']; // Grab the green
                $blue = $rgb_array['blue']; // Grab the blue

                // Sort out if we're using alpha
                $using_alpha = ((array_key_exists('alpha', $rgb_array)) && ($rgb_array['alpha'] > 0));
                if ($using_alpha) {
                    $alpha = 255 - ($rgb_array['alpha'] * 2 + 1);
                }

                // Destroy the temporary image
                imagedestroy($temp_img);
            }

            // Now we need to work out how much padding we're giving, and where

            // The axis
            if (intval(round(floatval($sx) / $thumb_options['scale_to'])) != $width) {
                $pad_axis = 'x';
            } else {
                $pad_axis = 'y';
            }

            // The amount
            if ($pad_axis == 'x') {
                $padding = intval(round(floatval($width) - (floatval($sx) / $thumb_options['scale_to'])));
            } else {
                $padding = intval(round(floatval($height) - (floatval($sy) / $thumb_options['scale_to'])));
            }

            // The distribution
            if (($thumb_options['where'] == 'start') || (($thumb_options['where'] == 'start_if_vertical') && ($pad_axis == 'y')) || (($thumb_options['where'] == 'start_if_horizontal') && ($pad_axis == 'x'))) {
                $pad_amount = 0;
            } elseif (($thumb_options['where'] == 'end') || (($thumb_options['where'] == 'end_if_vertical') && ($pad_axis == 'y')) || (($thumb_options['where'] == 'end_if_horizontal') && ($pad_axis == 'x'))) {
                $pad_amount = $padding;
            } else {
                $pad_amount = intval(floatval($padding) / 2.0);
            }

            // Now set all of the parameters needed for blitting our image $sx and $sy are fine, since they cover the whole image
            $source_x = 0;
            $source_y = 0;
            $_width = ($pad_axis == 'x') ? intval(round(floatval($sx) / $thumb_options['scale_to'])) : $width;
            $_height = ($pad_axis == 'y') ? intval(round(floatval($sy) / $thumb_options['scale_to'])) : $height;
            $dest_x = ($pad_axis == 'x') ? $pad_amount : 0;
            $dest_y = ($pad_axis == 'y') ? $pad_amount : 0;
        }
    }

    // Resample/copy
    $gd_version = get_gd_version();
    if ($gd_version >= 2.0) { // If we have GD2
        // Set the background if we have one
        if (!is_null($thumb_options) && !is_null($red)) {
            $dest = imagecreatetruecolor($width, $height);
            imagealphablending($dest, false);
            if ((function_exists('imagecolorallocatealpha')) && ($using_alpha)) {
                $back_col = imagecolorallocatealpha($dest, $red, $green, $blue, 127 - intval(floatval($alpha) / 2.0));
            } else {
                $back_col = imagecolorallocate($dest, $red, $green, $blue);
            }
            imagefilledrectangle($dest, 0, 0, $width, $height, $back_col);
            if (function_exists('imagesavealpha')) {
                imagesavealpha($dest, true);
            }
        } else {
            $dest = imagecreatetruecolor($_width, $_height);
            imagealphablending($dest, false);
            if (function_exists('imagesavealpha')) {
                imagesavealpha($dest, true);
            }
        }

        imagecopyresampled($dest, $source, $dest_x, $dest_y, $source_x, $source_y, $_width, $_height, $sx, $sy);
    } else {
        // Set the background if we have one
        if (!is_null($thumb_options) && !is_null($red)) {
            $dest = imagecreate($width, $height);

            $back_col = imagecolorallocate($dest, $red, $green, $blue);
            imagefill($dest, 0, 0, $back_col);
        } else {
            $dest = imagecreate($_width, $_height);
        }

        imagecopyresized($dest, $source, $dest_x, $dest_y, $source_x, $source_y, $_width, $_height, $sx, $sy);
    }

    // Clean up
    imagedestroy($source);

    // Save...

    if (is_null($ext2)) {
        $ext2 = get_file_extension($to);
    }
    // If we've got transparency then we have to save as PNG
    if (!is_null($thumb_options) && isset($using_alpha) && $using_alpha || $ext2 == '') {
        $ext2 = 'png';
    }

    if ((function_exists('imagepng')) && ($ext2 == 'png')) {
        if (strtolower(substr($to, -4)) != '.png') {
            $to .= '.png';
        }
        $test = @imagepng($dest, $to, 9);
        if (!$test) {
            if ($exit_on_error) {
                warn_exit(do_lang_tempcode('ERROR_IMAGE_SAVE', @strval($php_errormsg)));
            }
            require_code('site');
            attach_message(do_lang_tempcode('ERROR_IMAGE_SAVE', @strval($php_errormsg)), 'warn');
            return $from;
        } else {
            require_code('images_png');
            png_compress($to, $width <= 300 && $width != -1 || $height <= 300 && $height != -1 || $box_width <= 300 && $box_width != -1);
            fix_permissions($to);
            sync_file($to);
        }
    } elseif ((function_exists('imagejpeg')) && (($ext2 == 'jpg') || ($ext2 == 'jpeg'))) {
        $test = @imagejpeg($dest, $to, intval(get_option('jpeg_quality')));
        if (!$test) {
            if ($exit_on_error) {
                warn_exit(do_lang_tempcode('ERROR_IMAGE_SAVE', @strval($php_errormsg)));
            }
            require_code('site');
            attach_message(do_lang_tempcode('ERROR_IMAGE_SAVE', @strval($php_errormsg)), 'warn');
            return $from;
        } else {
            fix_permissions($to);
            sync_file($to);
        }
    } elseif ((function_exists('imagegif')) && ($ext2 == 'gif')) {
        $test = @imagegif($dest, $to);
        if (!$test) {
            if ($exit_on_error) {
                warn_exit(do_lang_tempcode('ERROR_IMAGE_SAVE', @strval($php_errormsg)));
            }
            require_code('site');
            attach_message(do_lang_tempcode('ERROR_IMAGE_SAVE', @strval($php_errormsg)), 'warn');
            return $from;
        } else {
            fix_permissions($to);
            sync_file($to);
        }
    } else {
        if ($exit_on_error) {
            warn_exit(do_lang_tempcode('UNKNOWN_FORMAT', escape_html($ext2)));
        }
        require_code('site');
        attach_message(do_lang_tempcode('UNKNOWN_FORMAT', escape_html($ext2)), 'warn');
        return $from;
    }

    // Clean up
    imagedestroy($dest);

    fix_permissions($to);
    sync_file($to);

    return _image_path_to_url($to);
}

/**
 * Convert an image path to a URL, as convert_image returns a URL not a path.
 *
 * @param  PATH $to_path Path
 * @return URLPATH URL
 */
function _image_path_to_url($to_path)
{
    $file_base = get_custom_file_base();
    if (substr($to_path, 0, strlen($file_base) + 1) != $file_base . '/') {
        //fatal_exit(do_lang_tempcode('INTERNAL_ERROR')); // Nothing in the code should be trying to generate a thumbnail outside the base directory
        return '';
    }

    $to_url = str_replace('%2F', '/', rawurlencode(substr($to_path, strlen($file_base) + 1)));
    return $to_url;
}

/**
 * Check we can load the given file, given our memory limit.
 *
 * @param  PATH $file_path The file path we are trying to load
 * @param  boolean $exit_on_error Whether to exit Composr if an error occurs
 * @return boolean Success status
 */
function check_memory_limit_for($file_path, $exit_on_error = true)
{
    $ov = ini_get('memory_limit');

    $_what_we_will_allow = get_value('real_memory_available_mb');
    $what_we_will_allow = is_null($_what_we_will_allow) ? null : (intval($_what_we_will_allow) * 1024 * 1024);

    if ((substr($ov, -1) == 'M') || (!is_null($what_we_will_allow))) {
        if (is_null($what_we_will_allow)) {
            $total_memory_limit_in_bytes = intval(substr($ov, 0, strlen($ov) - 1)) * 1024 * 1024;

            $what_we_will_allow = $total_memory_limit_in_bytes - memory_get_usage() - 1024 * 1024 * 8; // 8 is for 8MB extra space needed to finish off
        }

        $details = @getimagesize($file_path);
        if ($details !== false) { // Check it is not corrupt. If it is corrupt, we will give an error later
            $magic_factor = 3.0; /* factor of inefficency by experimentation */

            $channels = 4;//array_key_exists('channels', $details) ? $details['channels'] : 3; it will be loaded with 4
            $bits_per_channel = 8;//array_key_exists('bits', $details) ? $details['bits'] : 8; it will be loaded with 8
            $bytes = ($details[0] * $details[1]) * ($bits_per_channel / 8) * ($channels + 1) * $magic_factor;

            if ($bytes > floatval($what_we_will_allow)) {
                $max_dim = intval(sqrt(floatval($what_we_will_allow) / 4.0 / $magic_factor/*4 1 byte channels*/));

                // Can command line imagemagick save the day?
                $imagemagick = find_imagemagick();
                if ($imagemagick !== null) {
                    $shrink_command = $imagemagick . ' ' . escapeshellarg_wrap($file_path);
                    $shrink_command .= ' -resize ' . strval(intval(floatval($max_dim) / 1.5)) . 'x' . strval(intval(floatval($max_dim) / 1.5));
                    $shrink_command .= ' ' . escapeshellarg_wrap($file_path);
                    $err_cond = -1;
                    $output_arr = array();
                    if (php_function_allowed('shell_exec')) {
                        $err_cond = @shell_exec($shrink_command);
                        if (!is_null($err_cond)) {
                            return true;
                        }
                    }
                }

                $message = do_lang_tempcode('IMAGE_TOO_LARGE_FOR_THUMB', escape_html(integer_format($max_dim)), escape_html(integer_format($max_dim)));
                if (!$exit_on_error) {
                    attach_message($message, 'warn');
                } else {
                    warn_exit($message);
                }

                return false;
            }
        }
    }

    return true;
}

/**
 * Find the path to imagemagick.
 *
 * @return ?PATH Path to imagemagick (null: not found)
 */
function find_imagemagick()
{
    $imagemagick = '/usr/bin/convert';
    if (!@file_exists($imagemagick)) {
        $imagemagick = '/usr/local/bin/convert';
    }
    if (!@file_exists($imagemagick)) {
        $imagemagick = '/opt/local/bin/convert';
    }
    if (!@file_exists($imagemagick)) {
        $imagemagick = '/opt/cloudlinux/bin/convert';
    }
    if (!@file_exists($imagemagick)) {
        return null;
    }
    return $imagemagick;
}

/**
 * Adjust an image to take into account EXIF rotation.
 *
 * Based on a comment in:
 * http://stackoverflow.com/questions/3657023/how-to-detect-shot-angle-of-photo-and-auto-rotate-for-website-display-like-desk
 *
 * @param  resource $img GD image resource
 * @param  ~array                       $exif EXIF details (false: could not load)
 * @return array A pair: Adjusted GD image resource, Whether a change was made
 */
function adjust_pic_orientation($img, $exif)
{
    if ((function_exists('imagerotate')) && ($exif !== false) && (isset($exif['Orientation']))) {
        $orientation = $exif['Orientation'];
        if ($orientation != 1) {
            $mirror = false;
            $deg = 0;

            switch ($orientation) {
                case 2:
                    $mirror = true;
                    break;
                case 3:
                    $deg = 180;
                    break;
                case 4:
                    $deg = 180;
                    $mirror = true;
                    break;
                case 5:
                    $deg = 270;
                    $mirror = true;
                    break;
                case 6:
                    $deg = 270;
                    break;
                case 7:
                    $deg = 90;
                    $mirror = true;
                    break;
                case 8:
                    $deg = 90;
                    break;
            }

            if ($deg != 0) {
                $imgdest = imagerotate($img, floatval($deg), 0);
                imagedestroy($img);
                $img = $imgdest;
            }

            if ($mirror) {
                $width = imagesx($img);
                $height = imagesy($img);

                $src_x = $width - 1;
                $src_y = 0;
                $src_width = -$width;
                $src_height = $height;

                $imgdest = imagecreatetruecolor($width, $height);
                imagealphablending($imgdest, false);
                if (function_exists('imagesavealpha')) {
                    imagesavealpha($imgdest, true);
                }

                if (imagecopyresampled($imgdest, $img, 0, 0, $src_x, $src_y, $width, $height, $src_width, $src_height)) {
                    imagedestroy($img);
                    $img = $imgdest;
                }
            }

            return array($img, true);
        }
    }
    return array($img, false);
}

/**
 * Remove white/transparent edges from an image.
 *
 * @param  resource $img GD image resource
 * @return resource Trimmed image
 */
function remove_white_edges($img)
{
    $width = imagesx($img);
    $height = imagesy($img);

    // From top
    $remove_from_top = 0;
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $_color = imagecolorat($img, $x, $y);
            if ($_color != 0) {
                break 2;
            }
        }
        $remove_from_top++;
    }

    // From bottom
    $remove_from_bottom = 0;
    for ($y = $height - 1; $y >= 0; $y--) {
        for ($x = 0; $x < $width; $x++) {
            $color = imagecolorsforindex($img, imagecolorat($img, $x, $y));
            if (($color['red'] != 0 || $color['green'] != 0 || $color['blue'] != 0) && ($color['alpha'] != 127)) {
                break 2;
            }
        }
        $remove_from_bottom++;
    }

    // From left
    $remove_from_left = 0;
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $color = imagecolorsforindex($img, imagecolorat($img, $x, $y));
            if (($color['red'] != 0 || $color['green'] != 0 || $color['blue'] != 0) && ($color['alpha'] != 127)) {
                break 2;
            }
        }
        $remove_from_left++;
    }

    // From right
    $remove_from_right = 0;
    for ($x = $width - 1; $x >= 0; $x--) {
        for ($y = 0; $y < $height; $y++) {
            $color = imagecolorsforindex($img, imagecolorat($img, $x, $y));
            if (($color['red'] != 0 || $color['green'] != 0 || $color['blue'] != 0) && ($color['alpha'] != 127)) {
                break 2;
            }
        }
        $remove_from_right++;
    }

    // Any changes?
    if ($remove_from_top + $remove_from_bottom + $remove_from_left + $remove_from_right == 0 || $remove_from_left == $width || $remove_from_top == $height) {
        return $img;
    }

    // Do trimming...

    $target_width = $width - $remove_from_left - $remove_from_right;
    $target_height = $height - $remove_from_top - $remove_from_bottom;

    $imgdest = imagecreatetruecolor($target_width, $target_height);
    imagealphablending($imgdest, false);
    if (function_exists('imagesavealpha')) {
        imagesavealpha($imgdest, true);
    }

    if (imagecopyresampled($imgdest, $img, 0, 0, $remove_from_left, $remove_from_top, $target_width, $target_height, $target_width, $target_height)) {
        imagedestroy($img);
        $img = $imgdest;
    }

    return $img;
}

/**
 * Get the version number of GD on the system. It should only be called if GD is known to be on the system, and in use
 *
 * @return float The version of GD installed
 */
function get_gd_version()
{
    $info = gd_info();
    $matches = array();
    if (preg_match('#(\d(\.|))+#', $info['GD Version'], $matches) != 0) {
        $version = $matches[0];
    } else {
        $version = $info['version'];
    }
    return floatval($version);
}

