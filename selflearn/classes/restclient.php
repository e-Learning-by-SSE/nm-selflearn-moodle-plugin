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
use core\oauth2\api;
const REST_API = "api/rest/";

class restclient {
    private $client;
    private $selflearn_rest_api;

    function __construct() {
        global $PAGE;
        debugging('SelfLearn: REST Client(Constructor) - Start', DEBUG_DEVELOPER);
        $config = get_config('mod_selflearn');
        
        if (empty($config->selflearn_base_url)) {
            throw new Exception(get_string("error::No SelfLearn Base URL configured", "mod_selflearn"));
        }
        debugging('SelfLearn: REST Client(Constructor) - BASE URL configured', DEBUG_DEVELOPER);
        $this->selflearn_rest_api = $config->selflearn_base_url . REST_API;
        
        // Load OAuth2 service, which is configured to be used with this plugin
        if (empty($config->selflearn_oauth2_provider)) {
            throw new Exception(get_string("error::No OAuth2 provider configured", "mod_selflearn"));
        } 

        // Get sertvice account
        debugging('SelfLearn: REST Client(Constructor) - INIT API', DEBUG_DEVELOPER);
        $api = new api();
        debugging('SelfLearn: REST Client(Constructor) - Load Issuer', DEBUG_DEVELOPER);
        $issuer = $api->get_issuer($config->selflearn_oauth2_provider);
        // if (!$issuer->is_system_account_connected()) {
        //     throw new Exception("No OAuth2 service account configured");
        // }
        // Load OAuth2 client
        $return_url = $PAGE->url->out_as_local_url(false);
        $url = new moodle_url('/admin/oauth2callback.php', [
            'state' => $return_url,
            'sesskey' => sesskey(),
            'response_type' => 'code'
        ]);
        $url2 = new moodle_url('/admin/oauth2callback.php', [
            'sesskey' => sesskey(),
        ])->out_as_local_url(false);

        debugging('SelfLearn: REST Client(Constructor) - Get Client, Return URL: ' . $return_url . " - Full Return URL: " . $url, DEBUG_DEVELOPER);
        $this->client = $api->get_user_oauth_client($issuer, $url2, "", true);
        if (!$this->client->is_logged_in()) {
            debugging('SelfLearn: REST Client(Constructor) - Performing OAuth Login', DEBUG_DEVELOPER);
            debugging('SelfLearn: REST Client(Constructor) - Login URL: ' . $this->client->get_login_url(), DEBUG_DEVELOPER);
            redirect($this->client->get_login_url());
        }

        // $t = $this->client->request_token();
        // print("Token: " . $this->client->get_accesstoken());
        // $this->client = api::get_system_oauth_client($issuer);
        // if (!$this->client->is_logged_in()) {
        //     throw new Exception("OAuth2 service account disconnected");
        // }
    }

    /**
     * Uniform repsponse handling for REST API calls.
     * Converts the response to a JSON object and checks for errors.
     * @param string $response The response of the REST API call.
     * @return array The associative array of the JSON response.
     * @throws Exception If the response is not a valid JSON object or the REST API call was blocked for some reason.
     */
    private function handle_response(string $response) {
        // Check if the server is reachable
        if ($response == "Recv failure: Connection reset by peer") {
            throw new Exception(get_string('error::selflearn_not_reachable', 'mod_selflearn'));
        }
        $data = json_decode($response, true);
    
        // Check for errors in the response
        if (json_last_error() != JSON_ERROR_NONE && !is_array($data)) {
            // Most likely the REST API is blocked by Moodle for security reasons
            throw new Exception(get_string('error::rest_api_blocked_by_moodle', 'mod_selflearn'));
        } else if ($data["code"] == "FORBIDDEN") {
            if ($data["message"] == "Requires 'AUTHOR' role.") {
                // Moodle user has not the required SelfLearn role to access the REST API
                throw new Exception(get_string('error::wrong_role:author', 'mod_selflearn'));
            } else {
                // Unknown error
                $message = get_string('error::rest_api_blocked', 'mod_selflearn');
                throw new Exception(sprintf($message, $data["message"]));
            }
        } else if ($data["code"] == "UNAUTHORIZED") {
            if ($data["message"] == "UNAUTHORIZED") {
                // Moodle user has no SelfLearn account

                throw new Exception(get_string('error::unauthorized_user', 'mod_selflearn'));
            } else {
                // Unknown error
                $message = get_string('error::rest_api_blocked', 'mod_selflearn');
                throw new Exception(sprintf($message, $data["message"]));
            }
        }

        return $data;
    }

    /**
     * Searches for courses to imports. Requires either `$userId` or `$title` to be set.
     * @param string $username The userId of the author. If this parameter is set, all courses of the authors are returned.
     * @param string $title The title of the course to search for. If this parameter is set,
     * only courses with the given string in the title are returned, independent of the author.
     */
    function selflearn_list_courses($username, $title) {
        debugging('SelfLearn: REST Client(List Courses) - Init', DEBUG_DEVELOPER);
        $selflearn_courses = $this->selflearn_rest_api ."courses/";
    
        $search_params = [
            'page' => 1,
        ];
        // Search by title
        if ($title != null) {
            $search_params['title'] = $title;
        }
        // Search by user, e.g., for the current user
        if ($username != null) {
            $search_params['authorId'] = $username;
        }
        
        debugging('SelfLearn: REST Client(List Courses) - Before Get', DEBUG_DEVELOPER);
        $response = $this->client->get($selflearn_courses, $search_params);
        debugging('SelfLearn: REST Client(List Courses) - After Get', DEBUG_DEVELOPER);
        $data = $this->handle_response($response);
        debugging('SelfLearn: REST Client(List Courses) - Response parsed', DEBUG_DEVELOPER);
    
        $courses = [];
        foreach ($data["result"] as $course) {
            $courses[] = [
                'id' => $course['slug'],
                'name' => $course['title']
            ];
        }
    
        debugging('SelfLearn: REST Client(List Courses) - Return parsed data', DEBUG_DEVELOPER);
        return $courses;
    }
    
    function selflearn_get_course_title($slug) {
        $lms_url = $this->selflearn_rest_api . "courses/" . $slug;
    
        // Query
        $response = $this->client->get($lms_url);
        $http_status = $this->client->get_info()['http_code'];
        
        if ($http_status == 404) {
            // Course data not found for given slug, use fallback "Course: slug"
            return get_string("activity_prefix_course", 'selflearn') . $slug;
        } else {
            $data = $this->handle_response($response);
            return $data['title'];
        }
    }
}


