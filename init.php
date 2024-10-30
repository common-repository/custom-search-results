<?php
/* 
 * Copyright 2016 Quodlibet BVBA. All Rights Reserved.
 */
defined('ABSPATH') or die('Access Denied!');
function wesp_install()
{
    global $wpdb;
    global $wesp_db_version;
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $charset_collate = $wpdb->get_charset_collate();
    //Creat custom search database table
    $table_name = $wpdb->prefix . 'wesp_custom_searches';
	
        $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		name tinytext NOT NULL,
		searchterms text NOT NULL,
		type varchar(20) DEFAULT 'partial' NOT NULL,
                show_normal int(1) DEFAULT '1',
		PRIMARY KEY  (id),
                KEY term (searchterms(25), type(7) )
	) $charset_collate;";
	dbDelta( $sql );
    //Create custom search results database table
    $table_name = $wpdb->prefix . 'wesp_custom_results';
        $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		searchid mediumint(9) NOT NULL,
		post_id BIGINT(20) NOT NULL,
		priority mediumint(9) NOT NULL default 1,
		type varchar(20) DEFAULT 'partial' NOT NULL,
		PRIMARY KEY  (id, searchid, post_id),
                KEY searchid (searchid, type)
	) $charset_collate;";
	dbDelta( $sql );
    //Create search logging table
    $table_name = $wpdb->prefix . 'wesp_search_logging';
        $sql = "CREATE TABLE $table_name (
		searchterms text(120) NOT NULL,
                searchdate date not null,
                custom int(1) not null default '0',            
                count BIGINT(20) default '1',
		PRIMARY KEY  (searchterms(120), searchdate),
                KEY term (searchterms(120), custom)
	) $charset_collate;";
	dbDelta( $sql );  
        
	add_option( 'wesp_db_version', $wesp_db_version );
        //Set default values for search types
        add_option( "wesp_searchtype_post" , '1' );
        add_option( "wesp_searchtype_page" , '1' );
        
}
function wesp_update()
{
    //global $wpdb;
    global $wesp_db_version;
     if ( get_site_option( 'wesp_db_version' ) != $wesp_db_version ) {
        wesp_install();
    }
}
?>