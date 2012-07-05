/*global alert, confirm, google, YUI */
"use strict";
var pf_rel_path = "./pf2";
var pf_server_url = pf_rel_path + "/pf";
var pf_app_url = pf_rel_path + "/pf-app.php";
var tip_url = pf_rel_path + "/pf-util.php?action=gettip";var gamedict_url = "./gamedict.txt";
var ipdb_machine_url = "http://www.ipdb.org/machine.cgi?id=";

var map;
var infoWindow;

var markers = [];
var defaultLimit = 150;

var didYouMean = [];

// this dictionary is relevant to currently viewable venues only;
var curGameDict;

// this is full dictionary;
var gameDict = [];

function Venue(k, n, a, c, s, z, p, lat, lon, u, updated, isnew) {
	this.key = k;
	this.name = n;
	this.street = a;
	this.city = c;
	this.state = s;
	this.zipcode = z;
	this.phone = p;
	this.lat = lat;
	this.lon = lon;
	this.url = u;
	this.updated = updated;
	this.isnew = isnew;
}

var venue = new Venue();

var gameList = [];

function Game(k, a, d, c, p, i, updated, deleted) {
	this.key = k;
	this.abbr = a;
	this.desc = d;
	this.cond = c;
	this.price = p;
	this.ipdb = i;
	this.deleted = deleted;
	this.updated = updated;
}

var condText = { "0" : "Broken", "1" : "Poor", "2" : "Fair", "3" : "Good", "4" : "Excellent", "5" : "Like New" };

var commentList = [];

function Comment(t, d, n) {
	this.ctext = t;
	this.date = d;
	this.isnew = n;
}

var editing = false;
var editingAddress = false;

$(document).ready(function () {
	
	disableSaveButtonAnimated(false);
	refreshTip();
	
	$('#searchvenue').focus();
	
	initGameDict();
	
	initMap();
	
	doMapRefresh();	
	
});

function refreshTip() {
	$.ajax({
		url: encodeURI(tip_url),
		dataType: "text",
		success: function(tip) {$('#tip').hide().text(tip).fadeIn(400);}
	});
}

function toggleEditItemsVisibleAnimated(animated) {
	
	if (!animated) {
		$('.editing').toggle(editing);
		$('.notediting').toggle(!editing);
	} else {
		if (editing) {
			$('.editing').fadeIn("fast");
			$('.notediting').fadeOut("fast");
		} else {
			$('.editing').fadeOut("fast");
			$('.notediting').fadeIn("fast");
		}
	}
	
}

function toggleAddressItemsVisibleAnimated(animated) {
	
	if (!animated) {
		$('.address').toggle(editingAddress);
	} else {
		if (editingAddress) {
			$('.address').fadeIn("fast");
		} else {
			$('.address').fadeOut("fast");
		}
	}
	
	if (editingAddress) {
		$('#editaddressbutton').html('[-]');
	} else {
		$('#editaddressbutton').html('[+]');
	}
	
}

function refreshMapButtonClick() {
	doMapRefresh();
}

function doMapRefresh() {
	
	$('#refreshmapbutton').attr('disabled', 'disabled');
	
	var center = map.getCenter();
	var wrapped = new google.maps.LatLng (
		center.lat(),
		center.lng(),
		false
	);
	
	var latLng = wrapped.toString().replace("(", "").replace(")", "").replace(" ", "");
	
	var lookupurl = pf_server_url + "?n=" + latLng + "&l=" + defaultLimit;

	$.ajax({

		url: encodeURI(lookupurl),

		dataType: "xml",

		success: handleMapRefreshResult,

		error: errorMapRefresh

	});
	
}

function handleMapRefreshResult(xml) {
	
	$('#refreshmapbutton').removeAttr('disabled');
	
	loadGameDict($(xml).find("dict"));
	
	clearMapMarkers();
	
	var bounds = addVenuesToMap(xml);
	
	//map.fitBounds(bounds);
	
}

function errorMapRefresh() {
	$('#refreshmapbutton').removeAttr('disabled');
	alert('There was a server error refreshing the map.  Please try again later.');
}

function editAddressButtonClick(e) {
	
	editingAddress = !editingAddress;
	toggleAddressItemsVisibleAnimated(true);
	
}

function cancelEditAddressButtonClick() {
	editingAddress = false;
	toggleAddressItemsVisibleAnimated(true);
}

