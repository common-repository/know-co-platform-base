<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class know_platform{
	
	private $alerts = array();
	public $server = null;
	public $url_identifier = 'a';
	
	public function __construct(){

		//get know_platform cookie if it exists
		//if it does not exist, create it in the WP database. Hash it on the client side. 
		//it simply has a hash (available to the client), a type, and an int.
		//type is "user" for anything done through this plug. For events, type can be "event".
		//We sholudnt store anything other than the value, type, and hash.

		//$user_cookie = $know->get_cookie('user');
		//if($user_cookie) //$user_id = intval($user_cookie);

		//$event_cookie = $know->get_cookie('event');
		//if($event_cookie) //$event_id = intval($event_cookie);

		////

		//$know->set_cookie('user', $user_id);//automatically gets hashed
		//$know->delete_cookie('user', $user_id);//loggs out user


		get_option('know--settings-group');
		$this->server = get_option('know__server_url');
		$this->api_key = get_option('know__api_key');
		$this->api_secret = get_option('know__api_secret');
	}

	private function hash($value){
		return wp_hash_password($value);
	}

	public function get_cookie($name){
		$name = $this->sanitize('array_index', $name);
		if(isset($_COOKIE[$name])){
			$cookie_hash = $_COOKIE[$name];
			$cookie_hash = esc_sql($cookie_hash);
			global $wpdb;
			$table_name = $wpdb->prefix . 'know_base__cookies';
			$result = $wpdb->get_row("SELECT value, id FROM $table_name WHERE hash = '$cookie_hash'");
			if($result){
				//update last updated

				$wpdb->update($table_name, array(
					'last_updated' => $this->get_current_timestamp()
				), array(
					'id' => $result->id
				));

				return $result->value;
			}
		}
		return false;


	}

	public function set_cookie($name, $value){
		//create hash
		//store in DB with Type and Value
		$value = $this->sanitize('integer', $value);
		$cookie_hash = $this->hash($value);
		$name = $this->sanitize('api_name', $name);

		global $wpdb;
		$table_name = $wpdb->prefix . 'know_base__cookies';

		$wpdb->insert($table_name, array(
			'last_updated' => $this->get_current_timestamp(),
			'type' => $name,
			'hash' => $cookie_hash,
			'value' => $value
		));

		//setcookie($name, $cookie_hash, time() + (86400 * 30), "/"); // 86400 = 1 day
		setcookie($name, $cookie_hash, 0, "/");
	}

	public function delete_cookie($name){
		$name = $this->sanitize('array_index', $name);

		if(isset($_COOKIE[$name])){
			$cookie_hash = $_COOKIE[$name];
			$cookie_hash = esc_sql($cookie_hash);
			global $wpdb;
			$table_name = $wpdb->prefix . 'know_base__cookies';
			$result = $wpdb->get_row("SELECT id FROM $table_name WHERE hash = '$cookie_hash'");
			if($result){

				$wpdb->delete($table_name, array(
					'id' => $result->id
				));

			}
		}

		//we do this regardless just in case
		unset($_COOKIE[$name]);
		// empty value and expiration one hour before
		setcookie($name, '', time() - 3600, "/");
		return true;
	}
	
	public function enable_scripts(){
		
		?>
		<script>
			var know__platform__directory = '<?php echo plugin_dir_url( __FILE__ ); ?>';
			know__platform__directory = know__platform__directory.substring(0, know__platform__directory.length - 1);
		</script>
		<?php
	}
	
	public function post($url, $parameters = array()){
		 
		$args = array(
		    'body' => $parameters,
		    'sslverify' => false//For development
		);
		 
		$response = wp_remote_post( $url, $args );

		return wp_remote_retrieve_body( $response );
		////////////////////////////////////////////////////////////////
		//DEPRICATED

		$postdata = http_build_query($parameters);

		$opts = array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata
			),
			'ssl' => array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			)
		);

		$result = @ file_get_contents($url, false, stream_context_create($opts));
		return $result;
	}
	
	function has_valid_server(){
		
		$result = $this->post($this->server . '/system/externalRequests/system/test');
		
		if($result){
			$result = json_decode($result, true);
			if($result['valid_server_url'] == true) return true;
		}
		return false;
	}
	
	function add_alert($content = '', $heading = '', $type = ''){
		array_push($this->alerts, array(
			'content' => $content,
			'heading' => $heading,
			'type' => $type
		));
	}

	function display_alerts(){

		//support theme alert types
		$accepted_alert_types = array(
			'warning',
			'info',
			'success',
			'danger',
			'muted'
		);

		foreach($this->alerts as $alert){
			if(in_array($alert['type'], $accepted_alert_types)) $alert_type = $alert['type'];
			else $alert_type = 'danger';

			?>[alert type="<?php echo $alert_type; ?>" close="false" heading="<?php echo $alert['heading'] ?>"]<?php echo $alert['content']; ?>[/alert]<?php
		}
	}
	
	function log_in($username, $password){
		
		$this->log_out();

		//this should utilize platform methods. Change login to be part of the System App rather than its own component.
		
		$result = $this->post($this->server . '/system/externalRequests/system/authenticate', array(
			'username' => $username,
			'password' => $password
		));

		if($result){
			$result = json_decode($result, true);
			if($result){
				if($result['authenticated'] == true){
					$this->set_cookie('know__system__user_id', $result['user']['Id']);
					//$_SESSION['know']['user'] = $result['user'];
					return true;
				}
			}
		}
		
		return false;
	}

	public function sanitize($type, $value){
		if($type == 'api_name') return preg_replace('/[^a-zA-Z0-9_]/', '', $value);
		else if($type == 'array_index') return preg_replace('/[^a-zA-Z0-9_]/', '', $value);
		else if($type == 'integer') return intval($value);
		else if($type == 'alphanumeric') return preg_replace('/[^a-zA-Z0-9]/', '', $value);
		else return $value;
	}
	
	function is_user_logged_in(){
		$user_id = $this->get_cookie('know__system__user_id');
		if($user_id) return true;
		else return false;
		//$this->confirm_session();
		//if(isset($_SESSION['know']) && isset($_SESSION['know']['user'])) return true;
		//else return false;
	}

	public function get_current_user_id(){
		if($this->is_user_logged_in()) return $this->get_cookie('know__system__user_id');
		else return false;
	}
	
	public function log_out(){
		//$this->confirm_session();
		//$_SESSION['know'] = array();
		$this->delete_cookie('know__system__user_id');
	}
	
	function allow_logout(){
		//DEPRICATED
		//Should be called dynamically via Communicator OR inlucded via shortcode on a log out page.  
		return;
		if(isset($_POST['know__post_action']) && $_POST['know__post_action'] == 'logout'){
			$this->log_out();
			$this->add_alert('You have been successfully logged out.', '', 'info');
		}
	}
	
	function can_user($permission){
		if($this->is_user_logged_in()){

			$original_user = $this->get_cookie('know__system__original_user_id');

			if($original_user) $original_user_id = $original_user;
			else $original_user_id = null;
			
			//if(isset($_SESSION['know']['original_user'])) $original_user = $_SESSION['know']['original_user'];
			//else $original_user = array();
			
			$result = $this->post($this->server . '/system/externalRequests/system/can_user', array(
				'user' => $this->get_current_user_id(),//$_SESSION['know']['user'],
				'original_user' => $original_user_id,
				'Permission' => $permission
			));
			
			if($result){
				$result = json_decode($result, true);
				if($result){
					if($result['Valid'] == true) return true;
				}
			}
		}
		
	   return false;
	}
	
	function allow_login(){
		//DEPRICATED
		//Should be called dynamically via Communicator
		return;
		if(isset($_POST['know__post_action']) && $_POST['know__post_action'] == 'login' && isset($_POST['know__username']) && isset($_POST['know__password'])){
			
			$this->log_out();
		
			$result = $this->post($this->server . '/system/externalRequests/system/authenticate', array(
				'username' => $_POST['know__username'],
				'password' => $_POST['know__password']
			));

			if($result){
				$result = json_decode($result, true);
				if($result){
					if($result['authenticated'] == true){
						$_SESSION['know']['user'] = $result['user'];
					} else {
						$this->add_alert('Your login credentials were incorrect.', 'Uh oh!', 'danger');
					}
				}

			} else {
				$this->add_alert("We're having some trouble accessing your CRM server.", 'Uh oh!', 'danger');
			}
			
		}
	}
	
	function target_session($id, $auth_code){
		if($id != "" && $auth_code != ""){
			$this->log_out();
			$result = $this->post($this->server . '/system/externalRequests/system/target_session', array(
				'Id' => $id,
				'Auth_Code' => $auth_code
			));
			
			if($result){
				$result = json_decode($result, true);
				if($result['Authenticated'] == true){
					$this->set_cookie('know__system__user_id', $result['Target_User']['Id']);//confirm it's not 'id'
					$this->set_cookie('know__system__original_user_id', $result['Original_User']['Id']);//confirm it's not 'id'
					//$_SESSION['know']['user'] = $result['Target_User']['Id'];
					//$_SESSION['know']['original_user'] = $result['Original_User']['Id'];//confirm it's not 'id'
				}
			}
		}
	}

	public function communicate($app_api_name, $method_api_name, $data = array()){



		//if(isset($_SESSION['know']) && isset($_SESSION['know']['user'])) $user = $_SESSION['know']['user'];
		//else $user = array();

		$result = $this->post($this->server . '/system/api/app_router', array(
			'Platform' => array(
				'API_Name' => $app_api_name,
				'Method' => $method_api_name,
				'API_Key' => $this->api_key,
				'API_Secret' => $this->api_secret
			),
			'User_Id' => $this->get_current_user_id(),
			'Data' => $data
		));

		return json_decode($result, true);
	}

	public function get_timestamp_format(){
		return 'Y-m-d H:i:s';
	}

	public function get_current_timestamp(){
		return date($this->get_timestamp_format());
	}

	/*public function allow_targeted_session(){
		if(isset($_POST['know__post_action']) && $_POST['know__post_action'] == 'target_session' && isset($_POST['Id']) && isset($_POST['Auth_Code'])){
			$this->log_out();
			
			$auth_code = preg_replace("/[^a-z0-9]+/i", "", $_POST['Auth_Code']);
			$result = $this->post($this->server . '/system/externalRequests/system/target_session', array(
				'Id' => intval($_POST['Id']),
				'Auth_Code' => $auth_code
			));
			
			if($result){
				$result = json_decode($result, true);
				if($result['Authenticated'] == true){
					$_SESSION['know']['user'] = $result['Target_User'];
					$_SESSION['know']['original_user'] = $result['Original_User'];
				} else {
					$this->add_alert("We're having some trouble logging you in. Please contact support.", 'Uh oh!', 'danger');
				}
			}
		}
	}*/
}

