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

    var VenueViewModel = function(data) {
      var self = this;

      self.id = data.id;
      self.name = ko.observable(data.name);
      self.street = ko.observable(data.street);
      self.city = ko.observable(data.city);
      self.state = ko.observable(data.state);
      self.zipcode = ko.observable(data.zipcode);
      self.phone = ko.observable(data.phone);
      self.url = ko.observable(data.url);
      self.lat = ko.observable(data.lat);
      self.lon = ko.observable(data.lon);
      self.approved = ko.observable(data.approved);
      self.source = ko.observable(data.source);
      self.sourceid = ko.observable(data.sourceid);

      self.addressLong = function () {
        var addressLong = ' - ';

        addressLong = addressLong + (self.street() !== null ? self.street() : '');
        addressLong = addressLong + (self.city() !== null ? ', ' + self.city() : '');
        addressLong = addressLong + (self.state() !== null ? ' ' + self.state() : '');

        return addressLong;
      };
    };

    var VenueListViewModel = function(venues) {
      var self = this;

      self.venues = ko.observableArray();
      self.selected = ko.observable();

      self.edit = function (venue) {
        self.selected(venue);
      };

      _.each(venues, function (venue) {
        self.venues.push(new VenueViewModel(venue));
      });
    };

    var NotificationViewModel = function(data) {
      var self = this;

      self.id = data.id;
      self.text = ko.observable(data.text);
      self.global = ko.observable(data.global == '1');
      self.extra = ko.observable(data.extra);
      self.touserid = ko.observable(data.touserid);
      self.userStats = ko.observable(data.userStats);
    };

    var NotificationListViewModel = function(notifications) {
      var self = this;

      self.notifications = ko.observableArray();
      self.selected = ko.observable();

      self.edit = function (notification) {
        self.selected(notification);
      };

      _.each(notifications, function (notification) {
        self.notifications.push(new NotificationViewModel(notification));
      });
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
      self.venues = ko.observable();
      self.selected = ko.observable();

      self.submit = function() {
        self.venues(null);

        $.ajax({
          url: search_url + '?f=json&t=venue&q=' + self.name()
        }).done(function (data) {
          self.venues(new VenueListViewModel(data.venues));
          self.venues().selected.subscribe(function (venue) {
            self.selected(venue);
          })
        });
      };
    };

    var PinfinderManagementViewModel = function () {
      var self = this,
        geocoder = new google.maps.Geocoder(),
        approvedMsg = 'The venue \'{0}\' you added was approved!  Thank you!  -The Pinfinder Team',
        map;

      self.title = 'Pinfinder Management';
      self.stats = ko.observable();
      self.unapproved_venues = ko.observable();
      self.unapproved_comments = ko.observableArray();
      self.venue = ko.observable();
      self.game = ko.observable();
      self.manufacturers = ko.observable();
      self.notification = ko.observable();
      self.search_venue = ko.observable();
      self.searchVenueResults = ko.observableArray();
      self.user = ko.observable();
      self.notifications_pending = ko.observable();

      self.venueExtra = function (venue) {
        var addressLong = ' - ';

        addressLong = addressLong + (venue.street !== null ? venue.street : '');
        addressLong = addressLong + (venue.city !== null ? ', ' + venue.city : '');
        addressLong = addressLong + (venue.state !== null ? ' ' + venue.state : '');

        return addressLong;
      };

      self.getStats = function () {
        $.ajax({
          url: admin_url + '?q=stats'
        })
          .done(function (data) {
            self.stats(data);
          });
      };

      self.getOptions = function () {
        $.ajax({
          url: admin_url + '?q=options'
        })
          .done(function (data) {
            self.manufacturers(data.manufacturers);
          });
      };

      self.getUnapproved = function() {
        $.ajax({
          url: admin_url + '?q=unapproved'
        })
          .done(function (data) {
            self.unapproved_venues(new VenueListViewModel(data.venues));
            self.unapproved_venues().selected.subscribe(self.editVenue);
          });
      };

      self.getNotificationsPending = function () {
        $.ajax({
          url: admin_url + '?q=notifications'
        })
          .done(function (data) {
            self.notifications_pending(new NotificationListViewModel(data.notifications));
            self.notifications_pending().selected.subscribe(self.editNotification);
          });
      };

      self.updateMap = function() {
        if (typeof self.venue().lat() !== 'undefined') {
          var center = new google.maps.LatLng(self.venue().lat(), self.venue().lon());

          var mapOptions = {
            center:     center,
            zoom:       19,
            mapTypeId:  google.maps.MapTypeId.ROADMAP
          };

          map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);

          $('#map-canvas').show();
        } else {
          $('#map-canvas').hide();
        }
      };

      self.editVenue = function(venue) {
        self.venue(venue);

        location.hash = '#/venue_edit';

        _.defer(self.geocodeVenue);
      };

      self.saveVenue = function() {
        var payload = {
          op: 'saveVenue',
          data: ko.toJS(self.venue())
        };

        $.ajax({
          url: admin_url,
          type: 'POST',
          data: payload
        })
          .done(function (data) {
            if (self.venue().approved() === true) {
              if (self.venue().source() == 'user') {
                var notification = new NotificationViewModel({
                  text: approvedMsg.format(self.venue().name()),
                  global: 'false',
                  extra: 'q=' + self.venue().id,
                  touserid: self.venue().sourceid
                });

                $.ajax({
                  url: admin_url,
                  type: 'POST',
                  data: {
                    op: 'saveNotification',
                    data: ko.toJSON(notification)
                  }
                });
              }

              location.hash = '#/home';
            }
            $('#alert .modal-body').text('Server Response: ' + data.message);
            $('#alert').modal();
          });
      };

      self.deleteVenue = function() {
        var payload = {
          op: 'deleteVenue',
          data: ko.toJS(self.venue())
        };

        $.ajax({
          url: admin_url,
          type: 'POST',
          data: payload
        })
          .done(function (data) {
            if (data.success === true) {
              self.getStats();
              self.getUnapproved();
              location.hash = '#/home';
            }
            $('#alert .modal-body').text('Server Response: ' + data.message);
            $('#alert').modal();
          });
      };

      self.geocodeVenue = function() {
        if (self.venue() !== undefined) {
          var address = self.venue().street() + ' ' + self.venue().city() + ' ' + self.venue().state();

          geocoder.geocode( { 'address': address }, function (results, status) {
            if (status == google.maps.GeocoderStatus.OK) {

              self.venue().lat(results[0].geometry.location.lat());
              self.venue().lon(results[0].geometry.location.lng());

              self.updateMap();
            }
          });
        }
      };

      self.getUser = function (userid, callback) {
        self.user(null);

        $.ajax({
          url: admin_url + '?q=' + userid + '&t=user'
        })
          .done(function (data) {
            console.log('data', data);
            if (data.user !== undefined) {
              if (typeof callback === 'function') {
                var user = new UserViewModel(data.user);
                callback(user);
              }
            }
          });
      };

      self.editNotificationUser = function () {
        self.getUser(self.notification().touserid(), self.editUser)
      };

      self.newNotification = function () {
        self.notification(new NotificationViewModel({ global: true }));
      };

      self.saveNotification = function () {
        $.ajax({
          url: admin_url,
          type: 'POST',
          data: {
            op: 'saveNotification',
            data: ko.toJSON(self.notification())
          }
        })
          .done(function (data) {
            $('#alert .modal-body').text('Server Response: ' + data.message);
            $('#alert').modal();
            self.notification(null);
            self.getNotificationsPending();
          });
      };

      self.cancelNotification = function () {
        self.notification(null);
      };

      self.editNotification = function(notification) {
        self.notification(notification);
      };

      self.deleteNotification = function(notification) {
        var payload = {
          op: 'deleteNotification',
          data: ko.toJSON(notification)
        };

        $.ajax({
          url: admin_url,
          type: 'POST',
          data: payload
        })
          .done(function (data) {
            console.log('data', data);
            if (data.success === true) {
              self.notification(null);
              self.getNotificationsPending();
              $('#alert .modal-body').text('Server Response: ' + data.message);
              $('#alert').modal();
            } else {
              $('#alert .modal-body').text('Server Response: ' + data.message);
              $('#alert').modal();
            }
          });
      };

      self.sendNotifications = function () {
        $.ajax({
          url: admin_url + '?q=sendNotifications'
        })
          .done(function (data) {

            console.log('data', data);

            self.getNotificationsPending();
            $('#alert .modal-body').text('Server Response: ' + data.message);
            $('#alert').modal();
          });
      };

      self.cleanNotifications = function () {
        $.ajax({
          url: admin_url + '?q=cleanNotifications'
        })
          .done(function (data) {
            console.log('data', data);
            self.getNotificationsPending();
            $('#alert .modal-body').text('Server Response: ' + data.message);
            $('#alert').modal();
          });
      };

      self.editUser = function(user) {
        self.user(user);

        location.hash = '#/user';
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

      self.setTitle = function (title) {
        window.document.title = title + ' - Pinfinder Management';
      };

      init = function() {
        Path.map('#/home').to(function() {
          $('.content > div').removeClass('active');
          $('#home').addClass('active').fadeIn();
          self.setTitle('Home');
          self.getStats();
          self.getUnapproved();
        }).enter(clearPanel);

        Path.map('#/venue_edit').to(function() {
          if (self.venue() == undefined) {
            location.hash = '#/home';
          } else {
            $('.content > div').removeClass('active');
            $('#venue_edit').addClass('active').fadeIn('fast', function() {
              google.maps.event.trigger(map, 'resize');
            });
            self.setTitle(self.venue().name());
          }
        }).enter(clearPanel);

        Path.map('#/notifications').to(function() {
          $('.content > div').removeClass('active');
          $('#notifications').addClass('active').fadeIn();
          self.setTitle('Notifications');
          self.getNotificationsPending();
        }).enter(clearPanel);

        Path.map('#/user').to(function() {
          $('.content > div').removeClass('active');
          $('#user_edit').addClass('active').fadeIn();
          self.setTitle('User Edit');
        }).enter(clearPanel);

        Path.map('#/game/new').to(function() {
          self.game(new GameViewModel());
          $('.content > div').removeClass('active');
          $('#game_edit').addClass('active').fadeIn();
          self.setTitle('Add Game');
        }).enter(clearPanel);

        Path.map('#/search').to(function() {
          $('.content > div').removeClass('active');
          $('#search').addClass('active').fadeIn();
          self.setTitle('Search');
          self.search_venue = new SearchVenueViewModel();
          self.search_venue.selected.subscribe(self.editVenue);
        }).enter(clearPanel);

        Path.root('#/home');

        Path.listen();

        self.getOptions();
      };

      init();
    };

    ko.applyBindings(new PinfinderManagementViewModel());

  });

}(jQuery));
