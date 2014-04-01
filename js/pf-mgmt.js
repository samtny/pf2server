/*global alert, confirm, google, YUI */
"use strict";

var pf_server_url = "./pf";
var pf_mgmt_url = "./pf-mgmt.php";
var ifpa_tournament_root = "http://www.ifpapinball.com/view_tournament.php?t=";

var unapproved = [];
var unapprovedcomments = [];
var flagged = [];
var recent = [];
var addresschanges = [];
var notifications = 0;
var globalnotifications = [];
var upcomingtournaments = [];
var searchresults = [];

var geocoder;

var map;

$(document).ready(function () {
	geocoder = new google.maps.Geocoder();
	getStats();
	getUnapprovedLocs();
	getNotifications();
	getGlobalNotifications();
	getUpcomingTournaments();
	getAddressChanges();
	getUnapprovedComments();
	getRecentlyFlagged();
	getRecentActivity();
});

function getStats() {
	var statsurl = pf_mgmt_url + "?q=stats";
	$.ajax({
		url: encodeURI(statsurl),
		dataType: "json",
		success: handleGetStats,
		error: errorGetLocs
	});
}

function handleGetStats(data) {
	if (data.status == "success") {
		$(".stats ul").empty();
		$(".stats ul").append('<li>venues: ' + data.venues + '</li>');
		$(".stats ul").append('<li>games: ' + data.games + '</li>');
		$(".stats ul").append('<li>users: ' + data.users + '</li>');
		$(".stats ul").append('<li>30-day updates: ' + data.u30day + '</li>');
		$(".stats ul").append('<li>30-day new: ' + data.n30day + '</li>');
	} else {
		alert ("stats refresh failed; no data");
	}
}

function refreshGamedict(sender) {
	
	var refreshurl = pf_mgmt_url + "?q=refreshgamedict";
	
	$.ajax({
		
		url: encodeURI(refreshurl),
		
		dataType: "json",
		
		success: handleRefreshGamedict,
		
		error: errorGetLocs
		
	});
	
}

function handleRefreshGamedict(data) {
	if (data.status == "success") {
		alert ("gamedict.txt refresh successful");
	} else {
		alert ("gamedict.txt refresh failed");
	}
}

function refreshIFPATournaments(sender) {
	
	var refreshurl = pf_mgmt_url + "?q=refreshifpatournaments";
	
	$.ajax({
		
		url: encodeURI(refreshurl),
		
		dataType: "json",
		
		success: handleRefreshIFPATournamentsResult,
		
		error: errorGetLocs
		
	});
	
}

function handleRefreshIFPATournamentsResult(data) {
	if (data.status == "success") {
		getUpcomingTournaments();
                alert('Done!');
	} else {
		alert ("error refreshing IFPA tournaments");
	}
}

function clearSearch() {
    searchresults = [];
    $(".searchvenue").val("");
    refreshSearchResultsDisplay();
}

function searchVenue(sender) {
    
    var searchurl = pf_server_url + "?f=json&t=venue&q=" + $(".searchvenue").val();
    
    $.ajax({
        url: encodeURI(searchurl),
        dataType: "json",
        success: handleSearchVenueResult,
        error: errorGetLocs
    })
    
}

function handleSearchVenueResult(data) {
    
    searchresults = data.venues;
    
    refreshSearchResultsDisplay();
    
}

function refreshSearchResultsDisplay() {
    
    $(".search ul").empty();
    if (searchresults.length > 0) {
        for (var i = 0; i < searchresults.length; i++) {
            
            $(".search ul").append("<li>" + searchresults[i].name + " - (<label onclick=\"populateAssociateVenue(this)\">" + searchresults[i].id + "</label>) - " + searchresults[i].city + ", " + searchresults[i].state + "</li>");
        }
    }
    
}

function getUpcomingTournaments() {
	
	var tourneyurl = pf_mgmt_url + "?q=upcomingtournaments";
	
	$.ajax({
		
		url: encodeURI(tourneyurl),
		
		dataType: "json",
		
		success: handleUpcomingTournamentsResult,
		
		error: errorGetLocs
		
	});
	
}

