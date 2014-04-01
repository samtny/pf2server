<?php

include_once('pf-config.php');
include_once('pf-string.php');

//include_once('fsq-get.php');

/*$action = $_GET["action"];

if ($action == "fsqfreshen") {
	header("Content-type: text/plain");
	freshen_foursquare_ids();
} else if ($action == "venuecleannames") {
	header("Content-type: text/plain");
	freshen_venue_clean_names();
} else if ($action == "ipdbfreshen") {
	$srcFilePath = PF_PRIVATE_DATA_DIR . PATH_SEPARATOR . "/ipdb-20120625.txt";
	freshen_ipdb_data($srcFilePath);
} else if ($action == "gamedictfreshen") {
	header("Content-type: text/plain");
	freshen_gamedict();
}
*/

function freshen_gamedict() {
	
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	$sql = "select name, abbreviation, ipdb from game order by name, abbreviation ";
	
	$result = mysqli_query($link, $sql);
	
	if ($result) {
		
		$gameDict;
		
		while ($row = mysqli_fetch_assoc($result)) {
			
			$abbr = $row["abbreviation"];
			$name = $row["name"];
			$ipdb = $row["ipdb"];
			
			//echo "$name $abbr $ipdb" . "\n";
			
			if ($gameDict) {
				$gameDict .= '\g';
			}
			
			$gameDict .= $abbr . '\f' . $name . '\f' . $ipdb;
			
		}
		
		file_put_contents(PF_GAMEDICT_PATH, $gameDict);
		
	} else {
		trigger_error(mysqli_error());
	}
	
}

function freshen_neighborhoods($q, $t, $n, $l, $p, $o) {
	
	$result = get_result($q, $t, $n, $l, $p, $o);
	
	if ($result) {
		
		$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		$db_selected = mysql_select_db(DB_NAME);
		
		foreach ($result->venues as $venue) {
			
			$latLon = $venue->lat . "," . $venue->lon;
			
			usleep(200000);
			$rg = google_reverse_geocode_result($latLon);
			
			if ($rg->neighborhood) {
				$sql = "update venue set neighborhood = '" . mysql_real_escape_string($rg->neighborhood) . "', country = '" . mysql_real_escape_string($rg->country) . "' where venueid = " . mysql_real_escape_string($venue->id);
				$result = mysql_query($sql);
				echo $venue->name . " : " . $latLon . " : " . $rg->neighborhood . "\n";
			} else {
				$sql = "update venue set country = '" . mysql_real_escape_string($rg->country) . "' where venueid = " . mysql_real_escape_string($venue->id);
				$result = mysql_query($sql);
				echo $venue->name . " : " . $latLon . " : " . "FAIL\n";
			}
			
		}
		
	}
	
}

function freshen_ipdb_data($srcFilePath) {
	
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	$data = file_get_contents($srcFilePath);
	
	$trs = split("<tr>", $data);
	
	header("Content-type: text/plain");
	
	$mfids = array();
	
	$count = 0;
	foreach ($trs as $tr) {
		
		$tds = split("<td>", $tr);
		
		$tdcount = count($tds);
		
		if ($tdcount >= 5) {
		
			$re = preg_match("/gid=([0-9]+)&/i", $tds[1], $matches);
			
			if ($re > 0) {
				
				$gid = $matches[1];
				
				$mfname = rtrim($tds[2], "</td>");
				$date = rtrim($tds[3], "</td>");
				
				preg_match("/\s?([0-9]{4})/", $date, $matches);
				$year = $matches[1];
				
				echo "$gid - $mfname - $date - $year ";
				echo implode("", $tds);
				echo "\n";
				
				$mfid = $mfids[$mfname];
				
				if (!$mfid) {
					$sql = "select manufacturerid from manufacturer where name = '" . mysqli_real_escape_string($link, $mfname) . "'";
					$result = mysqli_query($link, $sql);
					if ($result) {
						$row = mysqli_fetch_assoc($result);
						$mfid = $row["manufacturerid"];
						mysqli_free_result($result);
					}
					if (!$mfid) {
						$sql = "insert into manufacturer (name) values ('" . mysqli_real_escape_string($link, $mfname) . "')";
						$result = mysqli_query($link, $sql);
						$mfid = mysqli_insert_id($link);
					}
					if ($mfid) {
						$mfids[$mfname] = $mfid;
					}
				}
				
				if ($mfid) {
					$sql = "update game set manufacturerid = " . mysqli_real_escape_string($link, $mfid);
					if ($year) {
						$sql .= ", year = '" . mysqli_real_escape_string($link, $year) . "' ";
					}
					$sql .= "where ipdb = '" . mysqli_real_escape_string($link, $gid) . "' ";
					$result = mysqli_query($link, $sql);
				}
				
			}
			
			
			
		}
		
		$count++;
		
	}
	
}

