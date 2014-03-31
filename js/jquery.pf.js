if (!String.prototype.format) {
  String.prototype.format = function() {
    var args = arguments;
    return this.replace(/{(\d+)}/g, function(match, number) {
      return typeof args[number] != 'undefined' ? args[number] : match;
    });
  };
}

(function($) {
  var Venue = function(data) {
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

    self.addressGeocode = function () {
      var addressLong = '';

      addressLong = addressLong + (self.street() !== null ? self.street() : '');
      addressLong = addressLong + (self.city() !== null ? ' ' + self.city() : '');
      addressLong = addressLong + (self.state() !== null ? ' ' + self.state() : '');

      return addressLong;
    };
  };

  var Notification = function(data) {
    var self = this;

    self.id = data.id;
    self.text = ko.observable(data.text);
    self.global = ko.observable(data.global == '1');
    self.extra = ko.observable(data.extra);
    self.touserid = ko.observable(data.touserid);
    self.userStats = ko.observable(data.userStats);
  };

  $.pf = {};

  $.pf.Pinfinder = function () {
    var self = this,
      search_url = 'pf';

    self.getRecent = function(oa) {
      $.ajax({ url: search_url + '?f=json&l=20' })
        .done(function (data) {
          oa([]);

          _.each(data.venues, function (venue) {
            oa.push(new Venue(venue));
          });
        });
    };

    return self;
  };

  $.pf.Admin = function () {
    var self = this;

    var admin_url = 'pf-admin.php',
      approvedMsg = 'The venue \'{0}\' you added was approved!  Thank you!  -The Pinfinder Team';

    self.getStats = function (o) {
      $.ajax({ url: admin_url + '?q=stats' }).done(function (data) { o(data); });

      return self;
    };

    self.getUnapproved = function(oa) {
      $.ajax({ url: admin_url + '?q=unapproved' })
        .done(function (data) {
          oa([]);

          _.each(data.venues, function (venue) {
            oa.push(new Venue(venue));
          });
        });

      return self;
    };

    self.getUnapprovedComments = function(oa) {
      $.ajax({ url: admin_url + '?q=unapproved_comments' })
        .done(function (data) {
          oa([]);

          _.each(data.comments, function (comment) {
            oa.push(new CommentViewModel(comment));
          });
        });

      return self;
    };

    self.createApprovedVenueNotification = function (venue) {
      if (venue.approved() === true && venue.source() === 'user') {
        var notification = new Notification({
          text: approvedMsg.format(venue.name()),
          global: 'false',
          extra: 'q=' + venue.id,
          touserid: venue.sourceid()
        });

        self.saveNotification(notification);
      }
    };

    self.saveVenue = function (venue) {
      return $.ajax({
        url: admin_url,
        type: 'POST',
        data: {
          op: 'saveVenue',
          data: ko.toJS(venue)
        }
      }).done(function (data) {
        self.createApprovedVenueNotification(venue);
      });
    };

    self.deleteVenue = function (venue) {
      var deleteVenue = this;

      deleteVenue.done = function (callback) {
        deleteVenue.doneCallback = callback;

        return deleteVenue;
      };

      deleteVenue.always = function (callback) {
        deleteVenue.alwaysCallback = callback;

        return deleteVenue;
      };

      $.ajax({
        url: admin_url,
        type: 'POST',
        data: {
          op: 'deleteVenue',
          data: ko.toJS(venue)
        }
      })
        .done(function (data) {
          if (deleteVenue.doneCallback !== undefined) {
            deleteVenue.doneCallback(data);
          }

          if (deleteVenue.alwaysCallback !== undefined) {
            deleteVenue.alwaysCallback(data);
          }
        })
        .always(function (arg0) {
          if (deleteVenue.alwaysCallback !== undefined) {
            deleteVenue.alwaysCallback(arg0);
          }
        });

      return deleteVenue;
    };

    self.getNotificationsPending = function (oa) {
      $.ajax({ url: admin_url + '?q=notifications' })
        .done(function (data) {
          oa([]);

          _.each(data.notifications, function (notification) {
            oa.push(new Notification(notification));
          });
          //o(new NotificationList(data.notifications));
          //self.notifications_pending().selected.subscribe(self.editNotification);
        });
    };

    self.newNotification = function (opts) {
      return new Notification(opts);
    };

    self.newGlobalNotification = function () {
      return self.newNotification({ global: true });
    };

    self.saveNotification = function (notification) {
      var saveNotification = this;

      saveNotification.done = function (callback) {
        saveNotification.doneCallback = callback;

        return saveNotification;
      };

      saveNotification.always = function (callback) {
        saveNotification.alwaysCallback = callback;

        return saveNotification;
      };

      $.ajax({
        url: admin_url,
        type: 'POST',
        data: {
          op: 'saveNotification',
          data: ko.toJSON(notification)
        }
      })
        .done(function (data) {
          if (saveNotification.doneCallback !== undefined) {
            saveNotification.doneCallback(data);
          }
        })
        .always(function (arg0) {
          if (saveNotification.alwaysCallback !== undefined) {
            saveNotification.alwaysCallback(arg0);
          }
        });

      return saveNotification;
    };

    self.deleteNotification = function (notification) {
      var deleteNotification = this;

      deleteNotification.done = function (callback) {
        deleteNotification.doneCallback = callback;

        return deleteNotification;
      };

      deleteNotification.always = function (callback) {
        deleteNotification.alwaysCallback = callback;

        return deleteNotification;
      };

      $.ajax({
        url: admin_url,
        type: 'POST',
        data: {
          op: 'deleteNotification',
          data: ko.toJS(notification)
        }
      })
        .done(function (data) {
          if (deleteNotification.doneCallback !== undefined) {
            deleteNotification.doneCallback(data);
          }
        })
        .always(function (arg0) {
          if (deleteNotification.alwaysCallback !== undefined) {
            deleteNotification.alwaysCallback(arg0);
          }
        });

      return deleteNotification;
    };

    self.sendNotifications = function () {
      var sendNotifications = this;

      sendNotifications.done = function (callback) {
        sendNotifications.doneCallback = callback;

        return sendNotifications;
      };

      sendNotifications.always = function (callback) {
        sendNotifications.alwaysCallback = callback;

        return sendNotifications;
      };

      $.ajax({
        url: admin_url + '?q=sendNotifications'
      })
        .done(function (data) {
          if (sendNotifications.doneCallback !== undefined) {
            sendNotifications.doneCallback(data);
          }
        })
        .always(function (arg0) {
          if (sendNotifications.alwaysCallback !== undefined) {
            sendNotifications.alwaysCallback(arg0);
          }
        });
    };

    return self;
  };
}(jQuery));