function handleUpcomingTournamentsResult(data) {
	
	if (data.status == "success") {
		upcomingtournaments = data.tournaments;
	}
	
	refreshUpcomingTournamentsDisplay();
	
}

function refreshUpcomingTournamentsDisplay() {
	
	$(".tournaments ul").empty();
	$.each(upcomingtournaments, function () {
		if (!this.venueId) {
			$(".tournaments ul").append('<li><b><a href="' + ifpa_tournament_root + this.ifpaId + '" target="_blank"><label>' + this.name + '</label></a> - <label>' + this.dateFrom + '</label></b> - <label onclick="populateAssociateVenue(this)">(' + this.priorVenueId + ')</label> - <input type="button" value="Apply" onclick="applyTournamentVenue(' + this.id + ')"></input><input type="button" value="Omit" onclick="omitTournament(' + this.id + ')"></input></li>');
		}
	});
	
}

function setTourneyNotify() {
    $(".globalmessage").val("Check out upcoming pinball tournaments near you!");
    $(".globalextra").val("t=special&q=upcomingtournaments");
}

function populateAssociateVenue(sender) {
	$('.tourneyvenue').val($(sender).text().replace('(', '').replace(')', ''));
}

function omitTournament(tournamentid) {
	
	$.ajax({
		type: "POST",
		url: "pf-mgmt.php",
		cache: false,
		data: {
			action: "omittournament",
			tournamentid: tournamentid
			}
	}).done(function (data) {
		
		var status = $(data).find('status').text();
		if (status === "success") {
			getUpcomingTournaments();
		} else {
			alert("Error omitting tournament.");
		}
		
	});
	
}

function applyTournamentVenue(tournamentid) {
	
	var venueid = $('input.tourneyvenue').val();
	
	$.ajax({
		type: "POST",
		url: "pf-mgmt.php",
		cache: false,
		data: {
			action: "associatetournamentvenue",
			tournamentid: tournamentid,
			venueid: venueid
			}
	}).done(function (data) {
		
		var status = $(data).find('status').text();
		if (status === "success") {
			$('input.tourneyvenue').val('');
			getUpcomingTournaments();
		} else {
			alert("Error applying tournament venue.");
		}
		
	});
	
}

function getGlobalNotifications() {
	
	var notifurl = pf_mgmt_url + "?q=globalnotifications";
	
	$.ajax({
		
		url: encodeURI(notifurl),
		
		dataType: "json",
		
		success: handleGlobalNotificationsResult,
		
		error: errorGetLocs
		
	});
	
}

function handleGlobalNotificationsResult(data) {
	
	if (data.status == "success") {
		globalnotifications = data.notifications_pending;
	}
	
	refreshGlobalNotificationsDisplay();
	
}

function refreshGlobalNotificationsDisplay() {
	
	$(".globalnotify ul").empty();
	$("input.globalmessage").val("");
	$("input.globalextra").val("");
	$.each(globalnotifications, function () {
		$(".globalnotify ul").append('<li><label>"' + this.message + '"</label> - <label>"' + this.extra + '"</label> - <input type="button" value="X" onclick="deleteGlobalNotification(this, ' + this.id + ')"></input></li>');
	});
	
}

function deleteGlobalNotification(sender, key) {
	
	$(sender).attr('disabled', 'disabled');
	
	$.ajax({
		type: "POST",
		url: "pf-mgmt.php",
		cache: false,
		data: {
			action: "deleteglobalnotification",
			key: key
			}
	}).done(function (data) {
		
		var status = $(data).find('status').text();
		if (status === "success") {
			getNotifications();
			getGlobalNotifications();
		} else {
			alert("Error deleting global notification.");
		}
		
	});
	
}

