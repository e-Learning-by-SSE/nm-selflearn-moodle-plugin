<?php
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;

require_once('restclient.php');

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/filelib.php');

class mod_selflearn_external extends external_api {
    public static function search_items_parameters() {       
        return new external_function_parameters(
            array(
                'search' => new external_value(PARAM_TEXT, 'Search query'),
                'fromAllAuthors' => new external_value(PARAM_INT, 'fromAllAuthors')
                )
        );
    }

    public static function search_items($search, $fromAllAuthors) {
        global $USER;
        $username = $fromAllAuthors == 1 ? null: $USER->username;
        $client = new restclient();

        $courses = $client->selflearn_list_courses($username, $search);
        return $courses;
    }

    public static function search_items_returns() {
        array(
            'id' => new external_value(PARAM_TEXT, 'ID of course'),
            'name' => new external_value(PARAM_TEXT, 'Title of the course')
        );
    }
}