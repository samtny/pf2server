<?php
session_start();
?>
<?php
include_once('pf-config.php');
include_once('pf-class.php');
include_once('pf-crypto.php');
include_once('pf-notify.php');
include_once('pf-apns.php');
include_once('pf-tournament.php');
include_once('pf-ifpa.php');
include_once('pf-batch.php');
?>
<?php
$session = $_COOKIE['session'];
if (!$session) {
	header('Location: pf-login.php');
	die();
}
/*
// mostly from here; http://www.devarticles.com/c/a/MySQL/PHP-MySQL-and-Authentication-101/3/
if (!$_SESSION['user'] || !$_SESSION['pass']) {
	header('Location: pf-login.php');
	die();
} else {
	$db = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("Couldn't connect to the database.");
	mysql_select_db(DB_NAME) or die("Couldn't select the database");
	$result = mysql_query("SELECT count(userid) FROM user WHERE password='$_SESSION[pass]' AND username='$_SESSION[user]'") or die("Couldn't query the user-database.");
	$num = mysql_result($result, 0);
	if (!$num) {
		header('Location: pf-login.php');
		die();
	} else {
		
	}
}
*/
?>
<?php if ($_POST): ?>
<?php

function approve_venue($venueid) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "update venue set approved = 1 where venueid = " . mysql_real_escape_string($venueid);
	
	$result = mysql_query($sql);
	
	if (!$result) {
		trigger_error(mysql_error());
	}
	
}

function add_venue_approved_user_notification_message($venueid) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select name, sourceid from venue where source = 'user' and venueid = " . mysql_real_escape_string($venueid) . "";
	
	$result = mysql_query($sql);
	
	if ($result) {
		$row = mysql_fetch_assoc($result);
		if ($row) {
			
			$venueName = $row['name'];
			$message = sprintf(PF_NEW_LOCATION_APPROVED_MSG_TEMPLATE, $venueName);
			
			$touserid = $row['sourceid'];
			
			$extra = "q=" . $venueid;
			
			if (strlen($touserid) > 0) {
			
				$n = new Notification();
				$n->message = $message;
				$n->touserid = $touserid;
				$n->extra = $extra;
				
				save_notifications(array($n));
				
			}
			
		}
	} else {
		trigger_error(mysql_error());
	}

}

?>
<?php
header ("Content-Type:text/xml");
$locxml = stripslashes($_POST["locxml"]);
$action = stripslashes($_POST["action"]);

