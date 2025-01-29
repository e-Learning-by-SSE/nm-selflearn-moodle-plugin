<?php
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once(__DIR__.'/../../config.php');
require_once(dirname(__FILE__) . '/classes/restclient.php');

$PAGE->requires->js_call_amd('mod_selflearn/query_update', 'queryCourses');
class mod_selflearn_mod_form extends moodleform_mod {
    public function definition() {
        global $USER, $OUTPUT;
        $username = $USER->username;
        $mform = $this->_form;
        $client = new restclient();
        
        try {
            $courses = $client->selflearn_list_courses($username, null);
            $options = [];
            foreach ($courses as $item) {
                $options[$item['id']] = $item['name'];
            }
            
            // Toggle-Switch (own <-> all content)
            $mform->addElement('advcheckbox', 'toggle', get_string('api_label_toggle_course_Selection', 'selflearn'), '', [
                'class' => 'custom-switch',
                'data-no-submit' => 1 // Prevents inclusion in form data
            ]);
            $mform->setType('toggle', PARAM_INT);

            $ajax_url = new moodle_url('/mod/selflearn/search.php');
            $mform->addElement('text', 'search_input', get_string('api_label_course_search', 'selflearn'), array(
                    'size' => '40',
                    'data-ajax-url' => $ajax_url,
                    'data-no-submit' => 1 // Prevents inclusion in form data
                ));
            $mform->setType('search_input', PARAM_TEXT);
        
            // // Available courses Dropdown (By Group <-> All)
            $mform->addElement('select', 'course_selection', get_string('api_label_course_selection', 'selflearn'), $options);
            $mform->setType('course_selection', PARAM_TEXT);
            $mform->addRule('course_selection', null, 'required', null, 'client');

            $this->standard_coursemodule_elements();
            $this->add_action_buttons();
        } catch (Exception $e) {
            if ($e->getMessage() == "Server not reachable") {
                $mform->addElement('html', $OUTPUT->notification(
                    get_string('error::selflearn_not_reachable', 'mod_selflearn'), 
                    \core\output\notification::NOTIFY_ERROR
                ));
            } else {
                $mform->addElement('html', $OUTPUT->notification(
                    get_string('error::rest_api_blocked', 'mod_selflearn'), 
                    \core\output\notification::NOTIFY_ERROR
                ));
            }

            $this->standard_hidden_coursemodule_elements();
            $this->add_action_buttons(true, false, false);
        }
    }

    public function get_data(){
        $data = parent::get_data();

        // Manually override select field if needed
        $elementName = 'course_selection';
        $submittedValue = optional_param($elementName, null, PARAM_RAW);
        
        if ($submittedValue !== null) {
            $data->{$elementName} = $submittedValue;
        }

        return $data;
    }
}