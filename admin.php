<?php

/* 
 * Copyright 2016 Quodlibet BVBA. All Rights Reserved.
 */
defined('ABSPATH') or die('Access Denied!');

//Admin Interface
add_action('admin_menu', 'wesp_plugin_setup_menu');
add_action('admin_init', 'wesp_admin_init');

//Customsearches POST actions
add_action( 'admin_post_wesp_add_customsearch', 'wesp_add_customsearch_action' );
add_action( 'admin_post_wesp_add_customresult', 'wesp_add_customresult_action' );
add_action( 'admin_post_wesp_delete_customresult','wesp_delete_customresult_action' );
add_action( 'admin_post_wesp_reorder_customresult','wesp_reorder_customresult_action' );
add_action( 'admin_post_wesp_delete_customsearch','wesp_delete_customsearch_action');
add_action( 'admin_post_wesp_edit_customsearch','wesp_edit_customsearch_action');
add_action( 'admin_post_wesp_save_posttypes','wesp_save_posttypes_action');
/**
 * Admin Menu
 */
function wesp_admin_init()
{
    //nothing
}
function wesp_general_setting_callback()
{
    echo __('<p>General settings for Custom Search Results</p>',"wesp");
    
}
function wesp_plugin_setup_menu(){
        add_menu_page( __('Wesp Plugin Page',"wesp"),  __('Custom Search Results',"wesp"), 'manage_options', 'wesp-plugin', 'wesp_admin_menu','dashicons-search' );
        add_submenu_page( 'wesp-plugin', __('Custom Results',"wesp"), __('Custom Results',"wesp"), 'manage_options', 'wesp-plugin-customsearches','wesp_admin_menu_custom','dashicons-admin-settings');
        add_submenu_page( 'wesp-plugin', __('Custom Post Types',"wesp"), __('Custom Post Types',"wesp"), 'manage_options', 'wesp-plugin-customposttypes','wesp_admin_menu_customtypes','dashicons-admin-post');
}
function wesp_general_setting_active_callback()
{
    
    echo '<input name="wesp_general_setting_active" id="wesp_general_setting_active" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'wesp_general_setting_active' ), false ) . ' /> '.__("Uncheck to deactive Custom Search Results","wesp");
}
/**
 * Select Custom Post Types that will be searched
 */
function wesp_admin_menu_customtypes()
{
    $redirect = urlencode( $_SERVER['REQUEST_URI'] );
    wesp_admin_tabs();
    
    $types = get_post_types(array(),"objects");
    //wesp_varinfo("posttypes", $types);
    ?>
<p><?php _e("Select the post types that you want to be searched from the regular search for on your website")?></p>
    <div>
    
      
    <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
        <input type="hidden" name="action" value="wesp_save_posttypes">
        <input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
        <table class="form-table">
        <?php
        //settings_fields( 'wesp_search_posttypes' );
        //do_settings_sections( 'wesp_search_posttypes' );
        foreach($types as $type)
        {
            $optionname = "wesp_searchtype_".$type->name;
            register_setting( 'wesp-plugin-customposttypes', $optionname );
            $checked = "";$disabled ="";$msg="";
            if(esc_attr( get_option($optionname) )==="1") { $checked =  "checked";}
            //Pages and posts will be defaulted to true, otherwise search won't work as before plugin was installed
            if($type->name == "post" || $type->name == "page")
            {
                $checked = "checked";
                $disabled = "disabled";
                $msg = "You can't disable search for posts and pages";
            }
            ?>
            <tr valign="top">
            <th scope="row"><?php echo $type->labels->name?></th>
            <td><input type="checkbox" name="<?php echo $optionname?>" value="1" <?php echo $checked ." ".$disabled ?> /><?php _e($msg)?></td>
            </tr>
            <?php
        }
        ?>

        </table>
         <?php submit_button(); ?>
    </form>
    </div>
    <?php
}

/**
 * Menu for the main settings page
 */

