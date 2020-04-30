<?php

use block_uploadvimeo\local\uploadvimeo;

require ('../../config.php');

require_login();

// Get params.
$courseid = required_param('courseid', PARAM_INT);
$urivideo = optional_param('urivideo', NULL, PARAM_TEXT);
$deletevideoid = optional_param('deletevideoid', NULL, PARAM_INT);
$userid = $USER->id;

if ($urivideo) {

    uploadvimeo::video_upload($courseid, $userid, $urivideo);
    redirect(new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]));
    
} else if ($deletevideoid) {
    
    uploadvimeo::video_delete($courseid, $deletevideoid);  
    redirect(new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]));
    
}

