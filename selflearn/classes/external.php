<?php
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
require_once($CFG->libdir . '/filelib.php');

class mod_selflearn_external extends external_api {

    public static function search_items_parameters() {       
        return new external_function_parameters(
            array('search' => new external_value(PARAM_TEXT, 'Search query'))
        );
    }

    public static function search_items($search) {               
        $lms_url = "https://staging.sse.uni-hildesheim.de:9011/skill-repositories";

        $curl = new curl();
        $search_params = [
            'name' => $search,
            'pageSize' => 10,
            'page' => 0,

        ];
        $data = json_encode($search_params);
        $headers = array(
            'Content-Type: application/json',
        );        
        $response = $curl->post($lms_url, $data, array('CURLOPT_HTTPHEADER' => $headers));
        $data = json_decode($response, true);

        $courses = [];
        foreach ($data["repositories"] as $course) {
            $courses[] = [
                'id' => $course['id'],
                'name' => $course['name']
            ];
        }

        // $arrayAsString = print_r($courses, true);
        // error_log("Debug: Array content - " . $arrayAsString);

        return $courses;
    }

    public static function search_items_returns() {
        array(
            'id' => new external_value(PARAM_INT, 'ID of course'),
            'name' => new external_value(PARAM_TEXT, 'Title of the course')
        );
    }
}
