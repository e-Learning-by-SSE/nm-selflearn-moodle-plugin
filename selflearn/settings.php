<?php
defined('MOODLE_INTERNAL') || die;

const DEFAULT_SELFLEARN_URL = 'http://147.172.178.48:4201/';

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_selflearn/selflearn_base_url',
        get_string('admin::Selflearn_URL', 'mod_selflearn'),
        get_string('admin::Selflearn_URL_Description', 'mod_selflearn'),
        DEFAULT_SELFLEARN_URL, // Default value
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