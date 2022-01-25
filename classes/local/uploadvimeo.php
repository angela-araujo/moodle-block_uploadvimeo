<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Class UploadVimeo.
 *
 * @package   block_uploadvimeo
 * @copyright 2020 CCEAD PUC-Rio (@angela-araujo)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uploadvimeo\local;

use Vimeo\Vimeo;
use context_course;

define('VIDEOS_PER_PAGE_TO_ADD_DB', 10);
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
        // 1. Get folder in DB.
        $folder = self::get_folder($userid);
        
        if (!$folder) {
            
            // 2. Get folder in Vimeo and persist in DB.
            $folder = self::vimeo_get_folder_to_persist($userid);
            
            if (!$folder) {
            
                // 3. Create folder in Vimeo and persist in DB.
                $folder = self::vimeo_create_folder($userid, $usernamefolder);
                if (!$folder) {
                    debugging(get_string('msg_error_not_create_folder', 'block_uploadvimeo', array('videoid' => $videoidvimeo, 'foldername'=>$usernamefolder)), NO_DEBUG_DISPLAY);
                    return false;
                }
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
        
        // Get name folder to search in vimeo.
        $user = $DB->get_record('user', array('id' => $userid), 'username', MUST_EXIST);
        $foldername = "MoodleUpload_{$user->username}";
        
        $foldervimeoarray = self::vimeo_search_folder($foldername, 10);
        
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
        } else {
            $msg = 'function vimeo_get_folder_to_persist: ' . var_dump($foldervimeoarray);
            debugging($msg, NO_DEBUG_DISPLAY);
            return false;
        }
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
        
        define('STATUS_FOLDER_CREATED', '201');
        
        $config = get_config('block_uploadvimeo');
        $account = $DB->get_record('block_uploadvimeo_account', ['id' => $config->accountvimeo]);
        $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
        
        $folder = $client->request('/me/projects', array('name' => $foldername), 'POST');
        
        if ($folder['status'] == STATUS_FOLDER_CREATED) { 
            
            $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $folder['body']['uri']));
            list($useridvimeo, $folderid) = explode(',', $urifolder);
            unset($useridvimeo);
            $foldervimeo = new \stdClass();
            $foldervimeo->userid = $userid;
            $foldervimeo->accountid = $config->accountvimeo;
            $foldervimeo->foldernamevimeo = $folder['body']['name'];
            $foldervimeo->folderidvimeo = $folderid;
            $foldervimeo->timecreated = time();
            $foldervimeo->timecreatedvimeo = strtotime($folder['body']['created_time']); // Ex.: [created_time] => 2020-09-29T14:15:37+00:00
            
            $moodlefolderid = $DB->insert_record('block_uploadvimeo_folders', $foldervimeo);
            
            return $DB->get_record('block_uploadvimeo_folders', array('id' => $moodlefolderid), '*', MUST_EXIST);
           
        } else {
            $msg = '<pre>function vimeo_create_folder: <br>' . var_dump($folder) . '</pre>';
            debugging($msg, NO_DEBUG_DISPLAY);   
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
            self::delete_video_not_found_in_vimeo($videoid);
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
        
        $countvideos = $DB->count_records_sql($selectcount. $sql, $params);
        
        $totalpage = ceil($countvideos/VIDEOS_PER_PAGE);
        $page = (($page > $totalpage-1) and ($page < 0) )? 0: $page;
        
        $folderspage = $DB->get_records_sql($select. $sql . $order, $params, $page*VIDEOS_PER_PAGE, VIDEOS_PER_PAGE);
        
        $order = 0;
        $myvideos = [];
        
        // Get videos from page.
        foreach ($folderspage as $video) {
            
            $videoname = $video->videonamevimeo;
            
            // From 14-09-2021 it became mandatory to add the hash parameter to the embed url.
            $search = 'https://vimeo.com/' . $video->videoidvimeo . '/';
            $hash = str_replace($search, '', $video->linkvideo); 
            
            $uri = 'https://player.vimeo.com/video/'.$video->videoidvimeo .
            '?title=0&amp;byline=0&amp;portrait=0&amp;badge=0&amp;autopause=0&amp;player_id=0'.
            '&amp;h='.$hash.'&amp;app_id='.$app_id;
            
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
     * Function that search folder in vimeo, run to all pages
     * 
     * @param Vimeo $client 
     * @param string $foldername
     * @param int $page
     * @param int $per_page
     * @return boolean|array array of folder found in vimeo or false if not
     */
    static protected function vimeo_search_folder ($foldername, $per_page = FOLDERS_PER_PAGE) {
        
        global $DB;
        $config = get_config('block_uploadvimeo');
        $account = $DB->get_record('block_uploadvimeo_account', ['id' => $config->accountvimeo]);
        $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
        
        $totalpages = 1;
        
        for ($page = 1; $page <= $totalpages; $page++) {
        
            $param = array('direction' => 'asc', 'sort' => 'name', 'per_page' => $per_page, 'page' => $page);
            
            $foldersvimeo = $client->request('/me/projects', $param, 'GET');
            
            $totalfolders = $foldersvimeo['body']['total'];
            
            $totalpages = ($totalfolders > FOLDERS_PER_PAGE)? ceil($totalfolders / FOLDERS_PER_PAGE): 1;
            
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
    static public function vimeo_get_all_videos_from_folder(\progress_trace $trace, $folder, $page_required = 1) {
        
        global $DB;
        
        $trace->output(" vimeo_get_all_videos_from_folder [accountid={$folder->accountid}, folderid={$folder->id}, foldervimeoid={$folder->folderidvimeo}]", 1);
        
        $params = array('id' => $folder->accountid, 'status' => '1');
        
        if ($account = $DB->get_record('block_uploadvimeo_account', $params) ) {
            
            $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
            
            $totalpage = $page_required;
            
            for ($page = $page_required; $page <= $totalpage; $page++) {
                
                $trace->output(" call [request(/me/projects/{$folder->folderidvimeo}/videos)] ", 1);
                $param = array('direction' => 'asc', 'sort' => 'date', 'per_page' => VIDEOS_PER_PAGE_TO_ADD_DB, 'page' => $page);
                $videos = $client->request("/me/projects/{$folder->folderidvimeo}/videos", $param);
                
                $totalvideos = $videos['body']['total'];
                $totalpage = ($totalvideos > VIDEOS_PER_PAGE_TO_ADD_DB)? ceil($totalvideos / VIDEOS_PER_PAGE_TO_ADD_DB): 1;
                $trace->output("folderid:{$folder->id} folderidvimeo:{$folder->folderidvimeo} {$folder->foldernamevimeo} totalvideo:$totalvideos page:$page/$totalpage ", 5);
                
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
                    $record->page             = $page;
                    
                    yield $record;
                } // End foreach $videos.
            } // End foreach $page.
        } // End if $account.
    }
    
    static public function add_all_videos(\progress_trace $trace) {
        
        global $DB;
        
        $trace->output(date("Y-m-d H:i:s"). " Get all folders to fetch videos... ", 0);
        $config = get_config('block_uploadvimeo');
        $log = util::get_json_file();
        
        $where = " ";
        $pagecurrent = 1;
        if ($log) {
            $trace->output("Searching last sync... " . json_encode($log), 0);
            $where = " WHERE f.foldernamevimeo >= (SELECT f1.foldernamevimeo 
                                                     FROM {block_uploadvimeo_folders} f1 
                                                    WHERE f1.id= {$log->folderid})";
        }
        
        $sql = "SELECT f.*,
                       CASE WHEN f.accountid = {$config->accountvimeo} THEN 0 ELSE 1 END order_account 
                  FROM {block_uploadvimeo_folders} f 
                $where  
              ORDER BY f.foldernamevimeo, order_account";
        
        if ( $folders = $DB->get_records_sql($sql) ) {
            
            foreach ($folders as $folder) {
                
                $num_video = 0;
                $pagecurrent = ($log->folderid == $folder->id)? $log->page: 1;
                
                foreach (self::vimeo_get_all_videos_from_folder($trace, $folder, $pagecurrent) as $video_vimeo_current) {

                    $video_vimeo = new \stdClass();
                    $video_vimeo->accountid        = $video_vimeo_current->accountid;
                    $video_vimeo->folderid         = $video_vimeo_current->folderid;
                    $video_vimeo->videoidvimeo     = $video_vimeo_current->videoidvimeo;
                    $video_vimeo->videonamevimeo   = $video_vimeo_current->videonamevimeo;
                    $video_vimeo->linkvideo        = $video_vimeo_current->linkvideo;
                    $video_vimeo->linkpicture      = $video_vimeo_current->linkpicture;
                    $video_vimeo->duration         = $video_vimeo_current->duration;
                    $video_vimeo->size_bytes       = $video_vimeo_current->size_bytes;
                    $video_vimeo->quality          = $video_vimeo_current->quality;
                    $video_vimeo->timecreated      = $video_vimeo_current->timecreated;
                    $video_vimeo->timecreatedvimeo = $video_vimeo_current->timecreatedvimeo;
                    $video_vimeo->timemodified     = $video_vimeo_current->timemodified;
                    
                    $num_video++;
                    $msgtrace = "videoidvimeo:{$video_vimeo->videoidvimeo}";

                    // Verify whether video exists.
                    $params = array(
                        'accountid' => $video_vimeo->accountid,
                        'videoidvimeo' => $video_vimeo->videoidvimeo
                    );
                    $video = $DB->get_record('block_uploadvimeo_videos', $params);
                    
                    if (!$video) {
                        // Add new video.
                        $trace->output("video #$num_video - Saving $msgtrace...", 10);
                        $DB->insert_record('block_uploadvimeo_videos', $video_vimeo);
                        
                    } else {
                        // Update video.
                        if ($video->videonamevimeo != $video_vimeo->videonamevimeo || 
                            $video->linkvideo != $video_vimeo->linkvideo ||
                            $video->linkpicture != $video_vimeo->linkpicture) {
                                
                                $trace->output("video #$num_video - Updating $msgtrace...", 10);
                                $video_vimeo->id = $video->id;
                                $DB->update_record('block_uploadvimeo_videos', $video_vimeo);
                            } else {
                                $trace->output("video #$num_video - Skipping $msgtrace...", 10);
                            }
                    }
                    $video_vimeo->page = $num_video;
                    util::save_json_file($video_vimeo_current);
                }
            }
            try {
                $trace->output("Deleting log...", 10);
                util::delete_file_temp(util::get_path_temp('log_sync.json'));
            } catch (\Exception $e) {
                $trace->output("Error to delete log: {$e->getMessage()}", 10);
                util::save_json_file(new \stdClass());
            }
            
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
    
    static public function update_images_from_vimeo(\progress_trace $trace) {
        global $DB;
        
        $params = array('quality' => 'Original');
        
        if ($videos_without_images = $DB->get_records('block_uploadvimeo_videos', $params)){
        
            $trace->output(date("Y-m-d H:i:s") . " Start update image s link for " . count($videos_without_images) . " videos...");
            
            foreach ($videos_without_images as $video_to_update) {
                $account = $DB->get_record('block_uploadvimeo_account', ['id' => $video_to_update->accountid]);
                $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
                
                $videovimeo = $client->request('/videos/'.$video_to_update->videoidvimeo, array(), 'GET');
                
                if ($videovimeo['status'] != '200') { // OK
                    $trace->output(date("Y-m-d H:i:s") . " -> Do not update videoid:{$video_to_update->id} link:{$video_to_update->linkvideo} return status {$videovimeo['status']} from vimeo");
                    continue;
                }
                
                if ($videovimeo['body']['transcode']['status'] != 'complete') {
                    $trace->output(date("Y-m-d H:i:s") . " -> Do not update videoid:{$video_to_update->id} link:{$video_to_update->linkvideo} return status transcode {$videovimeo['body']['transcode']['status']} from vimeo");
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
                $trace->output(date("Y-m-d H:i:s") . " -> Video updated success videoid:{$video_to_update->id} link:{$video_to_update->linkvideo}");
            }
        } else {
                $trace->output(date("Y-m-d H:i:s") . "  No videos to be updated");
            }
    }
    
    /**
     * Update video's record
     * 
     * 
     * @param string $foldername
     * @param int $accountid
     * @param bool $verbose
     */
    static public function update_videos_by_folder_or_account(string $foldername='', int $accountid=-1, bool $verbose=true) {
        global $DB;
        
        $params = array();
        $where = '';
        $countvideo['ok'] = [];
        $countvideo['error'] = [];        
        
        if ($foldername) {
            $where .= " AND f.foldernamevimeo = :foldername";
            $params['foldername'] = $foldername;
        }
        
        if ($accountid > 0) {
            $where .= " AND a.id = :accountid";
            $params['accountid'] = $accountid;
        }        
        
        $sql = "
              SELECT v.id, 
                     v.videoidvimeo, 
                     v.videonamevimeo,
                     v.linkvideo, 
                     v.folderid, 
                     f.folderidvimeo, 
                     f.foldernamevimeo,
                     a.id accountid,
                     a.name,
                     a.clientid,
                     a.clientsecret,
                     a.accesstoken,
                     a.app_id,
                     a.status
                FROM {block_uploadvimeo_videos} v
                JOIN {block_uploadvimeo_folders} f 
                  ON v.accountid = f.accountid 
                 AND v.folderid = f.id
                JOIN {block_uploadvimeo_account} a 
                  ON f.accountid = a.id
               WHERE 1 = 1 
               $where";
        $videos_to_update = $DB->get_records_sql($sql, $params);
        
        if (!$videos_to_update){
            if ($verbose) {
                mtrace(date("Y-m-d H:i:s") . "  No videos to be updated");
            }
            return false;
        }
            
        if ($verbose) {
            mtrace(date("Y-m-d H:i:s") . " Start update for " . count($videos_to_update) . " videos...");
        }
        
        foreach ($videos_to_update as $v) {                

            $client = new Vimeo($v->clientid, $v->clientsecret, $v->accesstoken);                
            $videovimeo = $client->request('/videos/'.$v->videoidvimeo, array(), 'GET');
            
            if ($videovimeo['status'] != '200') { // OK
                $countvideo['error'][] = "Account:{$v->accountid}-{$v->name} Folder:[{$v->folderidvimeo} - {$v->foldernamevimeo}] videoid:{$v->videoidvimeo} link:{$v->linkvideo} Error: {$videovimeo['status']}"; 
                
                continue;
            }
            
            if ($videovimeo['body']['transcode']['status'] != 'complete') {
                $countvideo['error'][] = "Account:{$v->accountid}-{$v->name} Folder:[{$v->folderidvimeo} - {$v->foldernamevimeo}] videoid:{$v->videoidvimeo} link:{$v->linkvideo} Error(transcode): {$videovimeo['body']['transcode']['status']}";
                continue;
            }
            
            $quality_size = array();
            foreach ($videovimeo['body']['download'] as $value) {
                $quality_size[$value['size']] = $value['public_name'];
            }
            ksort($quality_size, SORT_NUMERIC);
            end($quality_size);
            
            $data = new \stdClass();
            $data->id               = $v->id;
            $data->accountid        = $v->accountid;
            if (!$v->folderid) {
                $folder = self::get_folder_by_name($videovimeo['parent_folder']['name']);
                $data->forderid       = $folder->id;
            }
            $data->link             = $videovimeo['body']['link'];
            $data->linkpicture      = $videovimeo['body']['pictures']['sizes'][0]['link'];
            $data->duration         = $videovimeo['body']['duration'];
            $data->size_bytes       = key($quality_size);
            $data->quality          = $quality_size[key($quality_size)];
            $data->timecreatedvimeo = strtotime($videovimeo['body']['created_time']);
            $data->timemodified     = time();
            
            if ($DB->update_record('block_uploadvimeo_videos', $data) ) {
                $countvideo['ok'][] = "Account:{$v->accountid}-{$v->name} Folder:[{$v->folderidvimeo} - {$v->foldernamevimeo}] Success videoid:{$v->videoidvimeo} link:{$data->link}";                    
            }                
        }
        
        if ($verbose) {
            mtrace(date("Y-m-d H:i:s") . ' Resume:');
            
            mtrace(' Total updated ok: ' . count($countvideo['ok']) );                
            foreach ($countvideo['ok'] as $message) {
                mtrace('     ' . $message);
            }
            
            mtrace(' Total updated error: ' . count($countvideo['error']));            
            foreach ($countvideo['error'] as $message) {
                mtrace('     ' . $message);
            }
            
            mtrace(date("Y-m-d H:i:s") . ' Finish');
        }
        
        return true;
        
    }
    
    static public function delete_video_not_found_in_vimeo(int $videoid) {
        //  Status: 404 Error: The requested video couldn't be found.
        
        global $DB, $COURSE;
        
        $video = $DB->get_record('block_uploadvimeo_videos', ['id' => $videoid]);
        if (!$video) {
            return false;
        }
        
        $account = $DB->get_record('block_uploadvimeo_account', ['id' => $video->accountid]);
        $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);
        
        // Step 1: Get picture uri.
        $videovimeo = $client->request("/videos/{$video->videoidvimeo}", array(), 'GET');
        
        if ($videovimeo['status'] == 404){
            // Deleting in DB.
            $DB->delete_records('block_uploadvimeo_videos', ['id' => $videoid]);
            
            // Log delete event.
            $event = \block_uploadvimeo\event\video_deleted::create(
                array(
                    'courseid' => $COURSE->id,
                    'objectid' => $videoid,
                    'context' => context_course::instance($COURSE->id),
                    'other' => array('videoid' => $videoid)));
                $event->trigger();
        }
    }

    static public function zoom_full_upload(\progress_trace $trace) {
        global $DB;

        $zoommoduleid = $DB->get_field('modules', 'id', ['name' => 'zoom']);

        $sql =
          "SELECT z.*, cm.course
             FROM {zoom} z
             JOIN {course_modules} cm
               ON cm.instance = z.id AND cm.module = ?
        LEFT JOIN {block_uploadvimeo_zoom} uz
               ON uz.zoomid = z.id
            WHERE uz.id IS NULL";
        $params = [$zoommoduleid];

        $zoomstoupload = $DB->get_records_sql($sql, $params);

        $account = get_config('block_uploadvimeo', 'accountvimeo');
        $vimeo = $DB->get_record('block_uploadvimeo_account', ['id' => $account]);
        $vimeoclient = new \Vimeo\Vimeo($vimeo->clientid, $vimeo->clientsecret, $vimeo->accesstoken);

        $zoomservice = new \block_uploadvimeo\zoom();

        foreach ($zoomstoupload as $zoom) {
            try {
                $recordings = $zoomservice->get_recordings($zoom->meeting_id);
            } catch (\Exception $e) {
                $trace->output($e->errorcode . ' - ' . $e->response . ' - ' . $zoom->id);
                $record = (object)['zoomid' => $zoom->id, 'timecreated' => time()];
                if ($DB->insert_record('block_uploadvimeo_zoom', $record)) {
                    $trace->output('upload saved (no recordings):' . $zoom->id);
                } else {
                    $trace->output('WARNING: upload NOT saved:' . $zoom->id);
                }
                continue;
            }
            if (!$hostuser = self::zoom_get_host_user($zoom, $recordings)) {
                $trace->output('WARNING: user not found, host_email = ' . $recordings->host_email . ' ; upload NOT saved:' . $zoom->id);
            }

            $files = self::filter_recording_files($recordings->id, $recordings->recording_files, $zoomservice);

            foreach ($recordings->participant_audio_files as $file) {
                $zoomservice->delete_recording($recordings->id, $file->id);
            }
            $recordings = (object) [
                'download_access_token' => $recording->download_access_token,
                'recording_files' => $files
            ];
            self::zoom_send_recordings_to_vimeo($recordings, $hostuser, $zoom, $vimeoclient);
        }
    }

    public static function zoom_process_webhook($notification) {
        global $DB;

        $account = get_config('block_uploadvimeo', 'accountvimeo');
        $vimeo = $DB->get_record('block_uploadvimeo_account', ['id' => $account]);
        $vimeoclient = new \Vimeo\Vimeo($vimeo->clientid, $vimeo->clientsecret, $vimeo->accesstoken);

        $zoom = $DB->get_record('zoom', ['meeting_id' => $notification->payload->object->id]);

        $zoomservice = new \block_uploadvimeo\zoom();

        if (!$hostuser = self::zoom_get_host_user($zoom, $notification->payload->object)) {
            return false;
        }

        $files = self::filter_recording_files(
            $notification->payload->object->id,
            $notification->payload->object->recording_files,
            $zoomservice
        );

        if (isset($notification->payload->object->participant_audio_files)) {
            foreach ($notification->payload->object->participant_audio_files as $file) {
                $zoomservice->delete_recording($notification->payload->object->id, $file->id);
            }
        }

        $recordings = (object) [
            'download_access_token' => $notification->download_token,
            'recording_files' => $files
        ];

        self::zoom_send_recordings_to_vimeo($recordings, $hostuser, $zoom, $vimeoclient);
    }

    public static function zoom_delete_completed($trace) {
        global $DB;

        $sql = "SELECT uvz.*, z.meeting_id, uvv.accountid, uvv.videoidvimeo
                  FROM {block_uploadvimeo_zoom} uvz
                  JOIN {zoom} z
                    ON z.id = uvz.zoomid
                  JOIN {block_uploadvimeo_videos} uvv
                    ON uvv.videoidvimeo = uvz.vimeovideoid
                 WHERE uvz.vimeocompleted = 0";
        $videostocheck = $DB->get_records_sql($sql);

        $zoomservice = new \block_uploadvimeo\zoom();

        $trace->output('Videos para verificar: ' . count($videostocheck));
        foreach ($videostocheck as $v) {
            $account = $DB->get_record('block_uploadvimeo_account', ['id' => $v->accountid]);
            $client = new Vimeo($account->clientid, $account->clientsecret, $account->accesstoken);

            $videovimeo = $client->request('/videos/'.$v->videoidvimeo, array(), 'GET');

            if ($videovimeo['status'] == '200') {
                if ($videovimeo['body']['transcode']['status'] == 'complete') {
                    $v->vimeocompleted = 1;
                    $trace->output('Concluído: ' . $v->videoidvimeo);
                    $DB->update_record('block_uploadvimeo_zoom', $v);
                    $zoomservice->delete_recording($v->meeting_id, $v->recordingid);
                } else {
                    $trace->output('Processando: ' . $v->videoidvimeo);
                }
            } else {
                $trace->output('Erro: ' . var_export($videovimeo));
            }
        }
    }


    private static function upload_pull_metadata($recordingfile, $recordings, $hostuser, $zoom) {
        $videostart = new \DateTime($recordingfile->recording_start);
        $videostart->setTimezone(new \DateTimeZone('America/Sao_Paulo'));
        $videoname = $zoom->name . ' (' . $videostart->format('d/m/Y H:i:s') . ')';
        return [
            'upload' => [
                "approach" => "pull",
                "size" => $recordingfile->file_size,
                "link" => $recordingfile->download_url . '?access_token=' . $recordings->download_access_token
            ],
            'name' => $videoname,
            'description' => $hostuser->username,
            'privacy' => [
                'download' => false,
                'view' => 'unlisted',
                'embed' => 'whitelist',
                'add' => false,
                'comments' => 'nobody'
            ],
            'embed' => [
                'buttons' => [
                    'embed' => false,
                    'fullscreen' => true,
                    'like' => false,
                    'share' => false
                ],
                'color' => '#ff9933',
                'logos' => [
                    'vimeo' => false,
                    'custom' => [
                        'active' => false
                    ]
                ],
                'title' => [
                    'name' => 'hide',
                    'portrait' => 'hide'
                ],
                'speed' => true
            ]
        ];
    }

    private static function zoom_get_host_user($zoom, $recordings) {
        $users = get_enrolled_users(
            context_course::instance($zoom->course),
            'moodle/course:manageactivities',
            0,
            'u.id,u.username,u.email'
        );
        foreach ($users as $user) {
            if ($user->email == $recordings->host_email) {
                return $user;
            }
        }
        return null;
    }

    private static function zoom_send_recordings_to_vimeo($recordings, $hostuser, $zoom, $vimeoclient, $trace = null) {
        global $DB;

        foreach ($recordings->recording_files as $recordingfile) {

            // First insert without vimeoid
            // Important for webhook that may be trigered twice.
            $record = (object)[
                'zoomid' => $zoom->id,
                'timecreated' => time(),
                'recordingid' => $recordingfile->id,
                'vimeocompleted' => 0
            ];
            if ($record->id = $DB->insert_record('block_uploadvimeo_zoom', $record)) {

                // Send Zoom's download URL to Vimeo to upload using Pull Approach.
                $result = $vimeoclient->request('/me/videos',
                    self::upload_pull_metadata($recordingfile, $recordings, $hostuser, $zoom), 'POST');

                if ($result['status'] == 201) {
                    if (!is_null($trace)) {
                        $trace->output('Video sent to vimeo:' . $result['body']['uri']);
                    }

                    if (self::video_upload($zoom->course, $hostuser->id, $result['body']['uri'])) {
                        $vimeovideoid = explode('/', $result['body']['uri'])[2];
                        $record->vimeovideoid = $vimeovideoid;
                        if ($DB->update_record('block_uploadvimeo_zoom', $record)) {
                            if (!is_null($trace)) {
                                $trace->output('Upload saved:' . $zoom->id);
                            }
                        } else {
                            if (!is_null($trace)) {
                                $trace->output('WARNING: upload NOT saved:' . $zoom->id);
                            }
                        }
                    }
                } else {
                    if (!is_null($trace)) {
                        $trace->output('Video NOT sent to vimeo:' . var_export($result, true));
                    }
                }
            } else {
                if (!is_null($trace)) {
                    $trace->output('Upload not saved:' . $zoom->id);
                }
            }
        }
    }

    public static function filter_recording_files($meeting_id, $recording_files, $zoomservice) {
        global $DB;
        $files = [];
        foreach ($recording_files as $file) {
            if ($file->recording_type == 'audio_only') {

                try {
                    $zoomservice->delete_recording($meeting_id, $file->id);
                } catch (\Exception $e) {
                }

            } else if (($file->recording_type == 'shared_screen_with_speaker_view') &&
                       ($file->status == 'completed')) {

                if (!$DB->record_exists('block_uploadvimeo_zoom', ['recordingid' => $file->id])) {
                    $files[] = $file;
                }
            }
        }
        return $files;
    }
}
