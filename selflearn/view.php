<?php
require_once('../../config.php');
require_once('lib.php');
global $DB;

// ID of the activity
$id = required_param('id', PARAM_INT);
// ID -> Activity data
$cm = get_coursemodule_from_id('selflearn', $id);
$activity_data = $DB->get_record('selflearn', array('id' => $cm->instance), '*', MUST_EXIST);
// Activity Data -> SelfLearn Course URL
$url = $activity_data->url;
redirect($url);