function wesp_admin_tabs()
{
    $adminpage =  filter_var($_REQUEST["page"],FILTER_SANITIZE_STRING); 
    ?>
    <h2 class="nav-tab-wrapper">
        <a href="?page=wesp-plugin"  class="nav-tab <?php echo $adminpage == 'wesp-plugin' ? 'nav-tab-active' : ''; ?>">Search Statistics</a>
        <a href="?page=wesp-plugin-customsearches" class="nav-tab <?php echo $adminpage == 'wesp-plugin-customsearches' ? 'nav-tab-active' : ''; ?>">Customize Search Results</a>
        <a href="?page=wesp-plugin-customposttypes" class="nav-tab <?php echo $adminpage == 'wesp-plugin-customposttypes' ? 'nav-tab-active' : ''; ?>">Search Custom Post Types</a>
    </h2>
    <?php
}
function wesp_admin_menu()
{
    //Generat Settings Page Sections
    add_settings_section(
		'wesp_general_setting_section',
		__('General Settings',"wesp"),
		'wesp_general_setting_callback',
		'wesp-plugin'
	);
        wesp_admin_tabs();
        wesp_show_charts();  
}

function wesp_admin_menu_custom()
{
    //Custom Searches Section
    add_settings_section(
		'wesp_custom_results_section',
		__('Custom Search Results',"wesp"),
		'wesp_custom_results_callback',
		'wesp-plugin-customsearches'
	); 
    wesp_admin_tabs();
   if(!isset($_REQUEST["wesp_action"]))
   {
    wesp_add_customsearch_form();  
    wesp_list_customsearches();
   }
   else
   {
       switch($_REQUEST["wesp_action"])
       {
           case "editterm":
               wesp_edit_customsearch_form();
                break;
       }
   }  
}
/**
 * Maintain custom results for a specific Custom Search
 */
function wesp_edit_customsearch_form()
{
    $id = filter_var($_REQUEST["term"],FILTER_VALIDATE_INT);
    ?>
    <div class="wrap">
        <h2><?php _e("Search Term History","wesp")?></h2>
        <div id='searchhistory_div' style='width: 100%; height: 240px;'></div>
    </div>
    <?php
    wesp_list_customresults($id);
    wesp_add_custom_result_form($id);
}

/**
 * Form for adding a new Custom Result to a Custom Search
 */
function wesp_add_custom_result_form($id)
{
    ?>
        <p>
            <?php _e("Add new results to this custom search from the page maintenance of each individual page","wesp");?>
        </p>
    <?php
}
/**
 * A form to load any custom post
 * @global type $wpdb
 */
function wesp_admin_custom_post_search_form()
{
   //the_widget( "WP_Nav_Menu_Widget", $instance, $args );   
   //echo the_widget( "WP_Widget_Pages" );   
    //echo do_accordion_sections( 'nav-menus', 'side', null ); 
    $nav_menu_selected_id = "add-post-type-page";
    global $wp_meta_boxes;
    $initial_meta_boxes = array( 'add-post-type-page', 'add-post-type-post', 'add-custom-links', 'add-category' );
    $hidden_meta_boxes = array();
 
    foreach ( array_keys($wp_meta_boxes['nav-menus']) as $context ) {
        foreach ( array_keys($wp_meta_boxes['nav-menus'][$context]) as $priority ) {
            foreach ( $wp_meta_boxes['nav-menus'][$context][$priority] as $box ) {
                if ( in_array( $box['id'], $initial_meta_boxes ) ) {
                    unset( $box['id'] );
                } else {
                    $hidden_meta_boxes[] = $box['id'];
                }
            }
        }
    }
   //wesp_varinfo("wp_meta_boxes",$wp_meta_boxes);
    ?>
    <div >
        <form id="nav-menu-meta" class="nav-menu-meta" method="post" enctype="multipart/form-data">
			<input type="hidden" name="menu" id="nav-menu-meta-object-id" value="<?php echo esc_attr( $nav_menu_selected_id ); ?>" />
			<input type="hidden" name="action" value="add-menu-item" />
			<?php wp_nonce_field( 'add-menu_item', 'menu-settings-column-nonce' ); ?>
			<?php do_accordion_sections( 'nav-menus', 'side', null ); ?>
    </form></div>
<?php
}
/**
 * Form for adding a new Custom Search
 */
