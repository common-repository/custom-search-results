<?php
defined('ABSPATH') or die('Access Denied!');
/* 
 * Copyright 2016 Quodlibet BVBA. All Rights Reserved.
 */


add_action( 'load-post.php', 'wesp_post_meta_add' );
add_action( 'load-post-new.php', 'wesp_post_meta_add' );
/**
 * Add A checkbox on the post page to enable/disable indexing for the page
 * @param type $object
 * @param type $box
 */
function wesp_post_meta_index_display($object, $box)
{
   global $wpdb;
   $table_name_header = $wpdb->prefix . 'wesp_custom_searches';
   $table_name_results = $wpdb->prefix . 'wesp_custom_results';
   //Get a list of all the custom searches
   $query = "select id,name from " . $table_name_header ;
   $customsearch = $wpdb->get_results( $query );
   //get all custom searches post is already in
   $query = "select distinct( searchid ) from " . $table_name_results ." where post_id = " .$object->ID ;
   $customsearchesin = $wpdb->get_results( $query );
   $inarray = array();
   foreach($customsearchesin as $in)
   {
       $inarray[] = $in->searchid;
   }
   //wesp_varinfo("object",$object);
   $uri = remove_query_arg("msg",$_SERVER['REQUEST_URI']);
  
   $redirect = urlencode($uri );
   if($customsearch)
   {
    $posturl =    admin_url( 'admin-post.php' )."?action=wesp_add_customresult&_wp_http_referer=".$redirect;
    $postremoveurl =    admin_url( 'admin-post.php' )."?action=wesp_delete_customresult&_wp_http_referer=".$redirect;
    ?>
    <div >
        
        <ul>
            <?php
            foreach($customsearch as $search)
            {
                if(in_array($search->id,$inarray))
                {
                ?>
                    <li>
                        <a style="text-decoration:none" href="<?php echo $postremoveurl; ?>&term=<?php echo $search->id?>&page_id=<?php echo $object->ID?>">
                        <span class="dashicons dashicons-trash"></span>
                        </a>
                        <?php echo $search->name?></li>
                        
                <?php
                }
                else
                {
                ?>
                    <li><a style="text-decoration:none" href="<?php echo $posturl; ?>&term=<?php echo $search->id?>&page_id=<?php echo $object->ID?>">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php echo $search->name?></a></li>
                <?php
                }
            }
            ?>
        </ul>
        <p><?php _e("Manage Custom Searches this","wesp")?> <?php echo $object->post_type?> <?php _e("appears in.","wesp")?></p>
    </div>    
    <?php
   }
   else
   {?>
    <div>
        <p>
            <?php _e("No Custom searches are defined.","wesp")?>
            <a href="<?php echo admin_url("admin.php?page=wesp-plugin")?>"><?php e_("Create Custom Search")?></a>
        </p>
    </div>
    <?php
   }
}
function wesp_post_meta_add()
{
    //Add our meta box to each post type
    $types = get_post_types(array(),"names");
    foreach($types as $name)
    {
        add_meta_box( 
                'wesp_index',
                __('Custom Search Results',"wesp"), 
                'wesp_post_meta_index_display', 
                $name, 
                'side', 
                $priority = 'default');
    }
}
?>