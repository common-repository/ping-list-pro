<div style="margin-top: 8px;">
<?php
global $wpdb;

if(isset($_POST['up_new_pings'])){
		$auth = get_api_data();
		if($auth['status'] == "y"){
			update_option("ping_sites", $auth['pings']);
			update_option("ping_uri", $auth['pings']);
			$wpdb->query(
						$wpdb->prepare( 
						"UPDATE ping_secret_key
						SET time = '".strtotime(date("Y-m-d H:i:s"))."'
						WHERE id = 1
						"
						)
					);
			$msg = "Ping list updated.";
		}
}
if(isset($_POST['ping_edit'])){
	
		if (isset($_POST['ping_on_edit']))
			update_option("ping_on_edit", $_POST['ping_on_edit']);
		else
			update_option("ping_on_edit", "off");
			
			$msg = "Settings saved.";
}

if ($_POST['authn'])
    {
			if($_POST['urid'])
				$urid = trim($_POST['urid']);
			if($_POST['sec_key'])
				$sec_key = trim($_POST['sec_key']);
			if($_POST['usr_domain'])
				$domain = trim($_POST['usr_domain']);

			$urltopost = "http://secure.pinglist.net/authenticate.php";
			$datatopost = array (
			"urid" => $urid,
			"seckey" => $sec_key,
			"domain" => $domain,
			"authn" => "true"
			);

			$ch = curl_init ($urltopost);
			curl_setopt ($ch, CURLOPT_POST, true);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $datatopost);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			$returndata = curl_exec ($ch);

			$auth = json_decode($returndata);
			
	if ($auth->status == "y"):
	$res = $wpdb->get_row("select * from ping_secret_key limit 0,1");
		if(!$res){	
			$check = $wpdb->insert('ping_secret_key', array(
				'secret_key' => $auth->secret_key,
				'status' => $auth->status,
				'urid' => $auth->urid,
				'domain' => $auth->domain,
				'time' => $auth->time
			), array(
				'%s',
				'%s',
				'%d',
				'%s',
				'%d'
			));
			
		}
		else{
			$check = $wpdb->query(
						$wpdb->prepare( 
						"UPDATE ping_secret_key
						SET status = ".$auth->status."
						WHERE ID = 1
						"
						)
					);
		}
		if(!$check){
				$msg_error = "Error: Unable to connect to server. Please try again.";
			}
			update_option("ping_on_edit","on");
			update_option("ping_sites", implode("\n",$auth->pings_url));
			update_option("ping_uri", implode("\n",$auth->pings_url));
	
    elseif ($auth->status == "n"):
        $msg_error = "Error: Your account is not active at this time.";
	elseif ($auth->status == "domain"):
		$msg_error = "Error: This domain is not registered.";
	elseif ($auth->status == "secret"):
		$msg_error = "Error: Invalid secret key.";
	elseif ($auth->status == "auth"):
		$msg_error = "Error: Authentication failed.";
    endif;
	
    }
if ($_POST['submit_uri'])
    {
    $values = $_POST['ping_uri'];
    update_option("ping_sites", $values);
	update_option("ping_uri", $values);
	
    if (isset($_POST['ping_on_edit']))
        update_option("ping_on_edit", $_POST['ping_on_edit']);
    else
        update_option("ping_on_edit", "off");
		$msg = "Setting Saved!";
    }
	
	$check_stat = check_status();

if ( $check_stat[0]->status != "y"):
	$pings_urls = get_option("ping_uri");
		if($pings_urls != ""){
						update_option("ping_sites", $pings_urls);
					}
	if ($check_stat[0]->status == "n"):
        $msg_error = "Error: Your account is not active at this time.";
	elseif ($check_stat[0]->status == "domain"):
		$msg_error = "Error: This domain is not registered.";
	elseif ($check_stat[0]->status == "secret"):
		$msg_error = "Error: Invalid secret key.";
	elseif ($check_stat[0]->status == "auth"):
		$msg_error = "Error: Authentication failed.";
    endif;
	
if($msg_error):
?>
<div style="border:1px solid #e6db55;background-color:#ffffe0;padding:10px;font-size:16px;"><?php echo $msg_error; ?></div>
<?php endif; ?>

<div style="border:1px solid #e6db55;background-color:#ffffe0;padding:10px;font-size:16px;"><b>Notice:</b> You are currently using the <b>Lite</b> version of Ping List Pro which has limited functionality. Please <a href="http://secure.pinglist.net" target="_blank">Click here to upgrade your account</a> and unlock the full version of the plugin.</div>

<div style="width:500px;text-align:center;"><a href="http://secure.pinglist.net" target="_blank"><img src="http://pinglist.net/images/logo.png" border="0"></a><br>Fully Automatic Ping List Plugin For Professional Bloggers</div>