function wesp_add_customsearch_form()
{
    $redirect = urlencode( $_SERVER['REQUEST_URI'] );
    ?>
    <div class="postbox">
        <h2>Wordpress Custom Search Results </h2>
        <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
             <input type="hidden" name="action" value="wesp_add_customsearch">
            <input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
            <?php 
            echo '<p>'.__("Configuring custom Search Results allows you to ensure that your users find the correct pages based on the searches they perform.","wesp")."</p>";
            ?>
         
             <h2><?php _e("Add A custom Search","wesp")?></h2>
             <table >
                 <tr>
                     <td width="30%">
                         <label for="custom_search_add-name">Name</label>
                     </td>
                     <td>
                         <input size="25" type="text" name="custom_search_add-name" id="custom_search_add-name"/>
                     </td>
                 </tr>
                 <tr>
                     <td>
                         <label for="custom_search_add-term"><?php _e("Search Term","wesp")?></label>
                     </td>
                     <td>
                         <input size="75" type="text" name="custom_search_add-term" id="custom_search_add-term"/>
                     </td>
                 </tr>               
             </table>
         
         <input type="hidden" name="custom_search_add-type" value="partial"/>
         <?php submit_button(__('Add Custom Search',"wesp")); ?>
        </form>
    </div>
   <?php 
}
/**
 * Form for adding a new Custom Result to a Custom Search
 */
function wesp_edit_custom_search_form($customsearch)
{
    $redirect = urlencode( $_SERVER['REQUEST_URI'] );
    ?>
            <h3>Maintain Custom Search : <?php echo $customsearch->name;?></h3>
            <div class="postbox">
                
                <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
                <input type="hidden" name="action" value="wesp_edit_customsearch">
                <input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
                <input type="hidden" name="searchid" value="<?php echo $customsearch->id;?>">
                <table class="widefat fixed">
                 <tr>
                     <td width="30%">
                         <?php _e("Name","wesp");?>
                     </td>
                     <td>
                         <input size="75" type="text" name="name" id="name" value="<?php echo $customsearch->name;?>" />
                     </td>
                 </tr>
                 <tr>
                     <td>
                         <?php _e("Search Term","wesp");?>
                     </td>
                     <td>
                         <input size="75" type="text" name="term" id="term" value="<?php echo $customsearch->searchterms;?>"/>
                     </td>
                 </tr>
                 <tr>
                     <td valign="top">
                          <?php _e("Type","wesp");?>
                     </td>
                     <td>
                         <input type="radio" name="type" id="custom_search_add-type" value="partial" <?php if ($customsearch->type ==='partial') {echo "checked";}?>> <?php _e("Partial - Show custom results when search term <em> is part of</em> user's search","wesp");?>
                         <br/>
                         <input type="radio" name="type" id="custom_search_add-type" value="exact" <?php if ($customsearch->type ==='exact') {echo "checked";}?>> <?php _e("Exact - Show custom results when search term is <em>exactly</em>   user's search","wesp");?>
                     </td>
                 </tr>   
                 <tr>
                     <td valign="top">
                        <?php _e(" Show normal results","wesp");?>
                     </td>
                     <td>
                        <input type="checkbox" name="show_normal" value="1" <?php if ($customsearch->show_normal === '1') {echo "checked";}?>>
                        <?php _e("Uncheck to hide normal search results","wesp");?>
                        
                     </td>
                 </tr>  
               
                <tr>
                    <td colspan="2">
                        <?php submit_button(__("Save Changes")); ?> 
                    </td>
                </tr>
                 </table>       
            </form>
            </div>
    <?php
}
/**
 * List of Custom Results in a specific Custom  Search
 * @global type $wpdb
 */