function getNotifications() {
	
	var notifurl = pf_server_url + "?t=stats";
	
	$.ajax({
		
		url: encodeURI(notifurl),
		
		dataType: "xml",
		
		success: handleNotificationsResult,
		
		error: errorGetLocs
		
	});
	
}

function handleNotificationsResult(xml) {
	
	var status = $(xml).find("status").text();
	if (status === "success") {
		
		notifications = $(xml).find("stats").attr("notifications")
		
	} else {
		alert("status: " + status);
	}
	refreshNotificationsDisplay();
	
}

function refreshNotificationsDisplay() {
		
	if (notifications > 0) {
		$('.notifications_pending h3').text("Pending Notifications (" + notifications + ')');
		$('.notifications_pending input').show();
		$('.notifications_pending input').removeAttr("disabled");
	} else {
		$('.notifications_pending h3').text("Pending Notifications (None)");
		$('.notifications_pending input').hide();
	}
	
}

function sendNotifications(sender) {
	
	$(sender).attr("disabled", "disabled");
	
	$.ajax({
		type: "POST",
		url: "pf-mgmt.php",
		cache: false,
		data: {
			action: "sendnotifications"
			}
	}).done(function (xml) {
		
		var status = $(xml).find('status').text();
		if (status === "success") {
			getNotifications();
			getGlobalNotifications();
		} else {
			alert("Error sending notifications_pending: " + status);
		}
		
	});
	
}

function addGlobalNotification(sender) {
	
	$(sender).attr("disabled", "disabled");
	
	var msg = $("input.globalmessage").val();
	var xtr = $("input.globalextra").val();
	
	$.ajax({
		type: "POST",
		url: "pf-mgmt.php",
		cache: false,
		data: {
			action: "saveglobalnotification",
			message: msg,
			extra: xtr
			}
	}).done(function (xml) {

		var status = $(xml).find('status').text();
		if (status === "success") {
			getNotifications();
			getGlobalNotifications();
		} else {
			alert("Error adding global notification: " + status);
		}
		
	});
	
}

function getAddressChanges() {
	
	var changeurl = pf_server_url + "?t=mgmt&q=addresschanged";
	
	$.ajax({
		
		url: encodeURI(changeurl),
		
		dataType: "xml",
		
		success: handleAddressChangesResult,
		
		error: errorGetLocs
		
	});
	
}

function handleAddressChangesResult(xml) {
	
	var status = $(xml).find("status").text();
	if (status === "success") {
		addresschanges = locXmlToVenues(xml);
	} else if (status === "nomatch") {
		addresschanges = [];
	} else {
		alert("status: " + status);
	}
	refreshAddressChangesDisplay();
	
}

var curVenue;

function mapVenue(sender) {
    venue = unapproved[$(sender).index()];
    if (!curVenue || curVenue != venue) {
        var center = venue.lat ? new google.maps.LatLng(venue.lat, venue.lon) : venue.street + ', ' + venue.city + ' ' + venue.state;
        var mapOptions = {
            center:     center,
            zoom:       19,
            mapTypeId:  google.maps.MapTypeId.ROADMAP
        };
        map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
        $('#map-canvas').show();
        curVenue = venue;
    }
}