function toggleAddressFieldsVisible(visible) {
	$('.address').toggle(visible);
}

function hideNewVenueFieldsAnimated(animated) {
	if (!animated) {
		$('.newvenue').hide();
	} else {
		$('.newvenue').fadeOut("100");
	}
}

function setVenueVisible() {
	
	if (venue.isnew) {
		$('#venuesubtitle').hide();
		$('#editaddressbutton').hide();
	} else {
		$('#venuesubtitle').show();
		$('#editaddressbutton').show();
	}
	
	if ($('#search').css('display') !== 'none') {
		$('#search').fadeOut("fast", function () {
			$('#venue').fadeIn("slow");
		});
	}
	
}

function setSearchVisibleAnimated(animated) {
	
	if (!animated) {
		$('#venue').hide();
		$('#search').show();
	} else {
		if ($('#venue').css('display') !== 'none') {
			$('#venue').fadeOut("100", function () {
				if ($('#search').css('display') === 'none') {
					$('#search').fadeIn("100");
				}
			});
		} else {
			if ($('#search').css('display') === 'none') {
				$('#search').fadeIn("100");
			}
		}
	}
	
}

// map
function initMap() {
	
	var lat;
	var lon;
	var zoom;
	
	var latLonZoom = $.cookie("latLonZoomV0");
	if (latLonZoom) {
		var parts = latLonZoom.split("|");
		lat = parts[0];
		lon = parts[1];
		zoom = parseInt(parts[2], 10);
	}
	
	if (!lat || !lon || !zoom) {
		lat = 40.7316997;
		lon = -73.9895458;
		zoom = 12;
	}
	
	var myOptions = {
		center: new google.maps.LatLng(lat, lon),
		zoom: zoom,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		mapTypeControl: false,
		streetViewControl: false,
		zoomControlOptions: {
			style: google.maps.ZoomControlStyle.LARGE
		}
	};
	
	map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
	
	google.maps.event.addListener(map, 'click', function () {
		infoWindow.close();
	});
	
	google.maps.event.addListener(map, 'dragend', function () {
		saveMapPosition();
	});
	
	google.maps.event.addListener(map, 'zoom_changed', function () {
		saveMapPosition();
	});
	
}

function saveMapPosition() {

	var lat = map.getCenter().lat();
	var lon = map.getCenter().lng();
	var zoom = map.getZoom();
	
	var latLonZoom = lat + "|" + lon + "|" + zoom;
	
	$.cookie("latLonZoomV0", latLonZoom, { expires: 9000 });
	
}

function initGameDict() {
	
	$.ajax({

		url: encodeURI(gamedict_url),

		dataType: "text",

		success: handleGameDictResult,

		error: errorGameDictInit

	});
	
}

// YUI just for autocomplete, for now; ...
var ac;

function enableAutocomplete() {
	ac.render();
}

function handleGameDictResult(text) {
	
	// thank you, Flickr team;
	var entries = text.split("\\g");
	for (var n = 0, len = entries.length, entrySplit; n < len; n++) {
		entrySplit = entries[n].split("\\f");
		
		gameDict[entrySplit[0]] = {};
		//gameDict[n].a = entrySplit[0];
		gameDict[entrySplit[0]].a = entrySplit[0];
		gameDict[entrySplit[0]].d = entrySplit[1];
		gameDict[entrySplit[0]].i = entrySplit[2];
	}
	
	YUI().use('datasource', 'autocomplete', 'autocomplete-filters', function (Y) {
		
		//Y.one('body').addClass('yui3-skin-sam');
		
		ac = new Y.AutoComplete({
			inputNode : '#newgame',
			minQueryLength : 2,
			maxResults : 10,
			resultTextLocator : 'd',
			source : function (query) {
					
					var r = [];
					var t = new RegExp(query, "i");
					
					var l = gameDict.length;
					/*
					for (var n = 0; n < l; n++) {
						var e = gameDict[n];
						if (t.test(e.d)) {
							r.push(e);
						}
					}
					*/
					for (var a in gameDict) {
						if (t.test(gameDict[a].d)) {
							r.push(gameDict[a]);
						}
					}
					
					return r;
					
				}
		});
		
		ac.after('select', function (e) {
			$('#newgameabbr').val(e.result.raw.a);
			$('#condition').val('3');
			$('#price').val('0.75');
		});
		
	});
	
}

