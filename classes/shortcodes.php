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
            
            $video = $DB->get_record('block_uploadvimeo_videos', ['videoidvimeo' => $args['video']]);
            $account = $DB->get_record('block_uploadvimeo_account', ['id' => $video->accountid]);
        
            $uri = 'https://player.vimeo.com/video/'.$args['video'].'?title=0&amp;byline=0&amp;portrait=0&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=' . $account->app_id;
            return '<iframe src="'. $uri. '" width="600" height="400" frameborder="0" allow="autoplay; fullscreen" allowfullscreen title=""></iframe>';
        } else {
            
            return '';
        }
    }
    
}