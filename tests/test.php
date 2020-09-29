<?php
//defined('MOODLE_INTERNAL') || die;

use block_uploadvimeo\local\uploadvimeo;
use Vimeo\Vimeo;

require_once(__DIR__ .'/../../../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');

global $DB;

echo '<pre>';

$config = get_config('block_uploadvimeo');

$client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);

echo '<h1>TESTE BLOCK UPLOAD VIMEO </h1>';


$i = 0;

for ($page=1; $page <= 6; $page++) {
    
    $param = array('direction' => 'asc', 'sort' => 'name', 'per_page' => 100, 'page' => $page);
    
    $result = $client->request('/me/projects', $param, 'GET');
    
    foreach ($result['body']['data'] as $folderpage) {
        
        if (strpos($folderpage['name'], 'MoodleUpload_')) {
            
            $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $folderpage['uri']));
            list($useridvimeo, $folderid) = explode(',', $urifolder);
            
            $username = str_replace('MoodleUpload_', '', $folderpage['name']);
            $user = $DB->get_record('user', array('username' => $username), 'id,username', MUST_EXIST);
            
            $foldervimeo = new \stdClass();
            $foldervimeo->userid = $user->id;
            $foldervimeo->clientid = $config->config_clientid;
            $foldervimeo->foldername = $folderpage['name'];
            $foldervimeo->folderid = $folderid;
            $foldervimeo->timecreatedvimeo = strtotime($folderpage['created_time']); // Ex.: [created_time] => 2020-09-29T14:15:37+00:00
            $foldervimeo->timecreated = time();
            
            $folders[] = $foldervimeo;
        }
    }
}

echo '<br>';
print_r($folders);
echo '<hr>';
/*
list($sql, $params) = $DB->get_in_or_equal($usernames, SQL_PARAMS_NAMED, 'username');
$userids = $DB->get_records_sql('SELECT u.id, u.username FROM {user} u WHERE u.username ' . $sql , $params);

echo '<br>';
print_r($userids);
echo '<hr>';
*/



function get_videos_from_folder_pagination($folderid, $page = 1, $perpage = 20, $config) {
    global $OUTPUT;
    
    $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
    
    // per_page: The number of items to show on each page of results, up to a maximum of 100.
    // @see https://developer.vimeo.com/api/reference/folders#get_project_videos
    $perpage = ($perpage > 100)? 100: $perpage;
    
    $page = ($page < 1)? 1: $page;
    
    $folderspage = $client->request('/me/projects/'.$folderid.'/videos', array(
 //       'per_page' => $perpage,
        'page' => $page), 'GET');
    
    if ( (!$folderspage['body']['total']) or ($folderspage['body']['total'] = '0') ) {
        return array();
    }
    
    $totalvideos = $folderspage['body']['total'];
    
    echo '<br><br><br><h3>[body]</h3><pre>'; print_r($folderspage['body']); echo '</pre>';
    
    // Get videos from page.
    foreach ($folderspage['body']['data'] as $video) {
        $videoid = str_replace('/videos/', '', $video['uri']); //[uri] => /videos/401242079
        $uri = 'https://player.vimeo.com/video/'.$videoid.'?title=0&amp;byline=0&amp;portrait=0&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=168450';
        $htmlembed = '<iframe src="'. $uri. '" width="'. $config->config_width .'" height="' . $config->config_height . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen title="'.$video['name'].'"></iframe>';
        
        $displayvalue = '<a data-toggle="collapse" aria-expanded="false" aria-controls="videoid_'.$videoid.'" data-target="#videoid_'.$videoid.'">';
        $displayvalue .= '<img src="'.$video['pictures']['sizes'][0]['link'].'" class="rounded" name="thumbnail_'.$videoid.'" id="thumbnail_'.$videoid.'">';
        $displayvalue .= '<span style="margin-left:10px; margin-right:20px;">'.$video['name'].'</span></a>';
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
    return array(
        'totalvideos' => $totalvideos,
        'videos' => $myvideos);
}



function get_folder(string $foldername, $config) {
    
    $client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);
    
    $folderspage1 = $client->request('/me/projects', array(
        'direction' => 'asc',
        'sort' => 'name',
        'per_page' => 100,
        'page' => 1), 'GET');
    
    if ($folderspage1['body']['total'] <> '0') {
        
        $totalpages = ($folderspage1['body']['total'] > 100 )? ceil($folderspage1['body']['total'] / 100): 1;
        
        // Get folders from other pages.
        if ($totalpages >= 1) {
            for ($i = 1; $i <= $totalpages; $i++) {
                
                $foldersnextpage = $client->request('/me/projects', array(
                    'direction' => 'asc',
                    'sort' => 'name',
                    'per_page' => 100,
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
    
        // Search the specific folder
        $folderfinded = array_search($foldername, array_column($listfolder, 'name'));
        
        if ($folderfinded) {
            return $listfolder[$folderfinded];
        } else
            
            return false;
            
    } else
        
        return false;
        
        
}