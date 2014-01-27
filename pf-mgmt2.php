<?php

include('pf-session.php');

?>
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
          <a class="navbar-brand" href="http://pinballfinder.org/pf2/pf-mgmt.php" data-bind="text: title"></a>
        </div>
        <div class="collapse navbar-collapse" id="pf-navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="#/home">Home</a></li>
            <li><a href="#/game/new">Add Game</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row">
        <div class="col-xs-12">
          <div class="content">

            <div id="home">
              <h2>Stats</h2>
              <h2>Approve</h2>
              <div class="btn-group-vertical" data-bind="foreach: unapproved">
                <button type="button" class="btn btn-default form-control" data-bind="text: name, click: $parent.editVenue"></button>
              </div>
            </div>
            
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
                <label for="venuePhone">Phone</label>
                <input type="tel" class="form-control" id="venuePhone" placeholder="Phone" data-bind="value: phone">
              </div>
              <div class="form-group">
                <label for="venueURL">URL</label>
                <input type="url" class="form-control" id="venueURL" placeholder="URL" data-bind="value: url">
              </div>
            </div>
            
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
            
          </div>
        </div>
      </div>
      <hr>
      <footer>
        <p>Pinballfinder.org</p>
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
    <script src="js/pf-mgmt2.js" defer="true"></script>
  </body>
</html>
