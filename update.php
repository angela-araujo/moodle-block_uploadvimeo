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
    
    global $CFG;
    uploadvimeo::video_upload($courseid, $userid, $urivideo);
    redirect("$CFG->wwwroot/blocks/uploadvimeo/form.php?courseid=$courseid");
    
} else if ($deletevideoid) {
    
    uploadvimeo::video_delete($courseid, $deletevideoid);  
    redirect("$CFG->wwwroot/blocks/uploadvimeo/form.php?courseid=$courseid");
    
}

