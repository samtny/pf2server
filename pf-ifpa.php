<?php

include_once ('pf-config.php');

define ('IFPA_FEED_URI', 'http://www.ifpapinball.com/rss.php?m=calendar');

function get_ifpa_tournaments_from_feed() {
	
	$tournaments = array();
	
	$ch = curl_init(IFPA_FEED_URI);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$feedString = curl_exec($ch);
	curl_close($ch);
	
	if ($feedString) {
	
		$feed = simplexml_load_string($feedString);
		
		foreach ($feed->channel->item as $item) {
			
			$name = $item->title;
			$from;
			$thru;
			$ifpaId;
			
			if (preg_match('/held on (.*)/i', $item->description, $matches) == 1) {
				$from = date("Y-m-d", strtotime($matches[1]));
				$thru = $from;
				
			} elseif (preg_match('/held between (.+) and (.+)/i', $item->description, $matches) == 1) {
				$from = date("Y-m-d", strtotime($matches[1]));
				$thru = date("Y-m-d", strtotime($matches[2]));
			}
						
			if (preg_match('/t=(.*)/i', $item->link, $matches) == 1) {
				$ifpaId = $matches[1];
			}
			
			if ($name) {
				$t = new Tournament();
				$t->name = $name;
				$t->dateFrom = $from;
				$t->dateThru = $thru;
				$t->ifpaId = $ifpaId;
				$tournaments[] = $t;
			}
			
		}
	
	}
	
	return $tournaments;
	
}

?>