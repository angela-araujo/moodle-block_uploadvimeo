<?php

namespace block_uploadvimeo\local;

use Vimeo\Vimeo;
use context_course;

define('VIDEOS_PER_PAGE', 100);
define('UPLOADVIMEO_ERROR', -1);

// Connect to Vimeo.
require_once(__DIR__ . '/../../vendor/autoload.php');

global $CFG;

require_once($CFG->dirroot."/lib/weblib.php");

class uploadvimeo {
    
    public static function video_upload($courseid, $userid, $urivideo) {
        
        global $DB;
        
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $usernamefolder = 'MoodleUpload_' . $user->username;
        $videoid = str_replace('/videos/', '', $urivideo);
        $maxattemptverifyupload = 5;
        $msgdebug = '';
        
        // Log updaload event.
        $event = \block_uploadvimeo\event\video_uploaded::create(array('courseid' => $courseid,
                'objectid' => $courseid,
                'context' => context_course::instance($courseid),
                'other' => array('videoid' => $videoid, 'folder' => $usernamefolder)));
        $event->trigger();
        
        // verify upload video.
        for ($i = 1; $i <= $maxattemptverifyupload; $i++) {
            
            if ($i > 1) sleep(5);
            
            $uploadstatus = self::verify_upload($videoid);
            
            $msgdebug .= '<br>Attempt: ' . $i . '. upload.status: ' . $uploadstatus ;
            
            if ($uploadstatus == 'complete') break;   
            
        }
        
        if ( ! ($uploadstatus == 'complete') ) {
            debugging('Error verify status - videoid=' . $videoid . $msgdebug, NO_DEBUG_DISPLAY);
        } else {
            debugging('Status videoid ' . $videoid . ':' . $uploadstatus . ' verify: ' . $msgdebug , NO_DEBUG_DISPLAY);
        }
        $folder = self::get_folder($usernamefolder);
        
        if (!$folder) {
            
            $folder = self::create_folder($usernamefolder);
            if (!$folder) {
                debugging(get_string('msg_error_not_create_folder', 'block_uploadvimeo', array('videoid' => $videoid, 'foldername'=>$usernamefolder)), NO_DEBUG_DISPLAY);
                return false;
            }            
        }
        
        $moved = self::move_video_to_folder($folder['id'], $videoid);
        
        if (!$moved) {
            debugging(get_string('msg_error_not_move_video_folder', 'block_uploadvimeo', array('videoid' => $videoid, 'foldername'=>$usernamefolder)), NO_DEBUG_DISPLAY);
            return false;
        }
        
        return true;        
        
    }
    
    static public function video_delete ($courseid, $videoid) {
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);

        // DELETE https://api.vimeo.com/videos/{video_id}
        $deleted = $client->request('/videos/'.$videoid, array(), 'DELETE');
        
        if (!$deleted['status'] == '204') { // 204 No Content - The video was deleted.
            return false;
        }
        
        // Log delete event.
        $event = \block_uploadvimeo\event\video_deleted::create(array('courseid' => $courseid,
                'objectid' => $courseid,
                'context' => context_course::instance($courseid),
                'other' => array('videoid' => $videoid)));
        $event->trigger();
        
