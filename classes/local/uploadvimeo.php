<?php

namespace block_uploadvimeo\local;

use Vimeo\Vimeo;
use context_course;

define('VIDEOS_PER_PAGE', 10);
define('FOLDERS_PER_PAGE', 100);
define('UPLOADVIMEO_ERROR', -1);

// Connect to Vimeo.
require_once(__DIR__ . '/../../vendor/autoload.php');

global $CFG;

require_once($CFG->dirroot."/lib/weblib.php");

class uploadvimeo {
    
    /**
     * Routine executed after uploading the video to Vimeo:
     * 
     * 1. Insert video into the "block_uploadvimo_videos" table;
     * 2. Check if the video status is "complete" to move the video to the folder on vimeo.
     * 3. Update video information (linkpicture, duration, size_bytes, quality, folderid)
     * 
     * @param int $courseid
     * @param int $userid
     * @param string $urivideo
     * @return boolean
     */
    public static function video_upload($courseid, $userid, $urivideo) {
        
        global $DB;
        
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $usernamefolder = "MoodleUpload_{$user->username}";
        $videoidvimeo = str_replace('/videos/', '', $urivideo);
        $maxattemptverifyupload = 5;
        $msgdebug = '';
        
        $videoid = self::add_video_from_vimeo_to_moodle($videoidvimeo);
        
        // Log updaload event.
        $event = \block_uploadvimeo\event\video_uploaded::create(
            array(
                'courseid' => $courseid,
                'objectid' => $videoidvimeo,
                'context' => context_course::instance($courseid),
                'other' => array('videoid' => $videoidvimeo, 'folder' => $usernamefolder)));
        $event->trigger();
        
        // Get folder or create if not exsts.
        $folder = self::get_folder($userid);
        if (!$folder) {
            $folder = self::vimeo_create_folder($userid, $usernamefolder);
            if (!$folder) {
                debugging(get_string('msg_error_not_create_folder', 'block_uploadvimeo', array('videoid' => $videoidvimeo, 'foldername'=>$usernamefolder)), NO_DEBUG_DISPLAY);
                return false;
            }
        }
        
        // verify upload video to move.
        for ($i = 1; $i <= $maxattemptverifyupload; $i++) {            
            if ($i > 1) sleep(5);            
            $msgdebug .= '<br>Attempt: ' . $i . '. upload.status ';
            $videouploadcomplete = self::vimeo_verify_upload($videoidvimeo);            
            
            if ($videouploadcomplete) break;   
        }
        
        if (!$videouploadcomplete) {
            debugging('Error verify status - videoid=' . $videoidvimeo . $msgdebug, NO_DEBUG_DISPLAY);
            return false;
        }
        
        // Try move video to folder's user.
        for ($i = 1; $i <= $maxattemptverifyupload; $i++) {
            if ($i > 1) sleep(5);            
            $msgdebug .= '<br>Attempt: ' . $i . '. move folder in vimeo';
            
            if ($moved = self::vimeo_move_video_to_folder($folder, $videoidvimeo)) {
                break;
            }            
            debugging(get_string('msg_error_not_move_video_folder', 'block_uploadvimeo', array('videoid' => $videoidvimeo, 'foldername'=>$usernamefolder)), NO_DEBUG_DISPLAY);            
        }
        
        if (!$moved) {
            debugging(get_string('msg_error_not_move_video_folder', 'block_uploadvimeo', array('videoid' => $videoidvimeo, 'foldername'=>$usernamefolder)), NO_DEBUG_DISPLAY);
            return false;
        }
        
        
        $videouploadcomplete->id = $videoid;
        $videouploadcomplete->folderid = $folder->id;
        $videouploadcomplete->timemodified = time();
        
        /*
        $dataobject = new \stdClass();
        $dataobject->id = $videoid;
        $dataobject->folderid = $folder->id;
        $dataobject->linkpicture = $videouploadcomplete->linkpicture;
        $dataobject->duration = $videouploadcomplete->duration;
        $dataobject->size_bytes = $videouploadcomplete->size_bytes;
        $dataobject->quality = $videouploadcomplete->quality;        
        $dataobject->timemodified = time();*/
        
        $DB->update_record('block_uploadvimeo_videos', $videouploadcomplete);

        return true;        
    }
    
