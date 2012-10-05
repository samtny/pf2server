<?php

include_once('pf-config.php');
include_once('pf-class.php');

function get_geocode_result($query) {
	
	$gresult;
	
	$gresult = lookup_geocode_result($query);
	
	if (!$gresult) {
		$gresult = google_geocode_result($query);
	}
	
	return $gresult;
	
}

function google_geocode_result($query) {
	
	$gresult;
	
	$base_url = "http://maps.googleapis.com/maps/api/geocode/xml?sensor=false";
	
	$request_url = $base_url . "&address=" . urlencode($query);
	
	$ch = curl_init($request_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$response = curl_exec($ch);
	curl_close($ch);
	
    $xml = simplexml_load_string($response) or die();
	
	$status = $xml->status;
	
    if (strcmp($status, "OK") == 0) {
		
		// Successful geocode
		$gresult = new GeocodeResult();
			
		$gresult->coordinate->lat = (float)$xml->result[0]->geometry->location->lat;
		$gresult->coordinate->lon = (float)$xml->result[0]->geometry->location->lng;
		
		$type = $xml->result[0]->type;
		if (!$type) {
			$type = $xml->result[0]->type[0];
		}
		
		$tooSmall = array("street_address", "intersection", "premise", "subpremise", "natural_feature", "park", "point_of_interest", "post_box", "street_number", "floor", "room");
		
		if (!in_array($type, $tooSmall)) {
		
			if ($xml->result[0]->geometry->bounds) {
				$gresult->southwest->lat = (float)$xml->result[0]->geometry->bounds->southwest->lat;
				$gresult->southwest->lon = (float)$xml->result[0]->geometry->bounds->southwest->lng;
				$gresult->northeast->lat = (float)$xml->result[0]->geometry->bounds->northeast->lat;
				$gresult->northeast->lon = (float)$xml->result[0]->geometry->bounds->northeast->lng;
			} else {
				$gresult->southwest->lat = (float)$xml->result[0]->geometry->viewport->southwest->lat;
				$gresult->southwest->lon = (float)$xml->result[0]->geometry->viewport->southwest->lng;
				$gresult->northeast->lat = (float)$xml->result[0]->geometry->viewport->northeast->lat;
				$gresult->northeast->lon = (float)$xml->result[0]->geometry->viewport->northeast->lng;
			}
			
		}
		
		save_geocode_result($query, $gresult);
		
	}
	
	return $gresult;
	
}

function save_geocode_result($query, $gresult) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "insert into geocode ";
	
	if ($gresult->southwest->lat && $gresult->southwest->lon && $gresult->northeast->lat && $gresult->northeast->lon) {
		$sql .=  "(address, coordinate, southwest, northeast) ";
		$sql .= "values ('" . mysql_real_escape_string($query) . "', ";
		$sql .= "Point(" . mysql_real_escape_string($gresult->coordinate->lat) . ", " . mysql_real_escape_string($gresult->coordinate->lon) . "), ";
		$sql .= "Point(" . mysql_real_escape_string($gresult->southwest->lat) . ", " . mysql_real_escape_string($gresult->southwest->lon) . "), ";
		$sql .= "Point(" . mysql_real_escape_string($gresult->northeast->lat) . ", " . mysql_real_escape_string($gresult->northeast->lon) . ") ";
		$sql .= ") ";
	} else {
		$sql .= "(address, coordinate) ";
		$sql .= "values ('" . mysql_real_escape_string($query) . "', ";
		$sql .= "Point(" . mysql_real_escape_string($gresult->coordinate->lat) . ", " . mysql_real_escape_string($gresult->coordinate->lon) . ") ";
		$sql .= ") ";
	}
	
	$result = mysql_query($sql);
	
	if (!$result) {
		trigger_error(mysql_error());
	}
	
}

function lookup_geocode_result($query) {
	
	$gresult;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select X(coordinate) as lat, Y(coordinate) as lon, ";
	$sql .= "X(southwest) as swlat, Y(southwest) as swlon, ";
	$sql .= "X(northeast) as nelat, Y(northeast) as nelon ";
	$sql .= "from geocode where address = '" . mysql_real_escape_string($query) . "'";
	
	$result = mysql_query($sql);
	
	if ($result) {
		if (mysql_num_rows($result)) {
			
			$row = mysql_fetch_assoc($result);
			
			$gresult = new GeocodeResult();
			
			$gresult->coordinate->lat = $row["lat"];
			$gresult->coordinate->lon = $row["lon"];
			$gresult->southwest->lat = $row["swlat"];
			$gresult->southwest->lon = $row["swlon"];
			$gresult->northeast->lat = $row["nelat"];
			$gresult->northeast->lon = $row["nelon"];
			
		}
	} else {
		trigger_error(mysql_error());
	}
	
	return $gresult;
	
}

function geocode($address) {
	
	$lonlatstring;
	
	$lonlatstring = lookup_address($address);
	
	if (!$lonlatstring) {
		$lonlatstring = google_address($address);
	}
	
	return $lonlatstring;
	
}

function lookup_address($address) {
	
	$lonlatstring;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select X(coordinate) as lat, Y(coordinate) as lon from geocode where address = '" . mysql_real_escape_string($address) . "'";
	
	$result = mysql_query($sql);
	
	if ($result) {
		if (mysql_num_rows($result)) {
			$row = mysql_fetch_assoc($result);
			$lonlatstring = $row["lon"] . "," . $row["lat"];
		}
	} else {
		trigger_error(mysql_error());
	}
	
	return $lonlatstring;
	
}

