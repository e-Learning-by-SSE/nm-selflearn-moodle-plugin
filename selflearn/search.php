<?php
// Integriere das Moodle-Framework
require_once('../../config.php');
require_once('lib.php');

// Hole die Suchanfrage aus der AJAX-Anfrage
$search_query = optional_param('search_query', '', PARAM_TEXT);

if ($search_query !== '') {
    $courses = selflearn_list_courses(null, $search_query);

    echo json_encode($courses);
}
// if ($search_query !== '') {
//     global $DB;
    
//     // Beispiel einer SQL-Abfrage, um nach bestimmten Einträgen zu suchen
//     $lms_url = "https://staging.sse.uni-hildesheim.de:9011/skill-repositories";
//     $search_params = [
//         'title' => $title,
//         'pageSize' => 10,
//         'page' => 0,

//     ];
//     $data = json_encode($search_params);
//     $response = $curl->post($lms_url, $search_params);
//     $data = json_decode($response, true);
    
//     $courses = [];
//     if (json_last_error() != JSON_ERROR_NONE && !is_array($data)) {
//         $error_message = get_string('error_rest_api_blocked', 'selflearn');
//         $notification = new notification($error_message, notification::NOTIFY_ERROR);
//         echo $OUTPUT->render($notification);
//     } else {
//         foreach ($data["repositories"] as $course) {
//             $courses[] = [
//                 'id' => $course['id'],
//                 'name' => $course['name']
//             ];
//         }
//     }

//     echo json_encode($courses);
// }

die();