if ($locxml) {
	
	if ($action == "approvevenue") {
	
		$req = new Request();
		$req->loadXML($locxml);
		
		if (count($req->venues) == 1) {
			
			$id = $req->venues[0]->id;
			
			approve_venue($id);
			add_venue_approved_user_notification_message($id);
			
			$url = PF_ENDPOINT_PF2 . "?q=$id";
			$ch = curl_init($url);
			curl_exec($ch);
			curl_close($ch);
			
		}
		
	} else if ($action == "updatevenue") {
		
		$hash = compute_hash($locxml);
		
		$fields = array(
			'doc'=>urlencode($locxml),
            'hash'=>urlencode($hash)
        );

		//url-ify the data for the POST
		foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		rtrim($fields_string,'&');
		
		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		$url = PF_ENDPOINT_PF2;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,count($fields));
		curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
		//curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

		//execute post
		curl_exec($ch);

		//close connection
		curl_close($ch);
		
	}
	
} else if ($action == "approvecomment") {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	
	$key = stripslashes($_POST["key"]);
	$sql = "update comment set approved = 1 where commentid = " . mysql_real_escape_string($key);
	
	$result = mysql_query($sql);
	
	if ($result == 1) {
		print "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		print "<pinfinderapp><status>failure</status></pinfinderapp>";
	}
	
} else if ($action == "deletecomment") {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	
	$key = stripslashes($_POST["key"]);
	$sql = "delete from comment where commentid = " . mysql_real_escape_string($key);
	
	$result = mysql_query($sql);
	
	if ($result == 1) {
		print "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		print "<pinfinderapp><status>failure</status></pinfinderapp>";
	}
	
} else if ($action == "deleteglobalnotification") {

	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	
	$key = stripslashes($_POST["key"]);
	$sql = "delete from notification where global = 1 and notificationid = " . mysql_real_escape_string($key);
	
	$result = mysql_query($sql);
	
	if ($result == 1) {
		print "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		print "<pinfinderapp><status>failure</status></pinfinderapp>";
	}
	
} else if ($action == "deletevenue") {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	
	$key = stripslashes($_POST["key"]);
	$sql = "update venue set deleted = 1 where venueid = " . mysql_real_escape_string($key);
	
	$result = mysql_query($sql);
	if ($result == 1) {
		print "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		trigger_error(mysql_error());
		print "<pinfinderapp><status>failure</status></pinfinderapp>";
	}
	
} else if ($action == "approveaddresschange") {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	
	$key = stripslashes($_POST["key"]);
	$sql = "update venue set flag = '0' where venueid = " . mysql_real_escape_string($key);
	
	$result = mysql_query($sql);
	if ($result == 1) {
		print "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		trigger_error(mysql_error());
		print "<pinfinderapp><status>failure</status></pinfinderapp>";
	}
	
} else if ($action == "sendnotifications") {
	
	$notifications = get_pending_notifications();
	
	if ($notifications && count($notifications) > 0) {
		send_apns_notifications($notifications);
		mark_notifications_delivered($notifications);
	}
	
	print "<pinfinderapp><status>success</status></pinfinderapp>";
	
} else if ($action == "saveglobalnotification") {
	
	$message = stripslashes($_POST["message"]);
	$extra = stripslashes($_POST["extra"]);
	
	if ($message) {
		
		$n = new Notification();
		$n->message = $message;
		$n->extra = $extra;
		$n->global = TRUE;
		
		save_notifications(array($n));
		
		print "<pinfinderapp><status>success</status></pinfinderapp>";
		
	} else {
		
		print "<pinfinderapp><status>error</status></pinfinderapp>";
		
	}
	
} else if ($action == "associatetournamentvenue") {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	
	$tournamentid = $_POST["tournamentid"];
	$venueid = $_POST["venueid"];
	
	$sql = "update tournament set venueid = " . mysql_real_escape_string($venueid) . " where tournamentid = " . mysql_real_escape_string($tournamentid);
	
	$result = mysql_query($sql);
	
	if ($result == 1) {
		echo "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		echo "<pinfinderapp><status>error</status></pinfinderapp>";
	}
	
} else if ($action == "omittournament") {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	
	$tournamentid = $_POST["tournamentid"];
	
	$sql = "update tournament set omit = 1 where tournamentid = " . mysql_real_escape_string($tournamentid);
	
	$result = mysql_query($sql);
	
	if ($result == 1) {
		echo "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		echo "<pinfinderapp><status>error</status></pinfinderapp>";
	}
	
}

?>
<?php elseif ($_GET['q']) : ?>
<?php 
header ("Content-Type:application/json");

class MgmtResponse {
	public $status = "none";
	public $notifications = array();
	public $tournaments = array();
}

$q = $_GET['q'];

