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
    };

  $(document).ready(function () {
    var GameViewModel = function () {
      var self = this;

      self.name = ko.observable();
      self.manufacturer = ko.observable();
      self.year = ko.observable();
      self.ipdb = ko.observable();
    };

    var CommentViewModel = function (data) {
      var self = this;

      self.id = ko.observable(data.id);
      self.venueid = ko.observalbe(data.venueid);
      self.text = ko.observable(data.text);
    };

    var UserViewModel = function (data) {
      var self = this;

      self.id = data.id;
      self.username = ko.observable(data.username);
      self.firstName = ko.observable(data.fname);
      self.lastName = ko.observable(data.lname);
      self.lastNotified = ko.observable(data.lastnotified);
      self.banned = ko.observable(data.banned);

      self.stats = function() {
        return '...';
      }
    };

    var SearchVenueViewModel = function() {
      var self = this;

      self.name = ko.observable();
      self.venues = ko.observableArray();

      self.submit = function() {
        $.ajax({
          url: search_url + '?f=json&t=venue&q=' + self.name()
        }).done(function (data) {
          var venues = [];

          _.each(data.venues, function (venue) {
            venues.push(new VenueViewModel(venue));
          });

          self.venues(venues);
        });
      };
    };

    var PinfinderManagementViewModel = function () {
      var self = this,
        pinfinder = new $.pf.Pinfinder(),
        admin = new $.pf.Admin(),
        geocoder = new google.maps.Geocoder(),
        map;

      self.title = ko.observable();
      self.status = ko.observable();
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

      self.title.subscribe(function (title) { window.document.title = title + ' - Pinfinder Management'; });

      self.status.subscribe(function () { $('#alert').modal(); });

      self.venue.subscribe(function (venue) { location.hash = (venue !== null ? '#/venue_edit' : '#/home'); });

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
        admin.deleteNotification(self.notification())
          .done(function() {
            self.notification(null);
            self.getNotificationsPending();
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
          self.editVenue(results[0]);
        }
      };

      init = function() {
        Path.map('#/home').to(function() {
          $('.content > div').removeClass('active');
          $('#home').addClass('active').fadeIn();
          self.title('Home');

          admin.getStats(self.stats)
            .getUnapproved(self.unapproved_venues)
            .getUnapprovedComments(self.unapproved_comments);

          pinfinder.getRecent(self.recent_venues);
        }).enter(clearPanel);

        Path.map('#/venue_edit').to(function() {
          if (self.venue() == undefined) {
            location.hash = '#/home';
          } else {
            $('.content > div').removeClass('active');
            $('#venue_edit').addClass('active').fadeIn();
            self.title(self.venue().name());
            _.defer(self.geocodeVenue);
          }
        }).enter(clearPanel);

        Path.map('#/notifications').to(function() {
          $('.content > div').removeClass('active');
          $('#notifications').addClass('active').fadeIn();
          self.title('Notifications');

          admin.getNotificationsPending(self.notifications_pending);
        }).enter(clearPanel);

        Path.map('#/user').to(function() {
          $('.content > div').removeClass('active');
          $('#user_edit').addClass('active').fadeIn();
          self.title('User Edit');
        }).enter(clearPanel);

        Path.map('#/game/new').to(function() {
          self.game(new GameViewModel());
          $('.content > div').removeClass('active');
          $('#game_edit').addClass('active').fadeIn();
          self.title('Add Game');
        }).enter(clearPanel);

        Path.map('#/search').to(function() {
          $('.content > div').removeClass('active');
          $('#search').addClass('active').fadeIn();
          self.title('Search');
        }).enter(clearPanel);

        Path.root('#/home');

        Path.listen();

        self.getOptions();
      };

      self.search_venue(new SearchVenueViewModel());
      self.search_venue().venues.subscribe(self.searchReturnedResults);

      init();
    };

    ko.applyBindings(new PinfinderManagementViewModel());

  });

}(jQuery));
