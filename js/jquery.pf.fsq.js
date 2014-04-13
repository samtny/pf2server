(function ($) {
  $.pf.Fsq = function () {
    var self = this,
      base_url = '/pf2/pf-fsq2.php';

    self.access_token = ko.observable();
    self.ll = ko.observable();

    self.searchVenue = function (query, n) {
      return $.ajax({
        url: base_url + '?t=venue&q=' + query + (n !== undefined ? '&n=' + n : '')
      }).done(function (data) {
        console.log('data', data);
      });
    };

    self.getCookie = function (cname) {
      var name = cname + "=";
      var ca = document.cookie.split(';');
      for(var i=0; i<ca.length; i++) {
        var c = ca[i].trim();
        if (c.indexOf(name)==0) return c.substring(name.length,c.length);
      }
      return null;
    };

    self.refresh = function () {
      self.access_token(self.getCookie('fsq_access_token'));
    };

    self.geolocate = function () {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
          self.ll(position.coords.latitude + ',' + position.coords.longitude);
        }, function (error) {

        });
      }
    };

    self.geolocate();

    return self;
  };
}(jQuery));
