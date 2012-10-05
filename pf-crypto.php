<?php

include_once('pf-config.php');

function hash_for_array($a) {
	
	ksort($a);
	$string = implode("", $a);
	return compute_hash($string);
	
}

function hash_matches_array($a) {
	
	$hash;
	$b = array();
	
	foreach ($a as $key => $val) {
		
		if ($key == "hash") {
			$hash = $val;
		} else {
			$b[$key] = $val;
		}
		
	}
	
	if ($hash) {
		
		ksort($b);
		
		$string = implode("", $b);
		
		return hash_matches_string($string, $hash);
	
	}
	
	return false;
	
}

function hash_matches_document($doc, $hash) {

	$result = false;
	
	$eval = $doc . PFHASH;
	
	$computed = md5($eval);
	
	// convert both to uppercase;
	$hash = strtoupper($hash);
	$computed = strtoupper($computed);
	
	//print "computed: $computed hash: $hash";
	
	if ($computed == $hash) {
		$result = true;
	}
	
	return $result;

}

function hash_matches_string($string, $hash) {

	$result = false;
	
	$eval = $string . PFHASH;
	
	$computed = md5($eval);
	
	// convert both to uppercase;
	$hash = strtoupper($hash);
	$computed = strtoupper($computed);
	
	if ($computed == $hash) {
		$result = true;
	}
	
	return $result;

}

function compute_hash($string) {
	
	return md5($string . PFHASH);
	
}

?>