function errorGameDictInit() {
	// just don't set gameDict for now...
}

function searchButtonClick() {
	if (!$('#searchvenue').val() && !$('#searchaddress').val()) {
		displayAlert("Enter a Venue Name or Address to Search", $('#searchalert'));
	} else if ($('#searchvenue').val()) {
		doVenueSearch();
	} else {
		doAddressSearch();
	}
}

function displayAlert(message, lbl) {
	lbl.text(message).hide().fadeIn(400).delay(1600).fadeOut(400);
}

function doVenueSearch() {
	
	var venue = $('#searchvenue').val();
	var address = $('#searchaddress').val();
	
	var lookupurl = pf_server_url + "?q=" + venue + "&t=venue&l=" + defaultLimit;
	
	if (address) {
		lookupurl = lookupurl + "&n=" + address;
	}
	
	clearDidYouMean();
	$('#searchbutton').attr('disabled', 'disabled');
	
	$.ajax({

		url: encodeURI(lookupurl),

		dataType: "xml",

		success: handleVenueSearchResult,

		error: errorVenueSearch

	});
	
}

function handleVenueSearchResult(xml) {
	
	var status = $(xml).find("status").text();
	
	// re-enable buttons right away in case there is some problem later;
	$('#searchbutton').removeAttr('disabled');
	
	if (status === "success") {
		
		$(xml).find("loc").each(function (index) {
			
			// TODO: only add first venue, rest go to "did you mean?"
			if (index === 0) {
			
				var dist = $(this).find("dist").text();
				
				if (dist > 500) {
					
					var addit = confirm('That venue was not found in our database; do you wish to add it?');
					
					if (addit) {
						beginAddNewVenue();
					}
					
				} else {
					
					var venuekey = $(this).attr("key");
					var venue = $(this).find("name").text();
					var street = $(this).find("addr").text();
					var city = $(this).find("city").text();
					var state = $(this).find("state").text();
					var zipcode = $(this).find("zipcode").text();
					var phone = $(this).find("phone").text();
					var lat = $(this).find("lat").text();
					var lon = $(this).find("lon").text();
					
					//var foundit = confirm('Did you mean \'' + venue + "\' at " + street + " " + city + ", " + state + "?");
					var foundit = true;
					if (foundit) {
						
						clearMapMarkers();
						addVenuesToMap(xml);
						
						// current venue;
						loadGameDict($(xml).find("dict"));
						loadVenue(this);
						showMarkerInfoWindow(venuekey);
						
						// load nearby venues;
						var nearaddress = lat + ',' + lon;
						doNearbySearch(nearaddress);
						
					} else {
					
						var searchvenue = $('#searchvenue').val();
						var addit_ = confirm('Do you wish to add \'' + searchvenue + '\' to the database?');
						if (addit_) {
							beginAddNewVenue();
						}
						
					}
					
				}
				
			} else {
				
				// add this venue to "did you mean?"
				addDidYouMean(this);
				
			}
			
		});
		
	} else {
		var response = confirm('That venue was not found in our database; do you wish to add it?');
			
		if (response) {
			beginAddNewVenue();
		}
	}
	
}

function addDidYouMean(locxml) {
	
	didYouMean.push(locxml);
	
	var venue = $(locxml).find("name").text();
	var street = $(locxml).find("addr").text();
	var city = $(locxml).find("city").text();
	//var state = $(locxml).find("state").text();
	//var zipcode = $(locxml).find("zipcode").text();
	//var phone = $(locxml).find("phone").text();
	//var lat = $(locxml).find("lat").text();
	//var lon = $(locxml).find("lon").text();
	
	$('#didyoumean ul').append('<li onclick="didYouMeanListClick(this)">' + venue + ', ' + street + ', ' + city + '</li>');
	
	if (!$('#didyoumean').is(':visible')) {
		$('#didyoumean').show();
	}
	
}

function didYouMeanListClick(li) {
	
	var locxml = didYouMean[$(li).index()];
	var venuekey = $(locxml).attr("key");
	
	loadVenue(locxml);
	
	if (!isScrolledIntoView($('#map'))) {
		$(window).scrollTop(0);
	}
	
	showMarkerInfoWindow(venuekey);
	
}

