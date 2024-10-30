<?php

/* 
 * Copyright 2016 Quodlibet BVBA. All Rights Reserved.
 */
defined('ABSPATH') or die('Access Denied!');
//Add headers to be used during crawling only
//FUTURE VERSION USE
//require_once dirname( __FILE__ ) . '/crawl.php' ;
//add_action( 'wp_head', 'wesp_add_headers' );
/**
 * Add header info that will be used to build the search index
 * The headers will only be added when WESP crawler is accessing the page
 * @global type $post
 */

function wesp_add_headers()
{ 
    $browserAgent = $_SERVER['HTTP_USER_AGENT'];
    if($browserAgent === "WESP crawler")
    {
        global $post;
        $wesp_headers = '<!--Wordpress Enterprise Search Plugin-->';
        $wesp_headers .= wesp_addMeta("type",   $post->post_type);
        $wesp_headers .=wesp_addMeta("modified",$post->post_modified);
        $wesp_headers .=wesp_addMeta("content", $post->post_content);
        $wesp_headers .=wesp_addMeta("title",   $post->post_title);
        $wesp_headers .=wesp_addMeta("excerpt", $post->post_excerpt);
        $wesp_headers .=wesp_addMeta("author",  $post->post_author);
        $wesp_headers .= '<!--Wordpress Enterprise Search Plugin-->';
        echo $wesp_headers;
    }
}
/**
 * Add WESP meta tag to header, ensure the content is safe
 * @param type $name
 * @param type $value
 */
function wesp_addMeta($name,$value)
{
    return '<meta name="wesp_'.$name.'" content="'.wesp_safe($value).'"/>' . "\n";
}
function wesp_safe($value)
{
    $safevalue = addslashes(strip_tags($value));
    return $safevalue;
}
?>