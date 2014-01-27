<?php

include_once('pf-config.php');
include_once('pf-class.php');
include_once('pf-geocode.php');
include_once('pf-string.php');

function get_result($q, $t, $n = NULL, $l = PF_VENUES_LIMIT_DEFAULT, $p = NULL, $o = 'date') {
	if ($t == "gamelist") {
		return get_gamelist_result($q, $l);
	} else if ($t == "stats") {
		return get_stats_result();
	} else {
		return get_venue_result($q, $t, $n, $l, $p, $o);
	}
}

function get_venue_result($q, $t, $n, $l, $p, $o) {
	
	$result = new Result();
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$gamedicten = array();
	$venues = array();
	$games = array();
	$comments = array();
	
	$minimal = preg_match('/minimal/i', $p) ? true : false;
	$extended = preg_match('/extended/i', $p) ? true : false;
	
	$nlat = null;
	$nlon = null;
	$swlat = null;
	$swlon = null;
	$nelat = null;
	$nelon = null;
	if ($n) {
		
		preg_match('/^([0-9.-]+),([0-9.-]+)$/', $n, $latlon);
		if ($latlon) {
			$nlat = $latlon[1];
			$nlon = $latlon[2];
		} else {
			//$lonlatstring = geocode($n);
			//$coordinatesSplit = split(",", $lonlatstring);
			$gresult = get_geocode_result($n);
			//$nlat = $coordinatesSplit[1];
			//$nlon = $coordinatesSplit[0];
			$nlat = $gresult->coordinate->lat;
			$nlon = $gresult->coordinate->lon;
			$swlat = $gresult->southwest->lat;
			$swlon = $gresult->southwest->lon;
			$nelat = $gresult->northeast->lat;
			$nelon = $gresult->northeast->lon;
		}
		
	}
	
	if (!$l) {
		$l = PF_VENUES_LIMIT_DEFAULT;
	}
	if ($l == PF_VENUES_LIMIT_DEFAULT && $t == 'game') {
		$l = PF_VENUES_LIMIT_LARGE;
	}
	
	$venueSql =  "select v.*, ";
	$venueSql .= 	"m.machineid, m.condition, m.price, ";
	$venueSql .= 	"g.gameid, g.abbreviation, g.name as gamename, g.ipdb, g.new, g.rare, g.year as gameyear, mf.shortname as manufacturer, ";
	$venueSql .=	"cs.total as commentcount, ";
	$venueSql .=	"im.total as imagecount, ";
	$venueSql .=	"l.total as leaguecount, ";
	$venueSql .= 	"t.datefrom as tournamentdate ";
	$venueSql .= "from ( ";
	
	$venueSql .= "select v.venueid, v.name as venuename, v.nameclean as venuenameclean, v.namedm as venuenamedm, v.street, v.city, v.state, v.zipcode, v.neighborhood, v.country, v.phone, X(v.coordinate) as latitude, Y(v.coordinate) as longitude, v.url, v.foursquareid, v.flag, v.updatedate as venueupdated, v.createdate as venuecreated, ";
	if ($nlat && $nlon && $nlat != null && $nlon != null) {
		$venueSql .= "sqrt(($nlat - X(v.coordinate)) * ($nlat - X(v.coordinate)) + ($nlon - Y(v.coordinate)) * ($nlon - Y(v.coordinate))) as distance ";
	} else {
		$venueSql .= "null as distance ";
	}	
	$venueSql .= "from venue v ";
	if ($p == "nofilter" || $t == "mgmt") {
		$venueSql .= "where 1 = 1 ";
	} else {
		$venueSql .= "where v.approved = 1 and v.deleted = 0 and v.flag in ('0', 'A') and v.coordinate is not null ";
	}
	if ($swlat && $swlon && $nelat && $nelon && $swlat != null && $swlon != null && $nelat != null && $nelon != null) {
		if ($swlon <= $nelon) {
			$venueSql .= "and X(v.coordinate) between $swlat and $nelat and Y(v.coordinate) between $swlon and $nelon ";
		} else {
			$venueSql .= "and ( Y(v.coordinate) between $swlon and 180 or Y(v.coordinate) between -180 and $nelon ) and X(v.coordinate) between $swlat and $nelat ";
		}
	}
	if ($q) {
		if (!$t || $t == "key") {
			if (preg_match('/^[0-9]+$/', $q)) {
				$venueSql .= "and v.venueid = " . mysql_real_escape_string($q) . " ";
			} else {
				$venueSql .= "and 1 = 0 ";
			}
		} else if ($t == "venue") {
			$qclean = clean_location_name_string($q);
			$qdm = dm_location_name_string($q);
			$venueSql .= "and (v.name like '%" . mysql_real_escape_string($q) . "%' " . 
								"or v.nameclean like '%" . mysql_real_escape_string($qclean) . "%' " . 
								"or v.namedm like '" . mysql_real_escape_string($qdm) . "%') ";		
		} else if ($t == "game") {
			$qclean = clean_game_name_string($q);
			$venueSql .= "and v.venueid in (select v.venueid from venue v inner join machine m on v.venueid = m.venueid inner join game g on m.gameid = g.gameid where (g.abbreviation = '" . mysql_real_escape_string($q) . "' or g.name like '%" . mysql_real_escape_string($q) . "%' or g.nameclean like '%" . mysql_real_escape_string($qclean) . "%')) ";
		} else if ($t == "fsqid") {
			$venueSql .= "and v.venueid in (select v.venueid from venue v where v.foursquareid = '" . mysql_real_escape_string($q) . "') ";
		} else if ($t == "city") {
			$venueSql .= "and v.venueid in (select v.venueid from venue v where v.city = '" . mysql_real_escape_string($q) . "') ";
		} else if ($t == "special") {
			switch ($q) {
				case "recent":
					$venueSql .= "and v.venueid in (select v.venueid from venue v where datediff(curdate(), v.updatedate) <= 59) ";
					break;
				case "newgame":
					$venueSql .= "and v.venueid in (select m.venueid from machine m inner join game g on m.gameid = g.gameid where g.new = 1) ";
					break;
				case "raregame":
					$venueSql .= "and v.venueid in (select m.venueid from machine m inner join game g on m.gameid = g.gameid where g.rare = 1) ";
					break;
				case "minimecca":
					$venueSql .= "and v.venueid in (select m.venueid from machine m group by m.venueid having count(*) between 5 and 10) ";
					break;
				case "mecca":
					$venueSql .= "and v.venueid in (select m.venueid from (select m.venueid, count(*) as total from machine m group by m.venueid) m where m.total > 10) ";
					break;
				case "museum":
					$venueSql .= "and (v.name like '%museum%' or v.nameclean like '%museum%') ";
					break;
				case "upcomingtournaments":
					$venueSql .= "and v.venueid in (select t.venueid from tournament t where (DATEDIFF(NOW(), t.datefrom) between -10 and 0 or DATEDIFF(NOW(), t.datethru) between -10 and 0)) ";
					break;
				default:
					$venueSql .= "and 1 = 0 ";
					break;
			}
		} else if ($t == "mgmt") {
			switch ($q) {
				case "unapprovedcomment":
					$venueSql .= "and v.deleted = 0 and v.flag = '0' and v.venueid in (select c.venueid from comment c where c.approved = 0) ";
					break;
				case "unapproved":
					$venueSql .= "and v.approved = 0 and v.deleted = 0 and v.flag = '0' ";
					break;
				case "addresschanged":
					$venueSql .= "and v.approved = 1 and v.deleted = 0 and v.flag = 'A' ";
					break;
				case "flagged":
					$venueSql .= "and v.approved = 1 and v.deleted = 0 and v.flag not in ('0') ";
				default:
					break;
			}
		}	
	}
	
	$venueOrder = "distance, v.venueid";
	if ($o) {
		switch ($o) {
			case "name":
				$venueOrder = "venuename, v.venueid";
				break;
      case "date":
			default:
				$venueOrder = "venueupdated desc, v.venueid";
				break;
		}
	} else {
		// attempt to infer order from query;
		if ($t == "special" && $q == "recent" || !$q && !$t && !$n) {
			$venueOrder = "venueupdated desc, v.venueid";
		} else if ($t == "mgmt" && $q == "unapproved") {
			$venueOrder = "venuecreated desc, v.venueid";
		} else if ($t == "mgmt" && $q == "unapprovedcomment") {
			$venueOrder = "venueupdated desc, v.venueid";
		} else if ($t == "mgmt" && $q == "flagged") {
			$venueOrder = "venueupdated desc, v.venueid";
		} else if ($t == "venue" && $q) {
			$qclean = clean_location_name_string($q);
			$qdm = dm_location_name_string($q);
			$venueOrder = "case when venuenameclean = '" . mysql_real_escape_string($qclean) . "' then 10 " .
								"when venuenameclean like '" . mysql_real_escape_string($qclean) . "%' then 15 " .
								"when venuenamedm like '" . mysql_real_escape_string($qdm) . "%' then 20 " .
								"when venuenameclean like '%" . mysql_real_escape_string($qclean) . "%' then 30 " .
								"else 100 end, distance, v.venueid";
		}
	}
	$venueSql .= "order by $venueOrder ";
	
	$venueSql .= "limit $l ";
	$venueSql .= ") v ";
	$venueSql .= 	"left outer join machine m on v.venueid = m.venueid ";
	$venueSql .=	"left outer join game g on m.gameid = g.gameid ";
	$venueSql .=	"left outer join manufacturer mf on g.manufacturerid = mf.manufacturerid ";
	$venueSql .=	"left outer join (select venueid, count(*) as total from comment group by venueid) cs on v.venueid = cs.venueid ";
	$venueSql .= 	"left outer join (select venueid, count(*) as total from image group by venueid) im on v.venueid = im.venueid ";
	$venueSql .= 	"left outer join (select venueid, count(*) as total from leaguevenue group by venueid) l on v.venueid = l.venueid ";
	$venueSql .= 	"left outer join (select venueid, min(datefrom) as datefrom from tournament where venueid is not null and (DATEDIFF(NOW(), datefrom) <= 0 or DATEDIFF(NOW(), datethru) <= 0) group by venueid) t on v.venueid = t.venueid ";
	
	$finalOrder;
	
	if ($t == "special" && $q == "upcomingtournaments") {
		$finalOrder = "t.datefrom, v.venuename, v.venueid";
	} else {
		$finalOrder = $venueOrder;
	}
	
	$venueSql .= "order by $finalOrder, g.name, g.gameid ";
	
	$vresult = mysql_query($venueSql);
	if ($vresult) {
		$venue = null;
		while ($vrow = mysql_fetch_assoc($vresult)) {
			if ($venue != null && $venue->id != (int)$vrow["venueid"]) {
				$venues[] = $venue;
				$venue = null;
			}
			if ($venue == null) {
				
				$venue = new Venue();
				$venue->id = (int)$vrow["venueid"];
				$venue->name = $vrow["venuename"];
				$venue->lat = (float)$vrow["latitude"];
				$venue->lon = (float)$vrow["longitude"];
				
				if ($minimal == false) {
					
					$venue->street = $vrow["street"];
					$venue->city = $vrow["city"];
					$venue->state = $vrow["state"];
					$venue->zipcode = $vrow["zipcode"];
					if ($extended == true) {
						$venue->neighborhood = $vrow["neighborhood"];
					}
					$venue->country = $vrow["country"];
					$venue->phone = $vrow["phone"];
					$venue->url = $vrow["url"];
					$venue->fsqid = $vrow["foursquareid"];
					
					if ($venue->lat && $venue->lon && $nlat && $nlon) {
					
						$R = 3963.1676;
						$dlat = deg2rad($nlat - $venue->lat);
						$dlon = deg2rad($nlon - $venue->lon);
						$lat1 = deg2rad($venue->lat);
						$lat2 = deg2rad($nlat);
						$a = sin($dlat/2) * sin($dlat/2) + sin($dlon/2) * sin($dlon/2) * cos($lat1) * cos($lat2);
						$c = 2 * atan2(sqrt($a), sqrt(1-$a));
						$d = $R * $c;
						
						$venue->dist = sprintf("%01.2f", $d);

					}
					$venue->updated = date('c', strtotime($vrow["venueupdated"]));
					
					$venue->flag = $vrow["flag"];
					
					if ((int)$vrow["commentcount"] > 0) {
						$commentsql =  "select c.venueid, c.commentid, c.createdate as commentdate, c.text as commenttext from comment c ";
						if ($t == "mgmt" && $q == "unapprovedcomment") {
							$commentsql .= "where c.approved = 0 and c.venueid = " . $vrow["venueid"] . " order by c.createdate desc";
						} else {
							$commentsql .= "where c.approved = 1 and c.venueid = " . $vrow["venueid"] . " order by c.createdate desc";
						}
						$cresult = mysql_query($commentsql);
						if ($cresult) {
							while ($crow = mysql_fetch_assoc($cresult)) {
								$comment = new Comment();
								$comment->id = (int)$crow["commentid"];
								$comment->date = date('c', strtotime($crow["commentdate"]));
								$comment->text = $crow["commenttext"];
								$venue->addComment($comment);
							}
							mysql_free_result($cresult);
						}
					}
					
					if ((int)$vrow["tournamentdate"] > 0) {
						$tournamentsql = "select t.tournamentid, t.name, t.datefrom, t.datethru, t.ifpaid from tournament t where t.venueid = " . $vrow["venueid"] . " and (DATEDIFF(NOW(), t.datefrom) <= 0 or DATEDIFF(NOW(), t.datethru) <= 0) order by t.datefrom ";
						$tresult = mysql_query($tournamentsql);
						if ($tresult) {
							while ($trow = mysql_fetch_assoc($tresult)) {
								$t = new Tournament();
								$t->id = $trow["tournamentid"];
								$t->name = $trow["name"];
								$t->dateFrom = $trow["datefrom"];
								$t->dateThru = $trow["datethru"];
								$t->ifpaId = $trow["ifpaid"];
								$t->venueId = $vrow["venueid"];
								$venue->addTournament($t);
							}
							mysql_free_result($tresult);
						}
					}
					
					if ($extended == true) {
						
						if ((int)$vrow["imagecount"] > 0) {
							$imagesql =  "select i.imageid, i.venueid, i.default, i.imageurl from image i ";
							$imagesql .= "where i.venueid = " . $vrow["venueid"];
							
							$iresult = mysql_query($imagesql);
							if ($iresult) {
								while ($irow = mysql_fetch_assoc($iresult)) {
									$image = new Image();
									$image->id = (int)$irow["imageid"];
									$image->imageurl = $irow["imageurl"];
									$image->default = $irow["default"];
									$venue->addImage($image);
								}
								mysql_free_result($iresult);
							}
						}
						
						if ((int)$vrow["leaguecount"] > 0) {
							
							$leaguesql =  "select lv.leagueid, l.name, ";
							$leaguesql .= "ts.total as teamcount ";
							$leaguesql .= "from leaguevenue lv ";
							$leaguesql .= 	"inner join league l on lv.leagueid = l.leagueid ";
							$leaguesql .= 	"left outer join (select t.leagueid, tv.venueid, count(*) as total from team t inner join teamvenue tv on t.teamid = tv.teamid where tv.venueid = " . $vrow["venueid"] . " group by t.leagueid, tv.venueid) ts on lv.leagueid = ts.leagueid and lv.venueid = ts.venueid ";
							$leaguesql .= "where lv.venueid = " . $vrow["venueid"];
							
							$lresult = mysql_query($leaguesql);
							if ($lresult) {
								while ($lrow = mysql_fetch_assoc($lresult)) {
									
									$league = new League();
									$league->id = (int)$lrow["leagueid"];
									$league->name = $lrow["name"];
									
									if ((int)$lrow["teamcount"] > 0) {
										
										$teamsql = "select tv.teamid, t.name ";
										$teamsql .= "from teamvenue tv ";
										$teamsql .= 	"inner join team t on tv.teamid = t.teamid ";
										$teamsql .= "where tv.venueid = " . $vrow["venueid"] . " ";
										$teamsql .= 	"and t.leagueid = " . $lrow["leagueid"];
										
										$tresult = mysql_query($teamsql);
										if ($tresult) {
											while ($trow = mysql_fetch_assoc($tresult)) {
												
												$team = new Team();
												$team->id = (int)$trow["teamid"];
												$team->name = $trow["name"];
												
												$league->addTeam($team);
												
											}
											mysql_free_result($tresult);
										}
										
									}
									
									$venue->addLeague($league);
								}
								mysql_free_result($lresult);
							}
						}
					}
				}
			}
			if ($minimal == false) {
				$gid = $vrow["machineid"];
				if ($gid) {
					$gamedicten[$vrow["abbreviation"]] = $vrow["gamename"];
					$game = new Game();
					$game->id = (int)$gid;
					$game->abbr = $vrow["abbreviation"];
					$game->cond = $vrow["condition"];
					$game->price = $vrow["price"];
					if ($extended == true) {
						$game->name = game_name_without_manufacturer($vrow["gamename"]);
					}
					$game->ipdb = $vrow["ipdb"];
					$game->new = $vrow["new"];
					$game->rare = $vrow["rare"];
					if ($extended == true) {
						$game->manufacturer = $vrow["manufacturer"];
						$game->year = $vrow["gameyear"];
					}
					$venue->addGame($game);
				}
			}
		}
		if ($venue != null) {
			$venues[] = $venue;
		}
		mysql_free_result($vresult);
	} else {
		trigger_error(mysql_error());
	}
	
	asort($gamedicten);
	$result->meta->gamedict->en = $gamedicten;
	/*
	$message = new Message();
	$message->title = "Test message!";
	$message->body = "This is a test message.  Lorem ipsum is my middle name!  Enjoy & Share - Pinfinder Team";
	$result->meta->message = $message;
	*/
	if ($extended == true) {
		$result->meta->n = $n;
		$result->meta->q = $q;
	}
	
	$result->venues = $venues;
	
	if (count($venues) > 0) {
		$result->status->status = "success";
	} else {
		$result->status->status = "nomatch";
	}
	
	return $result;
	
}

