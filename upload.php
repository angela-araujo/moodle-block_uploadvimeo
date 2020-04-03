<?php
use Vimeo\Vimeo;
use Vimeo\Exceptions\VimeoUploadException;

$config = require(__DIR__ . '/vimeo_init.php');
$client = new Vimeo($config['client_id'], $config['client_secret'], $config['access_token']);
require ('../../config.php');
// Fetch params to tags
if (isset($_POST['shortname'])) {
    $shortname = $_POST['shortname'];
} else if (isset($_GET['shortname'])) {
    $shortname = $_GET['shortname'];
} else $shortname = '';

if (isset($_POST['username'])) {
    $username = $_POST['username'];
} else if (isset($_GET['username'])) {
    $username = $_GET['username'];
} else $username = ''; 

/*
//$response = $client->request('/videos/401097992/tags/formatonsections' , array(), 'PUT');
$json = '[{"tag":"testetaglote1"},{"tag":"testetaglote2"}]';
$response = $client->request('/videos/401097992/tags/' , $json, 'PUT', true);

//$response = $client->request('/videos/401097992/tags/formatonsections' , array(), 'PUT');
//$response = $client->request('/videos/401097992/tags/angela' , array(), 'PUT');

echo '<pre>';
print_r($response);

exit;
*/

/*
try { 
    // See: https://developer.vimeo.com/api/reference/videos#get_video_tags
     * 
     * PUT/videos/{video_id}/tags/{word}
     * 
    // $response = $client->request('/videos/{video_id}/tags',array(),'GET'); // lista tags do video {videoid}
    // $response = $client->request('/tags/{word}/videos',array(),'GET'); // lista todos os videos com a tag {word}
    // $response = $client->request('/videos/{video_id}/tags',array(),'PUT'); // 
    echo '<pre>';
    print_r($response);    
    exit;
} catch (VimeoUploadException $e) {
    echo 'error: '.$e;
}

*/

echo '<html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="http://localhost/moodle-38/theme/yui_combo.php?rollup/3.17.2/yui-moodlesimple.css" />
    <script id="firstthemesheet" type="text/css">
/** Required in order to fix style inclusion problems in IE with YUI **/</script>
    <link rel="stylesheet" type="text/css" href="http://localhost/moodle-38/theme/styles.php/boost/1585001363_1/all" />
    <link rel="stylesheet" type="text/css" href="styles.css" />
    <style>
.form-item .form-setting .defaultsnext {
    display: inline-block;
}
    </style>
</head>
';


if(isset($_FILES['video'])){
    
    //@TODO Tratar $_POST
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    $file_name = $_FILES['video']['name'];
    $file_size = $_FILES['video']['size'];
    $file_tmp = $_FILES['video']['tmp_name'];
    $file_type = $_FILES['video']['type'];
    $file_error = $_FILES['video']['error'];
    
    
    $uploadfile = __DIR__.'/uploads/'. $file_name; //@TODO: Alterar para arquivo temporario fora da pasta. Talvez moodledata/temp
    move_uploaded_file($file_tmp, $uploadfile);
    
    $maxsize = 524288000; //get_config('block_uploadvimeo', 'config_maxsize');
    
    if($file_size > $maxsize){
        $errors[]='O tamanho máximo do arquivo é de '. $maxsize .' bytes e você está tentando enviar um vídeo com '. $file_size . 'bytes'; // @TODO get_string();
    }
    
    if(empty($errors)==true){
        
        try {
            //echo '<div class="alert alert-primary" role="alert">Enviando arquivo...</div>';

            $uri = $client->upload($uploadfile, array(
                    "name" => $title,
                    "description" => $description,
                    "privacy.download" => "false",
                    "privacy.view" => "unlisted",
                    "privacy.embed" => "whitelist")  );
            
            
            $videoid = str_replace('/videos/', '', $uri);
            //echo $videoid; exit;
            echo '<div class="alert alert-primary" role="alert">Isso pode demorar um pouco, por favor, aguarde...</div>';
            
            // Check the transcode status
            $status=''; 
            $i = 0;
            
            sleep(30);
            
            //while (!$status === 'complete') {
                
                $i = $i + 1;
                
                $response = $client->request($uri . '?fields=transcode.status');
                $status = $response['body']['transcode']['status'] ;//=== 'complete';
                
                echo '<br>Status: '.$status;
                
                /*
                if ($response['body']['transcode']['status'] === 'complete') {
                    print 'Your video finished transcoding.';
                } elseif ($response['body']['transcode']['status'] === 'in_progress') {
                    print 'Your video is still transcoding.';
                } else {
                    print 'Your video encountered an error during transcoding.';
                }*/
                
                
                //if ($status === 'complete') {                   
                    
                    $response = $client->request($uri . '?fields=link');
                    $linkvideo = $response['body']['link'];
                    echo '<br><br>O link do seu vídeo é: <a target="_blank" href="' . $linkvideo . '">' . $linkvideo . '</a><br>';
                    
                    if (($shortname) and ($username)) {
                        
                        $response = $client->request('/videos/' . $videoid . '/tags/'.$shortname , array(), 'PUT');
                        echo '<br>Tags:<br>';
                        if ($response['status'] == '200') {
                            echo '#'. $shortname . '';
                        } else {
                            echo '<br>Erro ao incluir tag '. $shortname .'<br><pre>'; print_r($response); echo '</pre>';
                        }
                        
                        $response = $client->request('/videos/' . $videoid . '/tags/'.$username , array(), 'PUT');                        
                        if ($response['status'] == '200') {
                            echo ', #'. $username . '<br><br>';
                        } else {
                            echo '<br>Erro ao incluir tag '. $username .'<br><pre>'; print_r($response); echo '</pre>';
                        }
                    }
                   // break;
                 //}
                //sleep(10);

            //}
            
        } catch (VimeoUploadException $e) {
            $errors[] = $e->getMessage();
        }

        
    }else{
        print_r($errors);
    }
}