if ($q == "globalnotifications") {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select notificationid, message, extra from notification where global = 1 and delivered = 0 order by createdate";
	
	$result = mysql_query($sql);
	
	$response = new MgmtResponse();
	
	if ($result) {
		
		while ($row = mysql_fetch_assoc($result)) {
			
			$n = new Notification();
			$n->id = $row['notificationid'];
			$n->message = $row['message'];
			$n->extra = $row['extra'];
			
			$response->notifications[] = $n;
			
		}
		
		$response->status = "success";
		
	} else {
		$response->status = "sqlerror";
	}
	
	echo json_encode($response);
	
} elseif ($q == "upcomingtournaments") {
	
	$response = new MgmtResponse();
	$response->tournaments = get_untagged_tournaments();
	$response->status = "success";
	echo json_encode($response);
	
} else if ($q == "refreshifpatournaments") {
	
	$tournaments = get_ifpa_tournaments_from_feed();
	save_new_tournaments($tournaments);
	
	$response = new MgmtResponse();
	$response->status = "success";
	echo json_encode($response);
	
} else if ($q == "refreshgamedict") {
	
	freshen_gamedict();
	$response = new MgmtResponse();
	$response->status = "success";
	echo json_encode($response);
	
} else if ($q == "stats") {
	
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	$stats = array();
	
	$sql = "select count(*) as total from venue where deleted = 0 and approved = 1 and coordinate is not null ";
	$result = mysqli_query($link, $sql);
	if ($result) {
		if ($row = mysqli_fetch_assoc($result)) {
			$stats['venues'] = $row['total'];
		}
	}
	
	$sql = "select count(*) as total from machine ";
	$result = mysqli_query($link, $sql);
	if ($result) {
		if ($row = mysqli_fetch_assoc($result)) {
			$stats['games'] = $row['total'];
		}
	}
	
	$sql = "select count(*) as total from user ";
	$result = mysqli_query($link, $sql);
	if ($result) {
		if ($row = mysqli_fetch_assoc($result)) {
			$stats['users'] = $row['total'];
		}
	}
	
	$sql = "select count(*) as total from venue where datediff(NOW(), updatedate) between 0 and 30 and deleted = 0 and approved = 1 and coordinate is not null ";
	$result = mysqli_query($link, $sql);
	if ($result) {
		if ($row = mysqli_fetch_assoc($result)) {
			$stats['u30day'] = $row['total'];
		}
	}
	
	$sql = "select count(*) as total from venue where datediff(NOW(), createdate) between 0 and 30 and deleted = 0 and approved = 1 and coordinate is not null ";
	$result = mysqli_query($link, $sql);
	if ($result) {
		if ($row = mysqli_fetch_assoc($result)) {
			$stats['n30day'] = $row['total'];
		}
	}
	
	$stats['status'] = 'success';
	
	echo json_encode($stats);
	
}

?>
<?php else: ?>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Pinfinder Management Console</title>
	
	<style type="text/css">
	body {
		background-color: #dedede;
	}
	
	.header h2 {
		margin-bottom: 0px;
	}
	
	#container {
		width: 1100px;
		margin-left: auto;
		margin-right: auto;
		border: 1px solid black;
	}
	
	.unapproved table {
		width: 900px;
		table-layout: fixed;
	}
		
		.unapproved .update {
			width: 300px;
		}
		
		.unapproved .name, .unapproved .street, .unapproved .url, .unapproved .latlon {
			width: 150px;
			overflow: hidden;
		}
		
		.unapproved .city, .unapproved .state, .unapproved .phone {
			width: 100px;
			overflow: hidden;
		}
		
		.unapproved .zip, .unapproved .games {
			width: 50px;
			overflow: hidden;
		}
				
		.unapproved table td {
			overflow: hidden;
		}
	
	.addresschanges table {
		width: 900px;
		table-layout: fixed;
	}
		
		.addresschanges .update {
			width: 300px;
		}
		
		.addresschanges .name, .addresschanges .street, .addresschanges .url, .addresschanges .latlon {
			width: 150px;
			overflow: hidden;
		}
		
		.addresschanges .city, .addresschanges .state, .addresschanges .phone {
			width: 100px;
			overflow: hidden;
		}
		
		.addresschanges .zip, .addresschanges .games {
			width: 50px;
			overflow: hidden;
		}
				
		.addresschanges table td {
			overflow: hidden;
		}
	
	.newcomments table {
		width: 700px;
		border-collapse: collapse;
		table-layout: fixed;
	}
		
		.newcomments td {
			border-bottom: 1px solid #444;
		}
	
		.newcomments .comment {
			width: 300px;
		}
		
		.newcomments .venue {
			width: 200px;
		}
		
		.newcomments col {
			width: 200px;
		}
		
		.newcomments table label {
			width: 100%;
		}
	
	.recent ul, .flagged ul, .globalnotify ul, .tournaments ul, .stats ul {
		list-style: none;
		padding-left: 0px;
	}
	
	.stats ul {
		margin: 0px 0px 0px 0px;
	}
	
		.stats li {
			display: inline;
			list-style-type: none;
			padding-right: 20px;
		}
	
	.bold {
		font-weight: 600;
	}
	
	</style>
