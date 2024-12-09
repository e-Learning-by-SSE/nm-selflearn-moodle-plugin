<?php
defined('MOODLE_INTERNAL') || die();

$definitions = array(
    'report_cache' => array( // Cache identifier (unique for your plugin)
        'mode' => cache_store::MODE_SESSION,  // MODE_APPLICATION | MODE_SESSION | MODE_REQUEST
        'ttl' => 300 // Time-to-live (TTL) in seconds, adjust as necessary
    ),
);