<?php
die;
include_once('pf-config.php');
include_once('pf-class.php');
include_once('pf-oauth.php');
include_once('pf-user.php');
include_once('pf-crypto.php');

define('FSQ_ENDPOINT_V2', 'https://api.foursquare.com/v2');
define('FSQ_REDIRECT_URI', 'http://www.pinballfinder.org/pf2/pf-fsq.php');
define('FSQ_SERVICE_PREFIX', 'fsq');
define('FSQ_CHECKIN_SEARCH_LIMIT_MAX', 50);

$action = $_GET["action"];
$code = $_GET["code"];

if ($action == "authenticate") {
	
	$url = "https://foursquare.com/oauth2/authenticate";
	$url .= "?client_id=" . FSQ_CLIENT_ID;
	$url .= "&response_type=code";
	$url .= "&redirect_uri=" . urlencode(FSQ_REDIRECT_URI);
	
	Redirect($url);
	
} else if ($action == "checkinlist") {
	
	$ll = $_GET["ll"];
	$limit = $_GET["limit"];
	
	if (!$limit) {
		$limit = FSQ_CHECKIN_SEARCH_LIMIT_MAX;
	}
	
	$result = fsq_get_checkin_venues_result($ll, $limit);
	
	header("Content-type: application/xml");
	echo $result->saveXML();
	
} else if ($action == "checkin") {
	
	$result;
	
	$time = $_GET["time"];
	
	if (isset($time)) {
	
		if ((float)$time >= strtotime("-1 day") && (float)$time <= strtotime("+1 day")) {
			
			$venueid = $_GET["venueid"];
			$uuid = $_GET["uuid"];
			
			$hash = $_GET["hash"];
			
			if (hash_matches_string($venueid . $uuid . $time, $hash)) {
			
				$shout = $_GET["shout"];
				$eventid = $_GET["eventid"];
				
				$user = user_matching_uuid($uuid);
				
				$token;
				
				if ($user) {
					$token = token_matching_userid($user->id);
				}
				
				$result = fsq_checkin($venueid, $token->token, $shout, $eventid);
				
			} else {
				$result = new Result();
				$result->status->status = "badhash";
			}
			
		} else {
			
			$result = new Result();
			$result->status->status = "nicehat";
			
		}
		
	} else {
		
		$result = new Result();
		$result->status->status = "error";
		
	}
	
	header("Content-type: application/xml");
	echo $result->saveXML();
	
} else if ($action == "venuephotos") {
	
	$venueid = $_GET["venueid"];
	$uuid = $_GET["uuid"];
	
	$token;
	
	if ($uuid) {
		$user = user_matching_uuid($uuid);
		if ($user) {
			$token = token_matching_userid($user->id);
		}
	}
	
	$result = fsq_get_venue_photos_result($venueid, $token->token);
	
	header("Content-type: application/xml");
	echo $result->saveXML();
	
} else if ($code) {
	
	$url = "https://foursquare.com/oauth2/access_token";
	$url .= "?client_id=" . FSQ_CLIENT_ID;
	$url .= "&client_secret=" . FSQ_CLIENT_SECRET;
	$url .= "&grant_type=authorization_code";
	$url .= "&redirect_uri=" . urlencode(FSQ_REDIRECT_URI);
	$url .= "&code=" . $code;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		$r = json_decode($resultString);
		
		if ($r->access_token) {
			
			$fsqToken = $r->access_token;
			
			$user = user_matching_service_token(FSQ_SERVICE_PREFIX, $fsqToken);
			
			if (!$user) {
				
				$fsqUser = fsq_get_user($fsqToken);
				
				if ($fsqUser) {
					
					$userName = FSQ_SERVICE_PREFIX . $fsqUser->id;
					
					$user = user_matching_username($userName);
					
					if (!$user) {
					
						$user = create_user($userName, null, $fsqUser->lname, $fsqUser->fname);
					
					}
					
					$token = new Token();
					$token->service = FSQ_SERVICE_PREFIX;
					$token->token = $fsqToken;
					$token->userid = $user->id;
					
					if (!token_exists($token)) {
						save_token($token);
					}
					
				} else {
					
					header("Content-type: text/plain");
					echo "There was a problem linking Pinfinder to your Foursquare account; please try again or contact the server administrator.\n";
					die;
					
				}
				
			}
			
			$uuid = $user->uuid;
			
			if ($uuid) {
			
				$url = FSQ_REDIRECT_URI . "?uuid=" . urlencode($uuid);
				Redirect($url);
			
			} else {
				
				header("Content-type: text/plain");
				echo "There was a problem linking Pinfinder to your Foursquare account; please try again or contact the server administrator.\n";
				die;
			}
			
		} else {
			header("Content-type: text/plain");
			echo "There was a problem retrieving the Foursquare access token; please try again or contact the server administrator.\n";
			die;
		}
		
	} else {
		
		header("Content-type: text/plain");
		echo "There was a problem connecting to Foursquare to retrieve the access token; please try again or contact the server administrator.\n";
		die;
		
	}
	
}

