<?php

include_once('pf-config.php');
include_once('pf-class.php');
include_once('pf-string.php');
include_once('pf-get.php');
include_once('pf-token.php');
include_once('pf-user.php');

function process_request($request) {
	
	$result = new Result();
	
	$userid;
	
	$user = $request->user;
	if ($user) {
		$userid = $user->id;
		if (!$userid) {
			foreach ($user->tokens as $token) {
				$userid = userid_for_service_token($token->service, $token->token);
				if ($userid) break;
			}
		}
		if (!$userid) {
			$userid = insert_dummy_user();
		}
		foreach ($user->tokens as $token) {
			freshen_user_service_token($userid, $token->service, $token->token);
		}
                if ($userid) {
                    $user = get_user($userid);
                }
	}
	
        if (!($user->banned == true)) {
            
            foreach ($request->venues as $venue) {

                    $id = $venue->id;
                    $name = $venue->name;
                    $street = $venue->street;
                    $city = $venue->city;
                    $state = $venue->state;
                    $zipcode = $venue->zipcode;
                    $lat = $venue->lat;
                    $lon = $venue->lon;
                    $phone = $venue->phone;
                    $url = $venue->url;
                    $flag = $venue->flag;

                    if ($id) {
                            update_venue($id, $name, $street, $city, $state, $zipcode, $lat, $lon, $phone, $url, $flag);
                    } else {
                            $id = lookup_venue_id($name, $street, $city, $state);
                    }

                    if (!$id) {
                            $id = insert_venue($name, $street, $city, $state, $zipcode, $phone, $url, $userid);
                    }

                    foreach ($venue->games as $game) {

                            $gid = $game->id;
                            $abbr = $game->abbr;
                            $cond = $game->cond;
                            $price = $game->price;
                            $deleted = $game->deleted;

                            if (!$gid) {
                                    $gid = lookup_machine($id, $abbr);
                            }

                            if ($deleted == "1") {
                                    if ($gid) {
                                            delete_machine($gid);
                                    }
                            } else if ($gid) {
                                    update_game($gid, $cond, $price);
                            } else {
                                    insert_game($id, $abbr, $cond, $price);
                            }

                    }

                    foreach ($venue->comments as $comment) {

                            $cid = $comment->id;
                            $text = $comment->text;

                            if (!$cid) {
                                    insert_comment($id, $text);
                            }

                    }

                    $q = $id;
                    $t = "key";
                    $n;
                    $l = 1;
                    $p = "nofilter";
                    $o;
                    $saved = get_venue_result($q, $t, $n, $l, $p, $o);

                    if (count($saved->venues) > 0) {
                            $result->venues[] = $saved->venues[0];
                            $result->meta->gamedict->en = array_merge($result->meta->gamedict->en, $saved->meta->gamedict->en);
                    }

            }

            if (count($result->venues) > 0) {
                    $result->status->status = "success";
            } else {
                    $result->status->status = "error";
            }
            
        } else {
            $result->status->status = "denied";
        }
	
	return $result;
	
}

function delete_machine($machineId) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "delete from machine where machineid = " . mysql_real_escape_string($machineId);
	
	$result = mysql_query($sql);
	
	if (!$result) {
		trigger_error(mysql_error());
	}
	
}

function lookup_machine($venueId, $abbr) {
	
	$id;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select machineid from machine where venueid = " . mysql_real_escape_string($venueId) . " and gameid in (select gameid from game where abbreviation = '" . mysql_real_escape_string($abbr) . "')";
	
	$result = mysql_query($sql);
	
	if ($result) {
		if (mysql_num_rows($result)) {
			$row = mysql_fetch_assoc($result);
			$id = $row["machineid"];
		}
	} else {
		trigger_error(mysql_error());
	}
	
	return $id;
	
}

function insert_comment($venueId, $text) {
	
	$id;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "insert into comment (venueid, text) values (" . mysql_real_escape_string($venueId) . ", '" . mysql_real_escape_string($text) . "') ";
	
	$result = mysql_query($sql);
	
	if ($result) {
		$id = mysql_insert_id();
	} else {
		trigger_error(mysql_error());
	}
	
	return $id;
	
}

function insert_game($venueId, $abbr, $cond, $price) {
	
	$id;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql =  "insert into machine (venueid, gameid, `condition`, price) ";
	$sql .= "select " . mysql_real_escape_string($venueId) . " ";
	$sql .= 	", g.gameid ";
	$sql .=		", '" . mysql_real_escape_string($cond) . "' ";
	$sql .=		", '" . mysql_real_escape_string($price) . "' ";
	$sql .= "from game g where g.abbreviation = '" . mysql_real_escape_string($abbr) . "' ";
	$sql .= "limit 1";
	
	$result = mysql_query($sql);
	
	if ($result) {
		$id = mysql_insert_id();
	} else {
		trigger_error(mysql_error());
	}
	
	return $id;
	
}

