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

die();