<?php

use block_uploadvimeo\local\uploadvimeo;
use block_uploadvimeo\local\util;

require_once ('../../config.php');

require_login();

// Get params.
$courseid = required_param('courseid', PARAM_INT);
$urivideo = optional_param('urivideo', NULL, PARAM_TEXT);
$deletevideoid = optional_param('deletevideoid', NULL, PARAM_INT);
$videoid = optional_param('videoid', NULL, PARAM_INT);
$newthumbnail = util::get_param_file('newthumbnail_'.$videoid);
$userid = $USER->id;


if ($urivideo) {

    $response = uploadvimeo::video_upload($courseid, $userid, $urivideo);
    if ($response == false) {
        redirect(new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]), get_string('msg_error_vimeo', 'block_uploadvimeo'));
    } else {
        redirect(new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]));
    }
    
} else if ($deletevideoid) {
    
    uploadvimeo::vimeo_delete_video($courseid, $deletevideoid);  
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
        if ($urinewthumnail = uploadvimeo::vimeo_edit_thumbnail($videoid, $newthumbnail)){
            util::delete_file_temp($newthumbnail);    
            $response['urlnewthumbnail'] = $urinewthumnail;
        } else {
            $response['status'] = 1;
            $response['message'] = 'Não foi possível atualizar a imagem do vídeo. Tente novamente mais tarde.';
            $response['urlnewthumbnail'] = $newthumbnail;
        }
    }
    
    //echo json_encode($response);    

    redirect(new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]), $response['message']);

}



