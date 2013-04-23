<?php

include_once('pf-config.php');

define('TOURNAMENT_DEFAULT_LIMIT', 17);

function get_untagged_tournaments() {
	return get_untagged_tournaments_limit(TOURNAMENT_DEFAULT_LIMIT);
}

function get_untagged_tournaments_limit($limit) {
	
	$tournaments = array();
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select t.tournamentid, t.name as tournamentName, t.datefrom, t.datethru, t.venueid, t.ifpaid, ";
	$sql .= "prior.venueid as priorvenueid ";
	$sql .= "from tournament t ";
	
	$sql .= "left outer join (";
	$sql .= "select ifpaid, max(datefrom) as datefrom from tournament where venueid is not null group by ifpaid";
	$sql .= ") prior_max on t.ifpaid = prior_max.ifpaid ";
	$sql .= "left outer join tournament prior on prior_max.ifpaid = prior.ifpaid and prior_max.datefrom = prior.datefrom ";
	
	$sql .= "where t.venueid is null and t.omit = 0 ";
	$sql .= "order by t.datefrom, t.name ";
	$sql .= "limit $limit ";
	
	$result = mysql_query($sql);
	
	if ($result) {
		
		while ($row = mysql_fetch_assoc($result)) {
			
			$id = $row['tournamentid'];
			$name = $row['tournamentName'];
			$from = $row['datefrom'];
			$thru = $row['datethru'];
			$venueid = $row['venueid'];
			$priorvenueid = $row['priorvenueid'];
			$ifpaid = $row['ifpaid'];
			
			$t = new Tournament();
			$t->id = $id;
			$t->name = $name;
			$t->dateFrom = $from;
			$t->dateThru = $thru;
			$t->venueId = $venueid;
			$t->priorVenueId = $priorvenueid;
			$t->ifpaId = $ifpaid;
			
			$tournaments[] = $t;
			
		}
		
	} else {
		trigger_error(mysql_error());
		// TODO: log it;
	}
	
	return $tournaments;
	
}

function get_upcoming_tournaments() {
	
	$tournaments = array();
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select t.tournamentid, t.name as tournamentName, t.datefrom, t.datethru, t.venueid, t.ifpaid ";
	$sql .= "from tournament t ";
	$sql .= "where (DATEDIFF(t.datefrom, NOW()) between 0 and 14 or DATEDIFF(t.datethru, NOW()) between 0 and 14) ";
	$sql .= "order by t.datefrom, t.name ";
	
	$result = mysql_query($sql);
	
	if ($result) {
		
		while ($row = mysql_fetch_assoc($result)) {
			
			$id = $row['tournamentid'];
			$name = $row['tournamentName'];
			$from = $row['datefrom'];
			$thru = $row['datethru'];
			$venueid = $row['venueid'];
			$ifpaid = $row['ifpaid'];
			
			$t = new Tournament();
			$t->id = $id;
			$t->name = $name;
			$t->dateFrom = $from;
			$t->dateThru = $thru;
			$t->venueId = $venueid;
			$t->ifpaId = $ifpaid;
			
			$tournaments[] = $t;
			
		}
		
	} else {
		trigger_error(mysql_error());
		// TODO: log it;
	}
	
	return $tournaments;
	
}

function save_new_tournaments($tournaments) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$new = array();
	
	foreach ($tournaments as $t) {
		if ($t->name && $t->dateFrom) {
			$sql = "select tournamentid from tournament where name = '" . mysql_real_escape_string($t->name) . "' and datefrom = '" . mysql_real_escape_string($t->dateFrom) . "'";
			$result = mysql_query($sql);
			if ($result) {
				if (!mysql_num_rows($result)) {
					$new[] = $t;
				}
				mysql_free_result($result);
			} else {
				trigger_error(mysql_error());
				// TODO: log it...
			}
		}
	}
	
	save_tournaments($new);
	
}

function save_tournaments($tournaments) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	foreach ($tournaments as $t) {
		
		$name = $t->name;
		$from = $t->dateFrom;
		$thru = $t->dateThru;
		$venueId = $t->venueId;
		$ifpaId = $t->ifpaId;
		
		if ($name) {
			
			$sql = "insert into tournament (name, datefrom, datethru, venueid, ifpaid) ";
			$sql .= "values (";
			$sql .= "'" . mysql_real_escape_string($name) . "'";
			$sql .= ", '" . mysql_real_escape_string($from) . "'";
			$sql .= ", '" . mysql_real_escape_string($thru) . "'";
			$sql .= ", " . mysql_real_escape_string($venueId ? $venueId : "null") . "";
			$sql .= ", " . mysql_real_escape_string($ifpaId ? $ifpaId : "null") . "";
			$sql .= ")";
			
			$result = mysql_query($sql);
			
			if (!$result) {
				trigger_error(mysql_error());
				// TODO: log it
			}
			
		}
		
	}
	
}

?>