function freshen_foursquare_ids() {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select v.venueid, v.name, v.nameclean, X(v.coordinate) as lat, Y(v.coordinate) as lon, v.url, v.phone from venue v where v.approved = 1 and v.deleted = 0 and v.flag = '0' and v.coordinate is not null and v.foursquareid is null order by v.updatedate desc limit 20 ";
	
	$result = mysql_query($sql);
	
	if ($result && mysql_num_rows($result)) {
		
		while ($row = mysql_fetch_assoc($result)) {
			
			$id = $row["venueid"];
			$name = $row["name"];
			$nameClean = $row["nameclean"];
			$lat = $row["lat"];
			$lon = $row["lon"];
			$url = $row["url"];
			$phone = $row["phone"];
			
			usleep(100000);
			//$venueFsq = fsq_search_venue($nameClean, $lat . "," . $lon);
			$vResult = get_venue_match_result($nameClean, $lat . "," . $lon);
			
			if (count($vResult->venues) > 0) {
				
				$venueFsq = $vResult->venues[0];
				
				$sql = "update venue set foursquareid = '" . mysql_real_escape_string($venueFsq->fsqid) . "' where venueid = " . mysql_real_escape_string($id);
				mysql_query($sql);
				
				$logEntry = "updated ($id) - $name : '" . $venueFsq->name . "' : " . $venueFsq->fsqid . " (" . $venueFsq->dist . " miles)";
				
				file_put_contents(PF_LOG_FILE_FSQ, $logEntry . "\n", FILE_APPEND);
				
				echo $logEntry;
				
			} else {
			
				echo "fail";
				
			}
			
			echo "\n";
			
		}
		
	} else {
		trigger_error(mysql_error());
	}
	
}

function freshen_game_clean_names() {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select g.gameid, g.name from game g ";
	
	$result = mysql_query($sql);
	
	if ($result) {
		while ($row = mysql_fetch_assoc($result)) {
			$id = $row["gameid"];
			$name = $row["name"];
			$nameclean = clean_game_name_string($name);
			save_game_clean_name($id, $nameclean);
		}
	} else {
		trigger_error(mysql_error());
	}
	
}

function save_game_clean_name($gameid, $nameclean) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "update game set nameclean = '" . mysql_real_escape_string($nameclean) . "' where gameid = " . mysql_real_escape_string($gameid);
	
	$result = mysql_query($sql);
	
	if (!$result) {
		trigger_error(mysql_error());
	}
	
}

function freshen_venue_dm_names() {
	
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	$sql = "select v.venueid, v.name from venue v where deleted = 0";
	
	$result = mysqli_query($link, $sql);
	
	if ($result) {
		while ($row = mysqli_fetch_assoc($result)) {
			$id = $row['venueid'];
			$name = $row['name'];
			$namedm = dm_location_name_string($name);
			if ($namedm) {
				save_venue_dm_name($id, $namedm);
			}
		}
		mysqli_free_result($result);
	} else {
		trigger_error(mysqli_error());
	}
	
}

function save_venue_dm_name($venueid, $namedm) {
	
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	$sql = "update venue set namedm = '" . mysqli_real_escape_string($link, $namedm) . "' where venueid = $venueid limit 1 ";
	
	$result = mysqli_query($link, $sql);
	
	if (!$result) {
		trigger_error(mysqli_error());
	}
	
}

function freshen_venue_clean_names() {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select v.venueid, v.name from venue v where deleted = 0";
	
	$result = mysql_query($sql);
	
	if ($result) {
		while($row = mysql_fetch_assoc($result)) {
			$id = $row["venueid"];
			$name = $row["name"];
			$nameclean = clean_location_name_string($name);
			save_venue_clean_name($id, $nameclean);
		}
	} else {
		trigger_error(mysql_error());
	}
	
}

function save_venue_clean_name($venueid, $nameclean) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "update venue set nameclean = '" . mysql_real_escape_string($nameclean) . "' where venueid = $venueid";
	
	$result = mysql_query($sql);
	
	if (!($result == 1)) {
		trigger_error(mysql_error());
	}
	
}

?>