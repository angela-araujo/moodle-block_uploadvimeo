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
 * Renderer for the uload video to vimeo block.
 *
 * @package   block_uploadvimeo
 * @copyright 2020 CCEAD PUC-Rio
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use Vimeo\Vimeo;
use Vimeo\Exceptions\VimeoUploadException;

define('VIDEOS_PER_PAGE', 100);

class block_uploadvimeo_renderer extends plugin_renderer_base {
    
    /**
     * Display the main page block
     * 
     * @param int $blockid
     * @param int $courseid
     * @return string|boolean
     */
    public function display_main_page($courseid) {
        
        $data = new stdClass();
        $data->url = new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]);        
        return $this->render_from_template('block_uploadvimeo/main', $data);

    }
    

    /**
     * Display Page Videos Vimeo
     * @param int $courseid The course id 
     * @param int $userid The user logged
     * @param string $urivideo The uri returned by upload function in js.(e.g. $urivideo = '/videos/403691153')
     * @param object $config Settings block upload vimeo
     */
    public function display_page_videos(int $courseid, int $userid, string $urivideo = null, $config) {
        
        global $DB;       
        
        // Connect to Vimeo.
		require_once(__DIR__ . '/vendor/autoload.php');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $usernamefolder = 'MoodleUpload_' . $user->username;

        $folder = $this->get_folder($client, $usernamefolder);
        
        if ($urivideo) {            
            
            $videoid = str_replace('/videos/', '', $urivideo);
            
            $description = $this->get_description_video($client, $videoid);
            
            if (stripos($description, $user->username) === false ) {
                $description .= '(' . $user->username . ')';
            };
            
            $this->update_video($config, $client, $videoid, $description);

            if (!$folder) {
                
                $folder = $this->create_folder($client, $usernamefolder);
                
            } 
            
            $this->move_video_to_folder($client, $folder['id'], $videoid);
            
        }
        
        $textmyvideos = get_string('text_line1', 'block_uploadvimeo');
        
        $videos = '';
        
        // Get all the videos in a folder.
        if ($folder) {								  
							
            $videos = $this->get_videos_from_folder($config, $client, $folder['id']);            
            
            if ($videos) {
                
                $textmyvideos .= '<br><br>' . get_string('text_line2_with_video', 'block_uploadvimeo') . '<br><br>';
                
            } else {
                
                $textmyvideos .= '<br><br>' . get_string('text_line2_empty', 'block_uploadvimeo') . '<br><br>';
            }
            
        } else {
            
            $textmyvideos .= '<br><br>' . get_string('text_line2_empty', 'block_uploadvimeo') . '<br><br>';
            
        }        
         
        $data = new stdClass();        
        $data->heading = get_string('pluginname', 'block_uploadvimeo');
        $data->url = new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid, 'userid' => $userid]);
        $data->class = 'block_uploadviemto_links';        
        $data->myvideos = $videos;
		$data->textmyvideos = $textmyvideos;	
		$data->accesstoken = $config->config_accesstoken;
        
        // Start output to browser.
        echo $this->output->header();
        echo $this->render_from_template('block_uploadvimeo/form', $data);
        echo $this->output->footer();
        
    }
    

    /**
     * Get folder Vimeo
     * 
     * @param Vimeo $client
     * @param string $foldername
     * 
     * @return array[]|boolean array folder or false is no folder
     */
    private function get_folder($client, string $foldername) {
        
        $folderspage1 = $client->request('/me/projects', array(
                'direction' => 'asc', 
                'sort' => 'name',
                'per_page' => VIDEOS_PER_PAGE, 
                'page' => 1), 'GET');
        
        if ($folderspage1['body']['total'] <> '0') {
            
            $totalpages = ($folderspage1['body']['total'] > VIDEOS_PER_PAGE )? ceil($folderspage1['body']['total'] / VIDEOS_PER_PAGE): 1;
            
            // Get videos from first page
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
            
            // Get videos from other pages
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
                
                //@TODO: $this->move_videos_to_folder();
                //@TODO: $this->delete_folder();               
                
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
    
    private function get_videos_from_folder($config, $client, $folderid) {
        
        $videos = $client->request('/me/projects/'.$folderid.'/videos');
        
        if ($videos['body']['total'] <> '0') { // OK
            
            foreach ($videos['body']['data'] as $video) {
                
                $videoid = str_replace('/videos/', '', $video['uri']);
                $uri = 'https://player.vimeo.com/video/'.$videoid.'?title=0&amp;byline=0&amp;portrait=0&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=168450';
                $htmlembed = '<iframe src="'. $uri. '" width="'. $config->config_width .'" height="' . $config->config_height . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen title=""></iframe>';
                
                $myvideos[] = array('name' => $video['name'],
                        'linkvideo' => $video['link'],
                        'videoid' => $videoid, //[uri] => /videos/401242079
                        'htmlembed' => $htmlembed, //'' . $videovalue['embed']['html'] . '',
                        'thumbnail' => $video['pictures']['sizes'][0]['link'],
                );
                
            }
            
            return $myvideos;
            
        } else {
            
            return false;
            
        } 
        
    }
    
    private function move_video_to_folder($client, $folderid, $videoid) {
        
        $videoaddfolder = $client->request('/me/projects/' . $folderid . '/videos/' . $videoid, array(), 'PUT');
        
        if ($videoaddfolder['status'] != '204') {    // 204 No Content - The video was added.
            echo '<hr><pre>response video add folder: <br>'; print_r($videoaddfolder); echo '</pre>';
            return false;
        }
        return true;
    }
    
    private function delete_folder($client, $folderid) {
        
        $folderdeleted = $client->request('/me/projects/' . $folderid, array('should_delete_clips' => false), 'DELETE');
        
        if ($folderdeleted['status'] != '204') {    // 204 No Content - The video was added.
            echo '<hr><pre>response video add folder: <br>'; print_r($folderdeleted); echo '</pre>';
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
    private function create_folder(Vimeo $client, string $foldername) {

        $folder = $client->request('/me/projects', array('name' => $foldername), 'POST');
        
        if ($folder['status'] == '201') { // 201 Created - The folder was created.
            
            // $folder['body']['uri'] = /users/42385845/projects/1621667
            $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $folder['body']['uri'])); 
            
            list($useridvimeo, $folderid) = explode(',', $urifolder);
            
            return $folderid;
            
        } else {
            return false;
        }
        
    }
    
    private function update_video($config, $client, $videoid, $videodescription = null) {
        
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
            echo '<h5>update_video</h5><pre>'; print_r($editvideo); '</pre>';
            return false;
        }
        else 
            return true;
    }
    
    private function get_description_video(Vimeo $client, $videoid) {
        
        $editvideo = $client->request('/videos/'.$videoid, array(), 'GET');
        
        if (!$editvideo['status'] == '200') { // OK
            //echo '<h5>update_video</h5><pre>'; print_r($editvideo); '</pre>';
            return false;
        }
        else 
            return $editvideo['body']['description'];
    }
    

    
    
}
