<?php
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once(__DIR__.'/../../config.php');
require_once(dirname(__FILE__) . '/classes/restclient.php');
use core\output\help_icon;

$PAGE->requires->js_call_amd('mod_selflearn/update_courses', 'init');
class mod_selflearn_mod_form extends moodleform_mod {
    public function definition() {
        global $USER, $OUTPUT;
        $username = $USER->username;
        $mform = $this->_form;
        $client = new restclient();
        
        try {
            $ajax_url = new moodle_url('/mod/selflearn/search.php');
            $courses = $client->selflearn_list_courses($username, null);
            $options = [];
            foreach ($courses as $item) {
                $options[$item['id']] = $item['name'];
            }

            // Select course from list with matching parameters (actual selection; mandatory)
            $mform->addElement('select', 'course_selection', get_string('api_label_course_selection', 'selflearn'), $options);
            $mform->setType('course_selection', PARAM_TEXT);
            $mform->addRule('course_selection', null, 'required', null, 'client');
            $mform->addHelpButton('course_selection', 'help::course_selection', 'selflearn');
            
            // Use Moodle template to render: Toggle input
            // See: https://componentlibrary.moodle.com/admin/tool/componentlibrary/docspage.php/moodle/components/toggle/
            $togglehtml = $OUTPUT->render_from_template('core/toggle', [
                'id' => 'toggle',
                'checked' => true,
                'disabled' => false,
                'data-ajax-url' => $ajax_url,
                'dataattributes' => [
                    ['name' => 'no-submit', 'value' => 1]
                ],

                'extraclasses' => 'custom-switch'
            ]);

            // Alternative inclusion of the toggle element
            // $html = '<div style="display: flex; align-items: center; gap: 10px;">';
            // $html .= '<label for="toggle">'.get_string('api_label_toggle_course_Selection',  'selflearn').'</label>';
            // $html .= $togglehtml;
            // $html .= '</div>';
            
            // Toggle of own/all courses
            // <outer_div>
            //     <label_div>
            //         <label/>
            //         <addon/>
            //     </label_div>
            //     <input_div><toogle/></input_div>
            // </outer_div> 
            $outer_div = '<div id="fitem_id_toggle" class="mb-3 row fitem">';
            $label_div = '<div class="col-md-3 col-form-label d-flex pb-0 pe-md-0">';
            $label = '<label for="toggle">' . get_string('api_label_toggle_course_Selection', 'selflearn') . '</label>';
            $helpicon = new help_icon(
                'help::self-created_course_selection', // string name (must have a help string!)
                'selflearn' // component
            );
            $toggle_help = $OUTPUT->render($helpicon);
            $addon = '<div class="form-label-addon d-flex align-items-center align-self-start">'.$toggle_help.'</div>';
            $input_div = '<div class="col-md-9 d-flex align-items-center">'.$togglehtml.'</div>';
            $component = $outer_div.$label_div.$label.$addon.'</div>'.$input_div.'</div>';
            $mform->addElement('html', $component);

            // Search input for course title (optional)
            $mform->addElement('text', 'search_input', get_string('api_label_course_search', 'selflearn'), array(
                    'size' => '40',
                    'data-ajax-url' => $ajax_url,
                    'data-no-submit' => 1 // Prevents inclusion in form data
                ));
            $mform->setType('search_input', PARAM_TEXT);
            $mform->addHelpButton('search_input', 'help::search_input', 'selflearn');

            $this->standard_coursemodule_elements();
            $this->add_action_buttons();
        } catch (Exception $e) {
            $mform->addElement('html', $OUTPUT->notification(
                $e->getMessage(), 
                \core\output\notification::NOTIFY_ERROR
            ));

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