//we can use a wordpress action to define another function to call for things like alerts. We can default to plain text (maybe in a box or something), and then different pretty alerts can be defined on a per-theme basis

class know extends know_platform{};//Until further notice. "Know" class is depricated

class know_response{

    private $errors = array();
    private $alerts = array();
	private $logs = array();
    private $content = "";
	private $logout = false;
	private $deny_page_permission = false;

    public function __construct()
    {
        //nothing
    }
    
    public function add_error($content){
        array_push($this->errors, $content);
    }
    
    public function add_alert($content){
        array_push($this->alerts, $content);
    }
	
	public function add_log($content){
        array_push($this->logs, $content);
    }
    
    public function set_content($content){
        $this->content = $content;
    }
	
	public function get_content(){
		return $this->content;
	}
	
	public function get_errors(){
		return $this->errors;
	}
	
	public function import($response){
		if(gettype($response) == 'object') $content = $response->get_data();
		else if(gettype($response) == 'array') $content = $response;
		else return false;

		$this->errors = array_merge($this->errors, $content['errors']);
		$this->alerts = array_merge($this->alerts, $content['alerts']);
		$this->logs = array_merge($this->logs, $content['logs']);
		if($content['logout']===true){
			$this->logout=true;
		}
		if($content['deny_page_permission']===true){
			$this->logout=true;
		}
		return $response;
	}
    
    public function get_data(){
        $returnData = array();
        $returnData['content'] = $this->content;
        $returnData['errors'] = $this->errors;
        $returnData['alerts'] = $this->alerts;
		$returnData['logs'] = $this->logs;
		$returnData['logout'] = $this->logout;
		$returnData['deny_page_permission'] = $this->deny_page_permission;
        return $returnData;
    }
    
    public function get_json_data(){
        return json_encode($this->get_data());
    }
	
	public function trigger_logout(){
		$this->logout = true;
		$know = new know_platform();
		$know->log_out();
	}
	
	public function trigger_deny_page_permission(){
		$this->deny_page_permission = true;
	}
	
	/*Front end methods*/
	public function trigger_front_end_logout(){
		$this->trigger_logout();
		return $this->get_json_data();
	}
	
	public function trigger_front_end_deny_page_permission(){
		$this->trigger_deny_page_permission();
		return $this->get_json_data();
	}
    

}