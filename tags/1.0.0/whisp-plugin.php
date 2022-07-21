<?php
/**
 * Plugin Name:       WHISP
 * Plugin URI:        https://blog.whisp.io/whisp-plugin/
 * Description:       Easy Way to Capture New Leads.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Todd Westra
 * Author URI:        https://team.whisp.io/todd-westra
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whisp
 * Domain Path:       /whisp
*/

if(!defined('ABSPATH')) {
  die('Do not open this file directly.');
}

class WhispPlugin {

  function __construct() {
    add_action('admin_menu', array( $this, 'whisp_add_pages'));
    add_action('wp_enqueue_scripts', array( $this, 'load_scripts'));    
    add_action('admin_enqueue_scripts', array( $this, 'load_scripts'));
    add_action( 'admin_post_nopriv_save_my_custom_form', array( $this, 'whisp_save_form'));
    add_action( 'admin_post_save_my_custom_form', array( $this, 'whisp_save_form'));
    add_shortcode('WHISP', array( $this, 'whisp_shortcode_function'));
  }

  function whisp_activate() {
    global $wpdb;
    global $table_prefix;
    $table = $table_prefix.'whisp';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      product_identifier varchar(55) DEFAULT '' NOT NULL,
      type varchar(55) DEFAULT '' NOT NULL,
      source varchar(55) DEFAULT '' NOT NULL,
      campaign varchar(55) DEFAULT '' NOT NULL,
      other varchar(55) DEFAULT '' NOT NULL,
      code varchar(500) DEFAULT '' NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
  }

  function whisp_deactivate() {
    global $wpdb;
    global $table_prefix;
    $table = $table_prefix.'whisp';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "DROP TABLE IF EXISTS wp_whisp";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
   
  }


  function whisp_remove_database() {
       global $wpdb;
       $table_name = $wpdb->prefix . 'whisp';
       $sql = "DROP TABLE IF EXISTS $table_name";
       $wpdb->query($sql);
  }


  function whisp_add_pages() {
       add_menu_page(
          __( 'WHISP', 'textdomain' ),
          __( 'WHISP','textdomain' ),
          'manage_options',
          'whisp_form',
           array( $this, 'whisp_page_callback'),
          'dashicons-wordpress-alt'
      );
  }


  function load_scripts() {
      wp_register_style('wpwhisp_custom.css', plugins_url('whisp/inc/wpwhisp_custom.css'));
      wp_enqueue_style('wpwhisp_custom.css'); 
      wp_register_style('bootstrap.css', plugins_url('whisp/inc/bootstrap.css'));
      wp_enqueue_style('bootstrap.css'); 
      wp_register_script ('bootstrap.js', plugins_url('whisp/inc/bootstrap.js'));
      wp_enqueue_script ('bootstrap.js' );
      wp_register_script ('wpwhisp_custom.js', plugins_url('whisp/inc/wpwhisp_custom.js'));
      wp_enqueue_script ('wpwhisp_custom.js' );
  }



   function whisp_page_callback() {
    global $session;
    echo '<br /><h3>WHISP Admin</h3><button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#myModal" style="border: 1px solid #2271b1; font-weight: bold; color: #2271b1">
  Add New
</button><br /><br />';
    echo '  <div class="modal fade" id="myModal" role="dialog">
      <div class="modal-dialog">
      
        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Create Custom Code</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
  <form id="myForm" name="myform" action="' . esc_attr( admin_url('admin-post.php') ).'" method="POST" onsubmit="return validateForm()">
    <input type="hidden" name="action" value="save_my_custom_form" />
      <table>
        <tr>
          <td style="padding-bottom: 20px;">Product Identifier</td>
          <td style="padding-left: 10px;"><input name="whisp_pid" type="text" required/><br /><br /></td>
        </tr>
        <tr>
          <td style="padding-bottom: 20px;">WHISP Type</td>
          <td style="padding-left: 10px;">
           <select id="name" name="whisp_type" size="1" required>
              <option selected="selected" value="">-- Select --</option>
              <option value="text">Text</option>
              <option value="wheel">Wheel</option>
          </select><br /><br />
          </td>
        </tr>
        <tr>
          <td style="padding-bottom: 20px;">SOURCE</td>
          <td style="padding-left: 10px;"><input type="text" name="whisp_source" required/><br /><br /></td>
        </tr>
        <tr>
          <td style="padding-bottom: 20px;">CAMPAIGN</td>
          <td style="padding-left: 10px;"><input type="text" name="whisp_campaign" required/><br /><br /></td>
        </tr>
        <tr>
          <td style="padding-bottom: 20px;">OTHER</td>
          <td style="padding-left: 10px;"><input type="text" name="whisp_other" required/><br /><br /></td>
        </tr>
        <tr>
          <td></td>
          <td style="padding-left: 10px;"><input type="submit" value="ADD" /><br /><br /></td>
        </tr>
      </table>
  </form><br /> <br />
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>';

echo "<table class='table table-dark table-striped wpwhisptbl'>";
echo "<th>Product Identifier</th>";
echo "<th>Whisp Type</th>";
echo "<th>Source</th>";
echo "<th>Campaign</th>";
echo "<th>Other</th>";
echo "<th>&nbsp;</th>";
echo "<th>&nbsp;</th>";
$args = array(
  'post_type' => 'wp_whisp',
);
$query = new WP_Query( $args );

if ( $query->have_posts() ) {
    while ( $query->have_posts() ) { 
    $query->the_post();

      $wraptsco  = get_the_excerpt();
      $tsco = explode("whisptscoz", $wraptsco);

      echo "<tr>";

      echo "<td>";
            esc_attr(the_content());
      echo "</td>";

      echo "<td>";
      echo esc_attr($tsco[0]);
      echo "</td>";

      echo "<td>";
      echo esc_attr($tsco[1]);
      echo "</td>";

      echo "<td>";
      echo esc_attr($tsco[2]);
      echo "</td>";

      echo "<td>";
      echo esc_attr($tsco[3]);
      echo "</td>";

      echo "<td><div class='whispwp-code-header'><input type='text' style='width: 85%;background-color: #00172f !important;color: #26a9e0;' value='";
      esc_attr(the_title());
      echo "' id='whispCopyCodeInput".esc_attr(get_the_ID())."'><button style='float: right;margin-top: 2px;' onclick='whispwpCodeFunction(".esc_attr(get_the_ID()).")'>Copy</button></div></td>";
      echo "<td><button 
      onclick='whisp_delete(".esc_attr(get_the_ID()).")' class='btn btn-danger btn-sm'>
      Delete
    </button></td>";

      echo "</tr>";


    }
     echo "</table>";
     echo "<script>function whispwpCodeFunction(id) {
   var copyText = document.getElementById('whispCopyCodeInput'+id);
   copyText.select();
   document.execCommand('Copy');
   alert('Copy : ' + copyText.value);
 }</script>";

 
    wp_reset_postdata();
 

} ?>

