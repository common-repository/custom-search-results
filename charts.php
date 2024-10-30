<?php
/* 
 * Copyright 2016 Quodlibet BVBA. All Rights Reserved.
 */
defined('ABSPATH') or die('Access Denied!');
//Enqueue javascript resources
add_action( 'admin_enqueue_scripts', 'wesp_enqueue_scripts' );
//Register functions that produce output of ajax calls
add_action( 'wp_ajax_wesp_build_searchchart_table', 'wesp_build_searchchart_table' );
add_action( 'wp_ajax_wesp_build_heatmap_table', 'wesp_build_heatmap_table' );
/**
 * Enqueue javascript loading when they are required
 */
function wesp_enqueue_scripts()
{
    if(!isset($_REQUEST["page"]))
    {
        return ;
    }
    $adminpage =  filter_var($_REQUEST["page"],FILTER_SANITIZE_STRING); 
    if ($adminpage == 'wesp-plugin')
    {
        //Show the search heatmap
        $currentscope = "week";
        if(isset($_REQUEST["scope"]))
        {
            $currentscope = filter_var($_REQUEST["scope"],FILTER_SANITIZE_STRING);
        }
        wp_enqueue_script('wesp_chart_google', 'https://www.gstatic.com/charts/loader.js');
        wp_enqueue_script('wesp_heatmap_data', site_url().'/wp-admin/admin-ajax.php?action=wesp_build_heatmap_table&scope='.$currentscope);
    }
    if ($adminpage == 'wesp-plugin-customsearches')
    {
         //Show the history graph for this custom search
        if(isset($_REQUEST["term"]))
        {
            $term = filter_var($_REQUEST["term"],FILTER_VALIDATE_INT);
            wp_enqueue_script('wesp_chart_google', 'https://www.gstatic.com/charts/loader.js');
            wp_enqueue_script('wesp_searchhistory_data', site_url().'/wp-admin/admin-ajax.php?action=wesp_build_searchchart_table&term='.$term);
        }
    }
}
/**
 * Function that produces the javascript that initializes the graph with the search heatmap
 */
function wesp_build_heatmap_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wesp_search_logging';
    $currentscope = "week";
    if(isset($_REQUEST["scope"]))
    {
        $currentscope = filter_var($_REQUEST["scope"],FILTER_SANITIZE_STRING);
    }
    $today=date('Y-m-d H:i:s');
    $fromday = $today;
    switch($currentscope)
    {
        case "day":
            $fromday = date("Y-m-d",strtotime( $today ));
            break;
        case "week":
            $fromday = date("Y-m-d",strtotime( $today.'-7 days'));
            break;
        case "month":
            $fromday = date("Y-m-d",strtotime( $today.'-30 days'));
            break;
        case "year":
            $fromday = date("Y-m-d",strtotime( $today.'-365 days'));
            break;
    }
    $query_terms = "select searchterms, count ,custom from $table_name "
            . " where searchdate >= '$fromday' "
            . " group by searchterms order by count desc limit 100";
    $terms =  $wpdb->get_results($query_terms);
    wesp_print_db_info(false);
    header('Content-Type: application/javascript');
    //Calculate totals
    $total = 0;
    $totalcustom = 0;
    $totalnormal = 0;
     foreach($terms as $term) {
         $total += $term->count;
         if($term->custom == '1')
         {
              $totalcustom += $term->count;
         }
         else
         {
             $totalnormal += $term->count;
         }
     }
    ?>
    google.charts.load('current', {'packages':['treemap']});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Search', 'Parent', 'Searches','Custom']
          <?php
          $t = "All (".$total.")";
          $n = "Normal Searches (".$totalnormal.")";
          $c = "Custom Searches (".$totalcustom.")";
          ?>
          ,['<?=$t?>',null,<?=$total?>,0 ]
          ,['<?=$n?>','<?=$t?>',<?=$totalnormal?>,0 ]
          ,['<?=$c?>','<?=$t?>',<?=$totalcustom?>,1 ]
          <?php
          foreach($terms as $term) {
              $parent = $n;
              if($term->custom == '1')
              {
                   $parent = $c;
              }
          ?>,
          ['<?php echo $term->searchterms;?> ( <?php echo $term->count?> )',   '<?php echo $parent?>', <?php echo $term->count?>,<?php echo $term->custom?>]
          <?php
            }
          ?>
        ]);
        tree = new google.visualization.TreeMap(document.getElementById('heatmap_div'));
        tree.draw(data, {
          minColor: '#ffffb3',
          midColor: '#ddd',
          maxColor: '#99ff99',
          headerHeight: 15,
          fontColor: 'black',
          showScale: false
        });

      }
    <?php
}
/**
 * Function that produces the javascript that initializes the graph with the history of a particular custom search
 */
function wesp_build_searchchart_table()
{
    header('Content-Type: application/javascript'   );
    global $wpdb;
    if(isset($_REQUEST["term"]))
    {
      $term = filter_var($_REQUEST["term"],FILTER_VALIDATE_INT);  
    }
    else 
    {
     die("//No Search Term Provided");
    }
    $table_logging = $wpdb->prefix . 'wesp_search_logging';
    $table_searches = $wpdb->prefix . 'wesp_custom_searches';
    $log =  $wpdb->get_results("SELECT searchdate,count FROM $table_logging as l
            inner join $table_searches as s
            on s.searchterms = l.searchterms
            where s.id = ".$term);
    if($log)
    {
    ?>
      google.charts.load('current', {'packages':['annotationchart']});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('date', 'Date');
        data.addColumn('number', 'Searches');
        data.addRows([
        <?php
            $comma = "";
            foreach($log as $logrec)
            {
                $d = strtotime($logrec->searchdate);
                $jsdat = date("Y",$d) .",".date("m",$d).",".date("d",$d);
                ?><?php echo $comma?>[new Date(<?php echo $jsdat?>),<?php echo $logrec->count?>]
                <?php
                $comma = ",";
            }
        ?>
        ]);
        var chart = new google.visualization.AnnotationChart(document.getElementById('searchhistory_div'));
        var options = {
          displayAnnotations: false
        };
        chart.draw(data, options);
      }
      <?php
    }
    else 
    {
     ?>
      window.onload =  function() {document.getElementById('searchhistory_div').text =<?php _e("No History for this search","")?>;};
      <?php
    }
    die();
}
function wesp_heatmap_scope_url($scope,$label)
{
    $adminpage =  filter_var($_REQUEST["page"],FILTER_SANITIZE_STRING); 
    $currentscope = "week";
    if(isset($_REQUEST["scope"]))
    {
        $currentscope = filter_var($_REQUEST["scope"],FILTER_SANITIZE_STRING);
    }
    if($scope == $currentscope)
    {
        echo $label;
    }
    else
    {
        ?><a href="?page=<?php echo $adminpage;?>&scope=<?php echo $scope;?>"><?php echo $label;?></a><?php
    }
}
function wesp_show_charts()
{
    
    ?>
      <h2><?php _e('Search Overview','wesp')?></h2>
      <p>
            <?php _e("Drill down on the heatmap to see what your users are searching for.","wesp")?>
            <?php _e("This chart represents the 100 most frequent searches","wesp")?>
      </p>
      <div>
          <?php wesp_heatmap_scope_url("day","Day");?> - 
          <?php wesp_heatmap_scope_url("week","Week");?> - 
          <?php wesp_heatmap_scope_url("month","Month");?> - 
          <?php wesp_heatmap_scope_url("year","Year");?>
          
      <div id="heatmap_div" style="width: 900px; height: 500px;"></div>
      </div>
    <?php
}