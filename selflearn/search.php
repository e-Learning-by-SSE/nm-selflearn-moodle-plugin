<?php
// Integriere das Moodle-Framework
require_once('../../config.php');
require_once('lib.php');


// Hole die Suchanfrage aus der AJAX-Anfrage
$search_query = optional_param('search', '', PARAM_TEXT);
$include_from_all_authors = optional_param('toggle', '', PARAM_INT);

if ($search_query !== '') {
    global $USER;
    $username = $include_from_all_authors == 1 ? $USER->username : null;

    $client = new restclient();
    $courses = $client->selflearn_list_courses($username, $search_query);

    echo json_encode($courses);
}

die();