function wesp_list_customresults($id)
{
    global $wpdb;
    $table_name_header = $wpdb->prefix . 'wesp_custom_searches';
    $table_name_results = $wpdb->prefix . 'wesp_custom_results';
    $table_name_posts = $wpdb->prefix . 'posts';
    
    $query = "select * from " . $table_name_header . " where id = " . $id;
    $customsearch = $wpdb->get_row( $query );
    wesp_print_db_info(false);
    
    $filter = "1 = 1";
    if(isset($_REQUEST["s"]) && isset($_REQUEST["table"]))
    {
        if($_REQUEST["table"] =='resultssearch')
        {
            $st = filter_var($_REQUEST["s"],FILTER_SANITIZE_STRING); 
            $filter =" post_title like '%".$st."%'";
        }
    }
    $sort = "priority asc";
    if(isset($_REQUEST["orderby"]))
    {
        $o = filter_var($_REQUEST["orderby"],FILTER_SANITIZE_STRING); 
        $d = filter_var($_REQUEST["order"],FILTER_SANITIZE_STRING); 
        $sort = $o ." " .$d;
    }
    
    $query = "select r.*, p.post_title as title, p.post_type from " . $table_name_results . " as r "
            . " inner join ". $table_name_posts ." as p on p.ID = r.post_id "
            . " where r.searchid = " . $id 
            . " and ".$filter
            . " order by ".$sort;
    if($wpdb->num_rows > 0)
    {
        wesp_edit_custom_search_form($customsearch);
       
        $customresults = $wpdb->get_results( $query );
        wesp_print_db_info(false);
        if($wpdb->num_rows > 0)
        {
            if(!class_exists('WP_List_Table')){
            require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
        }
        class wesp_customresults_table extends WP_List_Table {
            var $db_rows;
            function set_data($rows)
            {
                $this->db_rows = $rows;
            }
            function get_columns(){
            $columns = array(
              'id'              => '<span class="dashicons dashicons-trash"></span>',
              'priority'        => __('Priority',"wesp"),
              'sort'            => '<span class="dashicons dashicons-sort"></span>',
              'title'           => __('Title',"wesp"),
              'type'            => __('Post Type',"wesp"),
              
            );
            return $columns;
          }
          function column_default($item,$column_name)
          {
              $redirect = urlencode( $_SERVER['REQUEST_URI'] );
              $postaction = "admin-post.php";
              $postaction = add_query_arg( '_wp_http_referer', $redirect."#cr", $postaction );
              $postaction = add_query_arg( 'term', $item["id"], $postaction );
              
              
              
              switch( $column_name ) { 
                  case "id":
                   $postaction = add_query_arg( 'action', "wesp_delete_customresult", $postaction );
                   return "<a href=".$postaction."><span class='dashicons dashicons-trash'></span></a>";  
                   break;
                  case "sort":
                      $postaction =add_query_arg( 'action', "wesp_reorder_customresult", $postaction );
                      $upaction =add_query_arg( 'direction', "up", $postaction );
                      $downaction =add_query_arg( 'direction', "down", $postaction );
                      $up = "<a href=".$upaction."><span class='dashicons dashicons-arrow-up'></span></a>";
                      $down = "<a href=".$downaction."><span class='dashicons dashicons-arrow-down'></span></a>";
                      return   $up."&nbsp;".$down;
                      break;
                  case "title":
                      return "<a href='".get_permalink($item["post_id"])."' target='_blank'>".$item["title"]."</a>";
                      break;
                  default:
                     return $item[$column_name];
              }
             
          }
          function prepare_items() {
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = 
               array(
                'priority' => array('priority',false),
                'title'    => array('title',false),
                'type'    => array('post_type',false)
                );
            $this->_column_headers = array($columns, $hidden, $sortable);
            $data = array();
            
            foreach($this->db_rows as $row)
            {
                $new = array();
                $new["id"] = $row->id;
                $new["title"] = $row->title;
                $new["priority"] = $row->priority;
                $new["type"] = $row->post_type;
                $new["post_id"] = $row->post_id;
                array_push($data,$new);
            }
            $total_items = count($data);
            $per_page = 10;
            $current_page = 1;
            if(isset($_REQUEST["paged"]))
            {
                $current_page = filter_var($_REQUEST["paged"],FILTER_SANITIZE_STRING);
            }
            $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
            $this->set_pagination_args( array(
                'total_items' => $total_items,                  //WE have to calculate the total number of items
                'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
                'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
               ) );
            $this->items = $data; 
          }
        }
        ?>
        <div class="wrap" id="cr">
        <h2><?php _e("Custom Results","wesp")?></h2>
        <?php
        $table = new wesp_customresults_table();
        $table->set_data($customresults);
        $table->prepare_items();
        ?>
        <form method="post">
        <input type="hidden" name="page" value="wesp-plugin-customresults" />
        <input type="hidden" name="table" value="resultssearch" />
        <?php
        $table->search_box(__('Filter',"wesp"), 'search');
        ?>
        </form>
        <?php
        $table->display();
        ?>
        
        </div>
            <?php
        }
    }
}
/**
 * List of Defined Custom Searches
 * @global type $wpdb
 */
