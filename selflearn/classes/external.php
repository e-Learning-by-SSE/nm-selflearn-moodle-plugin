<?php
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/selflearn/rest_client.php');

class mod_selflearn_external extends external_api {

    public static function search_items_parameters() {       
        return new external_function_parameters(
            array('search' => new external_value(PARAM_TEXT, 'Search query'))
        );
    }

    public static function search_items($search) {
        $courses = selflearn_list_courses(null, $search);
        return $courses;
    }

    public static function search_items_returns() {
        array(
            'id' => new external_value(PARAM_TEXT, 'ID of course'),
            'name' => new external_value(PARAM_TEXT, 'Title of the course')
        );
    }
}