// from http://stackoverflow.com/questions/487073/jquery-check-if-element-is-visible-after-scrolling;
function isScrolledIntoView(elem)
{
    var docViewTop = $(window).scrollTop();
    var docViewBottom = docViewTop + $(window).height();

    var elemTop = $(elem).offset().top;
    var elemBottom = elemTop + $(elem).height();

    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
}

function clearDidYouMean() {
	didYouMean = [];
	$('#didyoumean ul').empty();
	$('#didyoumean').hide();
}

function showMarkerInfoWindow(key) {
	
	$(markers).each(function () {
		if (this.key === key) {
			map.setZoom(14);
			map.setCenter(this.position);
			google.maps.event.trigger(this, 'click');
			return false;
		}
	});
	
}

function loadGameDict(newdict) {
	curGameDict = newdict;
}

function errorVenueSearch(jqXHR, textStatus, errorThrown) {
	$('#searchbutton').removeAttr('disabled');
	alert('Internal error while getting result; please check your search terms and try again');
}

function loadVenue(loc) {
	
	clearVenue();
	
	editing = false;
	//toggleEditItemsVisibleAnimated(false);
	toggleAddressFieldsVisible(false);
	disableSaveButtonAnimated(false);
	
	var key = $(loc).attr("key");
	var name = $(loc).find("name").text();
	var street = $(loc).find("addr").text();
	var city = $(loc).find("city").text();
	var state = $(loc).find("state").text();
	var zipcode = $(loc).find("zipcode").text();
	var phone = $(loc).find("phone").text();
	var lat = $(loc).find("lat").text();
	var lon = $(loc).find("lon").text();
	var url = $(loc).find("url").text();
	
	venue = new Venue(key, name, street, city, state, zipcode, phone, lat, lon, url, false);
	
	$(loc).find("game").each(function () {
		key = $(this).attr('key');
		var abbr = $(this).find('abbr').text();
		var desc = $(curGameDict).find('[key=' + abbr + ']').text();
		var cond = $(this).find('cond').text();
		var price = $(this).find('price').text();
		var ipdb = $(this).find('ipdb').text();
		var game = new Game(key, abbr, desc, cond, price, ipdb, false, false);
		addGameToList(game);
	});
	
	$(loc).find("comment").each(function () {
		var ctext = $(this).find('ctext').text();
		var cdate = $(this).find('cdate').text();
		var comment = new Comment(ctext, cdate, false);
		addCommentToList(comment, false);
	});
	
	refreshVenueDisplay();
	refreshGameDisplay();
	refreshCommentDisplay();
	
	enableAutocomplete();
	
	setVenueVisible();
	
}
	
function addGameToList(game, top) {
	
	if (top) {
		gameList.unshift(game);
	} else {
		gameList.push(game);
	}
	
}

function cancelEditVenueButtonClick() {
	
	editing = false;
	//toggleEditItemsVisibleAnimated(true);
	toggleAddressFieldsVisible(false);

}

function clearVenue() {
	venue = new Venue();
	gameList = [];
	commentList = [];
	refreshVenueDisplay();
	refreshGameDisplay();
	refreshCommentDisplay();
}

function refreshVenueDisplay() {
	
	if (venue.url) {
		var link = $('<a target="blank" />');
		link.attr('href', venue.url);
		link.text(venue.name);
		$('#title').html(link);
	} else {
		$('#title').html(venue.name);
	}
	
	var subtitle = venue.street + ', ' + venue.city + " " + venue.state + ', ' + venue.zipcode;
	if (venue.phone) {
		subtitle = subtitle + " - " + venue.phone;
	}
	$('#venuesubtitle').text(subtitle);
	
	$('#name').val(venue.name);
	$('#street').val(venue.street);
	$('#city').val(venue.city);
	$('#state').val(venue.state);
	$('#zipcode').val(venue.zipcode);
	$('#phone').val(venue.phone);
	$('#url').val(venue.url);
	
}

var condSelectTemplate = '<select class="condition editing" onchange="setGameConditionChanged(this, {rowid})"><option value=""></option><option value="0">Broken</option><option value="1">Poor</option><option value="2">Fair</option><option value="3">Good</option><option value="4">Excellent</option><option value="5">Like New</option></select>';
var priceSelectTemplate = '<select class="price editing" onchange="setGamePriceChanged(this, {rowid})")><option value=""></option><option value="0">Free-play</option><option value="0.25">$0.25</option><option value="0.50">$0.50</option><option value="0.75">$0.75</option><option value="1.00">$1.00</option><option value="1.25">$1.25</option><option value="1.50">$1.50</option><option value="1.75">$1.75</option><option value="2.00">$2.00</option><option value="2.25">$2.25</option><option value="2.50">$2.50</option><option value="2.75">$2.75</option><option value="3.00">$3.00</option></select>';