function refreshAddressChangesDisplay() {
	
	$(".addresschanges table tbody").empty();
	if (addresschanges.length > 0) {
		for (var i = 0; i < addresschanges.length; i++) {
			$(".addresschanges table tbody").append("<tr><td><input type=\"hidden\" value=\"" + addresschanges[i].key + "\" /><input type=\"text\" value=\"" + addresschanges[i].name + "\" /></td><td><input type=\"text\" value=\"" + addresschanges[i].street + "\" /></td><td><input type=\"text\" value=\"" + addresschanges[i].city + "\" /></td><td><input type=\"text\" value=\"" + addresschanges[i].state + "\" /></td><td><input type=\"text\" value=\"" + addresschanges[i].zipcode + "\" /></td><td><input type=\"text\" value=\"" + addresschanges[i].phone + "\" /></td><td><input type=\"text\" value=\"" + addresschanges[i].url + "\" /></td><td><input type=\"hidden\" name=\"lat\" value=\"" + addresschanges[i].lat + "\" /><input type=\"hidden\" name=\"lon\" value=\"" + addresschanges[i].lon + "\" /><a href=\"http://maps.google.com?q=" + addresschanges[i].lat + "," + addresschanges[i].lon + "\" target=\"map\">" + addresschanges[i].lat + "," + addresschanges[i].lon + "</a></td><td>" + addresschanges[i].games.length + "</td><td><input type=\"button\" value=\"Update\" onclick=\"saveVenueButtonClick(this)\" /><input type=\"button\" value=\"Approve\" onclick=\"approveAddressChangeButtonClick(this, " + addresschanges[i].key + ")\" /><input type=\"button\" value=\"X\" onclick=\"deleteVenue(this, " + addresschanges[i].key + ")\" /></td></tr>");
		}
		$('.addresschanges h3').text("Address Changed (" + addresschanges.length + ')');
		$('.addresschanges table').show();
	} else {
		$('.addresschanges h3').text("Address Changed (None)");
		$('.addresschanges table').hide();
	}
	
}

function getRecentlyFlagged() {
	
	var flaggedurl = pf_server_url + "?q=flagged&t=mgmt&l=10";
	
	$.ajax({
		
		url: encodeURI(flaggedurl),
		
		dataType: "xml",
		
		success: handleRecentlyFlaggedResult,
		
		error: errorGetLocs
		
	});
	
}

function handleRecentlyFlaggedResult(xml) {
	
	var status = $(xml).find("status").text();
	if (status === "success") {
		flagged = locXmlToVenues(xml);
	} else {
		alert("status: " + status);
	}
	refreshRecentlyFlaggedDisplay();
	
}

function refreshRecentlyFlaggedDisplay() {
	
	$(".flagged ul").empty();
	if (flagged.length > 0) {
		for (var i = 0; i < flagged.length; i++) {
			var venue = flagged[i];
			$(".flagged ul").append("<li>" + venue.date + " - <span class=\"bold\">" + venue.name + "</span> - " + venue.city + ", " + venue.state + " - " + "<span class=\"bold\">(" + venue.flag + ")</span>" + "</li>");
		}
	}
	
}

function getRecentActivity() {
	
	var recenturl = pf_server_url + "?l=25";
	
	$.ajax({
		
		url: encodeURI(recenturl),
		
		dataType: "xml",
		
		success: handleRecentActivityResult,
		
		error: errorGetLocs
		
	});
	
}

function handleRecentActivityResult(xml) {
	
	var status = $(xml).find("status").text();
	if (status === "success") {
		recent = locXmlToVenues(xml);
	} else {
		alert("status: " + status);
	}
	refreshRecentActivityDisplay();
	
}

function refreshRecentActivityDisplay() {
	
	$(".recent ul").empty();
	if (recent.length > 0) {
		for (var i = 0; i < recent.length; i++) {
			var venue = recent[i];
			$(".recent ul").append("<li>" + venue.date + " - <span class=\"bold\">" + venue.name + "</span> - " + venue.city + ", " + venue.state + "</li>");
		}
	}
	
}

function getUnapprovedComments() {
	
	$('.newcomments h3').text("New Comments (Updating...)");
	
	var lookupurl = pf_server_url + "?t=mgmt&q=unapprovedcomment";
	
	$.ajax({

		url: encodeURI(lookupurl),
	
		dataType: "xml",

		success: handleUnapprovedCommentsResult,

		error: errorGetLocs

	});
	
}

function getUnapprovedLocs() {
	
	$('.unapproved h3').text("New Venues (Updating...)");
	
	var lookupurl = pf_server_url + "?t=mgmt&q=unapproved";
	
	$.ajax({

		url: encodeURI(lookupurl),
	
		dataType: "xml",

		success: handleUnapprovedLocsResult,

		error: errorGetLocs

	});
	
}

