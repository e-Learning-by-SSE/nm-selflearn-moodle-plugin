<?php
require_once("../../config.php");
require_once("lib.php");


$id = required_param('id', PARAM_INT); // Course Id.

// Access + permissions.
$course = get_course($id);
// Verify the user has access to view the course
require_login($id);

$context = context_course::instance($id);
require_capability('mod/selflearn:viewgrades', $context);


// Fetch the enrolled users in the course
$enrolled_users = get_enrolled_users($context);
$sortby = optional_param('sortby', '0', PARAM_INT);
$sortorder = optional_param('sortorder', 'ASC', PARAM_ALPHA);
// // Sort the enrolled users by first name or last name, depending on the user's choice
// if ($sortby == 'firstname') {
//     usort($enrolled_users, function($a, $b) use ($sortorder) {
//         return $sortorder == 'ASC' ? strcmp($a->firstname, $b->firstname) : strcmp($b->firstname, $a->firstname);
//     });
// } elseif ($sortby == 'lastname') {
//     usort($enrolled_users, function($a, $b) use ($sortorder) {
//         return $sortorder == 'ASC' ? strcmp($a->lastname, $b->lastname) : strcmp($b->lastname, $a->lastname);
//     });
// }

// Determine the next sort order for the links
$nextsortorder = ($sortorder == 'ASC') ? 'DESC' : 'ASC';

// Fetch course module information
$modinfo = get_fast_modinfo($id);

// Specify the module name of the current plugin (e.g., 'mymodule' for mod_mymodule)
$modulename = 'selflearn';

// Get all activities in the course for the specified plugin
global $DB;
$courses = [];
$instances = [];
foreach ($modinfo->get_instances_of($modulename) as $cm) {
    if ($cm->uservisible) {
        $info = $DB->get_record('selflearn', ['id' => $cm->instance]);
        // Only include visible activities
        $instances[] = [
            'name' => $cm->name,
            'id' => $cm->id,
            'url' => new moodle_url('/mod/' . $modulename . '/view.php', ['id' => $cm->id]),
            'slug' => $info->slug
        ];
        $courses[] = ["slug" => $info->slug, "id" => $info->id];
    }
}

// Query progress for all enrolled students for all SelfLearn activities
$studentRoleId = 5;  // Student role ID (typically 5)
$students = [];
foreach ($enrolled_users as $user) {
    if (user_has_role_assignment($user->id, $studentRoleId, $context->id)) {
        $student = new stdClass();
        $student->username = $user->username;
        $student->firstname = $user->firstname;
        $student->lastname = $user->lastname;
        $students[] = $student;
    }
}

if (empty($students)) {
    // No students found, display a message
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("report::no_students", "selflearn"));
    echo $OUTPUT->footer();
    die();
}
if (empty($courses)) {
    // No selflearn activities defined, display a message
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("report::no_activities", "selflearn"));
    echo $OUTPUT->footer();
    die();
}

$progress = selflearn_query_progress($students, $courses);

$merged = [];
foreach ($students as $user) {
    // Initialisiere ein Array mit den Grundinformationen des Benutzers
    $combined = [
        $user->firstname,
        $user->lastname
    ];

    // Suche in $values nach der ID (username)
    foreach ($courses as $course) {
        $id = $course['slug'];
        $value = $progress[$user->username][$id];
        $combined[] = $value;
    }
    $combined[] = $progress[$user->username]['total_average'];

    // Füge das kombinierte Ergebnis zum finalen Array hinzu
    $merged[] = $combined;
}

usort($merged, function($a, $b) use ($sortby, $sortorder) {
    $index = $sortby;
    if ($index == 0 || $index == 1) {
        return $sortorder == 'ASC' ? strcmp($a[$index], $b[$index]) : strcmp($b[$index], $a[$index]);
    }
    return $sortorder == 'ASC' ? $a[$index] <=> $b[$index] : $b[$index] <=> $a[$index];
});

// Das Ergebnis anzeigen
$debug = print_r($merged, true);
error_log($debug);

// Page setup.
global $PAGE, $OUTPUT;
$pagetitle = get_string("report::title", "selflearn");
$pageurl = new moodle_url('/mod/selflearn/coursereport.php', ['id' => $id, 'sortby' => $sortby, 'sortorder' => $sortorder]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));
$PAGE->add_body_class('limitedwidth');


// Display.
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

echo '<table class="selflearn_table">';
echo '<tr>';
$fistNameUrl = new moodle_url('/mod/selflearn/coursereport.php', ['id' => $id, 'sortby' => 0, 'sortorder' => $nextsortorder]);
echo '<th><a href="' . $fistNameUrl . '">' . get_string("report::first_name", "selflearn") . '</a></th>';
echo '<th><a href="?id=' . $id . '&sortby=1&sortorder=' . $nextsortorder . '">' . get_string("report::last_name", "selflearn") . '</a></th>';
for ($i = 0; $i < count($instances); $i++) {
    echo '<th><a href="?id=' . $id . '&sortby=' . ($i + 2) . '&sortorder=' . $nextsortorder . '">' . $instances[$i]["name"] .'</a></th>';
}
echo '<th><a href="?id=' . $id . '&sortby=' . (count($instances) + 2) . '&sortorder=' . $nextsortorder . '">Σ</a></th>';
echo '</tr>';
// Loop through each enrolled user and display their data in the table
foreach ($merged as $entry) {
    echo '<tr>';
    for ($i = 0; $i < count($entry); $i++) {
        echo '<td>' . $entry[$i] . '</td>';
    }
    // echo '<td>' . $entry[0] . '</td>';
    // echo '<td>' . $entry[1] . '</td>';
    // foreach ($instances as $instance) {
    //     echo '<td>' . $progress[$user->username][$instances['slug']] . ' %</td>';
    // }
    // echo '<td>' . $progress[$user->username]["total_average"] . ' %</td>';
    echo '</tr>';
}
echo '</table>';

echo $OUTPUT->footer();