</head>
<body>
	
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=AIzaSyCMWL8VtaTA5ORZro3vPvwfZxWel1sgwPg&amp;sensor=false"></script>
	<script type="text/javascript" src="./js/pf-class.js"></script>
	<script type="text/javascript" src="./js/pf-mgmt.js"></script>
	
	<div class="container">
		<div class="header">
			<h2>Pinfinder Managment</h2>
			<div class="stats">
				<ul>

				</ul>
			</div>
		</div>
		<div class="unapproved">
			<h3>New Venues:</h3>
			<table>
				<colgroup>
					<col class="name" />
					<col class="street" />
					<col class="city" />
					<col class="state" />
					<col class="zip" />
					<col class="phone" />
					<col class="url" />
					<col class="latlon" />
					<col class="games" />
					<col class="update" />
				</colgroup>
				<thead>
					<tr>
						<th>Name</th>
						<th>Street</th>
						<th>City</th>
						<th>State</th>
						<th>Zip</th>
						<th>Phone</th>
						<th>URL</th>
						<th>LatLon</th>
						<th>Games</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					
				</tbody>
			</table>
		</div>
		<div class="addresschanges">
			<h3>Address Changed:</h3>
			<table>
				<colgroup>
					<col class="name" />
					<col class="street" />
					<col class="city" />
					<col class="state" />
					<col class="zip" />
					<col class="phone" />
					<col class="url" />
					<col class="latlon" />
					<col class="games" />
					<col class="update" />
				</colgroup>
				<thead>
					<tr>
						<th>Name</th>
						<th>Street</th>
						<th>City</th>
						<th>State</th>
						<th>Zip</th>
						<th>Phone</th>
						<th>URL</th>
						<th>LatLon</th>
						<th>Games</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					
				</tbody>
			</table>
		</div>
		<div class="newcomments">
			<h3>New Comments:</h3>
			<table>
				<colgroup>
					<col class="comment" />
					<col class="venue" />
					<col class="update" />
				</colgroup>
				<thead>
					<tr>
						<th>Comment</th>
						<th>Venue</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					
				</tbody>
			</table>
		</div>
		<div class="notifications">
			<h3>Pending Notifications</h3>
			<input type="button" value="Send Notifications" onclick="sendNotifications(this)" /> 
		</div>
		<div class="globalnotify">
			<h3>Global Notifications</h3>
			<ul>
			</ul>
			Message: <input type="text" class="globalmessage" />
			Extra: <input type="text" class="globalextra" />
			<input type="button" value="Add" onclick="addGlobalNotification(this)" />
		</div>
		<div class="tournaments">
			<h3>Upcoming Tournaments</h3>
			<input type="button" value="Refresh IFPA Tournaments" onclick="refreshIFPATournaments(this)" />
			<label>Associate Venue:</label><input type="text" class="tourneyvenue"></input>
			<ul>
			</ul>
		</div>
		<div class="maintenance">
			<h3>Maintenance</h3>
			<input type="button" value="Refresh gamedict.txt" onclick="refreshGamedict(this)" />
		</div>
		<div class="flagged">
			<h3>Recently Flagged</h3>
			<ul>
			</ul>
		</div>
		<div class="recent">
			<h3>Recent Activity</h3>
			<ul>
			</ul>
		</div>
	</div>
	
</body>
<?php endif; ?>