function handleUnapprovedCommentsResult(xml) {
	var status = $(xml).find("status").text();
	if (status === "success") {
		unapprovedcomments = locXmlToVenues(xml);
	} else if (status === "nomatch") {
		unapprovedcomments = [];
	} else {
		alert("status: " + status);
	}
	refreshUnapprovedCommentsDisplay();
}

function handleUnapprovedLocsResult(xml) {
	
	var status = $(xml).find("status").text();
	if (status === "success") {
		unapproved = locXmlToVenues(xml);
	} else if (status === "nomatch") {
		unapproved = [];
	} else {
		alert("status: " + status);
	}
	refreshUnapprovedDisplay();
	
}

function refreshUnapprovedCommentsDisplay() {
	$(".newcomments table tbody").empty();
	if (unapprovedcomments.length > 0) {
		var count = 0;
		for (var i = 0; i < unapprovedcomments.length; i++) {
			var venue = unapprovedcomments[i];
			for (var j = 0; j < venue.unapproved_comments.length; j++) {
				var comment = venue.unapproved_comments[j];
				$(".newcomments table tbody").append("<tr><td><input type=\"hidden\" value=\"" + comment.key + "\" /><label>" + comment.ctext + "</label></td><td><label>" + venue.name + "</label></td><td><input type=\"button\" value=\"Approve\" onclick=\"approveComment(this, " + comment.key + ")\" /><input type=\"button\" value=\"Delete\" onclick=\"deleteComment(this, " + comment.key + ")\" /><input type=\"button\" value=\"Flag Venue\" onclick=\"flagVenue(this, " + venue.key + ")\" /></td></tr>");
				count++;
			}
		}
		$('.newcomments h3').text("New Comments (" + count + ")");
		$('.newcomments table').show();
	} else {
		$('.newcomments h3').text("New Comments (None)");
		$('.newcomments table').hide();
	}
}

function deleteComment(sender, key) {
	$(sender).attr("disabled", "disabled");
	
	$.ajax({
		type: "POST",
		url: "pf-mgmt.php",
		cache: false,
		data: {
			action: "deletecomment",
			key: key
			}
	}).done(function (data) {
		
		var status = $(data).find('status').text();
		if (status === "success") {
			getUnapprovedComments();
		} else {
			alert("Error deleting comment.");
		}
		
	});
	
}

// this is the correct one;
function approveComment(sender, key) {
	$(sender).attr("disabled", "disabled");
	
	$.ajax({
		type: "POST",
		url: "pf-mgmt.php",
		cache: false,
		data: {
			action: "approvecomment",
			key: key
			}
	}).done(function (data) {
		
		var status = $(data).find('status').text();
		if (status === "success") {
			getUnapprovedComments();
		} else {
			alert("Error approving comment.");
		}
		
	});
	
}

function approveAddressChangeButtonClick(sender, key) {

	$(sender).attr("disabled", "disabled");
	
	$.ajax({
		type: "POST",
		url: "pf-mgmt.php",
		cache: false,
		data: {
			action: "approveaddresschange",
			key: key
			}
	}).done(function (data) {
		
		var status = $(data).find('status').text();
		if (status === "success") {
			getAddressChanges();
		} else {
			alert("Error approving address change.");
		}
		
	});

}

function flagVenue(sender, key) {
	$(sender).attr("disabled", "disabled");
	
	// usually flagged from unapproved_comments;
	for (var i = 0; i < unapprovedcomments.length; i++) {
		var venue = unapprovedcomments[i];
		if (venue.key == key) {
			venue.isflagged = true;
			saveVenue(venue);
			return false;
		}
	}
	
}

