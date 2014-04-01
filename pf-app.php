<?php session_start(); ?>
<?php if ($_POST): ?>
<?php
header ("Content-Type:text/xml"); // if this line is not absolutely first (no spaces) then older browsers (ie7) will fail to evaluate post data properly...

//include_once $_SERVER['DOCUMENT_ROOT'] . '/securimage/securimage.php';	 
//$securimage = new Securimage();

//if ($securimage->check($_POST['captcha_code']) == true) {
	
	$locxml = stripslashes($_POST["locxml"]);

	if ($locxml) {
		
		// TODO: this should be cURL'ed instead, majorly...
		include_once('./pf-class.php');
		include_once('./pf-post.php');
		
		$request = new Request();
		$request->loadXML($locxml);
		
		$result = process_request($request);
		print ($result->saveXML());
		
	}
	
//} else {
//	print ('<pinfinderapp><meta><status>failure</status></meta></pinfinderapp>');
//}

?>
<?php else: ?>
<?php include_once('pf-util.php'); ?>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript" src="http://www.pinballfinder.org/pf2/js/jquery.cookie.js"></script>
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=AIzaSyCMWL8VtaTA5ORZro3vPvwfZxWel1sgwPg&amp;sensor=false"></script>
<script type="text/javascript" src="http://yui.yahooapis.com/3.4.1/build/yui/yui-min.js"></script>
<script type="text/javascript" src="http://www.pinballfinder.org/pf2/js/pf-app.js"></script>

<div class="appcontainer">
	
	<div class="colInput">
		
		<div id="search" class="search">
			<h3>Search:</h3>
			<h4>Enter a venue name and/or address to Search for Pinball!</h4>
			<ul>
				<li><label for="searchvenue">Venue Name:</label><input id="searchvenue" type="text" class="text" onkeydown="if (event.keyCode == 13) document.getElementById('searchbutton').click()" /></li>
				<li><label for="searchaddress">Address/Area:</label><input id="searchaddress" type="text" class="text" onkeydown="if (event.keyCode == 13) document.getElementById('searchbutton').click()" /></li>
			</ul>
			<div class="buttons">
				<input id="searchbutton" class="button" type="button" value="Search" onclick="searchButtonClick()" />
			</div>
			<p><label id="searchalert" class="alert"></label></p>
			<div>
				<label id="tip" class="tip"></label>
			</div>
		</div>
		
		<div id="venue" class="venue">
			<h2 id="title" class="title"></h2>
			<div class="subtitle">
				<label id="venuesubtitle"></label>
				<span id="editaddressbutton" class="button clickexpand" class="editing" onclick="editAddressButtonClick(this)">[+]</span>
			</div>
			<div id="address" class="address">
				<ul>
					<li><label for="name">Venue Name:</label><input id="name" type="text" class="text" onchange="venueFieldChanged(this)" /></li>
					<li><label for="street">Street:</label><input id="street" type="text" class="text" onchange="venueFieldChanged(this)" /></li>
					<li><label for="city">City:</label><input id="city" type="text" class="text" onchange="venueFieldChanged(this)" /></li>
					<li><label for="state">State:</label><input id="state" type="text" class="text" onchange="venueFieldChanged(this)" /></li>
					<li><label for="zipcode">Zipcode:</label><input id="zipcode" type="text" class="text" onchange="venueFieldChanged(this)" /></li>
					<li><label for="phone">Phone:</label><input id="phone" type="text" class="text" onchange="venueFieldChanged(this)" /></li>
					<li><label for="url">URL:</label><input id="url" type="text" class="text" onchange="venueFieldChanged(this)" /></li>
				</ul>
			</div>
			<div id="games" class="games">
				<table class="gamesTable">
					<colgroup>
						<col class="col1" />
						<col class="col2" />
						<col class="col3" />
						<col class="col4" />
					</colgroup>
					<thead>
						<tr>
							<th><h3>Games:</h3></th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</thead>
					<tfoot>
						<tr id="gameDummy" style="display:none;">
							<td class="col1">
								<input type="hidden" class="key" value="{key}" />
								<label>{name}</label>
							</td>
							<td class="col2">
								<select class="condition editing" onchange="setGameConditionChanged(this, {rowindex})">
									<option value=""></option>
									<option value="0">Broken</option>
									<option value="1">Poor</option>
									<option value="2">Fair</option>
									<option value="3">Good</option>
									<option value="4">Excellent</option>
									<option value="5">Like New</option>
								</select>
							</td>
							<td class="col3">
								<select class="price editing" onchange="setGamePriceChanged(this, {rowindex})")>
									<option value=""></option>
									<option value="0">Free-play</option>
									<option value="0.25">$0.25</option>
									<option value="0.50">$0.50</option>
									<option value="0.75">$0.75</option>
									<option value="1.00">$1.00</option>
									<option value="1.25">$1.25</option>
									<option value="1.50">$1.50</option>
									<option value="1.75">$1.75</option>
									<option value="2.00">$2.00</option>
									<option value="2.25">$2.25</option>
									<option value="2.50">$2.50</option>
									<option value="2.75">$2.75</option>
									<option value="3.00">$3.00</option>
								</select>
							</td>
							<td class="col4">
								<input type="button" value="x" class="editing button" onclick="deleteGameAtIndex({rowindex})" />
							</td>
						</tr>
					</tfoot>
					<tbody>
					</tbody>
				</table>
				<div>
					<input id="newgameabbr" type="hidden" value="" />
					<input id="newgame" type="text" value="Type game name..." class="text newgame" onfocus="newgameOnFocus(this)" onblur="newgameOnBlur(this)" />
					<input type="button" class="button" value="Add" class="newgame" onclick="addNewGameButtonClick()" />
				</div>
			</div>
			<div id="comments" class="comments">
				<h3>Comments:</h3>
				<div class="editing">
					<input id="newcomment" class="newcomment text" type="text"/>
					<input type="button" class="button" value="Add" onclick="addCommentButtonClick()" />
				</div>
				<ul>
				</ul>
			</div>
			<div>
				<input type="button" class="button" value="Back to Search" onclick="startNewSearchButtonClick()" />
				<input id="savebutton" type="button" class="button" value="Save" onclick="saveVenueButtonClick()" />
			</div>
		</div>
	</div>
	
	<div id="result" class="colResult">
		<div id="map" class="map">
			<h3>Map:</h3>
			<div id="map_canvas" style="width:380px;height:350px;"></div>
			<div class="adSpace">
			</div>
			<div class="buttons">
				<input id="refreshmapbutton" type="button" class="button" value="Refresh Map" onclick="refreshMapButtonClick()" />
			</div>
		</div>
		<div id="didyoumean" class="didyoumean">
			<h3>Did you mean:</h3>
			<ul>
				<li>Mort's Bowling (1)</li>
				<li>Stucky's Ale House (4)</li>
				<li>Pinball Pit (2)</li>
			</ul>
		</div>
		<div id="nearby" class="nearby">
			<h3>Nearby:</h3>
			<ul>
				<li>Mort's Bowling (1)</li>
				<li>Stucky's Ale House (4)</li>
				<li>Pinball Pit (2)</li>
			</ul>
		</div>
	</div>
	
	<div id="copyright" class="copyright">
		Pinfinder Javascript App v0.1.0 (Alpha), Copyright Sam Thompson, <?php print(date('Y')); ?>
	</div>
	
</div>
<?php endif; ?>