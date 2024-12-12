<?php
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ('./lib.php');
require_once(__DIR__.'/../../config.php');

// use core\output\notification;
// $message = "This is a success message!";
// $notification = new notification($message, notification::NOTIFY_SUCCESS);
// echo $OUTPUT->render($notification);

$PAGE->requires->js_call_amd('mod_selflearn/query_update', 'queryCourses');
class mod_selflearn_mod_form extends moodleform_mod {
    public function definition() {

        global $USER;
        $username = $USER->username;
        $courses = selflearn_list_courses($username, null);
        $options = [];
        foreach ($courses as $item) {
            $options[$item['id']] = $item['name'];
        }
        $mform = $this->_form;

        // Toggle-Switch (own <-> all courses)
        $mform->addElement('advcheckbox', 'toggle', get_string('api_label_toggle_course_Selection', 'selflearn'), '', array('group' => 1), array(0, 1));
        $mform->setType('toggle', PARAM_INT);
        
        // Search all courses (Text field)
        $ajax_url = new moodle_url('/mod/selflearn/ajax.php');
        // echo "AJAX: URL: $ajax_url";
        $mform->addElement('text', 'search_input', get_string('api_label_course_search', 'selflearn'), array(
            'size' => '40',
            'data-ajax-url' => $ajax_url
        ));
        $mform->setType('search_input', PARAM_TEXT);
        $mform->hideIf('search_input', 'toggle', 'neq', true);
       
        // Available courses Dropdown (By Group <-> All)
        $mform->addElement('select', 'course_selection', get_string('api_label_course_selection', 'selflearn'), $options);
        $mform->setType('course_selection', PARAM_TEXT);
        $mform->addRule('course_selection', null, 'required', null, 'client');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function get_data(){
        $data = parent::get_data();

        return $data;
    }
}