function wesp_list_customsearches()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wesp_custom_searches';
    $query = "select * from " . $table_name . " order by name asc";
    
    if(isset($_REQUEST["s"]))
    {
        $st = filter_var($_REQUEST["s"],FILTER_SANITIZE_STRING); 
        $query = "select * from " . $table_name . " where name like '%".$st."%' or searchterms like '%".$st."%'  order by name asc";
    }
    
    $customsearches = $wpdb->get_results( $query  );
    wesp_print_db_info(false);
    if($wpdb->num_rows > 0)
    {
        if(!class_exists('WP_List_Table')){
            require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
        }
        class wesp_customsearches_table extends WP_List_Table {
            var $db_rows;
            function set_data($rows)
            {
                $this->db_rows = $rows;
            }
            function get_columns(){
            $columns = array(
              'id'              => '<span class="dashicons dashicons-trash"></span>',
              'name'            => __('Name',"wesp"),
              'searchterm'      => __('Search Term',"wesp")
            );
            return $columns;
          }
          function column_default($item,$column_name)
          {
              switch( $column_name ) { 
                  case "id":
                   $redirect = urlencode( $_SERVER['REQUEST_URI'] );
                   $postaction = "admin-post.php";
                   $postaction = add_query_arg( '_wp_http_referer', $redirect, $postaction );
                   $postaction = add_query_arg( 'term', $item["id"], $postaction   );
                   $postaction = add_query_arg( 'action', "wesp_delete_customsearch", $postaction );        
                   return "<a href=".$postaction."><span class='dashicons dashicons-trash'></span></a>";  
                   break;
                case "name":
                   return "<a href=".$_SERVER['REQUEST_URI'] ."&wesp_action=editterm&term=". $item["id"].">".$item[$column_name]."</a>";  
                   break;
                  default:
                     return $item[$column_name];
              }
             
          }
          function prepare_items() {
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = 
               array(
                'name'         => array('name',false),
                'searchterm'   => array('searchterm',false));
            $this->_column_headers = array($columns, $hidden, $sortable);
            $data = array();
            
            foreach($this->db_rows as $row)
            {
                $new = array();
                $new["id"] = $row->id;
                $new["name"] = $row->name;
                $new["searchterm"] = $row->searchterms;
                array_push($data,$new);
            }
            $total_items = count($data);
            $per_page = 10;
            $this->set_pagination_args( array(
                'total_items' => $total_items,                  
                'per_page'    => $per_page,                    
                'total_pages' => 1 
               ) );
            $this->items = $data; 
          }
        }
        ?>
        <div class="wrap">
        <h2><?php _e("Custom Searches","wesp")?></h2>
        <?php
        $table = new wesp_customsearches_table();
        $table->set_data($customsearches);
        $table->prepare_items();
        ?>
        <form method="post">
        <input type="hidden" name="page" value="wesp-plugin-customsearches" />
        <?php
        $table->search_box(__('Find',"wesp"), 'search');
        ?>
        </form>
        <?php
        $table->display();
        ?>
        
        </div>
        <?php
    }
    
}
/**
 * Database Handler for Adding a new Custom Search
 * @global type $wpdb
 */
