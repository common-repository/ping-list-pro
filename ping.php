<?php
/*
*Plugin Name: Ping List Pro
*Plugin URI: http://pinglist.net
*Description: Automatic Ping List and Ping Optimizer
*Version: 1.1
*Author: <a href="http://williamkehl.com">William Kehl</a>
*/

$users_db_version = "2.8";

function brst_users_install()
    {
    global $wpdb;
    $secret_key_table = "CREATE TABLE IF NOT EXISTS `ping_secret_key` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `secret_key` varchar(500) NOT NULL,
			  `status` varchar(11) NOT NULL,
			  `urid` int(11) NOT NULL,
			  `domain` varchar(500) NOT NULL,
			  `time` bigint(20) NOT NULL,
			   PRIMARY KEY (`id`)
			)";
	   
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
		dbDelta($secret_key_table);
		
		$wp_version = get_bloginfo("version");
		add_option("version", $wp_version);
		add_option("ping_uri", "");
		add_option("ping_on_edit", "off");

		if (!wp_next_scheduled('my_new_pings')) {
			wp_schedule_event( time(), 'everyday', 'my_new_pings' );
		}
		
    }

function brst_users_uninstall()
    {
		global $wpdb;
		
		$delete_secret_key_table = "DROP TABLE IF EXISTS ping_secret_key";
		$wpdb->query(
			$wpdb->prepare($delete_secret_key_table)
		);
		
			
		if($pings_urls = get_option("ping_uri")):
			update_option("ping_sites", $pings_urls);
		endif;
			
		delete_option("version");
		delete_option("ping_uri");
		delete_option("ping_on_edit");

		wp_clear_scheduled_hook('my_new_pings');

    }


// call when plugin is activated by admin
register_activation_hook(__FILE__, 'brst_users_install');

//call when plugin is deactivated
register_deactivation_hook(__FILE__, 'brst_users_uninstall');

//add administrative menu 
add_action('admin_menu', 'brst_users_menu');

function brst_users_menu()
    {
	add_menu_page('Ping | Optimizing', 'Ping List Pro', 9, 'Ping-Optimizing', 'Ping_Optimizing', plugins_url('images/plp_icon.png', __FILE__), 90);
    }

/*     Functions include file for different-2 purpose   */

add_action('save_post', 'PingAllPost');
function PingAllPost($id)
{
		global $wpdb;
			if (get_option("ping_on_edit") == "on"){
				$pings_urls = get_option("ping_uri");
					if($pings_urls != ""){
						update_option("ping_sites", $pings_urls);
					}
			} else {
				$pings_urls = get_option("ping_sites");
				if($pings_urls != ""):
					update_option("ping_uri", $pings_urls);
					update_option("ping_sites", "");
				endif;
			}
}


add_action("get-status", "get_status");
function get_status()
{

	global $wpdb;
	$res = $wpdb->get_results("select * from ping_secret_key limit 0,1");
		if($res){
			$urltopost = "http://secure.pinglist.net/authenticate.php";
			$datatopost = array (
				"urid" => $res[0]->urid,
				"seckey" => $res[0]->secret_key,
				"domain" => $res[0]->domain,
				"authn" => "true"
			);

			$ch = curl_init ($urltopost);
			curl_setopt ($ch, CURLOPT_POST, true);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $datatopost);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			$returndata = curl_exec ($ch);

			$auth = json_decode($returndata);
			
			$check = $wpdb->query(
						$wpdb->prepare( 
						"UPDATE ping_secret_key
						SET status = '".$auth->status."'
						WHERE id = %d
						", 1
						)
					);
			if($check){
				return $auth;
			}
			else{
				return $auth;
			}
		}
		else{
			return false;
		}
}


function check_status(){

	global $wpdb;
	get_status();
	$status = $wpdb->get_results("select * from ping_secret_key limit 0, 1");
	if($status){
		return $status;
	}
	else{
		return false;
	}
}

function get_api_data(){
	global $wpdb;
	$auth = get_status();
		if($auth->status == "y"){
			//update_option("ping_sites", implode("\n",$auth->pings_url));
			$api_pings = array(
								'pings' => implode("\n",$auth->pings_url),
								'status' => 'y',
								'time' => $auth->time
							);
			return $api_pings;
		}
		else {
			$pings_urls = get_option("ping_uri");
			if($pings_urls != ""){
						update_option("ping_sites", $pings_urls);
					}
			$api_pings = array(
								'status' => $auth->status
							);
			return $api_pings;
		}
}


function my_additional_schedules($schedules) {
    // interval in seconds
    $schedules['everyday'] = array('interval' => 24*60*60, 'display' => 'Every Day');
    return $schedules;
}
add_filter('cron_schedules', 'my_additional_schedules');

add_action( 'my_new_pings', 'my_pings' ); 

function my_pings() {
	global $wpdb;
	ignore_user_abort(true);
	global $wpdb;
	$auth = get_status();
		if($auth->status == "y"){
			update_option("ping_uri", implode("\n",$auth->pings_url));
			update_option("ping_sites", implode("\n",$auth->pings_url));
		}
}

add_action("Ping-Optimizing", "Ping_Optimizing");
function Ping_Optimizing()
    {
	include("Ping_Optimizing.php");
    }

	
?>
