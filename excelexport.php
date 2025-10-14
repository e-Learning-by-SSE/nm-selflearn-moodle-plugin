<?php
/**
 * SelfLearn Excel Export - German CSV Format
 * Exports progress report in German CSV format (semicolon separator, comma decimal)
 *
 * @package   selflearn
 * @copyright 2024 University of Hildesheim, Software Systems Engineering
 * @license   Apache License 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

require_once("../../config.php");

require_login();

$id = required_param('id', PARAM_INT); // Course Id.

// Get cached data
$cache = cache::make('mod_selflearn', 'report_cache');
$data = $cache->get($id);

// Check if data exists
if ($data === false || empty($data->data)) {
    print_error('error::no_data_available', 'selflearn');
}

// Export CSV
export_german_csv($data);

/**
 * Export data in German CSV format
 * - Semicolon (;) as column separator
 * - Comma (,) as decimal separator
 * - UTF-8 with BOM for Excel compatibility
 * 
 * @param object $data Cached report data
 */
function export_german_csv($data) {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="SelfLearn-Report-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen("php://output", "w");
    
    // Add UTF-8 BOM for Excel to recognize UTF-8 encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers to the CSV
    $header = array_merge(
        [
            get_string("report::first_name", "selflearn"), 
            get_string("report::last_name", "selflearn"), 
            get_string("report::username", "selflearn")
        ], 
        $data->courses, 
        [get_string("report::average", "selflearn")]
    );
    fputcsv($output, $header, ";", '"');  // Added quote character
    
    // Export data rows
    foreach ($data->data as $row) {
        $formatted_row = format_row_for_german_csv($row);
        fputcsv($output, $formatted_row, ";", '"');  // Added quote character
    }
    
    fclose($output);
    exit;
}

/**
 * Format row data for German CSV format
 * Converts percentage values to German decimal format (comma separator)
 * 
 * @param array $row Single data row
 * @return array Formatted row
 */
function format_row_for_german_csv($row) {
    $formatted = [];
    
    foreach ($row as $index => $value) {
        if ($index < 3) {
            // Names and username - keep as is
            $formatted[] = $value;
        } else {
            // Progress values
            if ($value === '---' || $value === 'N/A' || $value === null) {
                $formatted[] = '---';
            } else {
                // Always format with 1 decimal place and German comma
                $number = floatval($value);
                $formatted[] = number_format($number, 1, ',', '');
            }
        }
    }
    
    return $formatted;
}
?>