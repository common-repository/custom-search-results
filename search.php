<?php
defined('ABSPATH') or die('Access Denied!');
/* 
 * Copyright 2016 Quodlibet BVBA. All Rights Reserved.
 */


add_filter('the_posts', 'wesp_capture_search_results', 1); 

add_filter('pre_get_posts', 'wesp_capture_search_pre_get_posts');

/**
 * Only search post types that are configured to be searched
 * @param type $query
 * @return type
 */
function wesp_capture_search_pre_get_posts($query)
{
    
    if($query->is_search && !is_admin())
    {
        $types = get_post_types(array(),"names");
        $searchtypes = array();
        foreach($types as $type)
        {
            $optionname = "wesp_searchtype_".$type;
            if(esc_attr( get_option($optionname) )=="1") 
            { 
                $searchtypes[] = $type;
                
            }
        }
        $query->set('post_type',$searchtypes);
        return $query;

    }
}
/**
 * Add the custom results to the search if the search matches a custom search
 * @param type $posts
 * @return type
 */
function wesp_capture_search_results($posts)
{
    global $wesp_search_captured;
    if(!$wesp_search_captured)
    {
        if(is_search() &! is_paged())
        {
            global $wp_query;
            $logged = false;
            $show_normal = 1;
            global $wpdb;
            $searchterms = filter_var($_REQUEST["s"],FILTER_SANITIZE_STRING); 
            $table_name = $wpdb->prefix . 'wesp_custom_searches';
            $exactsearchesquery = "select post_id , show_normal from " . $table_name . " as s inner join wp_wesp_custom_results as r "
                ." on s.id = r.searchid "
                ." where s.type='exact' and "
                ." s.searchterms =  '".$searchterms."'"
                ." order by r.priority asc";
            $customsearches = $wpdb->get_results( $exactsearchesquery );
            $customresults = array();
            if($customsearches)
            {
                foreach($customsearches as $res)
                {
                    //add post info to customresults
                    array_push($customresults,get_post($res->post_id));
                    if($res->show_normal ===  '0')$show_normal=0;
                }
                //Log a custom search
                $logged = wesp_log_search($searchterms,1);
            }
            else
            {
                //Partial matches are only considered if there is no exact search match
                $partialquery = "select searchterms, id ,show_normal from " . $table_name . " as s where s.type = 'partial' ";
                $customsearches = $wpdb->get_results( $partialquery );
                wesp_print_db_info(false);
                if($customsearches)
                {
                    $matchingsearches = array();
                    foreach($customsearches as $search)
                    {
                        //Check if the search query contains this partial custom search
                        $pos =strpos($searchterms,$search->searchterms );
                        if($pos !== false)
                        {
                            //Log a custom partial search
                            $logged = wesp_log_search($search->searchterms,1);
                            array_push($matchingsearches,$search->id);
                            if($search->show_normal ===  '0')$show_normal=0;
                        }
                    }
                    if(count($matchingsearches)>0)
                    {
                        //Load the results for all matching searches, priorities are considered together
                        $inclause = join(",",$matchingsearches);
                        $partialresultsquery = "select post_id from wp_wesp_custom_results as r "
                                    . " where r.searchid in ( ".$inclause .")"
                                    . " order by priority asc ";
                        $customres = $wpdb->get_results( $partialresultsquery );
                        wesp_print_db_info(false);
                        foreach($customres as $res)
                        {
                            //add post info to customresults
                            $post = get_post($res->post_id);
                            if(is_object ($post))
                            {
                                array_push($customresults,$post);
                            }
                        }
                    }
                }
            }
            //add the original results to the output at the end
            if($show_normal === 1)
            {
                if(count($posts)> 0)
                {
                    $customresults = array_merge( $customresults,$posts); 
                }
            }
            else
            {
                //if the normal results shouldn't be displayed, don't show page links for them
                $perpage = $wp_query->posts_per_page;
                if(!$perpage) $perpage = 10;
                $wp_query->max_num_pages = (count($customresults) / $perpage);
            }
            $wp_query->found_posts = count($customresults);
            if(!$logged)
            {
                 //Log a normal search
                 wesp_log_search($searchterms,0);
            }
            $wesp_search_captured = true;
            //if any custom results were found, show them
            if(count($customresults)>0)
            {
                return $customresults;
            }
        }
        $wesp_search_captured = true;
    }
    return $posts;
}

/**
 * Log a users search in the logging table 
 * Log records are summarized by date
 * @global type $wpdb
 * @param type $searchterm
 * @param type $custom
 */
function wesp_log_search($searchterm,$custom)
{
     global $wpdb;
     $table_name = $wpdb->prefix . 'wesp_search_logging';
     $date=date("Y-m-d");
     $query = "INSERT INTO $table_name
        (searchterms, searchdate, custom,count)
      VALUES
        ('".$searchterm."','".$date."',". $custom.",1)
      ON DUPLICATE KEY UPDATE
        `count`     = `count` + 1";
     $wpdb->query($query);
     wesp_print_db_info(false);
     return true;
}