function refreshGameDisplay() {

	$('#games tbody').html('');
	
	$(gameList).each(function (i) {
		if (!this.deleted) {
			
			var nameLabel;
			
			if (this.ipdb) {
				nameLabel = "<a href=\"" + ipdb_machine_url + this.ipdb + "\" target=\"blank\">" + gameDict[this.abbr].d + "</a>";
			} else {
				nameLabel = "<label>" + gameDict[this.abbr].d + "</label>";
			}
			
			var condSelect = condSelectTemplate.replace("value=\"" + this.cond, "selected=\"selected\" value=\"" + this.cond);
			condSelect = condSelect.replace("{rowid}", i);
			var priceSelect = priceSelectTemplate.replace("value=\"" + this.price, "selected=\"selected\" value=\"" + this.price);
			priceSelect = priceSelect.replace("{rowid}", i);
			
			$('#games tbody').append("<tr id=\"game" + i + "\" class=\"gamerow\"><td class=\"col1\"><input type=\"hidden\" class=\"key\" value=\"" + this.key + "\">" + nameLabel + "</td><td class=\"col2\">" + condSelect + "</td><td class=\"col3\">" + priceSelect + "</td><td class=\"col4\"><input type=\"button\" value=\"x\" class=\"editing button\" onclick=\"deleteGameAtIndex(" + i + ")\" /></td></tr>");
			
		}
	});
	
	//toggleEditItemsVisibleAnimated(false);
	
}

function disableSaveButtonAnimated(animated) {
	if (animated) {
		$('#savebutton').attr("disabled", "disabled").delay(500).fadeOut("fast");
	} else {
		$('#savebutton').attr("disabled", "disabled").hide();
	}
}

function enableSaveButtonAnimated(animated) {
	if (animated) {
		$('#savebutton').removeAttr("disabled").fadeIn("fast");
	} else {
		$('#savebutton').removeAttr("disabled").show();
	}
}

function venueFieldChanged() {
	venue.updated = true;
	enableSaveButtonAnimated(true);
}

function refreshCommentDisplay() {
	$('#comments ul').html('');
	$(commentList).each(function (i) {
		$('#comments ul').append('<li><label>' + this.ctext + '</label></li>');
	});
}

function setGameConditionChanged(select, rowid) {
	gameList[rowid].cond = $(select).val();
	gameList[rowid].updated = true;
	enableSaveButtonAnimated(true);
}

function setGamePriceChanged(select, rowid) {
	gameList[rowid].price = $(select).val();
	gameList[rowid].updated = true;
	enableSaveButtonAnimated(true);
}

function deleteGameAtIndex(index) {
	
	$('#game' + index).hide('fast', function () {
		if (gameList[index].key) {
			gameList[index].deleted = true;
		} else {
			gameList.splice(index, 1);
		}
		refreshGameDisplay();
	});
	
	enableSaveButtonAnimated(true);
	
}

function doAddressSearch(nearaddress) {
	
	var address = null;
	
	if (!nearaddress) {
		address = $('#searchaddress').val();
	} else {
		address = nearaddress;
	}
	
	var lookupurl = pf_server_url + "?n=" + address + "&l=" + defaultLimit;
	
	clearDidYouMean();
	$('#searchbutton').attr('disabled', 'disabled');
	
	$.ajax({

		url: encodeURI(lookupurl),

		dataType: "xml",

		success: handleAddressSearchResult,

		error: errorAddressSearch

	});
	
}

function handleAddressSearchResult(xml) {

	$('#searchbutton').removeAttr('disabled');
	
	loadGameDict($(xml).find("dict"));
	
	clearMapMarkers();
	
	var bounds = addVenuesToMap(xml);
	
	map.fitBounds(bounds);
	
}

function errorAddressSearch(jqXHR, textStatus, errorThrown) {
	$('#searchbutton').removeAttr('disabled');
	alert('Internal error while getting result; please check your search terms and try again');
}

