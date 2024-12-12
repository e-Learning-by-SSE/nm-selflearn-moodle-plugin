<?php
/**
 * SelfLearn integration for Moodle.
 * REST API client to communicate with the SelfLearn platform.
 *
 * @package   selflearn
 * @copyright 2024 University of Hildesheim, Software Systems Engineering
 * @license   Apache License 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @author    Sascha El-Sharkawy
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/filelib.php');

const REST_API = "api/rest/";

/**
 * Searches for courses to imports. Requires either `$userId` or `$title` to be set.
 * @param string $userid The userId of the author. If this parameter is set, all courses of the authors are returned.
 * @param string $title The title of the course to search for. If this parameter is set,
 * only courses with the given string in the title are returned, independent of the author.
 */
function selflearn_list_courses($userid, $title) {
    GLOBAL $OUTPUT;

    $config = get_config('mod_selflearn');
    if (empty($config->selflearn_base_url)) {
        return false;
    }
    $selflearn_courses = $config->selflearn_base_url . REST_API ."courses/";

    $curl = new curl();
    $search_params = [];
    if ($title != null) {
        // Search for all courses by title
        $search_params = [
            'title' => $title,
            'page' => 1,
        ];
    } else {
        // Search for courses of the current user
        $search_params = [
            'authorId' => "zaepernickrothe", // $userid,
            'page' => 1,
        ];
    }
    $response = $curl->get($selflearn_courses, $search_params);
    $data = json_decode($response, true);


    $courses = [];
    if (json_last_error() != JSON_ERROR_NONE && !is_array($data)) {
        $error_message = get_string('error_rest_api_blocked', 'selflearn');
        $notification = new notification($error_message, notification::NOTIFY_ERROR);
        echo $OUTPUT->render($notification);
    } else {
        foreach ($data["result"] as $course) {
            $courses[] = [
                'id' => $course['slug'],
                'name' => $course['title']
            ];
        }
    }

    return $courses;
}

function selflearn_get_course_title($slug) {
    GLOBAL $OUTPUT;

    $config = get_config('mod_selflearn');
    if (empty($config->selflearn_base_url)) {
        return false;
    }
    $lms_url = $config->selflearn_base_url . REST_API . "courses/" . $slug;

    // Query
    $curl = new curl();
    $response = $curl->get($lms_url);
    $http_status = $curl->info['http_code'];
    
    if ($http_status == 404) {
        // Course data nout found for given slug, use fallback "Course: slug"
        return get_string("activity_prefix_course", 'selflearn') . $slug;
    } else {
        $data = json_decode($response, true);

        if (json_last_error() != JSON_ERROR_NONE && !is_array($data)) {
            $error_message = get_string('error_rest_api_blocked', 'selflearn');
            $notification = new notification($error_message, notification::NOTIFY_ERROR);
            echo $OUTPUT->render($notification);
        } else {
            return $data['title'];
        }
    }
}
