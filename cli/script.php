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
 * CLI script for report PUC ONLINE , use for debugging or immediate script
 * of all courses.
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * @package    report_puconline
 * @copyright  2022 Angela de Araujo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
use block_uploadvimeo\local\uploadvimeo;
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');


// now get cli options
list($options, $unrecognized) = cli_get_params(
    array('script' => '', 'verbose'=>false, 'help'=>false, 'foldername' => '', 'accountid' => -1 ), 
    array('s' => 'script', 'v'=>'verbose', 'h'=>'help', 'f' => 'foldername', 'a' => 'accountid',  ));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help ="
Execute Block UploadVimeo script.
    
Options:
-v, --verbose       Print verbose progess information
-h, --help          Print out this help
-s, --script        Action to execute:   
                    update
    
                    Script's params 'update':
                    -f, --foldername    Folder name
                    -a, --accountid     Account id
    
Example:
\$sudo -u www-data /usr/bin/php blocks/uploadvimeo/cli/script.php --verbose --script=update --foldername=MoodleUpload_f0000 --accountid=99
\$sudo -u www-data /usr/bin/php blocks/uploadvimeo/cli/script.php --verbose --script=update --foldername=MoodleUpload_f0000
\$sudo -u www-data /usr/bin/php blocks/uploadvimeo/cli/script.php --verbose --script=update --accountid=99
or
\$sudo -u www-data /usr/bin/php blocks/uploadvimeo/cli/script.php -v -s=update -f=MoodleUpload_f0000 -a=99
\$sudo -u www-data /usr/bin/php blocks/uploadvimeo/cli/script.php -v -s=update -f=MoodleUpload_f0000
\$sudo -u www-data /usr/bin/php blocks/uploadvimeo/cli/script.php -v -s=update -a=99
    
";

if ($options['help']) {
    echo $help;
    die;
}

$verbose    = !empty($options['verbose']);
$script     = $options['script'];
$foldername = $options['foldername'];
$accountid  = $options['accountid'];

if (is_int($accountid)) {
    $accountid  = intval($accountid);
} else {
    $accountid -1;
}

if (!empty($script)) {
    
    if ($script = 'update') {
        
        if ($foldername !== '' or $accountid > -1) {
            uploadvimeo::update_videos_by_folder_or_account($foldername, $accountid, $verbose);
            exit();
        } else {
            exit('You must enter at least one parameter (foldername or accountid). See the help below:' . PHP_EOL . PHP_EOL . $help);
        }           
       
    } else {
            exit('It is necessary to inform a valid script name. See the help below:' . PHP_EOL . PHP_EOL . $help);
    }    
    
} else {
    
    exit('It is necessary to inform the script name. See the help below:' . PHP_EOL . PHP_EOL . $help);
    
}
