<?php

$url = 'http://www.pinballfinder.org/pf2/pf';

$params = $_GET + array("p" => "extended", "o" => "name");

$url .= "?";
foreach ($params as $key => $val) {
	$url .= $key . "=" . urlencode($val) . "&";
}
$url = rtrim($url, "&");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);

if ($result) {
	
	$XML = new DOMDocument();
	$XML->loadXML($result); 
	
	$xslt = new XSLTProcessor(); 
	
	$XSL = new DOMDocument();
	$XSL->load( 'venuelist.xsl' );
	
	$xslt->importStylesheet( $XSL ); 
	
	header("Content-type: text/html; charset=utf-8");
	echo $xslt->transformToXML( $XML ); 
	
}

?>