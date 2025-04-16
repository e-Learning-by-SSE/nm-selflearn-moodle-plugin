<?php
$string['pluginname'] = 'SelfLearn';
$string['modulename'] = 'SelfLearn';
$string['modulenameplural'] = 'SelfLearn';
$string['admin::Selflearn_URL'] = 'SelfLearn URL';
$string['admin::Selflearn_URL_Description'] = 'Root URL for the SelfLearn application. Must end with a slash (e.g., https://www.uni-hildesheim.de/selflearn/)';
$string['admin::selflearn_oauth2_provider'] = 'OAuth2 Service-Account of the SelfLearn application (e.g., provided by Keycloak)';
$string['admin::selflearn_oauth2_provider_Description'] = 'OIDC service to authenticate in SelfLearn';
$string['menu::authoring_page_label'] = 'SelfLearn - Authoring Area';
$string['api_label_toggle_course_Selection'] = 'Limit to self-created content';
$string['api_label_course_selection'] = 'Select course';
$string['api_label_course_search'] = 'Search by course title';
$string['help::course_selection'] = '(mandatory) Select course';
$string['help::course_selection_help'] = 'With the options "Limit to self-created content" and "Search by course title", the search can be narrowed down. Up to 20 results are displayed in the selection field. To create the SelfLearn activity, a course must be selected.';
$string['help::search_input'] = '(optional) Search by course title';
$string['help::search_input_help'] = 'Optionally specify a title that should be part of the course name to restrict the course selection.';
$string['help::self-created_course_selection'] = 'Limit to self-created content (optional)';
$string['help::self-created_course_selection_help'] = 'By default, the selection is limited to self-created materials. Disabling this option allows all available materials to be searched.';
$string['activity_prefix_course'] = 'Course: ';
$string['report::no_students'] = 'No students enrolled in this course';
$string['report::no_activities'] = 'No SelfLearn activity defined';
$string['report::title'] = 'SelfLearn Activity Report';
$string['report::first_name'] = 'First Name';
$string['report::last_name'] = 'Last Name';
$string['report::export_btn'] = 'Export';
$string['error::rest_api_blocked_by_moodle'] = 'Error: SelfLearn REST API blocked by Moodle, please contact an administrator.';
$string['error::rest_api_blocked'] = 'Error: Connection to SelfLearn REST API blocked. Reason: {reason}';
$string['error::wrong_role:author'] = 'Error: You need to be a SelfLearn "Author" to perform the action.';
$string['error::unauthorized_user'] = 'Fehler: You need a SelfLearn account. Please log into the SelfLearn platform ({$a->link}) once, before continuing your request.';
$string['error::selflearn_not_reachable'] = 'Error: SelfLearn platform not reachable, please contact an administrator.';
$string['error::No SelfLearn Base URL configured'] = 'Error: SelfLearn URL unknown, please contact an administrator.';
$string['error::No OAuth2 provider configured'] = 'Error: No SelfLearn Auth provider configuered, please contact an administrator.';
?>
