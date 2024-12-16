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
}