    /**
     * Delete video in Vimeo and in DB.
     * 
     * @param int $courseid
     * @param int $videoid 
     * @return boolean
     */
    static public function vimeo_delete_video ($courseid, $videoid) {
        
        global $DB;
        
        $params = array('id' => $videoid);        
        
        $sql = "SELECT v.videoidvimeo, a.*
                  FROM {block_uploadvimeo_videos} v 
                  JOIN {block_uploadvimeo_folders} f ON f.id = v.folderid 
                  JOIN {block_uploadvimeo_account} a ON a.id = f.accountid
                 WHERE v.id = :id ";
        
        if ($vimeo = $DB->get_record_sql($sql, $params)) {
        
            $client = new Vimeo($vimeo->clientid, $vimeo->clientsecret, $vimeo->accesstoken);
    
            // DELETE https://api.vimeo.com/videos/{video_id}
            $deleted = $client->request('/videos/'.$vimeo->videoidvimeo, array(), 'DELETE');
            
            if (!$deleted['status'] == '204') { // 204 No Content - The video was deleted.
                return false;
            }
            
            // Deleting in DB.
            $DB->delete_records('block_uploadvimeo_videos', $params);
            
            // Log delete event.
            $event = \block_uploadvimeo\event\video_deleted::create(
                array(
                    'courseid' => $courseid,
                    'objectid' => $videoid,
                    'context' => context_course::instance($courseid),
                    'other' => array('videoid' => $videoid)));
            $event->trigger();
            
            return true;
        }
        
        return false;
    }
    

    /**
     * Get folder from user to account upload.
     * @param int $userid
     * @return mixed|\stdClass|false
     */
    static public function get_folder($userid) {
        
        global $DB;        
        $config = get_config('block_uploadvimeo');        
        $params = array(
            'userid' => $userid, 
            'accountid' => $config->accountvimeo);
        
        return $DB->get_record('block_uploadvimeo_folders', $params);
    }
    
    
    static public function get_folder_by_name($foldername) {
        
        global $DB;
        $config = get_config('block_uploadvimeo');
        $params = array(
            'foldername' => $foldername,
            'accountid' => $config->accountvimeo);
        
        return $DB->get_record('block_uploadvimeo_folders', $params);
    }
    
    
    /**
     * USE CAREFULLY!! 
     * This function can be very slow if there are too many folders in vimeo which causes a lot of calls to api.
     * 
     * 
     * Search if folder exists in vimeo. If exists, 
     * 
     * @param int $userid
     * @return mixed|\stdClass|false|boolean
     */
    static public function vimeo_get_folder_to_persist($userid){
        
        global $DB;
        
        $config = get_config('block_uploadvimeo');
        $account = $DB->get_record('block_uploadvimeo_account', ['id' => $config->accountvimeo]) ;
        
        $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
        
        // Get name folder to search in vimeo.
        $user = $DB->get_record('user', array('id' => $userid), 'username', MUST_EXIST);
        $foldername = "MoodleUpload_{$user->username}";
        
        $foldervimeoarray = self::vimeo_search_folder($client, $foldername);
        
        // If founded in vimeo then persist folder in db.
        if ($foldervimeoarray) {
            $foldervimeo = new \stdClass();
            $foldervimeo->userid = $userid;
            $foldervimeo->accountid = $account->id;
            $foldervimeo->foldernamevimeo = $foldername;
            $foldervimeo->folderidvimeo = $foldervimeoarray['folderid'];
            $foldervimeo->timecreated = time();
            $foldervimeo->timecreatedvimeo = strtotime($foldervimeoarray['timecreatedvimeo']);
            
            $moodlefolderid = $DB->insert_record('block_uploadvimeo_folders', $foldervimeo);
            
            return $DB->get_record('block_uploadvimeo_folders', array('id' => $moodlefolderid), '*', MUST_EXIST);
        } else
            return false;
    }

    static public function get_short_title($title, $length) {

        if (strlen($title) > $length) {
            return trim(substr($title, 0, $length), ' ') . '...';
        } else {
            return $title;
        }
    }
    
