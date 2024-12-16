<?php
require_once("../../config.php");

require_login();

$id = required_param('id', PARAM_INT); // Course Id.
$cache = cache::make('mod_selflearn', 'report_cache');
$data = $cache->get($id);
export_csv($data);


function export_csv($data) {
    global $DB;
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="SelfLearn-Report.csv"');
    
    $output = fopen("php://output", "w");
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers to the CSV
    $header = array_merge([get_string("report::first_name", "selflearn"), get_string("report::last_name", "selflearn")], $data->courses, ['Σ']);
    fputcsv($output, $header, ";");
    
    foreach ($data->data as $row) {
        fputcsv($output, $row, ";");
    }
    
    fclose($output);
    exit;
}