function refreshUnapprovedDisplay() {
	$(".unapproved table tbody").empty();
	if (unapproved.length > 0) {
		for (var i = 0; i < unapproved.length; i++) {
			$(".unapproved table tbody").append("<tr onClick=\"mapVenue(this)\"><td><input type=\"hidden\" value=\"" + unapproved[i].key + "\" /><input type=\"text\" value=\"" + unapproved[i].name + "\" /></td><td><input type=\"text\" value=\"" + unapproved[i].street + "\" /></td><td><input type=\"text\" value=\"" + unapproved[i].city + "\" /></td><td><input type=\"text\" value=\"" + unapproved[i].state + "\" /></td><td><input type=\"text\" value=\"" + unapproved[i].zipcode + "\" /></td><td><input type=\"text\" value=\"" + unapproved[i].phone + "\" /></td><td><input type=\"text\" value=\"" + unapproved[i].url + "\" /></td><td><input type=\"hidden\" name=\"lat\" value=\"" + unapproved[i].lat + "\" /><input type=\"hidden\" name=\"lon\" value=\"" + unapproved[i].lon + "\" /><a href=\"http://maps.google.com?q=" + unapproved[i].lat + "," + unapproved[i].lon + "\" target=\"map\">" + unapproved[i].lat + "," + unapproved[i].lon + "</a></td><td>" + unapproved[i].games.length + "</td><td><input type=\"button\" value=\"Update\" onclick=\"saveVenueButtonClick(this)\" /><input type=\"button\" value=\"Approve\" onclick=\"approveVenueButtonClick(this)\" /><input type=\"button\" value=\"X\" onclick=\"deleteVenue(this, " + unapproved[i].key + ")\" /></td></tr>");
		}
		$('.unapproved h3').text("New Venues (" + unapproved.length + ')');
		$('.unapproved table').show();
	} else {
		$('.unapproved h3').text("New Venues (None)");
		$('.unapproved table').hide();
                $('#map-canvas').hide();
	}
}

// yep, this is the correct way again;
function deleteVenue(sender, key) {
	
	$(sender).attr("disabled", "disabled");
	
	$.ajax({
		type: "POST",
		url: "pf-mgmt.php",
		cache: false,
		data: {
			action: "deletevenue",
			key: key
			}
	}).done(function (data) {
		
		var status = $(data).find('status').text();
		if (status === "success") {
			getUnapprovedLocs();
		} else {
			alert("Error deleting venue.");
		}
		
	});
	
}

function reverseGeocodeVenueButtonClick(sender) {
	
	$(sender).attr("disabled", "disabled");
	
	var index = sender.parentNode.parentNode.rowIndex;
	
	var venue = unapproved[index-1];
	
	var latlng = new google.maps.LatLng(venue.lat, venue.lon);
    geocoder.geocode({'latLng': latlng}, function(results, status) {
      if (status == google.maps.GeocoderStatus.OK) {
        if (results[1]) {
          //alert(results[1].formatted_address);
		  //alert(results[1]);
		  var state = "";
		  var city = "";
		  var zipcode = "";
		  for (var i = 0; i < results[0].address_components.length; i++) {
			for (var j = 0; j < results[0].address_components[i].types.length; j++) {
				if (results[0].address_components[i].types[j] == "postal_code") {
					zipcode = results[0].address_components[i].short_name;
				} else if (results[0].address_components[i].types[j] == "locality") {
					city = results[0].address_components[i].long_name;
				} else if (results[0].address_components[i].types[j] == "administrative_area_level_1") {
					state = results[0].address_components[i].long_name;
				}
			}
		  }
		  //alert(city + ', ' + state + ' ' + zipcode);
		  venue.city = city;
		  venue.state = state;
		  venue.zipcode = zipcode;
		  refreshUnapprovedDisplay();
        }
      } else {
        alert("Geocoder failed due to: " + status);
      }
    });

	
}

function approveVenueButtonClick(sender) {
	$(sender).attr("disabled", "disabled");
	var index = sender.parentNode.parentNode.rowIndex;
	var venue = unapprovedRowToVenue(index);
	venue.isapproved = true;
	saveVenue(venue);
}