function get_gamelist_result($q, $l) {
	
	$result = new Result();
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$gamedicten = array();
	
	$cleanq = clean_game_name_string($q);
	
	$safeq = mysql_real_escape_string($q);
	$safecleanq = mysql_real_escape_string($cleanq);
	
	if (!$l) {
		$l = PF_GAMENAMES_LIMIT_DEFAULT;
	}
	
	$safel = mysql_real_escape_string($l);
	
	$sql =  "select g.abbreviation, g.name as gamename from game g where (g.name like '%$safeq%' or g.nameclean like '%$safecleanq%' or g.abbreviation = '$safeq') ";
	$sql .= "order by case when g.abbreviation = '$safeq' then 1 when g.name like '$safeq%' then 2 when g.nameclean like '$safeq%' then 3 else 4 end, g.name, g.abbreviation ";
	$sql .= "limit $l";
	
	$gresult = mysql_query($sql);
	
	if ($gresult) {
		if (mysql_num_rows($gresult)) {
			while ($row = mysql_fetch_assoc($gresult)) {
				$gamedicten[$row["abbreviation"]] = $row["gamename"];
			}
			mysql_free_result($gresult);
		}
	} else {
		trigger_error(mysql_error());
	}
	
	//asort($gamedicten);
	$result->meta->gamedict->en = $gamedicten;
	
	if (count($gamedicten) > 0) {
		$result->status = "success";
	} else {
		$result->status = "nomatch";
	}
	
	return $result;
	
}

function get_stats_result() {

	$result = new Result();
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select count(*) as ncount from notification n where n.delivered = 0";
	
	$sresult = mysql_query($sql);
	
	if ($sresult) {
		
		$row = mysql_fetch_assoc($sresult);
		
		$ncount;
		if ($row == FALSE) {
			$ncount = 0;
		} else {
			$ncount = $row['ncount'];
		}
		
		$stats = new Stats();
		$stats->notifications = $ncount;
		
		$result->meta->stats = $stats;
		
		$result->status->status = "success";
		
	} else {
		$result->status->status = "error";
	}
	
	return $result;
	
}

?>
