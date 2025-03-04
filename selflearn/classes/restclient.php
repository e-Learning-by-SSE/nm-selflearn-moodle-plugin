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
        $config = get_config('mod_selflearn');
        
        if (empty($config->selflearn_base_url)) {
            throw new Exception("No SelfLearn Base URL configured");
        }
        $this->selflearn_rest_api = $config->selflearn_base_url . REST_API;
        
        // Load OAuth2 service, which is configured to be used with this plugin
        if (empty($config->selflearn_oauth2_provider)) {
            throw new Exception("No OAuth2 provider configured");
        } 

        // Get sertvice account
        $api = new api();
        $issuer = $api->get_issuer($config->selflearn_oauth2_provider);
        // if (!$issuer->is_system_account_connected()) {
        //     throw new Exception("No OAuth2 service account configured");
        // }
        // Load OAuth2 client
        $this->client = $api->get_user_oauth_client($issuer, $PAGE->url, "", true);
        if (!$this->client->is_logged_in()) {
            print("Log in");
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
     * Searches for courses to imports. Requires either `$userId` or `$title` to be set.
     * @param string $username The userId of the author. If this parameter is set, all courses of the authors are returned.
     * @param string $title The title of the course to search for. If this parameter is set,
     * only courses with the given string in the title are returned, independent of the author.
     */
    function selflearn_list_courses($username, $title) {
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
        $response = $this->client->get($selflearn_courses, $search_params);
        if ($response == "Recv failure: Connection reset by peer") {
            throw new Exception("Server not reachable");
        }
        $data = json_decode($response, true);
    
        $courses = [];
        if (json_last_error() != JSON_ERROR_NONE && !is_array($data)) {
            throw new Exception("REST API Blocked");
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
        $lms_url = $this->selflearn_rest_api . "courses/" . $slug;
    
        // Query
        $response = $this->client->get($lms_url);
        $http_status = $this->client->get_info()['http_code'];
        
        if ($http_status == 404) {
            // Course data not found for given slug, use fallback "Course: slug"
            return get_string("activity_prefix_course", 'selflearn') . $slug;
        } else {
            $data = json_decode($response, true);
    
            if (json_last_error() != JSON_ERROR_NONE && !is_array($data)) {
                throw new Exception("REST API Blocked");
            } else {
                return $data['title'];
            }
        }
    }
}