function wesp_add_customsearch_action()
{
    status_header(200);
    global $wpdb;
    $name = $_REQUEST['custom_search_add-name'];
    $term = $_REQUEST['custom_search_add-term'];
    $type = $_REQUEST['custom_search_add-type'];
    
    $table_name = $wpdb->prefix . 'wesp_custom_searches';
    $time = current_time( 'mysql', 1 ); 
    $wpdb->insert( 
	$table_name, 
	array( 
		'name' => $name, 
		'searchterms' => $term,
                'type' => $type,
                'time' => $time
	));
    $msg = wesp_print_db_info(false);
    if($msg ==='')
    {
        $msg = __("Custom Search term added.","wesp");
    }
    
    wesp_admin_post_return($msg);
}
/**
 * Database Handler for Editing a new Custom Search
 * @global type $wpdb
 */
function wesp_edit_customsearch_action()
{
    status_header(200);
    global $wpdb;
    $name = filter_var($_REQUEST["name"],FILTER_SANITIZE_STRING);
    $term = filter_var($_REQUEST["term"],FILTER_SANITIZE_STRING);
    $type = filter_var($_REQUEST["type"],FILTER_SANITIZE_STRING);
    $show_normal = filter_var($_REQUEST["show_normal"],FILTER_VALIDATE_INT);
    $id =   filter_var($_REQUEST["searchid"],FILTER_VALIDATE_INT);
    $table_name = $wpdb->prefix . 'wesp_custom_searches';
    $time = current_time( 'mysql', 1 ); 
    
    $wpdb->update( 
	$table_name, 
	array( 
		'name' => $name,	
		'searchterms' => $term,
                'type' => $type,
                'show_normal' => $show_normal
	), 
	array( 'id' => $id ), 
	array( 
		'%s',	
		'%s',
                '%s',
                '%d'
	), 
	array( '%d' ) 
);
    $msg = wesp_print_db_info(false);
    if($msg ==='')
    {
        $msg = __("Custom Search term added.","wesp");
    }
    
    wesp_admin_post_return($msg);
}
/**
 * Database Handler for adding a new Custom Result to a Custom Search
 * @global type $wpdb
 */
function wesp_add_customresult_action()
{
    status_header(200);
    global $wpdb;
    $id = filter_var($_REQUEST["term"],FILTER_VALIDATE_INT);
    $postid = filter_var($_REQUEST["page_id"],FILTER_VALIDATE_INT);
    //get the max prio that already exists
    $max = 0;
    $table_name = $wpdb->prefix . 'wesp_custom_results';
    $query = "select max(priority) as prio from " . $table_name . " where searchid = " .$id;
    $priorec = $wpdb->get_row( $query );
    if($priorec)
    {
        $max = $priorec->prio;
        $max++;
    }
    wesp_print_db_info(false);
    
    $wpdb->insert( 
	$table_name, 
	array( 
		'searchid' => $id, 
		'post_id' => $postid,
                'priority' => $max
	));
        $msg = wesp_print_db_info(true);
        if($msg ==='')
        {
            $msg = __("Custom Search result added.","wesp");
        }
        //die();
        wesp_admin_post_return($msg);
}
/**
 * Database Handler for deleting a Custom Result to a Custom Search
 * @global type $wpdb
 */