function update_game($gid, $cond, $price) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "update machine set `condition` = '" . mysql_real_escape_string($cond) . "', price = '" . mysql_real_escape_string($price) . "' where machineid = " . mysql_real_escape_string($gid);
	
	$result = mysql_query($sql);
	
	if (!$result) {
		trigger_error(mysql_error());
	}
	
}

function insert_venue($name, $street, $city, $state, $zipcode, $phone, $url, $userid) {
	
	$id;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "insert into venue (name, nameclean, namedm, street, city, state, zipcode, phone, url, updatedate, source, sourceid) values (";
	
	$sql .= $name != null ? "'" . mysql_real_escape_string($name) . "' " : "null";
	
	$sql .= $name != null ? ", '" . mysql_real_escape_string(clean_location_name_string($name)) . "' " : ", null ";
	
	$sql .= $name != null ? ", '" . mysql_real_escape_string(dm_location_name_string($name)) . "' " : ", null ";
	
	$sql .= $street != null ? ", '" . mysql_real_escape_string($street) . "' " : ", null ";
	
	$sql .= $city != null ? ", '" . mysql_real_escape_string($city) . "' " : ", null ";
	
	$sql .= $state != null ? ", '" . mysql_real_escape_string($state) . "' " : ", null ";
	
	$sql .= $zipcode != null ? ", '" . mysql_real_escape_string($zipcode) . "' " : ", null ";
	
	$sql .= $phone != null ? ", '" . mysql_real_escape_string($phone) . "' " : ", null ";
	
	$sql .= $url != null ? ", '" . mysql_real_escape_string($url) . "' " : ", null ";
	
	$sql .= ", NOW() ";
	
	$sql .= ($userid && $userid != null) ? ", 'user' " : ", null ";
	
	$sql .= ($userid && $userid != null) ? ", '" . mysql_real_escape_string($userid) . "' " : ", null ";
	
	$sql .= ")";
	
	$result = mysql_query($sql);
	
	if ($result) {
		$id = mysql_insert_id();
	} else {
		trigger_error(mysql_error());
	}
	
	return $id;
	
}

function update_venue($id, $name, $street, $city, $state, $zipcode, $lat, $lon, $phone, $url, $flag) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "update venue set updatedate = NOW() ";
	
	if ($name) {
		$sql .= $name != null ? ", name = '" . mysql_real_escape_string($name) . "' " : ", name = null ";
		$sql .= $name != null ? ", nameclean = '" . mysql_real_escape_string(clean_location_name_string($name)) . "' " : ", cleanname = null ";
		$sql .= $name != null ? ", namedm = '" . mysql_real_escape_string(dm_location_name_string($name)) . "' " : ", namedm = null ";
	}
	
	if ($street) {
		$sql .= $street != null ? ", street = '" . mysql_real_escape_string($street) . "' " : ", street = null ";
	}
	
	if ($city) {
		$sql .= $city != null ? ", city = '" . mysql_real_escape_string($city) . "' " : ", city = null ";
	}
	
	if ($state) {
		$sql .= $state != null ? ", state = '" . mysql_real_escape_string($state) . "' " : ", state = null ";
	}
	
	if ($zipcode) {
		$sql .= $zipcode != null ? ", zipcode = '" . mysql_real_escape_string($zipcode) . "' " : ", zipcode = null ";
	}
	
	if ($lat && $lon) {
		if ($lat != null && $lon != null) {
			$sql .= ", coordinate = Point(" . mysql_real_escape_string($lat) . "," . mysql_real_escape_string($lon) . ") ";
		} else {
			$sql .= ", coordinate = null ";
		}
	}
	
	if ($phone) {
		$sql .= $phone != null ? ", phone = '" . mysql_real_escape_string($phone) . "' " : ", phone = null ";
	}
	
	if ($url) {
		$sql .= $url != null ? ", url = '" . mysql_real_escape_string($url) . "' " : ", url = null ";
	}
	
	if ($flag) {
		$sql .= $flag != null ? ", flag = '" . mysql_real_escape_string($flag) . "' " : ", flag = null ";
	}
	
	$sql .= "where venueid = " . mysql_real_escape_string($id) . " ";
	$sql .= "limit 1 ";
	
	$result = mysql_query($sql);
	
	if (!$result) {
		trigger_error(mysql_error());
	}
	
}

function lookup_venue_id($name, $street, $city, $state) {
	
	$id;
	
	$q = $name;
	$t = "venue";
	$n = $street . ' ' . $city . ' ' . $state;
	$l = 1;
	$p;
	$o;
	
	$result = get_venue_result($q, $t, $n, $l, $p, $o);
	
	if ($result && count($result->venues) == 1 && $result->venues[0]->dist && $result->venues[0]->dist <= PF_SAME_VENUE_MILES) {
		$id = $result->venues[0]->id;
	}
	
	return $id;
	
}


?>