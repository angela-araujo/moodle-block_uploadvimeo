<?php

use block_uploadvimeo\local\uploadvimeo;

require_once ('../../config.php');

require_login();

// Get params.
$courseid = required_param('courseid', PARAM_INT);
$urivideo = optional_param('urivideo', NULL, PARAM_TEXT);
$deletevideoid = optional_param('deletevideoid', NULL, PARAM_INT);
$videoid = optional_param('videoid', NULL, PARAM_INT);
$newthumbnail = get_param_file('newthumbnail_'.$videoid);
$userid = $USER->id;


if ($urivideo) {

    $response = uploadvimeo::video_upload($courseid, $userid, $urivideo);
    if ($response == false) {
        redirect(new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]), get_string('msg_error_vimeo', 'block_uploadvimeo'));
    } else {
        redirect(new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]));
    }
    
} else if ($deletevideoid) {
    
    uploadvimeo::video_delete($courseid, $deletevideoid);  
    redirect(new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]));
    
} else if (($newthumbnail) and ($videoid)){
    $response = array(
            'status' => 0,
            'message' => 'Imagem alterada com sucesso!',
            'urlnewthumbnail' => ''
    );
    if (!file_exists($newthumbnail)) {
        $response['status'] = 1;
        $response['message'] = 'Arquivo não enconrado';
        $response['urlnewthumbnail'] = $newthumbnail;
    } else {    
        $urinewthumnail = uploadvimeo::edit_thumbnail($videoid, $newthumbnail);
        delete_file_temp($newthumbnail);    
        $response['urlnewthumbnail'] = $urinewthumnail;
    }
    
    //echo json_encode($response);
    

    redirect(new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]));

}

function get_param_file($param) {
    
    global $CFG;
    
    if (isset($_FILES[$param])) {
        $pathtemp = $CFG->dataroot . DIRECTORY_SEPARATOR .'temp'. DIRECTORY_SEPARATOR . 'vimeo';
        if (!is_dir($pathtemp)) {
            mkdir($pathtemp, 0777, true);
        }
        
        // Strip all suspicious characters from filename (moodlelib.php). 
        $filename = fix_utf8($_FILES[$param]['name']);
        $filename = preg_replace('~[[:cntrl:]]|[&<>"`\|\':\\\\/]~u', '', $filename);
        if ($filename === '.' || $filename === '..') {
            $filename = '';
        }        
        
        $newthumbnail = $pathtemp . DIRECTORY_SEPARATOR  . $filename;
        move_uploaded_file($_FILES[$param]['tmp_name'], $newthumbnail);
        return $newthumbnail;
    } else 
        return NULL;

}

function delete_file_temp ($file_pointer) {
    return unlink($file_pointer);
}
