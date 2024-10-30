<?php
defined('ABSPATH') or die('Access Denied!');
/* 
 * Copyright 2016 Quodlibet BVBA. All Rights Reserved.
 */

/**
 * Helper method for  debugging database actions
 * @global type $wpdb
 * @param type $logStatement
 * @return type
 */
function wesp_print_db_info($logStatement){

    global $wesp_debug;
    if($wesp_debug === true)
    {
        global $wpdb;
        if($wpdb->last_error !== '' ||  $logStatement) 
        {
            
        
            $str   = htmlspecialchars( $wpdb->last_error );
            $query = htmlspecialchars( $wpdb->last_query );
            print "<div id='error'>
            <p class='wpdberror'><strong>WordPress database error:</strong> [$str]<br />
            <code>$query</code></p>
            </div>";
            return $str;
        }
    }
    return "";
}
/**
 * After a post or get from the admin interface, return to where we came from
 * @param type $msg
 */
function wesp_admin_post_return($msg)
{
    if ( ! isset ( $_REQUEST['_wp_http_referer'] ) )
            die( 'Missing target.' );
        $url = add_query_arg( 'msg', $msg, urldecode( $_REQUEST['_wp_http_referer'] ) );
        wp_safe_redirect( $url );
        exit;
}
/**
 * Helper method to debug variable info
 * @param type $name
 * @param type $var
 */
function wesp_varinfo($name,$var)
{
    global $wesp_debug;
    if($wesp_debug === true)
    {
        echo $name.
             "\n<pre>";
        var_dump($var);
        echo "</pre>";
    }
}