    /**
     * Move video uploaded to a specific folder (project) in Vimeo.
     * 
     * @param \stdClass $folder
     * @param int $videoidvimeo
     * @return boolean
     */
    static private function vimeo_move_video_to_folder($folder, $videoidvimeo) {
        
        global $DB;        
        $config = get_config('block_uploadvimeo');
        $account = $DB->get_record('block_uploadvimeo_account', ['id' => $config->accountvimeo]);
        $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
                
        $videomoved = $client->request('/me/projects/' . $folder->folderidvimeo . '/videos/' . $videoidvimeo, array(), 'PUT');
        
        if ($videomoved['status'] != '204') {    // 204 No Content - The video was added.            
            return false;
        }
        return true;
    }
    
    /**
     * @deprecated 
     * 
     * @param int $folderid
     * @return boolean
     */
    static private function vimeo_delete_folder($folderid) {
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $folderdeleted = $client->request('/me/projects/' . $folderid, array('should_delete_clips' => false), 'DELETE');
        
        if ($folderdeleted['status'] != '204') {    // 204 No Content - The video was added.
            return false;
        }
        return true;
        
    }
    
    
    /**
     * Create folder in Vimeo and in DB.
     * 
     * @param int $userid
     * @param string $foldername
     *
     * @see https://developer.vimeo.com/api/reference/folders#create_project
     *      POST | https://api.vimeo.com/users/{user_id}/projects
     *      or
     *      POST | https://api.vimeo.com/me/projects
     * @return mixed a fieldset object folder created in DB.
     */
    static private function vimeo_create_folder($userid, $foldername) {
        
        global $DB;
        define(STATUS_FOLDER_CREATED, '201');
        
        $config = get_config('block_uploadvimeo');
        $account = $DB->get_record('block_uploadvimeo_account', ['id' => $config->accountvimeo]);        
        $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
        
        $folder = $client->request('/me/projects', array('name' => $foldername), 'POST');
        
        if ($folder['status'] == STATUS_FOLDER_CREATED) { 
            
            $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $folder['body']['uri']));
            list($useridvimeo, $folderid) = explode(',', $urifolder);
            
            $foldervimeo = new \stdClass();
            $foldervimeo->userid = $userid;
            $foldervimeo->accountid = $config->accountvimeo;
            $foldervimeo->foldernamevimeo = $folder['name'];
            $foldervimeo->folderidvimeo = $folderid;
            $foldervimeo->timecreated = time();
            $foldervimeo->timecreatedvimeo = strtotime($folder['created_time']); // Ex.: [created_time] => 2020-09-29T14:15:37+00:00
            
            $moodlefolderid = $DB->insert_record('block_uploadvimeo_folders', $foldervimeo);
            