function doNearbySearch(address) {
	
	var lookupurl = pf_server_url + "?n=" + address + "&l=" + defaultLimit;
	
	$.ajax({

		url: encodeURI(lookupurl),

		dataType: "xml",

		success: handleNearbySearchResult,

		error: errorNearbySearch

	});
	
}

function handleNearbySearchResult(xml) {

	loadGameDict($(xml).find("dict"));
	addVenuesToMap(xml);
	
}

function errorNearbySearch(jqXHR, textStatus, errorThrown) {
	// TODO: set status message label
}

function startNewSearchButtonClick() {
	
	setSearchVisibleAnimated(true);
	
}

function beginAddNewVenue() {
	
	clearVenue();
	
	var name = $('#searchvenue').val();
	
	venue = new Venue(null, name, '', '', '', '', '', null, null, null, true, true);
	refreshVenueDisplay();
	
	editing = true;
	//toggleEditItemsVisibleAnimated(false);
	editingAddress = true;
	toggleAddressItemsVisibleAnimated(false);
	
	//resetCaptcha();
	enableAutocomplete();
	
	setVenueVisible();
	
}

function clearMapMarkers() {
	$(markers).each(function () {
		this.setMap(null);
	});
	markers = [];
}

function addVenuesToMap(locxml) {
	
	var newMarkers = [];
	
	var bounds = new google.maps.LatLngBounds();
	
	var image = new google.maps.MarkerImage(
		pf_rel_path + '/images/pin.png',
		// w, h.
		new google.maps.Size(20, 40),
		// origin.
		new google.maps.Point(0, 0),
		// anchor.
		new google.maps.Point(10, 40)
	);

	var shape = {
		coord: [1, 1, 1, 40, 20, 40, 20, 1],
		type: 'poly'
	};
	
	$(locxml).find("loc").each(function () {
		
		var exists = false;
		var key = $(this).attr("key");
		$(markers).each(function () {
			if (this.key === key) {
				exists = true;
				return false;
			}
		});
		
		if (!exists) {
		
			var venue = $(this).find("name").text();
			var street = $(this).find("addr").text();
			var lat = $(this).find("lat").text();
			var lon = $(this).find("lon").text();
			
			var latlon = new google.maps.LatLng(lat, lon);
			bounds.extend(latlon);
			
			var marker = new google.maps.Marker({
				position: latlon,
				map: map,
				title: venue,
				icon: image,
				shape: shape
			});
			marker.key = key;
			marker.xml = $(this);
			
			var html = '<b>' + venue + '</b><br />' + street;
			
			if (!infoWindow) {
				infoWindow = new google.maps.InfoWindow();
			}
			
			google.maps.event.addListener(marker, 'click', function () {
				loadVenue(this.xml);
				infoWindow.setContent(html);
				infoWindow.open(map, this);
			});
			
			newMarkers.push(marker);
			
		}
		
	});
	
	markers.push.apply(markers, newMarkers);
	
	return bounds;
	
}

function newgameOnFocus(e) {
	if ($(e).val() === 'Type game name...') {
		$(e).val('');
	}
}

function newgameOnBlur(e) {
	if ($(e).val() === '') {
		$(e).val('Type game name...');
	}
}

function addNewGameButtonClick() {

	if ($('#newgame').val()) {
		
		if ($('#newgameabbr').val()) {
		
			var game = new Game();
			game.abbr = $('#newgameabbr').val();
			game.desc = $('#newgame').val();
			game.cond = "3";
			game.price = "0.75";
			game.updated = true;
			addGameToList(game, false);
			
			refreshGameDisplay();
			
			resetNewGame();
			
			enableSaveButtonAnimated(true);
			
			$('#newgame').focus();
		
		} else {
			alert('You must choose a valid pinball game name!');
		}
		
	}
	
}

function resetNewGame() {
	$('#newgameabbr').val('');
	$('#newgame').val('Type game name...');
	$('#condition').val('');
	$('#price').val('');
}

function addCommentButtonClick() {
	
	var commentString = $('#newcomment').val();
	
	if (commentString) {
		
		if (!(/</.test(commentString))) {
		
			var comment = new Comment(commentString, '', true);
			addCommentToList(comment, true);
			
			refreshCommentDisplay();
			
			enableSaveButtonAnimated(true);
			
			$('#newcomment').val('');
			
		} else {
			alert('Sorry, no html allowed in comments...');
		}
		
	}
	else {
		alert('Type a comment to add one...');
	}
	
}