function fsq_get_user($token) {
	
	$user;
	
	$url = FSQ_ENDPOINT_V2 . "/users/self";
	$url .= "?oauth_token=" . $token;
	$url .= "&v=" . FSQ_VERIFIED_DATE;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		header("Content-type: application/json");
		//echo $resultString;
		//die;
		$result = json_decode($resultString, true);
		
		$code = $result["meta"]["code"];
		
		if ($code == "200") {
			
			$user = new User();
			$user->id = $result["response"]["user"]["id"];
			$user->lname = $result["response"]["user"]["lastName"];
			$user->fname = $result["response"]["user"]["firstName"];
			
		}
		
	}
	
	return $user;
	
}

function fsq_get_venue_photos_result($venueid, $token) {
	
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
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		//header("Content-type: application/json");
		//echo $resultString;
		//die;
		
		$responsesResult = json_decode($resultString, true);
		
		$code = $responsesResult["meta"]["code"];
		
		if ($code == "200") {
		
			foreach ($responsesResult["response"]["responses"] as $photosResult) {
				//header("Content-type: application/json");
				//echo json_encode($photosResult);
				//die;
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

function fsq_get_venue_photo_urls($fsqid) {
	
	$urls;
	
	$url = FSQ_ENDPOINT_V2 . "/venues/$fsqid/photos?client_id=" . FSQ_CLIENT_ID . "&client_secret=" . FSQ_CLIENT_SECRET . "&v=" . FSQ_VERIFIED_DATE;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		$result = json_decode($resultString, true);
		
		$code = $result["meta"]["code"];
		
		if ($code == "200") {
			
			//return $resultString;
			
			$count = $result["response"]["photos"]["count"];
			
			if ((int)$count > 0) {
				
				$urls = array();
				
				foreach ($result["response"]["photos"]["groups"] as $group) {
					
					foreach ($group["items"] as $item) {
						
						$url;
						foreach ($item["sizes"]["items"] as $size) {
							
							$url = $size["url"];
							if ($size["width"] == 300 && $size["height"] == 300 || $size["width"] <= 100) {
								break;
							}
							
						}
						if ($url) {
							$urls[] = $url;
						}
						
					}
					
				}
				
			}
			
		}
		
	}
	
	return $urls;
	
}

function fsq_search_venue($query, $ll) {
	
	$venue;
	
	$querySplit = split(" ", $query);
	
	$url = FSQ_ENDPOINT_V2 . "/venues/search?intent=match&query=" . urlencode($query) . "&ll=$ll&client_id=" . FSQ_CLIENT_ID . "&client_secret=" . FSQ_CLIENT_SECRET . "&v=" . FSQ_VERIFIED_DATE;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		$result = json_decode($resultString, true);
		
		$code = $result["meta"]["code"];
		
		if (!$code == "200" || !$result["response"]["venues"] || count($result["response"]["venues"]) == 0) {
		
			if (count($querySplit) > 1) {
				// try again with just first part of name
				$url = FSQ_ENDPOINT_V2 . "/venues/search?intent=match&query=" . urlencode($querySplit[0]) . "&ll=$ll&client_id=" . FSQ_CLIENT_ID . "&client_secret=" . FSQ_CLIENT_SECRET . "&v=" . FSQ_VERIFIED_DATE;
				
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				
				$resultString = curl_exec($ch);
				
				curl_close($ch);
			}
			
		}
		
	}
		
	if ($resultString) {
		
		$result = json_decode($resultString, true);
		
		$code = $result["meta"]["code"];
		
		if ($code == "200" && $result["response"]["venues"]) {
			
			$venuesArray = $result["response"]["venues"];
			
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
			}
			
		}
		
		
	}
	
	return $venue;
	
}

function fsq_checkin($venueid, $token, $shout, $eventid) {
	
	$venueResult = new Result();
	
	$fields = array(
			'venueId'=>urlencode($venueid),
			'oauth_token'=>urlencode($token),
			'v'=>urlencode(FSQ_VERIFIED_DATE),
			'shout'=>urlencode($shout),
			'eventId'=>urlencode($eventid)
        );
	
	$fields_string;
	foreach($fields as $key=>$value) {
		if ($value) {
			$fields_string .= $key.'='.$value.'&';
		}
	}
	rtrim($fields_string,'&');
	
	$url = FSQ_ENDPOINT_V2 . "/checkins/add";
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		$checkinResult = json_decode($resultString, true);
		
		$code = $checkinResult["meta"]["code"];
		
		if ($code == "200") {
			
			$venueResult->status->status = "success";
			
		} else {
			$venueResult->status->status = "error";
		}
	
	} else {
		$venueResult->status->status = "error";
	}
	
	return $venueResult;
	
}

function fsq_get_checkin_venues_result($ll, $limit = FSQ_CHECKIN_SEARCH_LIMIT_MAX) {
	
	$venueResult = new Result();
	
	$url = FSQ_ENDPOINT_V2 . "/venues/search?intent=checkin&ll=$ll&limit=$limit&client_id=" . FSQ_CLIENT_ID . "&client_secret=" . FSQ_CLIENT_SECRET . "&v=" . FSQ_VERIFIED_DATE;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
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

?>