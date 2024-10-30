<?php
/*
Plugin Name:  Know Co. Platform Base
Description:  Assists custom wordpress plugins built for the Know Platform in connecting with your Know Server. 
Version:      1.0.2
Author:       Know Co.
Author URI:   https://getknow.co/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once(__DIR__.'/classes.php');

function know__head() {
	
	$know = new know_platform();
	
	if($know->is_user_logged_in()){ ?>
		<style>
			.know--logged-in {
				display:inherit;
			}
			.know--logged-out {
				display: none !important;
			}
		</style>
	<?php } else { ?>
		<style>
			.know--logged-in {
				display:none !important;
			}
			.know--logged-out {
				display: inherit;
			}
		</style>
	<?php }

}

add_action( 'wp_head', 'know__head' );

function know__logout($atts = [], $content = null, $tag = ''){
	
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
 
    $wporg_atts = shortcode_atts([
		'redirect' => ''
	], $atts, $tag);
	
	if($wporg_atts['redirect'] != ""){
		
		$know = new know_platform();
		$know->log_out();
		return '<meta http-equiv="refresh" content="0; url='.$wporg_atts['redirect'].'">';
		
	} else return 'Please specify a redirect.';
}

add_shortcode('know--logout', 'know__logout');

function know__target_session($atts = [], $content = null, $tag = ''){
	
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    $know = new know_platform();
 
    $wporg_atts = shortcode_atts([
		'redirect' => '',
		'server' => ''
	], $atts, $tag);
	
	if(isset($_POST['know__post_action']) && $_POST['know__post_action'] == 'target_session' && isset($_POST['Id']) && isset($_POST['Auth_Code'])){
		
		$auth_code = preg_replace("/[^a-z0-9]+/i", "", $_POST['Auth_Code']);

		$know->target_session(intval($_POST['Id']), $auth_code);
		
		if($wporg_atts['redirect'] != "" ) return '<meta http-equiv="refresh" content="0; url='.$wporg_atts['redirect'].'">';
	}
}

add_shortcode('know--target-session', 'know__target_session');

function know__register_settings(){
	register_setting('know--settings-group', 'know__server_url');
	register_setting('know--settings-group', 'know__api_key');
	register_setting('know--settings-group', 'know__api_secret');
}

add_action( 'admin_init', 'know__register_settings' );
 
function know__admin_menu(){
	
    add_menu_page( 'Know Platform Settings', 'Know Platform', 'manage_options', 'know_settings', 'know__admin_init');
    
	/*add_submenu_page(
	    'know-settings', // Parent
	    'Know Platform Settings', // Page Title
	   	'Know Platform', // Menu Title
	    'manage_options', // Capability
	    'know-settings', // Menu Slug
	    'know__admin_init' // Render Function
	);*/

}

add_action('admin_menu', 'know__admin_menu');
 
function know__admin_init(){

	?>

    <style>
    	.know--container{
    		padding-right: 10px;
    	}
    	.know--input{
    		width: 100%;
    	}
    </style>

    <div class="know--container">
	    <div class="wrap">
			<h1>Know Platform Settings</h1>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
			    <?php settings_fields( 'know--settings-group' ); ?>
			    <?php do_settings_sections( 'know--settings-group' ); ?>
			    <table class="form-table">

			        <tr valign="top">
			        	<th scope="row">
			        		<label for="know_server_url">Platform Server URL</label>
			        	</th>
			        	<td>
			        		<input type="text" name="know__server_url" class="know--input" id="know_server_url" value="<?php echo esc_attr( get_option('know__server_url') ); ?>"  placeholder="ex. https://yourcompany.getknow.co">
			        	</td>
			        </tr>

			        <tr valign="top">
			        	<th scope="row">
			        		<label for="know_api_key">API Key</label>
			        	</th>
			        	<td>
			        		<input type="text" name="know__api_key" class="know--input" id="know_api_key" value="<?php echo esc_attr( get_option('know__api_key') ); ?>"  placeholder="">
			        	</td>
			        </tr>

			        <tr valign="top">
			        	<th scope="row">
			        		<label for="know_api_key">API Secret</label>
			        	</th>
			        	<td>
			        		<input type="text" name="know__api_secret" class="know--input" id="know_api_key" value="<?php echo esc_attr( get_option('know__api_secret') ); ?>"  placeholder="">
			        	</td>
			        </tr>

			        
			    </table>
			    
			    <?php submit_button(); ?>

			</form>
		</div>
	</div>

    <?php
}