function addCommentToList(comment, top) {
	
	if (top) {
		commentList.unshift(comment);
	} else {
		commentList.push(comment);
	}
	//refreshCommentDisplay();
	
}

function refreshCommentDisplay() {

	$('#comments ul').html('');
	
	$(commentList).each(function (i) {
			
		$('#comments ul').append('<li><label>' + this.ctext + '</label></li>');
			
	});
	
}

function saveVenueButtonClick() {
	
	$('#savebutton').attr("disabled", "disabled");
	
	//if ($('#captcha_code').val() !== '') {
		editing = false;
		//toggleEditItemsVisibleAnimated(false);
		
		saveVenue();
		
		if (venue.isnew) {
			// get outta venue now plzz;
			setSearchVisibleAnimated(true);
			clearVenue();
			displayAlert("Thanks for the new pinball location!  After it is approved it will show on the map.", $('#searchalert'));
		}
		
	//} else {
	//	alert('Please enter the captcha code to save your edits...');
	//}
	
	disableSaveButtonAnimated(true);
	
}

function saveVenue() {
	var updated = false;
	if (venue.updated || venue.isnew) {
		updated = true;
	} else {
		$(gameList).each(function () {
			if (this.updated || this.deleted) {
				updated = true;
				return false;
			}
		});
		if (!updated) {
			$(commentList).each(function () {
				if (this.isnew) {
					updated = true;
					return false;
				}
			});
		}
	}
	if (updated) {
		postVenueToServer();
	}
}

function editButtonClick(button) {
	
	if (editing) {
		saveVenue();
	}
	
	editing = !editing;
	
	if (editing) {
		$('#editbutton').val("Done");
		enableAutocomplete();
	} else {
		$('#editbutton').val("Edit");
	}
	
	toggleEditItemsVisibleAnimated(true);
	
}

function postVenueToServer() {
	// this method should not create xml but instead form data, oh well...
	var root;
	var loc = '<loc';
	
	if (venue.key) { loc = loc + ' key="' + venue.key + '"'; }
	
	loc = loc + '>';
	
	if (venue.updated) {
		// we are actually gathering the inputs nows;
		if (venue.isnew) {
			loc = loc + "<name>" + $('#name').val() + "</name>";
		}
		loc = loc + "<addr>" + $('#street').val() + "</addr>";
		loc = loc + "<city>" + $('#city').val() + "</city>";
		loc = loc + "<state>" + $('#state').val() + "</state>";
		loc = loc + "<zipcode>" + $('#zipcode').val() + "</zipcode>";
		loc = loc + "<phone>" + $('#phone').val() + "</phone>";
		loc = loc + "<url>" + $('#url').val() + "</url>";
	}
	
	$(gameList).each(function () {
		if (this.deleted || this.updated) {
			
			var game = '<game';
			
			if (this.key) {
				game = game + ' key="' + this.key + '"';
				if (this.deleted) {
					game = game + ' deleted="1"';
				}
				game = game + '>';
			} else {
				if (!this.deleted) {
					game = game + "><abbr>" + this.abbr + "</abbr>";
				} else {
					game = game + ">";
				}
			}
			if (!this.deleted) {
				game = game + "<cond>" + this.cond + "</cond>";
				game = game + "<price>" + this.price + "</price>";
			}
			
			game = game + '</game>';
			
			loc = loc + game;
		}
	});
	
	$(commentList).each(function () {
		if (this.isnew) {
			var c = "<comment><ctext>" + this.ctext + "</ctext></comment>";
			loc = loc + c;;
		}
	});
	
	loc = loc + '</loc>';
	root = '<pinfinderapp><locations>' + loc + '</locations></pinfinderapp>';
		
	//var captcha = $('#captcha_code').val();
	
	$.ajax({
		type: "POST",
		url: pf_app_url,
		cache: false,
		data: {
			locxml: root//,
			//captcha_code: captcha
			}
	}).done(function (data) {
		
		var status = $(data).find('status').text();
		if (status === "success") {
			
			venue.updated = false;
			
			$(gameList).each(function () {
				this.updated = false;
			});
			
			$(commentList).each(function () {
				this.isnew = false;
			});
			
		} else {
			alert("The server encountered an error saving your edits.  Please try again.");
		}
		
	});
	
}