<div id="profile-page" class="wrap">
<form method="post" action="?page=Ping-Optimizing">
<table class="form-table" style="background-color:#F1F1F1;border:1px solid #E3E3E3;width:500px;">
<tbody>
<tr>
<td colspan="3"><span style="font-weight:bold;font-size:16px;">Step 1: Verify Your Account</span> <br>(Don't have an account? <a href="http://secure.pinglist.net" target="_blank" style="font-weight:bold;">Click here to sign up</a>)</td>
</tr>
<tr>
    <th scope="row" style="text-align:right;width:120px;">
        <label for="urid" ><b>Pinglist.net User ID:</b></label>
    </th>
 <td style="text-align:right;width:20px;"><div style="padding-bottom:20px;padding-right:0px;">#uid-</div></td>
    <td style="text-align:left;padding-left:0px;">
    <input type="text" name="urid" id="urid" Placeholder="Enter your User ID..." />
        <br>
        <span class="description">Please enter your User ID</span>
    </td>
</tr>
<tr>
    <th scope="row" style="text-align:right;width:120px;">
        <label for="my-text-field"><b>Secret Key:</b></label>
    </th>
 <td></td>
    <td style="text-align:left;padding-left:0px;">
    <input type="text" name="sec_key" id="sec-key" Placeholder="Enter your secret key..." />
		<input type="hidden" name="usr_domain" id="usr_domain" value="<?php 
		$domain = get_option('siteurl'); //or home
		echo $domain; 
		?>" />
        <br>
        <span class="description">Please enter your secret key.</span>
    </td>
</tr>
<tr>
<td></td>
<td></td>
<td style="text-align:left;padding-left:0px;">
<input id="submit" class="button button-primary" type="submit" name="authn" value="Verify Account" /><br><br>
</td>
</tr>
</tbody>
</table>
<br><br>
<table class="form-table" style="background-color:#F1F1F1;border:1px solid #E3E3E3;width:500px;">
<tbody>
<tr>
<td colspan="3"><span style="font-weight:bold;font-size:16px;">Plugin Options</span></td>	
</tr>
<tr>
    <th scope="row" style="text-align:right;width:160px;">
        <label for="urid" ><b>Automatic Ping List:</b></label>
    </th>
    <td style="text-align:left;padding-left:0px;">
    <input type="checkbox" name="auto_update" disabled="disabled" />
        <br>
        <span class="description">Automatically downloads and updates a fresh ping list every day. This option is only available to Basic and Premium members. <a href="http://secure.pinglist.net" target="_blank">Learn more</a></span>
    </td>
</tr>
<tr>
    <th scope="row" style="text-align:right;width:120px;">
        <label for="urid" ><b>Send ping on edit:</b></label>
    </th>
    <td style="text-align:left;padding-left:0px;">
    <input type="checkbox" name="ping_on_edit" <?php
			if (get_option("ping_on_edit") == "on")
				{
		?>checked="checked" <?php
				}
		?>id="checkbox" />
        <br>
        <span class="description">This option enables/disables Wordpress from sending multiple pings while editing a post. <a href="http://secure.pinglist.net" target="_blank">Learn more</a></span>
    </td>
</tr>
</table>
	<p>
		
		<input id="ping_onedit" class="button button-primary" type="submit" name="ping_edit" value="Save Options" />
	</p>
</form>

</div>
<?php
else:
if($msg){
?>
<div style="border:1px solid #e6db55;background-color:#ffffe0;padding:10px;font-size:16px;"><?php echo $msg; ?></div>
<?php }
elseif($msg_error){
?>
<div style="border:1px solid #e6db55;background-color:#ffffe0;padding:10px;font-size:16px;"><?php echo $msg_error; ?></div>
<?php } ?>

<div style="width:500px;text-align:center;"><a href="http://secure.pinglist.net" target="_blank"><img src="http://pinglist.net/images/logo.png" border="0"></a><br>Fully Automatic Ping List Plugin For Professional Bloggers</div>
<br><div style="width:500px;">Subscription: <span style="color:#06ce63;font-weight:bold;">Active</span><div style="float:right;"><a href="http://secure.pinglist.net" target="_blank">Members' Login</a></div></div>
	
<table class="form-table" style="background-color:#F1F1F1;border:1px solid #E3E3E3;width:500px;">
<tbody>
<tr>
<td><b>Current Ping List</b></td><td style="text-align:right;"><form action="?page=Ping-Optimizing" method="post">
	<input type="submit" name="up_new_pings"  class="button button-primary" value="Download New Ping List"/>
</form></td>
</tr>
<tr>
<td colspan="2">
<form method="post" action="?page=Ping-Optimizing">
<textarea id="ping-uri" class="large-text code" rows="3" name="ping_uri" style="width:500px;"><?php
    if(get_option("ping_sites"))
	echo get_option("ping_sites");
	else
	echo get_option("ping_uri");
?></textarea>
</td>
</tr>
<tr>
    <th scope="row" style="text-align:right;width:160px;">
        <label for="urid" ><b>Automatic Ping List:</b></label>
    </th>
    <td style="text-align:left;padding-left:0px;">
    <input type="checkbox" name="auto_update" disabled="disabled" checked />
        <br>
        <span class="description">Automatically downloads and updates a fresh ping list every day. This option is only available to Basic and Premium members. <a href="http://secure.pinglist.net" target="_blank">Learn more</a></span>
    </td>
</tr>
<tr>
    <th scope="row" style="text-align:right;width:120px;">
        <label for="urid" ><b>Send ping on edit:</b></label>
    </th>
    <td style="text-align:left;padding-left:0px;">
    <input type="checkbox" name="ping_on_edit" <?php
			if (get_option("ping_on_edit") == "on")
				{
		?>checked="checked" <?php
				}
		?>id="checkbox" />
        <br>
        <span class="description">This option enables/disables Wordpress from sending multiple pings while editing a post. <a href="http://secure.pinglist.net" target="_blank">Learn more</a></span><br>
    </td>
</tr>
</tbody>
</table>
<br>
<input id="submit" class="button button-primary" type="submit" value="Save Changes" name="submit_uri">
<?php
endif;
?>
</div>
