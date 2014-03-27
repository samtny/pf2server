<?php include('pf-session.php'); ?>
<html>
  <head>
    <title>Pinfinder Management Interface</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="css/pf-mgmt2.css" rel="stylesheet">
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
          <a class="navbar-brand" href="#/home" data-bind="text: title"></a>
        </div>
        <div class="collapse navbar-collapse" id="pf-navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="#/notifications">Notifications</a></li>
            <li><a href="#/search">Search</a></li>
            <li><a href="#/game/new">Add Game</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row">
        <div class="col-xs-12">
          <div class="content">

            <!-- home -->
            <div id="home">
              <!-- stats -->
              <div class="panel panel-default">
                <div class="panel-body">
                  <div class="row" data-bind="with: stats">
                    <div class="col-xs-6 col-sm-2">
                      Venues: <span data-bind="text: parseInt(venues).toLocaleString()"></span>
                    </div>
                    <div class="col-xs-6 col-sm-2">
                      New: <span data-bind="text: parseInt(n30day).toLocaleString()"></span>
                    </div>
                    <div class="col-xs-6 col-sm-2">
                      Updated: <span data-bind="text: parseInt(u30day).toLocaleString()"></span>
                    </div>
                    <div class="col-xs-6 col-sm-2">
                      Users: <span data-bind="text: parseInt(users).toLocaleString()"></span>
                    </div>
                    <div class="col-xs-6 col-sm-2">
                      Machines: <span data-bind="text: parseInt(machines).toLocaleString()"></span>
                    </div>
                  </div>
                </div>
              </div>
              <!-- approve list -->
              <h4 data-bind="visible: unapproved().length > 0">Approve</h4>
              <div class="list-group" data-bind="foreach: unapproved">
                <a class="list-group-item link" data-bind="click: $parent.editVenue"><span class="h4" data-bind="text: name"></span><small data-bind="text: addressLong()"></small></a>
              </div>
              <!-- comments list -->
              <h4 data-bind="visible: comments().length > 0">Comments</h4>
              <div class="list-group" data-bind="foreach: comments">
                <a class="list-group-item link"><span class="h4" data-bind="text: name"></span><small data-bind="text: text"></small></a>
              </div>

            </div>
            <!-- end home -->

            <!-- venue -->
            <div id="venue" data-bind="with: venue">
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
              <div class="form-group">
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
              <div class="checkbox">
                <label>
                  <input type="checkbox" data-bind="checked: approved">Approve
                </label>
              </div>
              <button type="button" class="btn btn-default" data-bind="click: $parent.saveVenue">Save</button>
              <button type="button" class="btn btn-default" data-bind="click: $parent.geocodeVenue">Geocode</button>
              <button type="button" class="btn btn-default pull-right" data-bind="click: $parent.deleteVenue">Delete</button>
            </div>
            <!-- end venue -->

            <!-- game -->
            <div id="game" data-bind="with: game">
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
            </div>
            <!-- end game -->

            <!-- notifications -->
            <div id="notifications">

              <!-- notification -->
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

              <!-- notification list -->
              <h2>Pending Notifications</h2>
              <div class="list-group" data-bind="foreach: notifications">
                <a class="list-group-item link" data-bind="click: $parent.editNotification"><span class="h4" data-bind="text: text"></span></a>
              </div>

              <!-- actions -->
              <button type="button" class="btn btn-default" data-bind="click: newNotification, visible: !notification()">New</button>
              <button type="button" class="btn btn-default" data-bind="click: sendNotifications, visible: !notification()">Send All</button>
              <button type="button" class="btn btn-default" data-bind="click: cleanNotifications, visible: !notification()">Clean</button>

            </div>
            <!-- end notifications -->

            <div id="search">
              <div class="form-group" data-bind="with: search">
                <label for="searchVenue">Venue Name</label>
                <input type="text" class="form-control" id="searchVenue" placeholder="Venue Name" data-bind="value: venueName">
              </div>

              <!-- venue search results -->
              <h2>Venues</h2>
              <div class="list-group" data-bind="foreach: searchVenueResults">
                <a class="list-group-item link" data-bind="click: $parent.editVenue"><span class="h4" data-bind="text: name"></span></a>
              </div>

              <!-- actions -->
              <button type="button" class="btn btn-default" data-bind="click: $root.searchSubmit">Search</button>
            </div>

            <!-- user -->
            <div id="user" data-bind="with: user">
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
            </div>
            <!-- /user -->

          </div>
        </div>
      </div>
      <hr>
      <footer>
        <p>PinballFinder.org</p>
      </footer>
    </div>
    
    <!-- Modal -->
    <div class="modal fade" id="alert" tabindex="-1" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body">
            ...
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </div><!-- /.modal-content -->
      </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    
    <script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/knockout-3.0.0.js"></script>
    <script src="js/path.min.js"></script>
    <script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyCMWL8VtaTA5ORZro3vPvwfZxWel1sgwPg&amp;sensor=false"></script>
    <script src="js/pf-mgmt2.js" defer></script>
  </body>
</html>