 <?php

    }//END


   function whisp_save_form() {
      global $wpdb;
      $pid = sanitize_text_field($_POST['whisp_pid']);
      $whisptype = sanitize_text_field($_POST['whisp_type']);
      $source = sanitize_text_field($_POST['whisp_source']);
      $campaign = sanitize_text_field($_POST['whisp_campaign']);
      $other = sanitize_text_field($_POST['whisp_other']);
      $tsco = $whisptype.' whisptscoz '.$source.' whisptscoz '.$campaign.' whisptscoz '.$other;
      $wcode01 = "[WHISP product_identifier=";
      $wcode02 = "]";
      $whisp_shortcode = $wcode01.'"'.$pid.'" button_type="'.$whisptype.'"'.$wcode02 ;

 
      if((!empty($pid)) && (!empty($whisptype)) && (!empty($source)) && (!empty($campaign)) && (!empty($other))){
        
        $my_post = array(
          'post_content' => $pid, 
          'post_title' => $whisp_shortcode, 
          'post_excerpt' => $tsco, 
          'post_status' => 'publish',
          'post_type' => 'wp_whisp'
        );
       
        // Insert the post into the database
          wp_insert_post( $my_post );
          
          wp_redirect( site_url('/wp-admin/admin.php?page=whisp_form') ); 
          die;
      } else {
          wp_redirect( site_url('/wp-admin/admin.php?page=whisp_form') );
          die;
      }

      // Create post object

  }


  function whisp_shortcode_function($atts) {
    $a = shortcode_atts(array(
      'product_identifier' => '',
      'button_type' => ''
    ), $atts);
   $product_id = $a['button_type'];
    switch( $product_id ){
      case 'wheel': 
          $output =  '<div id="button_wtf"><img width="150px" class="taptext-wheel btn-tap-text" src="'.esc_url(plugin_dir_url( __FILE__ ).'assets/spinthewheel.png').'"  /></div>';
          echo wp_get_script_tag( array(
              'type' => 'text/javascript',
              'id' => 'taptext-lib',
              'data-chatbtn' => 'true',
              'data-productidentifer' => esc_attr($a['product_identifier']),
              'src' => esc_url('https://hub.taptext.com/scripts/taptext_lib.js')
          ) );
          break;

      case 'text': 
          $output =  '<div id="button_wtf">
      <a href="javascript:" class="pink-btn" data-itemurl="" data-utm_source="" data-utm_medium="" data-utm_campaign="" data-utm_term="" data-utm_content="" data-productidentifer="' . esc_attr($a['product_identifier']) . '" onclick="onTapTextClick(this)" style="float: left; cursor:pointer"><img src="'.esc_url(plugin_dir_url( __FILE__ ).'assets/taptext.png').'" width="150px" style="float: left;" /></a></div>';
          echo wp_get_script_tag( array(
              'type' => 'text/javascript',
              'id' => 'taptext-lib',
              'data-chatbtn' => 'true',
              'data-productidentifer' => esc_attr($a['product_identifier']),
              'src' => esc_url('https://hub.taptext.com/scripts/taptext_lib.js')
          ) );
          break;

      default:
          $output = '<div>&nbsp;</div>';
          break;
    }
     return $output;
  ?>

  <?php
  }
}


if(isset($_GET['whisp_delete'])){
    $id = intval($_GET['whisp_delete']);
    wp_delete_post($id);
}


if( class_exists('WhispPlugin')) {
  $whispplugin = new WhispPlugin();
}

function whisp_js() {
    echo '<script type="text/javascript" id="taptext-lib" data-chatbtn="true" data-productidentifer="ba99e74a-34acd733" src="https://hub.taptext.com/scripts/taptext_lib.js"></script><script src="https://code.jquery.com/jquery-3.5.0.js"></script>';
}
// Add hook for admin <head></head>
add_action( 'admin_head', 'whisp_js' );
// Add hook for front-end <head></head>
add_action( 'wp_head', 'whisp_js' );


register_activation_hook( __FILE__, array( $whispplugin, 'whisp_activate'));
register_deactivation_hook( __FILE__, array( $whispplugin, 'whisp_remove_database'));