function saveVenueButtonClick(sender) {
	
	$(sender).attr("disabled", "disabled");
	
	var index = sender.parentNode.parentNode.rowIndex;
	
	var venue = unapprovedRowToVenue(index);
	
	//if (venue.lat && venue.lon) {
	//	saveVenue(venue);
	//} else {
		geocodeAndSaveVenue(venue);
	//}
	
}

function venueToUnapprovedRow(venue) {
	
	for (var i = 0; i < unapproved.length; i++) {
		if (unapproved[i].key === venue.key) {
			unapproved[i].lat = venue.lat;
			unapproved[i].lon = venue.lon;
			return false;
		}
	}
	
}

function unapprovedRowToVenue(index) {
	
	var row = $(".unapproved tr").eq(index);
	
	var key = row.find("td").eq(0).find("input").eq(0).val();
	var name = row.find("td").eq(0).find("input").eq(1).val();
	
	var street = row.find("td").eq(1).find("input").eq(0).val();
	var city = row.find("td").eq(2).find("input").eq(0).val();
	var state = row.find("td").eq(3).find("input").eq(0).val();
	var zip = row.find("td").eq(4).find("input").eq(0).val();
	var phone = row.find("td").eq(5).find("input").eq(0).val();
	var url = row.find("td").eq(6).find("input").eq(0).val();
	
	var lat = row.find("td").eq(7).find("input").eq(0).val();
	var lon = row.find("td").eq(7).find("input").eq(1).val();
	
	return new Venue(key, name, street, city, state, zip, phone, lat, lon, url, true, true);
	
}

function saveVenue(venue) {
	
	var locxml = venuesToLocXml(venue);
	var payload = locxml;
	//alert(payload);
	
	var action = venue.isapproved == true ? "approvevenue" : "updatevenue";
	
	$.ajax({
		type: "POST",
		url: "pf-mgmt.php",
		cache: false,
		data: {
			locxml: payload,
			action: action
			}
	}).done(function (data) {
		
		var status = $(data).find('status').text();
		if (status === "success") {
			getUnapprovedLocs();
			getUnapprovedComments();
			getAddressChanges();
                        getNotifications();
                        getRecentActivity();
		} else {
			alert("Error saving venue " + status);
		}
		
	});
	
}

function geocodeAndSaveVenue(venue) {
	
	var address = venue.street + ' ' + venue.city + ' ' + venue.state;
	
	geocoder.geocode( { 'address': address}, function( results, status ) {
		
		if (status == google.maps.GeocoderStatus.OK) {
			
			venue.lat = results[0].geometry.location.lat();
			venue.lon = results[0].geometry.location.lng();
			
			// now reverse it;
			var latlng = new google.maps.LatLng(venue.lat, venue.lon);
			geocoder.geocode({'latLng': latlng}, function(results, status) {
			  if (status == google.maps.GeocoderStatus.OK) {
				
				if (results[1]) {
				  //alert(results[1].formatted_address);
				  //alert(results[1]);
				  var state = "";
				  var city = "";
				  var zipcode = "";
				  for (var i = 0; i < results[0].address_components.length; i++) {
					for (var j = 0; j < results[0].address_components[i].types.length; j++) {
						if (results[0].address_components[i].types[j] == "postal_code") {
							zipcode = results[0].address_components[i].short_name;
						} else if (results[0].address_components[i].types[j] == "locality") {
							city = results[0].address_components[i].long_name;
						} else if (results[0].address_components[i].types[j] == "administrative_area_level_1") {
							state = results[0].address_components[i].long_name;
						}
					}
				  }
				  venue.city = city;
				  venue.state = state;
				  venue.zipcode = zipcode;
				  saveVenue(venue);
				}
			  } else {
				// just save venue;
				saveVenue(venue);
			  }
			});
			
		} else {
			// geocode failed, just save venue;
			saveVenue(venue);
		}
		
	});
	
}

function errorGetLocs(jqXHR, textStatus, errorThrown) {
	alert('There was a server error getting location data: ' + errorThrown);
}