<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    classified_ads
 */

i_solemnly_declare(I_UNDERSTAND_SQL_INJECTION | I_UNDERSTAND_XSS | I_UNDERSTAND_PATH_INJECTION);

if (!addon_installed('classified_ads')) {
    return paragraph(do_lang_tempcode('MISSING_ADDON', escape_html('classified_ads')), 'y1h3lkkxrcy2mjehmt7na6n0p6c4ed23', 'red-alert');
}

if (!addon_installed('catalogues')) {
    return paragraph(do_lang_tempcode('MISSING_ADDON', escape_html('catalogues')), 'xosodjlsl900rswpx7vw68a7xg2tgjod', 'red-alert');
}
if (!addon_installed('ecommerce')) {
    return paragraph(do_lang_tempcode('MISSING_ADDON', escape_html('ecommerce')), 'hf9170z7ri5420765w7qecb5lkqr7i4t', 'red-alert');
}

require_lang('classifieds');

if (!isset($map['param'])) {
    $map['param'] = 'classifieds';
}
$catalogue_name = $map['param'];

$show_free = ((isset($map['show_free'])) && ($map['show_free'] == '1'));

$prices = $GLOBALS['SITE_DB']->query_select('ecom_classifieds_prices', array('*'), array('c_catalogue_name' => $catalogue_name), 'ORDER BY c_price');

$data = array();
foreach ($prices as $price) {
    if ((!$show_free) && ($price['c_price'] == 0.0)) {
        continue;
    }

    $data[] = array(
        'PRICE' => float_to_raw_string($price['c_price']),
        'CURRENCY' => get_option('currency'),
        'LABEL' => get_translated_text($price['c_label']),
    );
}

echo static_evaluate_tempcode(do_template('CLASSIFIEDS', array('_GUID' => '7216f4a435534cc609344101c8ea3031', 'DATA' => $data)));