function google_address($address) {
	
	$lonlatstring;
	
	$base_url = "http://maps.googleapis.com/maps/api/geocode/xml?sensor=false";
	
	$request_url = $base_url . "&address=" . urlencode($address);
	
    $ch = curl_init($request_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$response = curl_exec($ch);
	curl_close($ch);
	
    $xml = simplexml_load_string($response) or die();
	
	$status = $xml->status;
	
    if (strcmp($status, "OK") == 0) {
		
		// Successful geocode
		$lat = $xml->result[0]->geometry->location->lat;
		$lon = $xml->result[0]->geometry->location->lng;
		$lonlatstring = $lon . "," . $lat;
		
		$swlat = $xml->result[0]->geometry->viewport->southwest->lat;
		$swlon = $xml->result[0]->geometry->viewport->southwest->lng;
		$sw = $swlon . "," . $swlat;
		
		$nelat = $xml->result[0]->geometry->viewport->northeast->lat;
		$nelon = $xml->result[0]->geometry->viewport->northeast->lng;
		$ne = $nelon . "," . $nelat;
		
		save_address_result($address, $lonlatstring, $sw, $ne);
		
	}
	
	return $lonlatstring;
	
}

function save_address_result($address, $lonlatstring, $sw, $ne) {
	
	$id;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$coordinatesSplit = split(",", $lonlatstring);
	$lon = $coordinatesSplit[0];
	$lat = $coordinatesSplit[1];
	
	$swSplit = split(",", $sw);
	$swLon = $swSplit[0];
	$swLat = $swSplit[1];
	
	$neSplit = split(",", $ne);
	$neLon = $neSplit[0];
	$neLat = $neSplit[1];
	
	$sql =  "insert into geocode (address, coordinate, southwest, northeast) ";
	$sql .= "values ('" . mysql_real_escape_string($address) . "', ";
	$sql .= "Point(" . mysql_real_escape_string($lat) . ", " . mysql_real_escape_string($lon) . "), ";
	$sql .= "Point(" . mysql_real_escape_string($swLat) . ", " . mysql_real_escape_string($swLon) . "), ";
	$sql .= "Point(" . mysql_real_escape_string($neLat) . ", " . mysql_real_escape_string($neLon) . ") ";
	$sql .= ") ";
	
	$result = mysql_query($sql);
	
	if ($result) {
		$id = mysql_insert_id();
	} else {
		trigger_error(mysql_error());
	}
	
	return $id;
	
}

function google_reverse_geocode_result($latlon) {
	
	$rgresult;
	
	$base_url = "http://" . GOOGLE_MAPS_HOST . "/maps/api/geocode/xml?";
	
	$request_url = $base_url . "latlng=" . $latlon . "&sensor=false";
	
	$ch = curl_init($request_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$response = curl_exec($ch);
	curl_close($ch);
	
    $xml = simplexml_load_string($response) or die();
	
	$result = $xml->result[0];
	//print $xml->asXML();
	
	if ($result) {
		
		$rgresult = new ReverseGeocodeResult();
		
		$i = 0;
		$part = $result->address_component[$i];
		while ($part) {
			
			$type = $part->type[0];
			$name = $part->long_name[0];
			$short = $part->short_name[0];
			
			
			if (strcmp($type, "street_number") == 0) {
				$rgresult->street = $name;
			} else if (strcmp($type, "route") == 0) {
				$rgresult->street = $rgresult->street . " " . $name;
			} else if (strcmp($type, "sublocality") == 0) {
				$rgresult->city = $name;
			} else if (strcmp($type, "locality") == 0 && !$city) {
				$rgresult->city = $name;
			} else if (strcmp($type, "administrative_area_level_1") == 0) {
				$rgresult->state = $name;
				$rgresult->stateshort = $short;
			} else if (strcmp($type, "country") == 0) {
				$rgresult->country = $name;
			} else if (strcmp($type, "postal_code") == 0) {
				$rgresult->zip = $name;
			} else if (strcmp($type, "neighborhood") == 0) {
				$rgresult->neighborhood = $name;
			}
			
			$i = $i + 1;
			$part = $result->address_component[$i];
		}
	}
	
	return $rgresult;
	
}

function reversegeocode($latlon, &$street, &$city, &$state, &$country, &$zip, &$stateshort) {

	$address;
	
	$base_url = "http://" . GOOGLE_MAPS_HOST . "/maps/api/geocode/xml?";
	
	$request_url = $base_url . "latlng=" . $latlon . "&sensor=false";
	
	$xml = simplexml_load_file($request_url) or die("url not loading");
	
	$result = $xml->result[0];
	//print $xml->asXML();
	$i = 0;
	$part = $result->address_component[$i];
	while ($part) {
		
		$type = $part->type[0];
		$name = $part->long_name[0];
		$short = $part->short_name[0];
		
		if (strcmp($type, "street_number") == 0) {
			$street = $name;
		} else if (strcmp($type, "route") == 0) {
			$street = $street . " " . $name;
		} else if (strcmp($type, "sublocality") == 0) {
			$city = $name;
		} else if (strcmp($type, "locality") == 0 && !$city) {
			$city = $name;
		} else if (strcmp($type, "administrative_area_level_1") == 0) {
			$state = $name;
			$stateshort = $short;
		} else if (strcmp($type, "country") == 0) {
			$country = $name;
		} else if (strcmp($type, "postal_code") == 0) {
			$zip = $name;
		}
		
		$i = $i + 1;
		$part = $result->address_component[$i];
	}
	
	return $address;

}

?>