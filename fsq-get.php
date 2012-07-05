<?php

include_once('fsq-config.php');
include_once('pf-class.php');
include_once('pf-oauth.php');
include_once('pf-user.php');

function fsq_get_result($action, $ll, $limit, $uuid, $venueid) {
	
	if ($action == "checkinlist") {
		return get_checkinlist_result($ll, $limit);
	} else if ($action == "venuephotos") {
		$user = user_matching_uuid($uuid);
		$token = service_token_matching_userid(FSQ_SERVICE_PREFIX, $user->id);
		return get_venue_photos_result($venueid, $token->token);
	} else {
		$result = new Result();
		$result->status->status = "invalidaction";
		return $result;
	}
	
}

function get_checkinlist_result($ll, $limit) {
	
	$venueResult = new Result();
	
	if (!$limit) {
		$limit = FSQ_CHECKIN_SEARCH_LIMIT_MAX;
	}
	
	$url = FSQ_ENDPOINT_V2 . "/venues/search";
	$url .= "?intent=checkin&ll=$ll";
	$url .= "&limit=$limit";
	$url .= "&client_id=" . FSQ_CLIENT_ID;
	$url .= "&client_secret=" . FSQ_CLIENT_SECRET;
	$url .= "&v=" . FSQ_VERIFIED_DATE;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, FSQ_CURL_USERAGENT);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		$fsqResult = json_decode($resultString, true);
		
		$code = $fsqResult["meta"]["code"];
		
		if ($code == "200") {
			
			if ($fsqResult["response"]["venues"]) {
			
				$venuesArray = $fsqResult["response"]["venues"];
				
				foreach ($venuesArray as $v) {
					
					$venue = new Venue();
				
					$venue->name = $v["name"];
					$venue->url = $v["url"];
					$venue->fsqid = $v["id"];
					if ($v["contact"]) {
						$venue->phone = $v["contact"]["formattedPhone"];
					}
					$venue->dist = (float)$v["location"]["distance"] / 1609.344;
					
					$venueResult->venues[] = $venue;
					
				}
				
				$venueResult->status->status = "success";
				
			} else {
				
				$venueResult->status->status = "nomatch";
				
			}
			
		} else {
			
			$venueResult->status->status = "error";
			
		}
		
	} else {
		
		$venueResult->status->status = "error";
		
	}
	
	return $venueResult;
	
}

function get_venue_photos_result($venueid, $token) {
	
	$result = new Result();
	
	$venue = new Venue();
	$venue->fsqid = $venueid;
	
	$url = FSQ_ENDPOINT_V2 . "/multi";
	
	$r;
	
	if ($token) {
		$r .= urlencode("/venues/$venueid/photos?group=checkin") . "," . urlencode("/venues/$venueid/photos?group=venue");
	} else {
		$r = urlencode("/venues/$venueid/photos?group=venue");
	}
	
	$url .= "?requests=$r";
	
	$url .= "&client_id=" . FSQ_CLIENT_ID;
	$url .= "&client_secret=" . FSQ_CLIENT_SECRET;
	$url .= "&v=" . FSQ_VERIFIED_DATE;
	
	if ($token) {
		$url .= "&oauth_token=" . urlencode($token);
	}
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, FSQ_CURL_USERAGENT);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		$responsesResult = json_decode($resultString, true);
		
		$code = $responsesResult["meta"]["code"];
		
		if ($code == "200") {
		
			foreach ($responsesResult["response"]["responses"] as $photosResult) {

				$count = $photosResult["response"]["photos"]["count"];
				
				if ((int)$count > 0) {
					
					foreach ($photosResult["response"]["photos"]["items"] as $item) {
						
						$thumburl;
						$imageurl;
						foreach ($item["sizes"]["items"] as $size) {
							
							if (!$imageurl && ($size["width"] <= 300 || $size["height"] <= 300)) {
								$imageurl = $size["url"];
							}
							
							if (!$thumburl && ($size["width"] <= 36 || $size["height"] <= 36)) {
								$thumburl = $size["url"];
							}
							
						}
						if ($imageurl) {
							$image = new Image();
							$image->imageurl = $imageurl;
							if ($thumburl) {
								$image->thumburl = $thumburl;
							}
							$venue->addImage($image);
						}
						
						unset($imageurl);
						unset($thumburl);
					
					}
					
				}
				
			}
			
			$result->status->status = "success";
			
		} else {
			$result->status->status = "error";
		}
		
	} else {
		
		$result->status->status = "error";
		
	}
	
	$result->addVenue($venue);
	
	return $result;
	
}

function get_venue_match_result($query, $ll) {
	
	$result = new Result();
	
	$querySplit = split(" ", $query);
	
	$url = FSQ_ENDPOINT_V2 . "/venues/search?intent=match&query=" . urlencode($query) . "&ll=$ll&client_id=" . FSQ_CLIENT_ID . "&client_secret=" . FSQ_CLIENT_SECRET . "&v=" . FSQ_VERIFIED_DATE;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, FSQ_CURL_USERAGENT);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		$fsqResult = json_decode($resultString, true);
		
		$code = $fsqResult["meta"]["code"];
		
		if (!$code == "200" || !$fsqResult["response"]["venues"] || count($fsqResult["response"]["venues"]) == 0) {
		
			if (count($querySplit) > 1) {
				// try again with just first part of name
				$url = FSQ_ENDPOINT_V2 . "/venues/search?intent=match&query=" . urlencode($querySplit[0]) . "&ll=$ll&client_id=" . FSQ_CLIENT_ID . "&client_secret=" . FSQ_CLIENT_SECRET . "&v=" . FSQ_VERIFIED_DATE;
				
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_USERAGENT, FSQ_CURL_USERAGENT);
				
				$resultString = curl_exec($ch);
				
				curl_close($ch);
			}
			
		}
		
	}
		
	if ($resultString) {
		
		$fsqResult = json_decode($resultString, true);
		
		$code = $fsqResult["meta"]["code"];
		
		if ($code == "200" && $fsqResult["response"]["venues"]) {
			
			$venuesArray = $fsqResult["response"]["venues"];
			
			$minDist = 9999999;
			$minChanges = 9999999;
			$nearestVenue;
			foreach ($venuesArray as $v) {
				
				if ($v["location"]["distance"] && $v["location"]["distance"] < $minDist) {
					
					if (location_names_match($query, $v["name"])) {
						
						$changes = levenshtein($query, $v["name"]);
						
						if ($changes <= $minChanges) {
							
							$nearestVenue = $v;
							$minChanges = $changes;
							$minDist = $v["location"]["distance"];
							
						}
						
					}
					
				}
				
			}
			
			if ($nearestVenue) {
				$venue = new Venue();
				
				$venue->name = $nearestVenue["name"];
				$venue->url = $nearestVenue["url"];
				$venue->fsqid = $nearestVenue["id"];
				if ($nearestVenue["contact"]) {
					$venue->phone = $nearestVenue["contact"]["formattedPhone"];
				}
				$venue->dist = (float)$nearestVenue["location"]["distance"] / 1609.344;
				
				$result->addVenue($venue);
			}
			
		}
		
		
	}
	
	return $result;
	
}

?>