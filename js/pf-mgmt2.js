(function ($) {

  var clearPanel = function () {
    $('.content .active').hide();
  };
  
  var GameViewModel = function () {
    var game = this;
    
    game.name = ko.observable();
    game.manufacturer = ko.observable();
    game.year = ko.observable();
    game.ipdb = ko.observable();
  };

  var PinfinderManagementViewModel = function () {
    var mgmt = this;

    mgmt.title = 'Pinfinder Management';
    mgmt.unapproved = ko.observableArray([]);
    mgmt.venue = ko.observable();
    mgmt.game = ko.observable();
    mgmt.manufacturers = ko.observable();

    mgmt.editVenue = function(venue) {
      mgmt.venue(venue);
      location.hash = '#/venue';
    };
    
    mgmt.saveGame = function() {
      var payload = {
        op: 'newgame',
        data: ko.toJS(mgmt.game())
      };

      $.ajax({
        url: 'pf-admin.php',
        type: 'POST',
        data: payload
      })
      .done(function (data) {
        if (data.success === false) {
          $('#alert .modal-body').text(data.message);
          $('#alert').modal();
        } else {
          location.hash = '#/home';
          $('#alert .modal-body').text(data.message);
          $('#alert').modal();
        }
      });
    };

    init = function() {
      Path.map('#/home').to(function() {
        $('.content > div').removeClass('active');
        $('#home').addClass('active').fadeIn();
      }).enter(clearPanel);
      
      Path.map('#/venue').to(function() {
        $('.content > div').removeClass('active');
        $('#venue').addClass('active').fadeIn();
      }).enter(clearPanel);
      
      Path.map('#/game/new').to(function() {
        mgmt.game(new GameViewModel());
        $('.content > div').removeClass('active');
        $('#game').addClass('active').fadeIn();
      }).enter(clearPanel);
      
      Path.root('#/home');

      Path.listen();
      
      $.ajax({
        url: 'pf-admin.php?q=unapproved'
      })
      .done(function (data) {
        if (data.venues !== undefined) {
          mgmt.unapproved(data.venues);
        }
      });
      
      $.ajax({
        url: 'pf-admin.php?q=options'
      })
      .done(function (data) {
console.log(data);
        mgmt.manufacturers(data.manufacturers);
      });
    };

    init();
  };

  ko.applyBindings(new PinfinderManagementViewModel());

}(jQuery));
