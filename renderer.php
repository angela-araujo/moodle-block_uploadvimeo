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


class block_uploadvimeo_renderer extends plugin_renderer_base {
    
    /**
     * Display the main page block
     * 
     * @param int $blockid
     * @param int $courseid
     * @return string|boolean
     */
    function display_main_page($courseid) {
        
        $data = new stdClass();
        $data->url = new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]);        
        return $this->render_from_template('block_uploadvimeo/main', $data);

    }
    

    function display_page_videos(int $courseid, int $userid, string $urivideo = null, $config){
        
        global $DB;
		
        $folderid = false;        
        
        // Connect to Vimeo.
		require_once(__DIR__ . '/vendor/autoload.php');
        $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
        
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $usernamefolder = 'MoodleUpload_' . $user->username;
        $listmyvideos = array();

        // Verify if folder exists
        $folders = $client->request('/me/projects', array(), 'GET'); // echo '<pre>'; print_r($response['body']['data'][1]); echo '</pre>'; exit;
        
        if ($folders['body']['total'] <> '0') {
            
            // Looking for the folder from the user moodle.
            foreach ($folders['body']['data'] as $folderkey => $foldervalue) {
                
                if ($usernamefolder == $foldervalue['name']) {
                    
                    $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $foldervalue['uri']));
                    list($useridvimeo, $folderid) = explode(',', $urifolder);
                    break;
                    
                }
            }
        }
        
        
        // If there is uri video, then uploaded success.
        //$videoid = '403040371';
        
        //if ($videoid) {
        if ($urivideo) {
            $videoid = str_replace('/videos/', '', $urivideo);
            
            if ($folderid) {
                // Add video to the folder.
                $videoaddfolder = $client->request('/me/projects/' . $folderid . '/videos/' . $videoid, array(), 'PUT');
                
            } else {
                // PUT /me/projects/{project_id}/videos/{video_id}
                // Create folder
                $foldercreated = $client->request('/me/projects', array('name' => $usernamefolder), 'POST');
                
                if ($foldercreated['status'] == '201') { // 201 Created	- The folder was created.
                    
                    $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $foldercreated['body']['uri'])); ///users/42385845/projects/1621667
                    
                    
                    list($useridvimeo, $folderid) = explode(',', $urifolder);
                    
                    $videoaddfolder = $client->request('/me/projects/' . $folderid . '/videos/' . $videoid, array(), 'PUT');
                    
                    if ($videoaddfolder['status'] != '204') {    // 204 No Content	- The video was added.
                        echo '<hr><pre>response video add folder: <br>'; print_r($videoaddfolder); echo '</pre>';
                    }
                    
                } else {
                    echo '<hr><pre>response create folder: <br>'; print_r($foldercreated); echo '</pre>';
                }
                
                
            }
        }
        
        $textmyvideos = get_string('text_line1', 'block_uploadvimeo');
        
        // Get all the videos in a folder.
        if ($folderid) {								  
							
            $videos = $client->request('/me/projects/'.$folderid.'/videos');
            
            if ($videos['body']['total'] <> '0') { // OK
                
                $textmyvideos .= '<br><br>' . get_string('text_line2_with_video', 'block_uploadvimeo') . '<br><br>';
                
                foreach ($videos['body']['data'] as $videokey => $videovalue) {
                    $videoid = str_replace('/videos/', '', $videovalue['uri']);
                    $uri = 'https://player.vimeo.com/video/'.$videoid.'?title=0&amp;byline=0&amp;portrait=0&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=168450';
                    $htmlembed = '<iframe src="'. $uri. '" width="600" height="400" frameborder="0" allow="autoplay; fullscreen" allowfullscreen title=""></iframe>';
                    
                    $listmyvideos[] = array('name' => $videovalue['name'],
                            'linkvideo' => $videovalue['link'],
                            'videoid' => $videoid, //[uri] => /videos/401242079
                            'htmlembed' => $htmlembed, //'' . $videovalue['embed']['html'] . '',
                            'thumbnail' => $videovalue['pictures']['sizes'][0]['link'],
                    );
					 
                }
            } else {
                $textmyvideos .= '<br><br>' . get_string('text_line2_empty', 'block_uploadvimeo') . '<br><br>';
            }
        }else {
            $textmyvideos .= '<br><br>' . get_string('text_line2_empty', 'block_uploadvimeo') . '<br><br>';
        }
        
         
        $data = new stdClass();
        
        $data->heading = get_string('pluginname', 'block_uploadvimeo');
        $data->url = new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid, 'userid' => $userid]);
        
        // Return link course.
        $data->returnlink = new moodle_url('/course/view.php', ['id' => $courseid]);
        $data->returntext = get_string('returncourse', 'block_uploadvimeo');        
        
        $data->class = 'block_uploadviemto_links';
        $data->myvideos = $listmyvideos;
		$data->textmyvideos = $textmyvideos;	
		$data->accesstoken = $config->config_accesstoken;
        
        // Start output to browser.
        echo $this->output->header();
        echo $this->render_from_template('block_uploadvimeo/form', $data);
        echo $this->output->footer();
        
    }
    
    
}
