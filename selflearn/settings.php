<?php
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_selflearn/selflearn_base_url',
        get_string('admin::Selflearn_URL', 'mod_selflearn'),
        get_string('admin::Selflearn_URL_Description', 'mod_selflearn'),
        'https://www.uni-hildesheim.de/selflearn/', // Default value
        PARAM_TEXT
    ));

    $oauth2services = \core\oauth2\api::get_all_issuers();
    $choices = [];
    foreach ($oauth2services as $issuer) {
        $id = $issuer->get('id');
        $name = $issuer->get('name');
        $choices[$id] = $name;
    }

    // Add a setting to select an OAuth2 service.
    $settings->add(new admin_setting_configselect(
        'mod_selflearn/selflearn_oauth2_provider',
        get_string('admin::selflearn_oauth2_provider', 'mod_selflearn'),
        get_string('admin::selflearn_oauth2_provider_Description', 'mod_selflearn'),
        '', // Default value (empty means no service selected).
        $choices
    ));
}