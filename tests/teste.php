<?php
use Vimeo\Vimeo;

// Connect to Vimeo.
require_once(__DIR__ . '/../vendor/autoload.php');
define('VIDEOS_PER_PAGE', 100);
define('MICRO_SEGUNDOS', 1000000);

//$config = get_config('block_uploadvimeo');

$config = new stdClass();
$config->config_clientid = '86735c153060ee440657fb7dae7b155f77020dbe';
$config->config_clientsecret = 'rWvVyg2zOhhbI7xDtp9C0w6mmFPyGEg7KMS33CXAXJRKF/U0AlBx8tTkJbU4VxB5oPLaxUNGGsgMuBFeH0uWlBAj0iHOP29YTX9ustz1grX70jcxgZXocV95rfi3mS2h';
$config->config_accesstoken = '59724ac31f0459ca7e9a59e0cb0f0ff6';

$client = new Vimeo($config->config_clientid, $config->config_clientsecret, $config->config_accesstoken);

echo '<h1>Teste Bloco Upload Vimeo</h1>';

echo '<pre>';

$inicio1 = microtime(true);

$folderspage1 = $client->request('/me/projects', array(
    'direction' => 'asc',
    'sort' => 'name',
    'per_page' => VIDEOS_PER_PAGE,
    'page' => 1), 'GET');

//Seu primeiro script
$total1 = microtime(true) - $inicio1;
echo '<br><br>Tempo de execução do primeiro script $clien->request: ' . $total1/MICRO_SEGUNDOS;


if ($folderspage1['body']['total'] <> '0') {
    
    $totalpages = ($folderspage1['body']['total'] > VIDEOS_PER_PAGE )? ceil($folderspage1['body']['total'] / VIDEOS_PER_PAGE): 1;
    
    $aux = 0;
    // Get videos from first page.
    $inicio2 = microtime(true);
    foreach ($folderspage1['body']['data'] as $folderpage1) {
        
        $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $folderpage1['uri']));
        list($useridvimeo, $folderid) = explode(',', $urifolder);
        
        $listfolder[] = array(
            'id' => $folderid,
            'name' => $folderpage1['name'],
            'uri' => $folderpage1['uri'],
            'created_time' => $folderpage1['created_time'],
        );
        
        //echo '<br>' . $aux++ . ' | uri: '. $folderpage1['uri'] . ' | created: ' . $folderpage1['created_time'] . ' | name: ' . $folderpage1['name'];
        
    }
    $total2 = microtime(true) - $inicio2;
    echo '<br><br>Tempo de execução do script primeiro foreach: ' . $total2/MICRO_SEGUNDOS;
    
    
    // Get videos from other pages.
    if ($totalpages > 1) {
        
        for ($i = 2; $i <= $totalpages; $i++) {
            
            $inicio = microtime(true);
            $foldersnextpage = $client->request('/me/projects', array(
                'direction' => 'asc',
                'sort' => 'name',
                'per_page' => VIDEOS_PER_PAGE,
                'page' => $i ), 'GET');
            $total = microtime(true) - $inicio;
            echo '<br><br>Tempo de execução do script $client->request(others): ' . $total/MICRO_SEGUNDOS;
            
            $inicio3 = microtime(true);
            foreach ($foldersnextpage['body']['data'] as $foldernextpage) {
                
                $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $foldernextpage['uri']));
                list($useridvimeo, $folderid) = explode(',', $urifolder);
                
                $listfolder[] = array(
                    'id' => $folderid,
                    'name' => $foldernextpage['name'],
                    'uri' => $foldernextpage['uri'],
                    'created_time' => $foldernextpage['created_time'],
                );
                //echo '<br>' . $aux++ . ' | uri: '. $foldernextpage['uri'] . ' | created: ' . $foldernextpage['created_time'] . ' | name: ' . $foldernextpage['name'];
            }
            
            $total3 = microtime(true) - $inicio3;
            echo '<br><br>Tempo de execução do script foreach other folders: ' . $total3/MICRO_SEGUNDOS;
        }
        
    }
    
    
    
    // Search the specific folder
    $inicio4 = microtime(true);
    $folderfinded = array_search('MoodleUpload_angela', array_column($listfolder, 'name'));    
    $achou = $listfolder[$folderfinded];
    $total4 = microtime(true) - $inicio4;
    echo '<br><br>Tempo de execução do primeiro script array_search: ' . $total4/MICRO_SEGUNDOS;
    
    
    //echo '<br>achou: '; print_r($achou);
    //echo '<br>';
    
    $inicio5 = microtime(true);
    //echo '<br>funcao nova retornou: '; 
    $listfolder[search_array( $listfolder, 'MoodleUpload_angela', 'name' )]; // Saída int(2)
    $total5 = microtime(true) - $inicio5;
    echo '<br><br>Tempo de execução do primeiro script funcao nova iterator: ' . $total5/MICRO_SEGUNDOS;
    
    $total6 = microtime(true) - $inicio1;
    echo '<br><br>Tempo de execução Total: ' . $total6/MICRO_SEGUNDOS;
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
 * @param mixed|optional $index
 *   Allow to define a specific index where the data will be searched
 *
 * @return integer|string
 *   If given needle can be found in given haystack, its index will
 *   be returned. Otherwise, -1 will
 *
 * @see http://www.php.net/manual/en/function.array-search.php#97645
 */
function search_array($haystack, $needle, $index = NULL) {
    
    if ( is_null( $haystack ) ) {
        return -1;
    }
    
    $arrayIterator = new \RecursiveArrayIterator($haystack);
    
    $iterator = new \RecursiveIteratorIterator($arrayIterator);
    
    while( $iterator->valid() ) {
        
        if( ( ( isset($index) and ($iterator->key() == $index ) ) or
            ( ! isset($index) ) ) and ($iterator->current() == $needle ) ) {
                
                return $arrayIterator->key();
            }
            
            $iterator -> next();
    }
    
    return -1;
}