<?php include('pf-session.php'); ?>
<html>
  <head>
    <title>PinballFinder Management Interface</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="css/pf-mgmt2.css" rel="stylesheet">
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
  </head>
  <body>
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#pf-navbar-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#/home">Pinfinder Management</a>
        </div>
        <div class="collapse navbar-collapse" id="pf-navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="#/notifications">Notifications</a></li>
            <li><a href="#/search">Search</a></li>
            <li><a href="#/game/new">Add Game</a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a data-bind="attr: { href: mailto }" target="_top">Contact</a></li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span data-bind="text: about">About</span> <b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a href="#/login">Login</a></li>
                <li><a href="#/faq">FAQ</a></li>
                <li><a href="#/code">Source Code</a></li>
                <li><a href="#/terms">Terms of Use</a></li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row">
        <div class="col-xs-12">
          <div class="content">
            <div id="home">
              <!-- ko template: 'stats' --><!-- /ko -->
              <div class="row">
                <div class="col-xs-12 col-lg-6">
                  <!-- ko template: 'unapproved_venues' --><!-- /ko -->
                  <!-- ko template: 'recent_venues' --><!-- /ko -->
                </div>
                <!-- ko template: 'unapproved_comments' --><!-- /ko -->
              </div>
            </div>

            <div class="form-group" id="search">
              <!-- ko template: 'search_venue' --><!-- /ko -->
            </div>

            <div class="form-group" id="notifications">
              <!-- ko template: 'notification_edit' --><!-- /ko -->
              <!-- ko template: 'notifications_pending' --><!-- /ko -->
            </div>

            <div class="form-group" id="venue_edit" data-bind="template: { if: venue, name: 'venue', data: venue }"></div>
            <div class="form-group" id="game_edit" data-bind="template: { if: game, name: 'game', data: game }"></div>
            <div class="form-group" id="user_edit" data-bind="template: { if: user, name: 'user', data: user }"></div>

            <div class="form-group" id="login">
              <h4>Login to Pinfinder Pinball Finder</h4>
              <p>
                Choose a service below to login to Pinball Finder:
              </p>
              <div id="fb-root"></div>
              <div class="form-group">
                Facebook: <fb:login-button size="medium"></fb:login-button>
              </div>
              <div class="form-group">
                Foursquare: <a href="https://foursquare.com/oauth2/authenticate?client_id=32ELRIRFDEMAAHSPKAAITTQGMZKXAOBBRJ1EE05ULH2A3CUV&response_type=code&redirect_uri=http://local.pinballfinder.org/pf2/pf-fsq2.php"><img src="https://playfoursquare.s3.amazonaws.com/press/logo/connect-black.png" alt="Connect to Foursquare" /></a>
              </div>
            </div>

            <div class="form-group" id="code">
              <!-- ko template: 'github_commits' --><!-- /ko -->
            </div>

            <div class="form-group" id="terms">
              <h3>Terms of Use</h3>
              <p>
                By using this website, you agree to the following; Nothing.  It is a website, not a marketplace.
              </p>
              <h4>Privacy Policy</h4>
              <p>
                Your privacy is important enough that we feel we should be clear about how we use any data you submit to this website:
              </p>
              <p>
                We do not knowingly keep or collect any personal information about you.  You should be aware, however, that like all web servers our server does keep normal access logs that may include the date, time, IP Address and additional information about your connection to Pinfinder Pinball Finder.
              </p>
              <h4>Social Media</h4>
              <p>
                When you choose to login to this site using Facebook or any social network, we store your (<a href="https://www.facebook.com/me">already publicly available</a>) 'id' for that service.  This helps us track how you are using Pinfinder Pinball Finder, so we can give you badges and other cool stuff that can only have a "Positive Effect On Your Life"&reg;.  We do not collect any other information about you.  To help you feel completely comfortable with this, or at least understand what we are collecting, here is the (again, already publicly available) Facebook id of the creator of this site; 1430209517.  There.  Now if we do anything with your information that you don't like, you can do it right back to us.  Tit-for-tat.
              </p>
              <p>
                As a side note, you may be interested to know that most of these social networks do TRY to cram a bunch of your personal information down our throats when you login here.  Facebook Login, for example, wants to give us your; full name, hometown, email address, location, username and gender.  But, like some kind of biblical samaritan, we quietly reject this information and turn the other butt-cheek.  We don't store it or use it, because we are not interested in it.  But we do feel obligated to mention it here.
              </p>
              <h4>Partners, Third Parties</h4>
              <p>
                No, we do not currently have any partners or 'third-parties' with whom we share any information.
              </p>
              <h4>Advertising</h4>
              <p>
                Nope.  We don't carry any avertising, and we don't share any of your information with advertisers.
              </p>
              <h4>Cookies</h4>
              <p>
                We don't set any cookies.  Another miracle.
              </p>
              <p>
                None of this is a joke; your privacy is important to us.  That's why we put it right here.
              </p>
              <p>
                -Pinfinder Team
              </p>
            </div>
          </div>
        </div>
      </div>
      <hr>
      <footer>
        <p>PinballFinder.org</p>
      </footer>
    </div>

    <!-- stats -->
    <script type="text/html" id="stats">
      <div class="list-group" data-bind="with: stats">
        <a class="list-group-item link">
          <div class="row">
            <div class="col-xs-6 col-sm-2"><span class="h5">Venues: </span><small data-bind="text: parseInt(venues).toLocaleString()"></small></div>
            <div class="col-xs-6 col-sm-2"><span class="h5">New: </span><small data-bind="text: parseInt(n30day).toLocaleString()"></small></div>
            <div class="col-xs-6 col-sm-2"><span class="h5">Updated: </span><small data-bind="text: parseInt(u30day).toLocaleString()"></small></div>
            <div class="col-xs-6 col-sm-2"><span class="h5">Users: </span><small data-bind="text: parseInt(users).toLocaleString()"></small></div>
            <div class="col-xs-6 col-sm-2"><span class="h5">Machines: </span><small data-bind="text: parseInt(machines).toLocaleString()"></small></div>
          </div>
        </a>
      </div>
    </script>

    <!-- unapproved_venues -->
    <script type="text/html" id="unapproved_venues">
      <!-- ko if: unapproved_venues().length -->
      <h4>Approve</h4>
      <!-- ko template: { name: 'venue_list', data: unapproved_venues } --><!-- /ko -->
      <!-- /ko -->
    </script>

    <!-- recent_venues -->
    <script type="text/html" id="recent_venues">
      <!-- ko if: recent_venues().length -->
      <h4>Recent</h4>
      <!-- ko template: { name: 'venue_list', data: recent_venues } --><!-- /ko -->
      <!-- /ko -->
    </script>

    <!-- venue_list -->
    <script type="text/html" id="venue_list">
      <div class="list-group" data-bind="foreach: $data">
        <a class="list-group-item link" data-bind="click: $root.venue"><span class="h5" data-bind="text: name"></span><small data-bind="text: addressLong()"></small></a>
      </div>
    </script>

    <!-- comment_list -->
    <script type="text/html" id="comment_list">
      <div class="form-group">
        <div class="list-group" data-bind="foreach: $data">
          <a class="list-group-item link"><span class="h4" data-bind="text: name"></span><small data-bind="text: text"></small></a>
        </div>
      </div>
    </script>

    <!-- unapproved_comments -->
    <script type="text/html" id="unapproved_comments">
      <h4 data-bind="visible: unapproved_comments().length">Comments</h4>
      <!-- ko template: { if: unapproved_comments().length, name: 'comment_list', data: unapproved_comments } --><!-- /ko -->
    </script>

    <!-- notifications_pending -->
    <script type="text/html" id="notifications_pending">
      <!-- ko if: notifications_pending().length -->
      <h4>Pending Notifications</h4>
      <!-- ko template: { name: 'notification_list', data: notifications_pending } --><!-- /ko -->
      <!-- /ko -->
      <div class="form-group">
        <button type="button" class="btn btn-default" data-bind="click: $root.newNotification">New</button>
        <button type="button" class="btn btn-default" data-bind="click: $root.sendNotifications">Send All</button>
        <button type="button" class="btn btn-default" data-bind="click: $root.cleanNotifications">Clean</button>
      </div>
    </script>

    <!-- notification list -->
    <script type="text/html" id="notification_list">
      <div class="form-group">
        <div class="list-group" data-bind="foreach: $data">
          <a class="list-group-item link" data-bind="click: $root.notification"><span class="h4" data-bind="text: text"></span></a>
        </div>
      </div>
    </script>

    <!-- notification_edit -->
    <script type="text/html" id="notification_edit">
      <div class="form-group" data-bind="with: notification">
        <div class="form-group">
          <label for="notificationText">Notification Text</label>
          <textarea class="form-control" id="notificationText" data-bind="value: text" rows="3" placeholder="Notification text"></textarea>
        </div>
        <div class="form-group">
          <label for="notificationExtra">Extra</label>
          <input type="text" class="form-control" id="notificationExtra" placeholder="Extra Data" data-bind="value: extra">
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" data-bind="checked: global">Global
          </label>
        </div>
        <div class="form-group" data-bind="visible: !global()">
          <label for="notificationUser">User Id</label>
          <input type="text" class="form-control" id="notificationUser" placeholder="User Id" data-bind="value: touserid">
        </div>
        <button type="button" class="btn btn-default" data-bind="click: $parent.saveNotification">Save</button>
        <button type="button" class="btn btn-default" data-bind="click: $parent.cancelNotification">Cancel</button>
        <button type="button" class="btn btn-default pull-right" data-bind="click: $parent.deleteNotification">Delete</button>
      </div>
    </script>

    <!-- venue -->
    <script type="text/html" id="venue">
      <div class="row">
        <div class="col-xs-12 col-md-6">
          <div class="form-group">
            <label for="venueName">Venue</label>
            <input type="text" class="form-control" id="venueName" placeholder="Venue Name" data-bind="value: name">
          </div>
          <div class="form-group">
            <label for="venueStreet">Street</label>
            <input type="text" class="form-control" id="venueStreet" placeholder="Street" data-bind="value: street">
          </div>
          <div class="form-group">
            <label for="venueCity">City</label>
            <input type="text" class="form-control" id="venueCity" placeholder="City" data-bind="value: city">
          </div>
          <div class="form-group">
            <label for="venueState">State</label>
            <input type="text" class="form-control" id="venueState" placeholder="State" data-bind="value: state">
          </div>
          <div class="form-group">
            <label for="venueZipcode">Zipcode</label>
            <input type="text" class="form-control" id="venueZipcode" placeholder="Zipcode" data-bind="value: zipcode">
          </div>
        </div>
        <div class="col-xs-12 col-md-6">
          <div class="form-group">
            <label for="map-canvas">Map</label>
            <div id="map-canvas"></div>
          </div>
          <div class="form-group">
            <label for="venuePhone">Phone</label>
            <input type="tel" class="form-control" id="venuePhone" placeholder="Phone" data-bind="value: phone">
          </div>
          <div class="form-group">
            <label for="venueURL">URL</label>
            <input type="url" class="form-control" id="venueURL" placeholder="URL" data-bind="value: url">
          </div>
          <div class="form-group">
            <label for="venueFsq">Foursquare Id</label>
            <input type="text" class="form-control" id="venueFsq" placeholder="Foursquare Id" data-bind="value: fsq">
          </div>
        </div>
      </div>
      <div class="checkbox">
        <label>
          <input type="checkbox" data-bind="checked: approved">Approve
        </label>
      </div>
      <button type="button" class="btn btn-default" data-bind="click: $parent.saveVenue">Save</button>
      <button type="button" class="btn btn-default" data-bind="click: $parent.geocodeVenue">Geocode</button>
      <button type="button" class="btn btn-default pull-right" data-bind="click: $parent.deleteVenue">Delete</button>
    </script>

    <!-- game -->
    <script type="text/html" id="game">
      <div class="form-group">
        <label for="gameName">New Game</label>
        <input type="text" class="form-control" id="gameName" placeholder="Game Name" data-bind="value: name">
      </div>
      <div class="form-group">
        <label for="gameCo">Manufacturer</label>
        <select class="form-control" id="gameCo" data-bind="options: $parent.manufacturers, optionsText: 'name', value: manufacturer, optionsCaption: 'Choose...'"></select>
      </div>
      <div class="form-group">
        <label for="gameYear">Year</label>
        <input type="text" class="form-control" id="gameYear" placeholder="Year" data-bind="value: year">
      </div>
      <div class="form-group">
        <label for="gameIPDB">IPDB</label>
        <input type="text" class="form-control" id="gameIPDB" placeholder="IPDB" data-bind="value: ipdb">
      </div>
      <button type="button" class="btn btn-default" data-bind="click: $parent.saveGame">Save</button>
    </script>

    <!-- search_venue -->
    <script type="text/html" id="search_venue">
      <div class="form-group" data-bind="with: search_venue">
        <div class="form-group">
          <label for="searchName">Venue</label>
          <input type="text" class="form-control" id="searchName" placeholder="Venue Name" data-bind="value: name">
        </div>
        <div class="form-group">
          <label for="searchGame">Game</label>
          <input type="text" class="form-control" id="searchGame" placeholder="Game Name" data-bind="value: game">
        </div>
        <div class="form-group">
          <label for="searchAddress">Address / Nearby</label>
          <input type="text" class="form-control" id="searchAddress" placeholder="Current Location" data-bind="value: address">
        </div>
        <div class="form-group">
          <button type="button" class="btn btn-default" data-bind="click: submit">Search</button>
        </div>
        <!-- ko template: { if: venues(), name: 'venue_list', data: venues } --><!-- /ko -->
      </div>
    </script>

    <!-- user -->
    <script type="text/html" id="user">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" class="form-control" id="username" placeholder="Username" data-bind="value: username">
      </div>
      <div class="form-group">
        <label for="userFirstName">First Name</label>
        <input type="text" class="form-control" id="userFirstName" placeholder="First Name" data-bind="value: firstName">
      </div>
      <div class="form-group">
        <label for="userLastName">Last Name</label>
        <input type="text" class="form-control" id="userLastName" placeholder="Last Name" data-bind="value: lastName">
      </div>
      <div class="form-group">
        <label for="userLastNotified">Last Notified</label>
        <input type="text" class="form-control" id="userLastNotified" placeholder="Last Notified" data-bind="value: lastNotified">
      </div>
      <div class="form-group">
        <div class="checkbox">
          <label>
            <input type="checkbox" data-bind="checked: banned">Banned
          </label>
        </div>
      </div>
      <div class="form-group">
        <button type="button" class="btn btn-default" data-bind="click: $parent.saveUser">Save</button>
      </div>
    </script>

    <!-- github_commits -->
    <script type="text/html" id="github_commits">
      <h4>Commit log for Pinball Finder</h4>
      <div class="form-group">
        <div class="list-group" data-bind="foreach: commits">
          <a class="list-group-item link" data-bind="attr: { href: url }">
            <span data-bind="text: summary"></span>
          </a>
        </div>
      </div>
    </script>

    <!-- Modal -->
    <div class="modal fade" id="alert" tabindex="-1" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body" data-bind="text: status"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-1.10.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/underscore-min.js"></script>
    <script src="js/knockout-3.0.0.js"></script>
    <script src="js/path.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCMWL8VtaTA5ORZro3vPvwfZxWel1sgwPg&amp;sensor=false"></script>
    <script src="js/jquery.pf.js"></script>
    <script src="js/jquery.pf.fsq.js"></script>
    <script src="js/pf-auth.js"></script>
    <script src="js/pf-github.js"></script>
    <script src="js/pf-mgmt2.js" defer></script>
  </body>
</html>
