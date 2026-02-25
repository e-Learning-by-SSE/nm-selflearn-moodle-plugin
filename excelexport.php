<?php
/**
 * SelfLearn CSV Export
 * Exports progress report using Moodle's built-in CSV writer (locale-aware)
 *
 * @package   selflearn
 * @copyright 2024 University of Hildesheim, Software Systems Engineering
 * @license   Apache License 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

require_once("../../config.php");
require_once($CFG->libdir . '/csvlib.class.php');

require_login();

$id = required_param('id', PARAM_INT); // Course Id.

// Get cached data
$cache = cache::make('mod_selflearn', 'report_cache');
$data = $cache->get($id);

if ($data === false || empty($data->data)) {
    $course = get_course($id);
    $context = context_course::instance($id);
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/mod/selflearn/excelexport.php', ['id' => $id]));
    $PAGE->set_title(get_string('report::title', 'selflearn'));
    $PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));

    echo $OUTPUT->header();
    echo '<div class="alert alert-warning" role="alert">';
    echo '<h4>' . get_string('report::export_no_data_title', 'selflearn') . '</h4>';
    echo '<p>' . get_string('report::export_no_data_message', 'selflearn') . '</p>';
    $reporturl = new moodle_url('/mod/selflearn/coursereport.php', ['id' => $id, 'refresh' => 1]);
    echo html_writer::link($reporturl, get_string('report::refresh_data', 'selflearn'), ['class' => 'btn btn-primary mt-2']);
    echo '</div>';
    echo $OUTPUT->footer();
    die();
}

// Determine delimiter based on locale
$decsep = get_string('decsep', 'langconfig');
$delimiter = ($decsep === ',') ? 'semicolon' : 'comma';

// Create Moodle's built-in CSV writer
$csv = new csv_export_writer($delimiter);
$csv->set_filename('SelfLearn-Report');

// Add header row
$header = array_merge(
    [
        get_string("report::first_name", "selflearn"),
        get_string("report::last_name", "selflearn"),
        get_string("report::username", "selflearn")
    ],
    $data->courses,
    [get_string("report::average", "selflearn")]
);
$csv->add_data($header);

// Add data rows
foreach ($data->data as $row) {
    $formatted = [];
    foreach ($row as $index => $value) {
        if ($index < 3) {
            $formatted[] = $value;
        } else {
            if ($value === '---' || $value === 'N/A' || $value === null) {
                $formatted[] = '---';
            } else {
                $formatted[] = number_format(floatval($value), 1, $decsep, '');
            }
        }
    }
    $csv->add_data($formatted);
}
$csv->filename = 'SelfLearn-Report-' . date('Y-m-d') . '.csv';
$csv->download_file();