        return true;
    }
    
    static public function get_folder(string $foldername) {
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $folderspage1 = $client->request('/me/projects', array(
                'direction' => 'asc',
                'sort' => 'name',
                'per_page' => VIDEOS_PER_PAGE,
                'page' => 1), 'GET');
        
        if ($folderspage1['body']['total'] <> '0') {
            
            $totalpages = ($folderspage1['body']['total'] > VIDEOS_PER_PAGE )? ceil($folderspage1['body']['total'] / VIDEOS_PER_PAGE): 1;
            
            // Get videos from first page.
            foreach ($folderspage1['body']['data'] as $folderpage1) {
                
                $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $folderpage1['uri']));
                list($useridvimeo, $folderid) = explode(',', $urifolder);
                
                $listfolder[] = array(
                        'id' => $folderid,
                        'name' => $folderpage1['name'],
                        'uri' => $folderpage1['uri'],
                        'created_time' => $folderpage1['created_time'],
                );
            }
            
            // Get videos from other pages.
            if ($totalpages > 1) {
                for ($i = 2; $i <= $totalpages; $i++) {
                    
                    $foldersnextpage = $client->request('/me/projects', array(
                            'direction' => 'asc',
                            'sort' => 'name',
                            'per_page' => VIDEOS_PER_PAGE,
                            'page' => $i ), 'GET');
                    
                    foreach ($foldersnextpage['body']['data'] as $foldernextpage) {
                        
                        $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $foldernextpage['uri']));
                        list($useridvimeo, $folderid) = explode(',', $urifolder);
                        
                        $listfolder[] = array(
                                'id' => $folderid,
                                'name' => $foldernextpage['name'],
                                'uri' => $foldernextpage['uri'],
                                'created_time' => $foldernextpage['created_time'],
                        );
                    }
                }
            }
            
            /*
             $folderduplicate = array_count_values(array_column($listfolder, 'name'))[$foldername];
             if ($folderduplicate > 1) {
             
                 //move_videos_to_folder();
                 //delete_folder();
             
             } */
            
            // Search the specific folder
            $folderfinded = array_search($foldername, array_column($listfolder, 'name'));
            
            if ($folderfinded) {
                return $listfolder[$folderfinded];
            } else
                
                return false;
                
        } else
            
            return false;
                
    }
    
    /**
     * @deprecated use new get_videos_from_folder_pagination
     *  
     * @param int $folderid
     * @return mixed
     */
    static public function get_videos_from_folder($folderid, $page = 1) {

        global $OUTPUT;
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);

        $folderspage = $client->request('/me/projects/'.$folderid.'/videos', array(
            'per_page' => VIDEOS_PER_PAGE), 'GET');

        $totalpages = ($folderspage['body']['total'] > VIDEOS_PER_PAGE)? ceil($folderspage['body']['total'] / VIDEOS_PER_PAGE): 1;
        
        if (($folderspage['body']['total'] <> '0') && ($page >= 1 && $page <= $totalpages)) {
                
            $folderspage = $client->request('/me/projects/'.$folderid.'/videos', array(
                'per_page' => VIDEOS_PER_PAGE,
                'page' => $page), 'GET');
        
            //echo '<pre>'; print_r($folderspage); echo '</pre>';

            foreach ($folderspage['body']['data'] as $video) {

                $videoid = str_replace('/videos/', '', $video['uri']); //[uri] => /videos/401242079
                $uri = 'https://player.vimeo.com/video/'.$videoid.'?title=0&amp;byline=0&amp;portrait=0&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=168450';
                $htmlembed = '<iframe src="'. $uri. '" width="'. $config->config_width .'" height="' . $config->config_height . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen title="'.$video['name'].'"></iframe>';
                $videotitle = self::get_short_title($video['name'], 50);

                $displayvalue = '<a data-toggle="collapse" aria-expanded="false" aria-controls="videoid_'.$videoid.'" data-target="#videoid_'.$videoid.'">';
                $displayvalue .= '<img src="'.$video['pictures']['sizes'][0]['link'].'" class="rounded" name="thumbnail_'.$videoid.'" id="thumbnail_'.$videoid.'">';
                $displayvalue .= '<span style="margin-left:10px; margin-right:20px;">'.$videotitle.'</span></a>';
                $titleinplace = new \core\output\inplace_editable('block_uploadvimeo', 'title', $videoid, true,
                        $displayvalue, $video['name'],
                        get_string('edittitlevideo', 'block_uploadvimeo'),  
                        'Novo título para o vídeo ' . format_string($video['name']));                
                
                $myvideos[] = array('name' => $video['name'],
                        'linkvideo' => $video['link'],
                        'videoid'   => $videoid, 
                        'htmlembed' => $htmlembed, //'' . $videovalue['embed']['html'] . '',
                        'thumbnail' => $video['pictures']['sizes'][0]['link'],
                        'titleinplace' => $OUTPUT->render($titleinplace)
                );
                
            }

            return $myvideos;

        } else {
            
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
    
    static private function move_video_to_folder($folderid, $videoid) {
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $videoaddfolder = $client->request('/me/projects/' . $folderid . '/videos/' . $videoid, array(), 'PUT');
        
        if ($videoaddfolder['status'] != '204') {    // 204 No Content - The video was added.            
            return false;
        }
        return true;
    }
    
    static private function delete_folder($folderid) {
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $folderdeleted = $client->request('/me/projects/' . $folderid, array('should_delete_clips' => false), 'DELETE');
        
        if ($folderdeleted['status'] != '204') {    // 204 No Content - The video was added.
            return false;
        }
        return true;
        
    }
    
    
    /**
     * Create folder in Vimeo
     * @param Vimeo $client
     * @param string $foldername
     *
     * @see https://developer.vimeo.com/api/reference/folders#create_project
     *      POST | https://api.vimeo.com/users/{user_id}/projects
     *      or
     *      POST | https://api.vimeo.com/me/projects
     * @return int|boolean
     */
    static private function create_folder(string $foldername) {
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $folder = $client->request('/me/projects', array('name' => $foldername), 'POST');
        
        if ($folder['status'] == '201') { // 201 Created - The folder was created.
            
            // $folder['body']['uri'] = /users/42385845/projects/1621667
            $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $folder['body']['uri']));
            
            list($useridvimeo, $folderid) = explode(',', $urifolder);
            
            return array('id'=>$folderid);
            
        } else {
            return false;
        }
        
    }
    
    static private function update_video($videoid, $videodescription = null) {
        
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
    
    static private function get_description_video($videoid) {
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $editvideo = $client->request('/videos/'.$videoid, array(), 'GET');
        
        if (!$editvideo['status'] == '200') { // OK
            return UPLOADVIMEO_ERROR;
        }
        return $editvideo['body']['description'];
    }
    
    
    static public function edit_title($videoid, $newtitle){
        
        global $COURSE;
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $array = array('name' => $newtitle );
        
        // Edit video.
        // PATCH https://api.vimeo.com/videos/{video_id}
        $editvideo = $client->request('/videos/'.$videoid, $array, 'PATCH');
        
        if (!$editvideo['status'] == 200) { // OK
            debugging(get_string('msg_error_vimeo', 'block_uploadvimeo', $videoid), NO_DEBUG_DISPLAY);
            return false;
        }
        
        $video = $client->request('/videos/' . $videoid, array(), 'GET');        
        
        // Log.
        $event = \block_uploadvimeo\event\video_edit_title::create( array(
                        'context' => context_course::instance($COURSE->id),
                        'other' => array('videoid' => $videoid) ));
        $event->trigger();
        
        return $video['body']['pictures']['sizes'][0]['link'];
    }
    
    static public function edit_thumbnail($videoid, $newimage){
        
        //@see: https://developer.vimeo.com/api/upload/thumbnails
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        // Step 1: Get picture uri.
        $video = $client->request('/videos/' . $videoid, array(), 'GET');
        if (!$video['status'] == 200){
            return false;
        }
        $picture_uri = $video['body']['metadata']['connections']['pictures']['uri'];
        
        // Step 2: Get the upload link for the thumbnail. create the thumbnail's resource
        $newlink = $client->request($picture_uri, array(), 'POST');
        if (!$newlink['status'] == 200) {
            return false;
        }
        
         // Step 3: Upload the thumbnail image file. 
        $response = $client->uploadImage($picture_uri, $newimage, true);
        
        if ($response) {
            $video = $client->request('/videos/' . $videoid, array(), 'GET');
            if ($video['status'] == 200){
                return $video['body']['pictures']['sizes'][0]['link'];
            }
        }
        
        return false;            
        
    }
    
    static private function verify_upload($videoid) {
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $video = $client->request('/videos/'.$videoid, array(), 'GET');
        
        if (!$video['status'] == '200') { // OK
            return UPLOADVIMEO_ERROR;
        }
        
        /*
        $link = $video['body']['link'];
        $response = $client->request('', array(), 'HEAD', array('upload' => $link));
        if (! $response['status'] == 200) {
            return false;
        }
        */
        
        return $video['body']['upload']['status'];
        //return $video['body']['status'];
    }
    
    /**
     * Get all videos from the specific page with pagination.
     * 
     * @param int $folderid
     * @param int $page
     * @param int $perpage
     * @return array [totalvideos => , myvideos => [] ]
     */
    static public function get_videos_from_folder_pagination($folderid, $page = 1, $perpage = 20) {
        global $OUTPUT;
        
        $config = get_config('block_uploadvimeo');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        // Only to get total videos. @todo: Melhorar rotina
        $result = $client->request('/me/projects/'.$folderid.'/videos', array('per_page' => 100, 'page' => 1), 'GET');
        $totalvideos = $result['body']['total'];
        
        /**
         * per_page: The number of items to show on each page of results, up to a maximum of 100.
         * @see https://developer.vimeo.com/api/reference/folders#get_project_videos 
         */
        $perpage = ($perpage > 100)? 100: $perpage;

        $page = ($page < 1)? 1: $page;
        
        $folderspage = $client->request('/me/projects/'.$folderid.'/videos', array(
            'per_page' => $perpage,
            'page' => $page,
            'sort' => 'alphabetical', // Options: alphabetical, date, default, duration, last_user_action_event_date
            'direction' => 'desc',
        ), 'GET');
        
        if ( (!$folderspage['body']['total']) or ($folderspage['body']['total'] = '0') ) {
            return array();
        }
        
        // Get videos from page.
        foreach ($folderspage['body']['data'] as $video) {
            $videoid = str_replace('/videos/', '', $video['uri']); //[uri] => /videos/401242079
            $uri = 'https://player.vimeo.com/video/'.$videoid.'?title=0&amp;byline=0&amp;portrait=0&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=168450';
            $htmlembed = '<iframe src="'. $uri. '" width="'. $config->config_width .'" height="' . $config->config_height . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen title="'.$video['name'].'"></iframe>';
            $videotitle = self::get_short_title($video['name'], 50);

            $displayvalue = '<a data-toggle="collapse" aria-expanded="false" aria-controls="videoid_'.$videoid.'" data-target="#videoid_'.$videoid.'">';
            $displayvalue .= '<img src="'.$video['pictures']['sizes'][0]['link'].'" class="rounded" name="thumbnail_'.$videoid.'" id="thumbnail_'.$videoid.'">';
            $displayvalue .= '<span style="margin-left:10px; margin-right:20px;">'.$videotitle.'</span></a>';
            $titleinplace = new \core\output\inplace_editable('block_uploadvimeo', 'title', $videoid, true,
                $displayvalue, $video['name'],
                get_string('edittitlevideo', 'block_uploadvimeo'),
                'Novo título para o vídeo ' . format_string($video['name']));
                
            $myvideos[] = array('name' => $video['name'],
                'linkvideo' => $video['link'],
                'videoid'   => $videoid,
                'htmlembed' => $htmlembed, //'' . $videovalue['embed']['html'] . '',
                'thumbnail' => $video['pictures']['sizes'][0]['link'],
                'titleinplace' => $OUTPUT->render($titleinplace)
            );
            
        }
        $nextlink = new \moodle_url($folderspage['body']['paging']['next']);
        $previouslink = new \moodle_url($folderspage['body']['paging']['previous']);
        $firstlink = new \moodle_url($folderspage['body']['paging']['first']);
        $lastlink = new \moodle_url($folderspage['body']['paging']['last']);
        
        return array(
            'totalvideos' => $totalvideos,
            'page' => $folderspage['body']['page'],
            'perpage' => $folderspage['body']['per_page'],
            'next' => $nextlink->get_param('page'),
            'previous' => $previouslink->get_param('page'),
            'first' => $firstlink->get_param('page'),
            'last' => $lastlink->get_param('page'),
            'videos' => $myvideos);
        
    }    
    
}