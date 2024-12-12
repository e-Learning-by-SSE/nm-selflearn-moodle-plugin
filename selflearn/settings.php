<?php
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_selflearn/selflearn_base_url',
        get_string('admin::Selflearn_URL', 'mod_selflearn'),
        get_string('admin::Selflearn_URL_Description', 'mod_selflearn'),
        'http://app:4200/', // Default value
        PARAM_TEXT
    ));
}