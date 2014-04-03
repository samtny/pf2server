
(function($) {
  $.pf = $.pf || {};

  $.pf.Commit = function (data) {
    var self = this;

    self.date = data.commit.committer.date;
    self.name = data.commit.committer.name;
    self.message = data.commit.message;
    self.url = data.html_url;

    self.summary = [self.date, self.name, self.message].join(' ');

    return self;
  };

  $.pf.Github = function () {
    var self = this,
      github_api_url = 'https://api.github.com',
      commits_path = '/repos/samtny/pf2server/commits',
      request_defaults = {
        headers: {
          Accept: 'application/vnd.github.v3+json'
        }
      };

    self.request = function (opts) {
      return $.ajax(
        $.extend({}, request_defaults, opts)
      );
    };

    self.getCommits = function (oa) {
      return self.request({
        url: github_api_url + commits_path
      }).done(function (data) {
        _.each(data, function (item) {
          oa.push(new $.pf.Commit(item));
        });
        console.log(ko.toJS(oa));
      });
    };

    return self;
  }

}(jQuery));
