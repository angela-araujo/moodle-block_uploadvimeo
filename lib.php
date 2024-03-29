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

use block_uploadvimeo\local\uploadvimeo;


function block_uploadvimeo_inplace_editable($itemtype, $itemid, $newvalue) {
    
    if ($itemtype === 'title') {
        
        // Must call validate_context for either system, or course or course module context.
        // This will both check access and set current context.
        \external_api::validate_context(context_system::instance());
        
        // Check permission of the user to update this item.
        //require_capability('block/uploadvimeo:seepagevideos', context_system::instance());
        
        //throw new Exception('Itemid = '.print_r($itemid,true));
        
        // Clean input and update the record.
        $newvalue = clean_param($newvalue, PARAM_NOTAGS);

        $response = uploadvimeo::vimeo_edit_title($itemid, $newvalue);

        $shortenedvalue = uploadvimeo::get_short_title($newvalue, 50);
        
        $video = uploadvimeo::get_a_specific_video($itemid);
        
        if ($response) {
            
            $displayvalue = '<a data-toggle="collapse" aria-expanded="false" aria-controls="videoid_'.$itemid.'" data-target="#videoid_'.$itemid.'">';
            $displayvalue .= '<img src="'.$video->linkpicture.'" class="rounded" name="thumbnail_'.$itemid.'" id="thumbnail_'.$itemid.'">';
            $displayvalue .= '<span style="margin-left:10px; margin-right:20px;">'.$shortenedvalue.'</span></a>';
            
            // Prepare the element for the output:
            return new \core\output\inplace_editable('block_uploadvimeo', 'title', $itemid, true,
                    $displayvalue, $newvalue, 
                    get_string('edittitlevideo', 'block_uploadvimeo'));
        }
    }
}
function block_uploadvimeo_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $uploadvimeonode=null) {
    global $PAGE, $COURSE;
    $url = new moodle_url('/blocks/uploadvimeo/account.php', ['courseid' => $COURSE->id]);
    $uploadvimeonode->add('Account configuration', $url);
}