/*
add_action( 'wp_ajax_know_admin_save', 'know_admin_save' );

function know_admin_save() {
	global $wpdb; // this is how you get access to the database

	$response = $_POST;

	//$_POST['know_server_url']
	//$_POST['know_api_key']

	header('Content-Type: application/json');

	echo json_encode($response);

	wp_die(); // this is required to terminate immediately and return a proper response
}*/


function know_platform_communicate() {
	//global $wpdb; // this is how you get access to the database

	//Can be called by unregistered and logged in users, doesn't matter.

	if(preg_match('/^[a-zA-Z0-9-_]+$/i', $_POST['API_Name']) == 1 &&
		preg_match('/^[a-zA-Z0-9-_]+$/i', $_POST['Method']) == 1){

		$know = new know_platform();
		if(isset($_POST['Platform']) && filter_var($_POST['Platform'], FILTER_VALIDATE_URL)){
			$know->server = $_POST['Platform'];//This is if they inlude a one off platform URL.
		}

		if(isset($_POST['Data'])) $data = $_POST['Data'];//Can contain any arbitraty amount of data needed to send through to the Platform. The platform validates. Might be an array, etc.
		else $data = array();

		$result = $know->communicate($_POST['API_Name'], $_POST['Method'], $data);

		header('Content-Type: application/json');

		echo json_encode($result);
	}

	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'wp_ajax_know_platform_communicate', 'know_platform_communicate' );

function know_platform_load_login() {
	
	$know = new know_platform();

	$sendArray = array(
		'Logged_In' => $know->is_user_logged_in()
	);

	echo json_encode($sendArray);

	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'wp_ajax_know_platform_load_login', 'know_platform_load_login' );

function know_platform_process_login() {
	
	$know = new know_platform();

	$username = $_POST['Username'] ? $_POST['Username'] : '';
	$password =  $_POST['Password'] ? $_POST['Password'] : '';

	if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
    	$sendArray = array(
			'Logged_In' => $know->log_in($username, $password)
		);
	}

	echo json_encode($sendArray);

	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'wp_ajax_know_platform_process_login', 'know_platform_process_login' );

function know_platform__cleanup_cookies() {
	
	global $wpdb;

	$know = new know_platform();

	//remove all cookies from DB that haven't been updated in the last day
	$table_name = $wpdb->prefix . 'know_base__cookies';
	$min_timestamp = date($know->get_timestamp_format(), strtotime('-1 day', strtotime($know->get_current_timestamp())));
	//subtract a set amount of time
	$results = $wpdb->get_results("SELECT id FROM $table_name WHERE last_updated < '$min_timestamp'");
	
	foreach($results as $result){
		$wpdb->delete($table_name, array(
			'id' => $result->id
		));
	}

}

add_action('know_platform__cleanup_cookies', 'know_platform__cleanup_cookies');

function know_base__activation() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'know_base__cookies';

	$sql = "CREATE TABLE $table_name (
	  id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
	  last_updated TIMESTAMP DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  type VARCHAR(55) DEFAULT '' NOT NULL,
	  hash VARCHAR(255) DEFAULT '' NOT NULL,
	  value MEDIUMINT(20) DEFAULT 0 NOT NULL,
	  PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$result = dbDelta( $sql );

	if (! wp_next_scheduled ( 'know_platform__cleanup_cookies' )) {
		wp_schedule_event(time(), 'hourly', 'know_platform__cleanup_cookies');
    }
}

register_activation_hook( __FILE__, 'know_base__activation' );

function know_base__deactivation() {
	wp_clear_scheduled_hook('know_platform__cleanup_cookies');
}

register_deactivation_hook(__FILE__, 'know_base__deactivation');