function wesp_delete_customresult_action()
{
    status_header(200);
    global $wpdb;
    $table_name = $wpdb->prefix . 'wesp_custom_results';
    $id = filter_var($_REQUEST["term"],FILTER_VALIDATE_INT);
    $postid = filter_var($_REQUEST["page_id"],FILTER_VALIDATE_INT);
    //$term = filter_var($_REQUEST["term"],FILTER_VALIDATE_INT);
    
    if(!$postid)//a direct table id was specified
    {
        $wpdb->delete( 
            $table_name, 
            array( 
                    'id' => $id
            ));
    }
    else
    {
         $wpdb->delete( 
            $table_name, 
            array( 
                    'searchid' => $id,
                    'post_id'   => $postid
            ));
    }
    
        $msg = wesp_print_db_info(true);
        if($msg ==='')
        {
            $msg = __("Custom Search result deleted.","wesp");
        }
       
        wesp_admin_post_return($msg);
}
/**
 * Database Handler for deleting a Custom Result to a Custom Search
 * @global type $wpdb
 */
function wesp_delete_customsearch_action()
{
    status_header(200);
    global $wpdb;
    $table_name = $wpdb->prefix . 'wesp_custom_searches';
    $table_name_results = $wpdb->prefix . 'wesp_custom_results';
    $id = filter_var($_REQUEST["term"],FILTER_VALIDATE_INT);
    //$postid = filter_var($_REQUEST["page_id"],FILTER_VALIDATE_INT);
    
    //Delete The search
    $wpdb->delete( 
	$table_name, 
	array( 
		'id' => $id
	));
        $msg = wesp_print_db_info(false);
    //Delete all results    
    $wpdb->delete( 
	$table_name_results, 
	array( 
		'searchid' => $id
	));
        $msg = wesp_print_db_info(false);    
        if($msg ==='')
        {
            $msg = __("Custom Search deleted.","wesp");
        }
        wesp_admin_post_return($msg);
}
/**
 * Database handler for re-ordering custom results for a custom search
 * direction = up/down
 * @global type $wpdb
 */
function wesp_reorder_customresult_action()
{
    status_header(200);
    global $wpdb;
    $table_name = $wpdb->prefix . 'wesp_custom_results';
    $id = filter_var($_REQUEST["term"],FILTER_VALIDATE_INT);
    $direction = filter_var($_REQUEST["direction"],FILTER_SANITIZE_STRING);
    $sign = "<";
    $order = "desc";
    if($direction == "down")
    {
        $sign = ">";
        $order = "asc"; 
    }
    //$postid = filter_var($_REQUEST["page_id"],FILTER_VALIDATE_INT);
    //TODO : switch priority with item that's above or below depending on direction
    $query = "select id,priority,searchid from ".$table_name." where id = " .$id;
    $current = $wpdb->get_row($query);
    if($current)
    {
        $query2 ="select id,searchid, priority from ".$table_name." where searchid = ".$current->searchid." and priority ". $sign." ".$current->priority." "   
                    ."order by priority ".$order." "
                    ."limit 0,1";
        $switch = $wpdb->get_row($query2);
        if($switch)
        {
            //switch the priorities
            $wpdb->update( 
                    $table_name, 
                    array( 
                            'priority' => $switch->priority
                    ), 
                    array( 'id' => $current->id ), 
                    array( 
                           '%d'	
                    ) 
            );
             $msg = wesp_print_db_info(false);
             $wpdb->update( 
                    $table_name, 
                    array( 
                            'priority' => $current->priority
                    ), 
                    array( 'id' => $switch->id ), 
                    array( 
                           '%d'	
                    ) 
            );
        }
    }
    
    
        $msg .= wesp_print_db_info(false);
        if($msg ==='')
        {
            $msg = __("Priority changed.","wesp");
        }
    
        wesp_admin_post_return($msg);
       
}

function wesp_save_posttypes_action()
{
    status_header(200);
    //wesp_varinfo("request",$_REQUEST);
    $types = get_post_types(array(),"names");
    foreach($types as $type)
    {
        $optionname = "wesp_searchtype_".$type;
        if(isset($_REQUEST[$optionname]))
        {
            $val = filter_var($_REQUEST[$optionname],FILTER_VALIDATE_INT);
        }
        else {
            $val = 0;
        }
        if($type === "post" || $type =="page")
        {
            $val = 1;
        }
        update_option( $optionname ,$val ); 
    }
    $msg = "Search Post Type settings stored";
    wesp_admin_post_return($msg);
}

?>