if (!String.prototype.format) {
  String.prototype.format = function() {
    var args = arguments;
    return this.replace(/{(\d+)}/g, function(match, number) {
      return typeof args[number] != 'undefined' ? args[number] : match;
    });
  };
}

(function ($) {
  var admin_url = 'pf-admin.php',
    search_url = 'pf',
    clearPanel = function () {
      $('.content .active').hide();
      $('html, body').scrollTop(0);
    };

  $(document).ready(function () {
    var SearchVenue = function() {
      var self = this,
        pinfinder = new $.pf.Pinfinder();

      self.name = ko.observable();
      self.game = ko.observable();
      self.address = ko.observable();

      self.venues = ko.observableArray();

      self.geolocate = function () {
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(function (position) {
            self.latLng = position.coords.latitude + ',' + position.coords.longitude;
          }, function (error) {

          });
        }
      };

      self.submit = function() {
        pinfinder.request(new $.pf.VenueQuery({
            name: self.name(),
            game: self.game(),
            address: (self.address() !== undefined && self.address().length > 0) ? self.address() : self.latLng
          }))
          .done(function (data) {
          var venues = [];

          _.each(data.venues, function (venue) {
            venues.push(new $.pf.Venue(venue));
          });

          self.venues(venues);
        });
      };

      self.geolocate();

      return self;
    };

    var PinfinderManagementViewModel = function () {
      var self = this,
        pinfinder = new $.pf.Pinfinder(),
        fsq = new $.pf.Fsq(),
        admin = new $.pf.Admin(),
        github = new $.pf.Github(),
        geocoder = new google.maps.Geocoder(),
        map;

      self.title = ko.observable();
      self.status = ko.observable();
      self.mailto = ko.observable(['mailto:', 'pin', 'finder', 'app', '@', 'pinballfinder.org', '?Subject=Pinballfinder.org'].join(''));
      self.about = ko.observable('About');

      self.stats = ko.observable();
      self.unapproved_venues = ko.observableArray();
      self.recent_venues = ko.observableArray();
      self.unapproved_comments = ko.observableArray();
      self.venue = ko.observable();
      self.game = ko.observable();
      self.manufacturers = ko.observable();
      self.notification = ko.observable();
      self.search_venue = ko.observable();
      self.searchVenueResults = ko.observableArray();
      self.user = ko.observable();
      self.notifications_pending = ko.observableArray();
      self.commits = ko.observableArray();
      self.fsq_access_token = ko.observable();

      self.title.subscribe(function (title) { window.document.title = title + ' - Pinfinder Management'; });

      self.status.subscribe(function () { $('#alert').modal(); });
      self.status.extend({ notify: 'always' });

      self.venue.subscribe(function (venue) { location.hash = (venue !== null ? '#/venue_edit' : '#/home'); });

      fsq.access_token.subscribe(function (token) {
        if (token && token.length) {
          $.ajax({
            url: 'https://api.foursquare.com/v2/users/self?v=20140411&oauth_token=' + token
          }).done(function (data) {
            console.log('data', data);
            self.about('Welcome, ' + data.response.user.firstName);
          });
        }
      });

      self.saveVenue = function() {
        admin.saveVenue(self.venue())
          .done(function() {
            self.venue(null);
          })
          .always(function (data) {
            self.status('Server Response: ' + data.message);
          });
      };

      self.deleteVenue = function() {
        admin.deleteVenue(self.venue())
          .done(function() {
            location.hash = '#/home';
          })
          .always(function (data) {
            self.status('Server Response: ' + data.message);
          });
      };

      self.updateMap = function() {
        if (typeof self.venue().lat() !== 'undefined') {
          var mapOptions = { zoom:       19,  mapTypeId:  google.maps.MapTypeId.ROADMAP  };

          map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
          $('#map-canvas').show();

          google.maps.event.trigger(map, 'resize');

          map.setCenter(new google.maps.LatLng(self.venue().lat(), self.venue().lon()));
        } else {
          $('#map-canvas').hide();
        }
      };

      self.newNotification = function () {
        self.notification(self.admin.newGlobalNotification());
      };

      self.saveNotification = function() {
        admin.saveNotification(self.notification())
          .done(function() {
            self.notification(null);
            self.getNotificationsPending();
          })
          .always(function (data) {
            self.status('Server Response: ' + data.message);
          });
      };

      self.deleteNotification = function(notification) {
        admin.deleteNotification(notification)
          .done(function() {
            self.notification(null);
            admin.getNotificationsPending(self.notifications_pending);
          })
          .always(function (data) {
            self.status('Server Response: ' + data.message);
          });
      };

      self.cancelNotification = function () {
        self.notification(null);
      };

      self.sendNotifications = function () {
        admin.sendNotifications()
          .done(function() {
            self.getNotificationsPending();
          })
          .always(function (data) {
            self.status('Server Response: ' + data.message);
          });
      };

      self.geocodeVenue = function() {
        if (self.venue() !== undefined) {
          var address = self.venue().addressGeocode();

          if (address.trim().length) {
            geocoder.geocode( { 'address': address }, function (results, status) {
              if (status == google.maps.GeocoderStatus.OK) {

                self.venue().lat(results[0].geometry.location.lat());
                self.venue().lon(results[0].geometry.location.lng());

                self.updateMap();
              }
            });
          }
        }
      };

      self.getUser = function (userid, callback) {
        self.user(null);

        $.ajax({
          url: admin_url + '?q=' + userid + '&t=user'
        })
          .done(function (data) {
            if (data.user !== undefined) {
              if (typeof callback === 'function') {
                var user = new UserViewModel(data.user);
                callback(user);
              }
            }
          });
      };

      self.editUser = function(user) {
        self.user(user);

        location.hash = '#/user';
      };

      self.editNotificationUser = function () {
        self.getUser(self.notification().touserid(), self.editUser)
      };

      self.getOptions = function () {
        $.ajax({ url: admin_url + '?q=options' }).done(function (data) { self.manufacturers(data.manufacturers); });
      };

      self.saveGame = function() {
        var payload = {
          op: 'newgame',
          data: ko.toJS(self.game())
        };

        $.ajax({
          url: admin_url,
          type: 'POST',
          data: payload
        })
        .done(function (data) {
          if (data.success === false) {
            $('#alert .modal-body').text('Server Response: ' + data.message);
            $('#alert').modal();
          } else {
            location.hash = '#/home';
            $('#alert .modal-body').text('Server Response: ' + data.message);
            $('#alert').modal();
          }
        });
      };

      self.searchReturnedResults = function (results) {
        if (results.length === 1) {
          self.venue(results[0]);
        }
      };

      init = function() {
        var gotoPath = function (id, title, func) {
          return function () {
            $('.content > div').removeClass('active');
            $(id).addClass('active').fadeIn();
            self.title(title);
            if (typeof func === 'function') { func(); }
          };
        };

        Path.map('#/home').to(new gotoPath('#home', 'Home', function () {
          admin.getStats(self.stats)
            .getUnapproved(self.unapproved_venues)
            .getUnapprovedComments(self.unapproved_comments);

          pinfinder.getRecent(self.recent_venues);
        })).enter(clearPanel);

        Path.map('#/venue_edit').to(new gotoPath('#venue_edit', 'Venue', function () {
          _.defer(self.geocodeVenue);
        })).enter(clearPanel);

        Path.map('#/notifications').to(new gotoPath('#notifications', 'Notifications', function () {
          admin.getNotificationsPending(self.notifications_pending);
        })).enter(clearPanel);

        Path.map('#/user').to(new gotoPath('#user_edit', 'User Edit')).enter(clearPanel);
        Path.map('#/game/new').to(new gotoPath('#game_edit', 'Add Game', function () { self.game(new GameViewModel); })).enter(clearPanel);
        Path.map('#/search').to(new gotoPath('#search', 'Search')).enter(clearPanel);

        Path.map('#/terms').to(new gotoPath('#terms', 'Terms of Use')).enter(clearPanel);
        Path.map('#/login').to(new gotoPath('#login', 'Login', fsq.refresh())).enter(clearPanel);

        Path.map('#/code').to(new gotoPath('#code', 'Code')).enter(clearPanel);

        Path.root('#/home');

        Path.listen();

        fsq.refresh();
        self.getOptions();

        github.getCommits(self.commits);

      };

      self.search_venue(new SearchVenue());
      self.search_venue().venues.subscribe(self.searchReturnedResults);

      init();
    };

    ko.applyBindings(new PinfinderManagementViewModel());

  });

}(jQuery));
