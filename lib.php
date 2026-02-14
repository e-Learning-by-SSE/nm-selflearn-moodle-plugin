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
require_once(__DIR__ . '/classes/restclient.php');

function selflearn_query_progress($users, $courses, $restclient = null) {
    $studentScores = [];

    if (empty($users) || empty($courses)) {
        return $studentScores;
    }

    // Create REST client
    if ($restclient === null) {
        try {
            $restclient = new restclient();
        } catch (Exception $e) {
            error_log("SelfLearn: Cannot create REST client - " . $e->getMessage());
            return ['_error' => 'unavailable', '_message' => $e->getMessage()];
        }
    }

    // Track if ALL API calls fail
    $total_calls = 0;
    $failed_calls = 0;
    $last_error = '';

    // Extract usernames for API call
    $usernames = array_map(function($user) {
        return $user->username;
    }, $users);

    foreach ($users as $user) {
        $totalScore = 0;
        $userScores = [];
        $validCourses = 0;

        foreach ($courses as $course) {
            $total_calls++;
            try {
                // Get progress for this specific course and user
                $progress_data = $restclient->selflearn_get_course_progress($course["slug"], [$user->username]);


                $score = null;
                $userFound = false;

                // Check if API returned any data for this user
                if (!empty($progress_data)) {
                    foreach ($progress_data as $student_progress) {
                        if ($student_progress['username'] === $user->username) {
                            $score = $student_progress['progress'];
                            $userFound = true;
                            break;
                        }
                    }
                }

                // Handle different cases based on API response
                if (!$userFound) {
                    // API returned empty array = user not enrolled in SelfLearn course
                    $userScores[$course["slug"]] = "---";
                } else if ($score === null) {
                    // User found but progress is null
                    $userScores[$course["slug"]] = 0;
                    $totalScore += 0;
                    $validCourses++;
                } else {
                    // User enrolled with actual progress (including 0%)
                    $userScores[$course["slug"]] = $score;
                    $totalScore += $score;
                    $validCourses++;
                }

            } catch (Exception $e) {
                // API error for this course
                error_log("SelfLearn: API error for course " . $course["slug"] . " - " . $e->getMessage());
                $userScores[$course["slug"]] = "Error";
                $failed_calls++;
                $last_error = $e->getMessage();
            }
        }

        // Calculate average only for courses where student is enrolled
        if ($validCourses > 0) {
            $averageScore = $totalScore / $validCourses;
            $userScores['total_average'] = round($averageScore, 2);
        } else {
            $userScores['total_average'] = "---";
        }

        $studentScores[$user->username] = $userScores;
    }

    // If ALL API calls failed, return error
    if ($failed_calls > 0 && $failed_calls === $total_calls) {
        return ['_error' => 'api_error', '_message' => $last_error];
    }

    return $studentScores;
}

/**
 * Fallback function when SelfLearn API is unavailable
 */
function selflearn_get_fallback_progress($users, $courses) {
    $studentScores = [];

    foreach ($users as $user) {
        $userScores = [];

        foreach ($courses as $course) {
            $userScores[$course["slug"]] = "SelfLearn unavailable";
        }

        $userScores['total_average'] = "---";
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
 * @param object $data The data from the form
 * @return int|bool true or the new id
 */
function selflearn_add_instance($data) {
    global $USER, $DB;

    $config = get_config('mod_selflearn');
    if (empty($config->selflearn_base_url)) {
        return false;
    }

    // Prepare data to be saved to the database
    $client = new restclient();
    $course_slug = $data->course_selection;
    $record = new stdClass();
    $record->userid = $USER->id;
    $record->course = $data->course;
    $record->slug = $course_slug;
    $record->url = $config->selflearn_base_url . "courses/" . $course_slug;
    $record->name = $client->selflearn_get_course_title($course_slug);
    // Type: Course | Nano-Module | Skill
    // Currently only Courses are supported
    $record->type ="Course";

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
    global $USER, $DB;
    $config = get_config('mod_selflearn');
    if (empty($config->selflearn_base_url)) {
        return false;
    }
    $client = new restclient();

    // Prepare data to be saved to the database
    $course_slug = $data->course_selection;
    $record = new stdClass();
    $record->id = $data->instance;
    $record->userid = $USER->id;
    $record->course = $data->course;
    $record->slug = $course_slug;
    $record->url = $config->selflearn_base_url . "courses/" . $course_slug;
    $record->name = $client->selflearn_get_course_title($course_slug);
    // Type: Course | Nano-Module | Skill
    // Currently only Courses are supported
    // Only form data is passed (not DB data) -> Type is currently hardcoded
    $record->type ="Course";

    // Update
    $DB->update_record('selflearn', $record);

    return true;
}

/**
 * Adds menu entries to the course navigation for the SelfLearn module:
 * - Authoring page
 * - Progress report of students (up comming)
 * @param settings_navigation $navigation The settings navigation object
 * @param stdClass $course The course
 * @param $context Course context
 * @return void
 */
function mod_selflearn_extend_navigation_course($navigation, $course, $context): void {  
    if (has_capability('moodle/course:update', context_course::instance($course->id))) {
        // SelfLearn authoring page
        $config = get_config('mod_selflearn');
        if (!empty($config->selflearn_base_url)) {
            $authoring_url = new moodle_url($config->selflearn_base_url . "dashboard/author");
            // $authoring_url = $config->selflearn_base_url . "dashboard/author";

            $settingsnode = navigation_node::create(get_string('menu::authoring_page_label', 'selflearn'), $authoring_url, navigation_node::TYPE_CUSTOM,
                null, 'selflearn_authoring_page', new pix_icon('monologo', '', 'mod_selflearn'));
            $navigation->add_node($settingsnode);
        }

        // Progress report of students
        $report_page = new moodle_url('/mod/selflearn/coursereport.php', ['id' => $course->id]);
        $settingsnode = navigation_node::create(get_string('report::title', 'selflearn'), $report_page, navigation_node::TYPE_SETTING,
            null, 'selflearn', new pix_icon('i/selflearn', ''));
        $navigation->add_node($settingsnode);
    }
}