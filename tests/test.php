<?php
use block_uploadvimeo\local\uploadvimeo;
use Vimeo\Vimeo;
require_once(__DIR__ .'/../../../config.php');

// Connect to Vimeo.
require_once(__DIR__ . '/../vendor/autoload.php');

$config_ccead = get_config('block_uploadvimeo');

$config_ecoa = new stdClass();
$config_ecoa->config_clientid = '86735c153060ee440657fb7dae7b155f77020dbe';
$config_ecoa->config_clientsecret = 'rWvVyg2zOhhbI7xDtp9C0w6mmFPyGEg7KMS33CXAXJRKF/U0AlBx8tTkJbU4VxB5oPLaxUNGGsgMuBFeH0uWlBAj0iHOP29YTX9ustz1grX70jcxgZXocV95rfi3mS2h';
$config_ecoa->config_accesstoken = '59724ac31f0459ca7e9a59e0cb0f0ff6';

$foldername = 'MoodleUpload_angela';
$folder = uploadvimeo::get_folder($foldername); //$folder = get_folder('MoodleUpload_f14224', $config_ecoa); 
$folderid = $folder['id']; //1803166; // MoodleUpload_angela (https://vimeo.com/manage/folders/1631548)
$perpage = 2;
$page = 3;

$config = get_config('block_uploadvimeo');
$client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);

echo '<h1>TESTE BLOCK UPLOAD VIMEO </h1>';
echo "<h2>Lista videos $foldername</h2>";

echo '<pre>';


$client = new Vimeo($config_ccead->config_clientid, $config_ccead->config_clientsecret, $config_ccead->config_accesstoken);
//$client = new Vimeo($config_ecoa->config_clientid, $config_ecoa->config_clientsecret, $config_ecoa->config_accesstoken);

//$videos = uploadvimeo::get_videos_from_folder_pagination($folderid, $page, 4); print_r($videos); exit;


$folderspage1 = $client->request('/me/projects/'.$folderid.'/videos', array(
    'per_page' => $perpage, 
    'page' => $page), 'GET');
//echo '<br><br><br><h3>[$folderspage1]</h3>'; print_r($folderspage1); echo '<hr>';
foreach ($folderspage1['body']['data'] as $video) {
    $lista[] = 'uri: ' . $video['uri'] . ' | createde: ' . $video['created_time'] . ' | name: ' . $video['name'];
}
echo '<br>[total] = '; print_r($folderspage1['body']['total']);
echo '<br>[page] = ';     print_r($folderspage1['body']['page']);
echo '<br>[per_page] = '; print_r($folderspage1['body']['per_page']);
echo '<br>[paging] = ';   print_r($folderspage1['body']['paging']);
echo '<br>Videos = '; print_r($lista);
//echo '<br>Videos = '; print_r($folderspage1['body']['data']);



/*
$folderspage = $client->request('/me/projects/'.$folderid.'/videos', array(
    'per_page' => 3,
    'page' => 1), 'GET');

//$totalvideos = $folderspage['body']['total'];   
*/
//$videos = get_videos_from_folder_pagination((int) $folderid, $page, $perpage, $config);

//echo '<br><br><br><h3>[totalvideos]</h3>'; print_r($videos['totalvideos']); echo '<hr>';

echo '<hr>';






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