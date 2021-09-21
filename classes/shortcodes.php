<?php
namespace block_uploadvimeo;

defined('MOODLE_INTERNAL') || die();

class shortcodes {
    
    /**
     * 
     * @param string $shortcode (string) Is the name of the shortcode found.
     * @param array $args (array) An associative array of the shortcode arguments.
     * @param string|null $content (string|null) When the shortcode wraps: the wrapped content.
     * @param object $env (object) The filter environment object, amongst other things contains the context.
     * @param unknown $next (Closure) The function to pass the content through when embedded shortcodes should apply.
     * @return string|unknown
     */
    public static function vimeoembed($shortcode, $args) {
        
        if ($shortcode == 'vimeo') {
            
            global $DB;
            
            // Videos CCE: [vimeo video="609143971:b9da9695b4"]
            list($videoidvimeo, $hash) = explode(":", $args['video']);
            
            $video = $DB->get_record('block_uploadvimeo_videos', ['videoidvimeo' => $videoidvimeo]);
            $account = $DB->get_record('block_uploadvimeo_account', ['id' => $video->accountid]);
            
            if (!$hash) {
                // From 14-09-2021 it became mandatory to add the hash parameter to the embed url.
                // linkd=https://vimeo.com/604940361/0425ef4c13
                $search = 'https://vimeo.com/' . $video->videoidvimeo . '/';
                $hash = str_replace($search, '', $video->linkvideo);
            }            
            
            $uri = 'https://player.vimeo.com/video/'. $videoidvimeo . '?h='. $hash . 
            '&amp;title=0&amp;byline=0&amp;portrait=0&amp;badge=0&amp;autopause=0&amp;player_id=0' .
            '&amp;app_id=' . $account->app_id;
            
            return '<iframe src="'. $uri. '" width="600" height="400" frameborder="0" allow="autoplay; fullscreen" allowfullscreen title=""></iframe>';
        } else {
            
            return '';
        }
    }
    
}