            return $DB->get_record('block_uploadvimeo_folders', array('id' => $moodlefolderid), '*', MUST_EXIST);
           
        } else {
            return false;
        }
        
    }
    
    static private function vimeo_update_video($videoid, $videodescription = null) {
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $yesno = array('false','true');
        $titlevisibility = array('hide','show','user');
        $whocomment = array('anybody', 'contacts', 'nobody');
        $view = array('anybody', 'contacts', 'disable', 'nobody', 'password', 'unlisted', 'users');
        $embed = array('private', 'public', 'whitelist');
        
        $array = array(
                'embed.buttons.embed'       => $yesno[$config->config_embedbuttonsembed],
                'embed.buttons.fullscreen'  => $yesno[$config->config_embedbuttonsfullscreen],
                'embed.buttons.like'        => $yesno[$config->config_embedbuttonslike],
                'embed.buttons.share'       => $yesno[$config->config_embedbuttonsshare],
                'embed.color'               => $config->config_embedcolor,
                'embed.logos.custom.active' => $yesno[$config->config_embedlogoscustomactive],
                'embed.logos.vimeo'         => $yesno[$config->config_embedlogosvimeo],
                'embed.title.name'          => $titlevisibility[$config->config_embedtitlename],
                'embed.title.portrait'      => $titlevisibility[$config->config_embedtitleportrait],
                'width'                     => $config->config_width,
                'height'                    => $config->config_height,
                'privacy.add'               => $yesno[$config->config_privacyadd],
                'privacy.comments'          => $whocomment[$config->config_privacycomments],
                'privacy.download'          => $yesno[$config->config_privacydownload],
                'privacy.embed'             => $embed[$config->config_privacyembed],
                'privacy.view'              => $view[$config->config_privacyview],
        );
        
        if ($videodescription) {
            $array['description'] = $videodescription;
        }
        
        // Edit video.
        // PATCH https://api.vimeo.com/videos/{video_id}
        $editvideo = $client->request('/videos/'.$videoid, $array, 'PATCH');
        
        if (!$editvideo['status'] == '200') { // OK
            return UPLOADVIMEO_ERROR;
        }
        else
            return true;
    }

    static public function vimeo_edit_title($videoid, $newtitle){
        
        global $COURSE, $DB;
        
        $params = array('videoid' => $videoid);        
        $sql = "SELECT v.videoidvimeo, a.*
                  FROM {block_uploadvimeo_videos} v
                  JOIN {block_uploadvimeo_account} a ON a.id = v.accountid
                 WHERE v.id = :videoid ";
        $vimeo = $DB->get_record_sql($sql, $params);
        
        if ($vimeo) {
        
            $client = new Vimeo($vimeo->clientid, $vimeo->clientsecret, $vimeo->accesstoken);
            
            $array = array('name' => $newtitle);
            
            // Edit video.
            // PATCH https://api.vimeo.com/videos/{video_id}
            $editvideo = $client->request('/videos/'.$vimeo->videoidvimeo, $array, 'PATCH');
            
            if (!$editvideo['status'] == 200) { // OK
                debugging(get_string('msg_error_not_update_video', 'block_uploadvimeo', $videoid), NO_DEBUG_DISPLAY);
                return false;
            }
            
            // Updating in DB.
            $dataobject = new \stdClass();
            $dataobject->id = $videoid;
            $dataobject->videonamevimeo = $newtitle;
            
            $DB->update_record('block_uploadvimeo_videos', $dataobject);
            
            // Log.
            $event = \block_uploadvimeo\event\video_edit_title::create( 
                array(
                    'courseid' => $COURSE->id,
                    'objectid' => $videoid,
                    'context' => context_course::instance($COURSE->id),
                    'other' => array('videoid' => $videoid) ));
            $event->trigger();           
            
            return true;
        }
        
        return false;

    }
    
    /**
     * Edit image's video in vimeo and update new link image in DB.
     * 
     * @see: https://developer.vimeo.com/api/upload/thumbnails
     * @param int $videoid
     * @param string $newimage path of file to update
     * @return boolean
     */
    static public function vimeo_edit_thumbnail($videoid, $newimage){
        global $DB;
        
        $video = $DB->get_record('block_uploadvimeo_videos', ['id' => $videoid]);        
        if (!$video) {
            debugging(date("Y-m-d H:i:s"). " [vimeo_edit_thumbnail] videoid=$videoid newimage=$newimage", NO_DEBUG_DISPLAY);
            return false;
        }
        
        $account = $DB->get_record('block_uploadvimeo_account', ['id' => $video->accountid]);
        $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);        
        
        // Step 1: Get picture uri.
        $videovimeo = $client->request("/videos/{$video->videoidvimeo}", array(), 'GET');
        if ($videovimeo['status'] != 200){
            debugging(date("Y-m-d H:i:s"). " [vimeo_edit_thumbnail] Get picture uri. Status: {$videovimeo['status']} Error: {$videovimeo['body']['error']}", NO_DEBUG_DISPLAY);
            return false;
        }
        $picture_uri = $videovimeo['body']['metadata']['connections']['pictures']['uri'];
        
        // Step 2: Get the upload link for the thumbnail. create the thumbnail's resource        
        $newlink = $client->request($picture_uri, array(), 'POST');
        if ($newlink['status'] != 201) {
            // The HTTP status of 201 Created indicates that your thumbnail resource is ready.
            debugging(date("Y-m-d H:i:s"). " [vimeo_edit_thumbnail] Get the upload link for the thumbnail. create the thumbnail's resource. Status: {$newlink['status']} ", NO_DEBUG_DISPLAY);
            return false;
        }
        
         // Step 3: Upload the thumbnail image file. 
        $new_uri_picture = $client->uploadImage($picture_uri, $newimage, true);
        debugging(date("Y-m-d H:i:s"). " [vimeo_edit_thumbnail] uploadImage $newimage / new_uri: $new_uri_picture", NO_DEBUG_DISPLAY);
        
        if ($new_uri_picture) {
            $videovimeo = $client->request('/videos/' . $video->videoidvimeo, array(), 'GET');
            if ($videovimeo['status'] == 200){
                
                //update link in db.
                $video->linkpicture = $videovimeo['body']['pictures']['sizes'][0]['link'];
                $video->timemodified = time();
                
                $DB->update_record('block_uploadvimeo_videos', $video);
                
                return $video->linkpicture;
            }
        }
        return false;
    }
    

    /**
     * Verify status of video uploaded.
     * 
     * $video['body']['upload']['status'] == 'complete'
     * 
     * @param int $videoidvimeo
     * @return boolean|\stdClass
     */
    static private function vimeo_verify_upload($videoidvimeo) {
        
        global $DB;        
        $config = get_config('block_uploadvimeo');
        $account = $DB->get_record('block_uploadvimeo_account', ['id' => $config->accountvimeo]);        
        $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
        
        $video = $client->request('/videos/'.$videoidvimeo, array(), 'GET');
        
        if ($video['status'] != '200') { // OK
            return false;
        }
        
        if ($video['body']['upload']['status'] != 'complete' /*and $video['body']['transcode']['status'] != 'complete'*/) {
            return false;
        }
        
        $quality_size = array();
        foreach ($video['body']['download'] as $value) {
            $quality_size[$value['size']] = $value['public_name'];
        }
        ksort($quality_size, SORT_NUMERIC);
        end($quality_size);
        
        $videouploaded = new \stdClass();
        $videouploaded->id               = -1;
        $videouploaded->accountid        = $account->id;
        $videouploaded->forderid         = -1;
        $videouploaded->videoidvimeo     = $videoidvimeo;
        $videouploaded->videonamevimeo   = $video['body']['name'];
        $videouploaded->linkvideo        = $video['body']['link'];
        $videouploaded->linkpicture      = $video['body']['pictures']['sizes'][0]['link'];
        $videouploaded->duration         = $video['body']['duration'];
        $videouploaded->size_bytes       = key($quality_size);
        $videouploaded->quality          = $quality_size[key($quality_size)];
        $videouploaded->timecreatedvimeo = strtotime($video['body']['created_time']);
        $videouploaded->timemodified     = time();

        return $videouploaded;
    }
    
    /**
     * Get all videos from the specific page with pagination.
     * 
     * @param int $folderid
     * @param int $page
     * @param int $perpage
     * @return array [totalvideos => , myvideos => [] ]
     */
    static public function get_videos_from_user($userid, $page=0) {
        
        global $OUTPUT, $DB;
        
        $config = get_config('block_uploadvimeo');
        $accountid = $config->accountvimeo;
        $account = self::get_account_by_id($accountid);
        $app_id = $account->app_id;
        
        $select = "SELECT v.*,
                       CASE WHEN f.accountid = :accountupload THEN 0 ELSE 1 END order_acount";
        $selectcount = "SELECT COUNT(1) ";
        
        $sql = "  FROM {block_uploadvimeo_folders} f
                  JOIN {block_uploadvimeo_videos} v
                    ON v.folderid = f.id
                 WHERE f.userid = :userid";
        
        $order = "ORDER BY order_acount, f.accountid, v.timecreatedvimeo DESC ";
        
        $params = array(
            'userid' => $userid, 
            'accountupload' => $accountid);
        
        $folderspage = $DB->get_records_sql($select. $sql . $order, $params, $page*VIDEOS_PER_PAGE, VIDEOS_PER_PAGE);
        $countvideos = $DB->count_records_sql($selectcount. $sql, $params);
        
        //print_r("<h1>total</h1><pre> ". count($folderspage));
        
        $order = 0;
        $myvideos = [];
        
        // Get videos from page.
        foreach ($folderspage as $video) {
            
            $videoname = $video->videonamevimeo;
            
            $uri = 'https://player.vimeo.com/video/'.$video->videoidvimeo.'?title=0&amp;byline=0&amp;portrait=0&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id='.$app_id;
            
            $htmlembed = '<iframe src="'. $uri. '" width="'. $config->config_width .'" height="' . $config->config_height . 
            '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen title="'.$videoname.'"></iframe>';
            $videotitle = self::get_short_title($videoname, 50);
            
            $displayvalue = '<a data-toggle="collapse" aria-expanded="false" aria-controls="videoid_'.$video->id.'" data-target="#videoid_'.$video->id.'">';
            $displayvalue .= '<img src="'.$video->linkpicture.'" class="rounded" name="thumbnail_'.$video->id.'" id="thumbnail_'.$video->id.'">';
            $displayvalue .= '<span style="margin-left:10px; margin-right:20px;">'.$videotitle.'</span></a>';
            $titleinplace = new \core\output\inplace_editable('block_uploadvimeo', 'title', $video->id, true,
                $displayvalue, $videoname,
                get_string('edittitlevideo', 'block_uploadvimeo'),
                'Novo título para o vídeo ' . format_string($videoname));
                
            $order++;
            
            $myvideos[] = array(
                'order' => $order,
                'name' => $video->videonamevimeo,
                'linkvideo' => $video->linkvideo,
                'videoid'   => $video->id,
                'videoidvimeo'=> $video->videoidvimeo,
                'htmlembed' => $htmlembed, 
                'thumbnail' => $video->linkpicture,
                'titleinplace' => $OUTPUT->render($titleinplace)
            );            
        }
        
        return array(
            'totalvideos'=> $countvideos,
            'videos' => $myvideos);
        
    }
    
    /**
     * Searches value inside a multidimensional array, returning its index
     *
     * Original function by "giulio provasi" (link below)
     *
     * @param mixed|array $haystack
     *   The haystack to search
     *
     * @param mixed $needle
     *   The needle we are looking for
     *
     * @param mixed $index (optional)
     *   Allow to define a specific index where the data will be searched
     *
     * @return integer|string
     *   If given needle can be found in given haystack, its index will
     *   be returned. Otherwise, -1 will
     *
     * @see http://www.php.net/manual/en/function.array-search.php#97645
     */
    static protected function search($haystack, $needle, $index = NULL) {
        
        if( is_null( $haystack ) ) {
            return -1;
        }
        
        $arrayIterator = new \RecursiveArrayIterator($haystack);
        
        $iterator = new \RecursiveIteratorIterator($arrayIterator);
        
        while( $iterator->valid() ) {
            
            if( ( (isset($index) and ($iterator->key() == $index) ) or
                ( !isset($index) ) ) and ($iterator->current() == $needle) ) {
                    
                    return $arrayIterator -> key();
                }
                
                $iterator->next();
        }
        
        return -1;
    }
    
    /**
     * Recursive function that search folder in vimeo, run to all pages
     * 
     * @param Vimeo $client 
     * @param string $foldername
     * @param int $page
     * @param int $per_page
     * @return boolean|array array of folder found in vimeo or false if not
     */
    static protected function vimeo_search_folder ($client, $foldername, $per_page = FOLDERS_PER_PAGE) {
        
        $totalpages = 1;
        
        for ($page = 1; $page <= $totalpages; $page++) {
        
            $param = array('direction' => 'asc', 'sort' => 'name', 'per_page' => $per_page, 'page' => $page);
            
            $foldersvimeo = $client->request('/me/projects', $param, 'GET');
            
            $totalfolders = $foldersvimeo['body']['total'];
            
            $totalpages = ($totalfolders > FOLDERS_PER_PAGE)? ceil($totalfolders / FOLDERS_PER_PAGE): 1;
                
            $totalpages = ($foldersvimeo['body']['total'] > $per_page )? ceil($foldersvimeo['body']['total'] / $per_page): 1;
            
            // Get folders from the page.
            foreach ($foldersvimeo['body']['data'] as $folder) {
                
                $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $folder['uri']));
                list($useridvimeo, $folderid) = explode(',', $urifolder);
                
                if (strcasecmp($folder['name'], $foldername) == 0) {
                    return array(
                        'folderid' => $folderid,
                        'foldername' => $folder['name'],
                        'timecreatedvimeo' => $folder['created_time'],
                    );
                }
            }            
        }
        return false;
    }
    
    /**
     * 
     * @param array $folders
     * @return \Generator
     */
    static public function vimeo_get_all_videos_from_folder($folders) {
        
        global $DB;
        
        foreach ($folders as $folder) {
            
            $listvideos = array();
            
            debugging(date("Y-m-d H:i:s"). " vimeo_get_all_videos_from_folder [accountid={$folder->accountid}, folderid={$folder->id}, foldervimeoid={$folder->folderidvimeo}], ", NO_DEBUG_DISPLAY);
            
            $params = array('id' => $folder->accountid, 'status' => '1');
            
            if ($account = $DB->get_record('block_uploadvimeo_account', $params) ) {
                
                $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
                
                $totalpage = 1;
                
                for ($page = 1; $page <= $totalpage; $page++) {
                    
                    $param = array('direction' => 'asc', 'sort' => 'date', 'per_page' => VIDEOS_PER_PAGE, 'page' => $page);
                    
                    debugging(date("Y-m-d H:i:s"). " vimeo_get_all_videos_from_folder [requeste(/me/projects/{$folder->folderidvimeo}/videos)], ", NO_DEBUG_DISPLAY);
                    $videos = $client->request("/me/projects/{$folder->folderidvimeo}/videos", $param);
                    
                    $totalvideos = $videos['body']['total'];
                    
                    $totalpage = ($totalvideos > VIDEOS_PER_PAGE)? ceil($totalvideos / VIDEOS_PER_PAGE): 1;
                    
                    foreach ($videos['body']['data'] as $video) {
                        
                        // Get information about quality and size from video.
                        $quality_size = array();
                        foreach ($video['download'] as $value) {
                            $quality_size[$value['size']] = $value['public_name'];
                        }
                        ksort($quality_size, SORT_NUMERIC);
                        end($quality_size);
                        
                        $record = new \stdClass();
                        
                        $record->accountid        = $folder->accountid;
                        $record->folderid         = $folder->id;
                        $record->videoidvimeo     = str_replace('/videos/', '', $video['uri']);
                        $record->videonamevimeo   = $video['name'];
                        $record->linkvideo        = $video['link'];
                        $record->linkpicture      = $video['pictures']['sizes'][0]['link'];
                        $record->duration         = $video['duration'];
                        $record->size_bytes       = key($quality_size);
                        $record->quality          = $quality_size[key($quality_size)];
                        $record->timecreated      = time();
                        $record->timecreatedvimeo = strtotime($video['created_time']);
                        $record->timemodified     = time();
                        
                        $listvideos[] = $record;
                    } // End foreach $videos.
                } // End foreach $page.
            } // End if $account.
            yield $listvideos;
        } // End foreach folders.
              
    }
    
    static public function add_all_videos() {
        
        global $DB;
        
        debugging(date("Y-m-d H:i:s"). " Get all folders to fetch videos... ", NO_DEBUG_DISPLAY);
        
        $conditions = array('foldernamevimeo' => 'MoodleUpload_renato');
        
        // Select all users in folder's table.
        if ( $folders = $DB->get_records('block_uploadvimeo_folders', $conditions) ) {
            
            foreach (self::vimeo_get_all_videos_from_folder($folders) as $videos_from_vimeo) {
                
                foreach ($videos_from_vimeo as $video_vimeo) {
                    
                    // Verify wheter video exists.
                    $params = array(
                        'folderid' => $video_vimeo->folderid,
                        'videoidvimeo' => $video_vimeo->videoidvimeo
                    );
                    $video = $DB->get_record('block_uploadvimeo_videos', $params);
                    
                    if (!$video) {
                        // Add new video.
                        $DB->insert_record('block_uploadvimeo_videos', $video_vimeo);
                    } else {
                        // Update video.
                        if ($video->videonamevimeo != $video_vimeo->videonamevimeo || $video->linkvideo != $video_vimeo->linkvideo ||
                            $video->linkpicture != $video_vimeo->linkpicture) {
                                $video_vimeo->id = $video->id;
                                $DB->update_record('block_uploadvimeo_videos', $video_vimeo);
                        }
                    }
                }
                
                
            };
        }
    }
    
    static public function get_account_by_id($accountid) {        
        global $DB;        
        $params = array('id' => $accountid);        
        return $DB->get_record('block_uploadvimeo_account', $params);
    }
    
    
    static public function get_a_specific_video($videoid) {        
        global $DB;        
        $params = array('id' => $videoid);        
        return $DB->get_record('block_uploadvimeo_videos', $params);
    }
    
    static public function add_video_from_vimeo_to_moodle($videoidvimeo) {
        global $DB;        
        $config = get_config('block_uploadvimeo');
        $account = $DB->get_record('block_uploadvimeo_account', ['id' => $config->accountvimeo]);        
        $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
        
        $videovimeo = $client->request("/videos/$videoidvimeo", []);
        if ($videovimeo['status'] != '200') {
            return false;
        }
        $videovimeo = $videovimeo['body'];
        
        $quality_size = array();
        foreach ($videovimeo['download'] as $value) {
            $quality_size[$value['size']] = $value['public_name'];
        }
        ksort($quality_size, SORT_NUMERIC);
        end($quality_size);
        
        $record = new \stdClass();
        
        $record->accountid        = $config->accountvimeo;
        $record->folderid         = -1;
        $record->videoidvimeo     = $videoidvimeo;
        $record->videonamevimeo   = $videovimeo['name'];
        $record->linkvideo        = $videovimeo['link'];
        $record->linkpicture      = '';
        $record->duration         = 0;
        $record->size_bytes       = 0;
        $record->quality          = '';
        $record->timecreated      = time();
        $record->timecreatedvimeo = strtotime($videovimeo['created_time']);
        $record->timemodified     = time();
        
        debugging(date("Y-m-d H:i:s"). " Inserting new video uploaded into db (accountid: {$record->accountid}, videoidvimeo: {$record->videoidvimeo}... ", NO_DEBUG_DISPLAY);
        return $DB->insert_record('block_uploadvimeo_videos', $record);
        
    }
    
    static public function update_images_from_vimeo () {
        global $DB;
        
        $params = array('quality' => 'Original');
        
        if ($videos_without_images = $DB->get_records('block_uploadvimeo_videos', $params)){
        
            foreach ($videos_without_images as $video_to_update) {
                $account = $DB->get_record('block_uploadvimeo_account', ['id' => $video_to_update->accountid]);
                $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
                
                $videovimeo = $client->request('/videos/'.$video_to_update->videoidvimeo, array(), 'GET');
                
                if ($videovimeo['status'] != '200') { // OK
                    continue;
                }
                
                if ($videovimeo['body']['transcode']['status'] != 'complete') {
                    continue;
                }
                
                $quality_size = array();
                foreach ($videovimeo['body']['download'] as $value) {
                    $quality_size[$value['size']] = $value['public_name'];
                }
                ksort($quality_size, SORT_NUMERIC);
                end($quality_size);
                
                $videouploaded = new \stdClass();
                $videouploaded->id               = $video_to_update->id;
                $videouploaded->accountid        = $video_to_update->accountid;
                if (!$video_to_update->folderid) {
                    $folder = self::get_folder_by_name($videovimeo['parent_folder']['name']);
                    $videouploaded->forderid       = $folder->id;
                }                
                $videouploaded->linkpicture      = $videovimeo['body']['pictures']['sizes'][0]['link'];
                $videouploaded->duration         = $videovimeo['body']['duration'];
                $videouploaded->size_bytes       = key($quality_size);
                $videouploaded->quality          = $quality_size[key($quality_size)];
                $videouploaded->timecreatedvimeo = strtotime($videovimeo['body']['created_time']);
                $videouploaded->timemodified     = time();
                
                $DB->update_record('block_uploadvimeo_videos', $videouploaded);
            }
        }
    }


}