<?php

function clean_game_name_string($unclean) {

	$clean = preg_replace("/the /i", "", trim($unclean));
	// remove (*)
	$clean = preg_replace("/\(.+\)/", "", $clean);
	// remove &amp;, &, etc
	$clean = preg_replace("/&amp/", "", $clean);
	// squash apostrophe
	$clean = preg_replace("/'/", "", $clean);
	
	// remove non-alpha-numeric
	$clean = preg_replace("/[^a-zA-Z0-9\s]/", " ", $clean);
	
	// questionable; stand-alone and/or/of/from
	$clean = preg_replace("/\sand\s|\sor\s|\sof\s|\sfrom\s/i", " ", $clean);
	
	// remove double-spacing
	$clean = preg_replace("/\s+/", " ", $clean);
	// trim again
	$clean = trim($clean);
	return $clean;
	
}

function clean_location_name_string($unclean) {
	
	// kill apostrophes in particular;
	$clean = preg_replace("/'/", "", $unclean);
	
	// replace non-alphanumeric with space
	$clean = preg_replace("/[^a-zA-Z0-9\s]/", " ", $clean);
	
	// remove double-spacing
	$clean = preg_replace("/\s+/", " ", $clean);
	
	// normalize numerics one thru ten, eleven
	$clean = preg_replace("/1st/i", "First", $clean);
	$clean = preg_replace("/2nd/i", "Second", $clean);
	$clean = preg_replace("/3rd/i", "Third", $clean);
	$clean = preg_replace("/4th/i", "Fourth", $clean);
	$clean = preg_replace("/5th/i", "Fifth", $clean);
	$clean = preg_replace("/6th/i", "Sixth", $clean);
	$clean = preg_replace("/7th/i", "Seventh", $clean);
	$clean = preg_replace("/8th/i", "Eighth", $clean);
	$clean = preg_replace("/9th/i", "Ninth", $clean);
	$clean = preg_replace("/10th/i", "Tenth", $clean);
	$clean = preg_replace("/11th/i", "Eleventh", $clean);
	
	// trim
	$clean = trim($clean);
	return $clean;
}

function clean_location_street_string($unclean) {
	$clean = preg_replace("/[^a-zA-Z0-9\s]/", " ", $unclean);
	$clean = trim($clean);
	$clean = preg_replace("/ Lane$| Drive$| Road$| Street$| Avenue$| Boulevard$| Place$| Way$/i", "", $clean);
	$clean = preg_replace("/ Ln$| Dr$| Rd$| St$| Ave$| Blvd$| Pl$/i", "", $clean);
	// remove double-spacing
	$clean = preg_replace("/\s+/", " ", $clean);
	// standalone n/e/s/w become full string;
	$clean = preg_replace("/\sw\s|\sw$/i", " West ", $clean);
	$clean = preg_replace("/\se\s|\se$/i", " East ", $clean);
	$clean = preg_replace("/\ss\s|\ss$/i", " South ", $clean);
	$clean = preg_replace("/\sn\s|\sn$/i", " North ", $clean);
	
	// trim again;
	$clean = trim($clean);
	return $clean;
}

?>