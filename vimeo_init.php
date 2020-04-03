<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

// Load the autoloader
require_once(__DIR__ . '/vendor/autoload.php');

// Load the configuration file.
if (!function_exists('json_decode')) {
    throw new Exception(
        'We could not find `json_decode`. `json_decode` is found in PHP 5.2 and up, but not found on many Linux ' .
        'systems due to licensing conflicts. If you are running Ubuntu try `sudo apt-get install php5-json`.'
    );
}
$config = json_decode(file_get_contents(__DIR__ . '/vimeo_config.json'), true);
if (empty($config['client_id']) || empty($config['client_secret'])) {
    throw new Exception(
        'We could not locate your client id or client secret in "' . __DIR__ . '/vimeo_config.json". Please create one, ' .
        'and reference config.json.example'
    );
}
return $config;

?>