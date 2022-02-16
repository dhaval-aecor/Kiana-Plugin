<?php
/*
Plugin Name: Kiana APIs
Plugin URI: 
Description: Additional Custom api for intract with app and perform smooth function
Author: 
Author URI: 
Text Domain: kiana-apis
Domain Path: /languages/
Version: 1.0.0
*/

if ( !defined( 'ABSPATH' ) ) {
    die();
}

define('KIAPIINC', plugin_dir_path(__FILE__).'include/');
define('KIAPIINCURL', plugin_dir_url( __FILE__ ).'include/');
// define('FCMAPIKEY', 'AAAAodNH55w:APA91bGvZKmzQOXUUqs623ri39VxzwJ6moz5P_yk7goN0e9ZrUWopLxWPPRGkh5ecGKeDxMY2yIaPyalBAUmiub8WjOP1WIHueViJ4_FowrnCYvZMWY2v-t0uLhNHqMcW5wlXmNWVc27');
define('FCMAPIKEY', 'AAAAZJe_YxE:APA91bFJGO6D53QQm_kc1UjAR4SMTG2zO711ffmXo0aGht0QsF6bpF8frp_x78ZrfcsOLf7CooOA6GI_I81AWtvASkP35y6oLMWtKgnOT077S0YwUm4v6m5D-skFDsBiresZlljAL_yI');
// define('TEXTLOCALAPIKEY', 'NWE0MzYyNTEzNDM2NjM3NzQ3NGU0ZjQxNzk2ZDVhNjY=');
// define('TEXTLOCALAPIKEY', 'ZjAwZjQ2MWMxYTU5MDFlNzJmZmMxMTFmNGJmYjZhYjE=');



require_once(KIAPIINC.'functions.php');