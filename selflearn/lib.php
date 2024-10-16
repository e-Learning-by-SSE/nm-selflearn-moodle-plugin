<?php
/**
 * SelfLearn integration for Moodle.
 *
 * @package   selflearn
 * @copyright 2024 University of Hildesheim, Software Systems Engineering
 * @license   Apache License 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @author    Sascha El-Sharkawy
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/filelib.php');

use core\output\notification;

global $SELFLEARN_BASE_URL;
$SELFLEARN_BASE_URL = "https://staging.sse.uni-hildesheim.de:9011/";

global $SELFLEARN_API_OWN_COURSES;
$SELFLEARN_API_OWN_COURSES = $SELFLEARN_BASE_URL . "skill-repositories/byOwner/1";

global $SELFLEARN_API_COURSE_DATA;
$SELFLEARN_API_COURSE_DATA = $SELFLEARN_BASE_URL . "skill-repositories/byId/";

global $SELFLEARN_WEB_COURSE_URL;
$SELFLEARN_WEB_COURSE_URL = "https://www.uni-hildesheim.de/selflearn/courses/";

function selflearn_list_courses($userid) {
    GLOBAL $OUTPUT, $SELFLEARN_API_OWN_COURSES;

    $curl = new curl();
    $response = $curl->get($SELFLEARN_API_OWN_COURSES);
    $data = json_decode($response, true);

    $courses = [];
    if (json_last_error() != JSON_ERROR_NONE && !is_array($data)) {
        $error_message = get_string('error_rest_api_blocked', 'selflearn');
        $notification = new notification($error_message, notification::NOTIFY_ERROR);
        echo $OUTPUT->render($notification);
    } else {
        foreach ($data["repositories"] as $course) {
            $courses[] = [
                'id' => $course['id'],
                'name' => $course['name']
            ];
        }
    }

    return $courses;
}

function selflearn_get_course_title($courseid) {
    GLOBAL $OUTPUT, $SELFLEARN_API_COURSE_DATA;
    $lms_url = $SELFLEARN_API_COURSE_DATA . $courseid;

    $curl = new curl();
    $response = $curl->get($lms_url);
    $data = json_decode($response, true);

    $title = "";
    if (json_last_error() != JSON_ERROR_NONE && !is_array($data)) {
        $error_message = get_string('error_rest_api_blocked', 'selflearn');
        $notification = new notification($error_message, notification::NOTIFY_ERROR);
        echo $OUTPUT->render($notification);
    } else {
        $title = $data['name'];
    }

    return $title;
}

function selflearn_search_all_courses($title) {
    GLOBAL $OUTPUT;
    $lms_url = "https://staging.sse.uni-hildesheim.de:9011/skill-repositories";

    $curl = new curl();
    $search_params = [
        'title' => $title,
        'pageSize' => 10,
        'page' => 0,

    ];
    $data = json_encode($search_params);
    $response = $curl->post($lms_url, $search_params);
    $data = json_decode($response, true);

    $courses = [];
    if (json_last_error() != JSON_ERROR_NONE && !is_array($data)) {
        $error_message = get_string('error_rest_api_blocked', 'selflearn');
        $notification = new notification($error_message, notification::NOTIFY_ERROR);
        echo $OUTPUT->render($notification);
    } else {
        foreach ($data["repositories"] as $course) {
            $courses[] = [
                'id' => $course['id'],
                'name' => $course['name']
            ];
        }
    }

    echo $OUTPUT->render($response);

    return $courses;
}

function selflearn_query_progress($users, $courses) {
    $studentScores = [];

    foreach ($users as $user) {
        $totalScore = 0;
        $userScores = [];

        foreach ($courses as $course) {
            // Generate a random score between 0 and 100
            $score = rand(0, 100);
            $userScores[$course["slug"]] = $score;
            $totalScore += $score;
        }

        // Calculate the average score for the student
        $averageScore = $totalScore / count($courses);

        // Add the average score to the user's scores
        $userScores['total_average'] = round($averageScore, 2);

        // Store the user's scores in the result array
        $studentScores[$user->username] = $userScores;
    }

    return $studentScores;
}

/**
 * This function takes the input of the __mod_form.php__ and saves the data to the database,
 * when the teacher creates a new Selflearn activity.
 * This function is automatically called by Moodle.
 *
 * @see mod_selflearn_mod_form
 * @see selflearn_delete_instance
 *
 * @global object $USER The creating teacher
 * @global object $DB The database object
 * @global object $SELFLEARN_WEB_COURSE_URL Base URL for accessing courses by their slug
 * @param object $data The data from the form
 * @return int|bool true or the new id
 */
function selflearn_add_instance($data) {
    global $USER, $DB, $SELFLEARN_WEB_COURSE_URL;

    // Prepare data to be saved to the database
    $course_slug = $data->course_selection;
    $record = new stdClass();
    $record->userid = $USER->id;
    $record->course = $data->course;
    $record->slug = $course_slug;
    $record->url = $SELFLEARN_WEB_COURSE_URL . $course_slug;
    $record->name = selflearn_get_course_title($course_slug);

    // Insert the data into the selflearn_data table
    return $DB->insert_record('selflearn', $record);
}

/**
 * This function is called when the teacher deletes a Selflearn activity
 * to delete the activity from the data base.
 * However, this is not directly called when the recycle bin plugin is installed.
 * In this case, the activity is only hidden and this function will be called
 * through a cron job.
 * 
 * @global object $DB The database object
 * @param int $id The id of the activity to be deleted
 * @return int|bool The id of the deleted activity or false
 */
function selflearn_delete_instance($id) {
    global $DB;

    // Delete the record from the selflearn table
    $id = $DB->delete_records('selflearn', ['id' => $id]);

    return $id;
}

function selflearn_update_instance($data, $mform) {
    global $USER, $DB, $SELFLEARN_WEB_COURSE_URL;

    error_log("UPDATE INSTANCE");

    // Prepare data to be saved to the database
    $course_slug = $data->course_selection;
    $record = new stdClass();
    $record->id = $data->instance;
    $record->userid = $USER->id;
    $record->course = $data->course;
    $record->slug = $course_slug;
    $record->url = $SELFLEARN_WEB_COURSE_URL . $course_slug;
    $record->name = selflearn_get_course_title($course_slug);

    // Update
    $DB->update_record('selflearn', $record);

    return true;
}

/**
 * 
 *
 * @param settings_navigation $navigation The settings navigation object
 * @param stdClass $course The course
 * @param $context Course context
 * @return void
 */
function mod_selflearn_extend_navigation_course($navigation, $course, $context): void {  
    if (has_capability('mod/selflearn:viewgrades', $context)) {
        $url = new moodle_url('/mod/selflearn/coursereport.php', ['id' => $course->id]);
        $settingsnode = navigation_node::create("Selflearn", $url, navigation_node::TYPE_SETTING,
            null, 'selflearn', new pix_icon('i/selflearn', ''));
        $navigation->add_node($settingsnode);
    }
}