<?php
/**
 * @package Quodlibet
 */
/*
Plugin Name: Custom Search Results
Plugin URI: http://quodlibet.be
Description: Custom Search Results helps you put the important results first on searches, shows what users are searching for on your site.
Version: 1.0
Author: Quodlibet
Author URI: http://quodlibet.be
License: GPLv2 or later
Text Domain: wesp
*/
defined('ABSPATH') or die('Access Denied!');
global $wesp_debug;
$wesp_debug = true;
require_once dirname( __FILE__ ) . '/init.php' ;
require_once dirname( __FILE__ ) . '/admin.php' ;
require_once dirname( __FILE__ ) . '/post.php' ;
require_once dirname( __FILE__ ) . '/search.php' ;
require_once dirname( __FILE__ ) . '/charts.php' ;
require_once dirname( __FILE__ ) . '/common.php' ; 
if($wesp_debug === true)
{
    define( 'WP_DEBUG', true ); 
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
}
//Version information
global $wesp_version;
$wesp_version = '1.0';
global $wesp_db_version;
$wesp_db_version = '2';
//Initialize the Plugin
register_activation_hook( __FILE__, 'wesp_install' );
add_action('plugins_loaded', 'wesp_update');




?>