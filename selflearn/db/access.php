<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'mod/selflearn:addinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ),
    ),
    'selflearn/ajax:access' => array(
        'captype' => 'read',                      // The type of capability (read/write)
        'contextlevel' => CONTEXT_SYSTEM,         // The context level (SYSTEM for global access)
        'archetypes' => array(
            'guest' => CAP_PROHIBIT,              // Guests are prohibited
            'user' => CAP_ALLOW,                  // Regular authenticated users are allowed
        ),
    ),
    'selflearn/selflearn:view' => array(
        'captype' => 'read',                      // The type of capability (read/write)
        'contextlevel' => CONTEXT_SYSTEM,         // The context level (SYSTEM for global access)
        'archetypes' => array(
            'guest' => CAP_PROHIBIT,              // Guests are prohibited
            'user' => CAP_ALLOW,                  // Regular authenticated users are allowed
        ),
    ),
);