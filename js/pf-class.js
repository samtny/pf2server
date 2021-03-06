function Venue(k, n, a, c, s, z, p, lat, lon, u, updated, isnew, isapproved, isflagged, date, f, source) {
	this.key = k;
	this.name = n;
	this.street = a;
	this.city = c;
	this.state = s;
	this.zipcode = z;
	this.phone = p;
	this.lat = lat;
	this.lon = lon;
	this.date = date;
	this.source = source;
	this.url = u;
	this.updated = updated;
	this.isnew = isnew;
	this.isapproved = isapproved;
	this.isflagged = isflagged;
	this.games = [];
	this.unapproved_comments = [];
	this.flag = f;
}

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

function Comment(t, d, n, k) {
	this.ctext = t;
	this.date = d;
	this.isnew = n;
	this.key = k;
}

function locXmlToVenues(xml) {
	result = [];
	if (xml) {
		gameDict = $(xml).find("dict");
		$(xml).find("loc").each(function (index) {
			var key = $(this).attr("key");
			var flag = $(this).attr("flag");
			var name = $(this).find("name").text();
			var street = $(this).find("addr").text();
			var city = $(this).find("city").text();
			var state = $(this).find("state").text();
			var zipcode = $(this).find("zipcode").text();
			var phone = $(this).find("phone").text();
			var dte = $(this).find("date").text();
			var lat = $(this).find("lat").text();
			var lon = $(this).find("lon").text();
			var url = $(this).find("url").text();
			
			venue = new Venue(key, name, street, city, state, zipcode, phone, lat, lon, url, false, false, false, false, dte, flag);
			
			$(this).find("game").each(function () {
				key = $(this).attr('key');
				var abbr = $(this).find('abbr').text();
				var desc = $(gameDict).find('[key=' + abbr + ']').text();
				var cond = $(this).find('cond').text();
				var price = $(this).find('price').text();
				var ipdb = $(this).find('ipdb').text();
				var game = new Game(key, abbr, desc, cond, price, ipdb, false, false);
				venue.games.push(game);
			});
			
			$(this).find("comment").each(function () {
				var ctext = $(this).find('ctext').text();
				var cdate = $(this).find('cdate').text();
				var ckey = $(this).attr('key');
				var comment = new Comment(ctext, cdate, false, ckey);
				venue.unapproved_comments.push(comment);
			});
			
			result.push(venue);
			
		});
	}
	return result;
}

function venuesToLocXml(venue) {
	
	var root;
	
	if (venue) {
		var loc = '<loc';
		if (venue.key) {
			loc = loc + ' key="' + venue.key + '"';
		}
		if (venue.isflagged === true) {
			loc = loc + ' flag="1"';
		}
		loc = loc + '>';
		if (venue.name !== null) {
			loc = loc + "<name>" + htmlSpecialChars(venue.name) + "</name>";
		}
		if (venue.street !== null) {
			loc = loc + "<addr>" + htmlSpecialChars(venue.street) + "</addr>";
		}
		if (venue.city !== null) {
			loc = loc + "<city>" + htmlSpecialChars(venue.city) + "</city>";
		}
		if (venue.state !== null) {
			loc = loc + "<state>" + htmlSpecialChars(venue.state) + "</state>";
		}
		if (venue.zipcode !== null) {
			loc = loc + "<zipcode>" + htmlSpecialChars(venue.zipcode) + "</zipcode>";
		}
		if (venue.phone !== null) {
			loc = loc + "<phone>" + htmlSpecialChars(venue.phone) + "</phone>";
		}
		if (venue.url !== null) {
			loc = loc + "<url>" + htmlSpecialChars(venue.url) + "</url>";
		}
		if (venue.lat !== null) {
			loc = loc + "<lat>" + venue.lat + "</lat>";
		}
		if (venue.lon !== null) {
			loc = loc + "<lon>" + venue.lon + "</lon>";
		}
		loc = loc + '</loc>';
		root = '<pinfinderapp><locations>' + loc + '</locations></pinfinderapp>';
	}
	
	return root;
	
}

function htmlSpecialChars(unsafe) {
    return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}