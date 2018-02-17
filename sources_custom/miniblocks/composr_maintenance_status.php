<?php

i_solemnly_declare(I_UNDERSTAND_SQL_INJECTION | I_UNDERSTAND_XSS | I_UNDERSTAND_PATH_INJECTION);

$myfile = fopen(get_file_base() . '/data/maintenance_status.csv', 'rb');
// TODO: #3032 (must default charset to utf-8 if no BOM though)

$header_row = fgetcsv($myfile); // Header row
unset($header_row[0]);

$rows = array();
while (($row = fgetcsv($myfile)) !== false) {
    $codename = $row[0];
    unset($row[0]);
    $rows[$codename] = array('DATA' => array_values($row), 'CODENAME' => $codename);
}
ksort($rows, SORT_NATURAL | SORT_FLAG_CASE);

fclose($myfile);

return do_template('BLOCK_COMPOSR_MAINTENANCE_STATUS', array(
    '_GUID' => '8c7ba3e7a2c667e7eebf36b9fe067868',
    'HEADER_ROW' => array_values($header_row),
    'ROWS' => $rows,
));
