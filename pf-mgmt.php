<?php
session_start();
$lifetime = 365*24*60*60;
setcookie(session_name(),session_id(),time()+$lifetime);
?>
<?php
include('pf-config.php');
include('pf-class.php');
include('pf-crypto.php');
?>
<?php
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
	}
}
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
	
	$key = stripslashes($_POST["key"]);
	$sql = "update comment set approved = 1 where commentid = " . mysql_real_escape_string($key);
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	$result = mysql_query($sql);
	
	if ($result == 1) {
		print "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		print "<pinfinderapp><status>failure</status></pinfinderapp>";
	}
	
} else if ($action == "deletecomment") {
	
	$key = stripslashes($_POST["key"]);
	$sql = "delete from comment where commentid = " . mysql_real_escape_string($key);
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	$result = mysql_query($sql);
	
	if ($result == 1) {
		print "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		print "<pinfinderapp><status>failure</status></pinfinderapp>";
	}
	
} else if ($action == "deletevenue") {
	
	$key = stripslashes($_POST["key"]);
	$sql = "update venue set deleted = 1 where venueid = " . mysql_real_escape_string($key);
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	$result = mysql_query($sql);
	if ($result == 1) {
		print "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		trigger_error(mysql_error());
		print "<pinfinderapp><status>failure</status></pinfinderapp>";
	}
	
} else if ($action == "approveaddresschange") {
	
	$key = stripslashes($_POST["key"]);
	$sql = "update venue set flag = '0' where venueid = " . mysql_real_escape_string($key);
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);	
	$result = mysql_query($sql);
	if ($result == 1) {
		print "<pinfinderapp><status>success</status></pinfinderapp>";
	} else {
		trigger_error(mysql_error());
		print "<pinfinderapp><status>failure</status></pinfinderapp>";
	}
	
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
		
	.recent ul {
		list-style: none;
		padding-left: 0px;
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
		<h2>Pinfinder Managment</h2>
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
		<div class="recent">
			<h3>Recent Activity</h3>
			<ul>
			</ul>
		</div>
	</div>
	
</body>
<?php endif; ?>



