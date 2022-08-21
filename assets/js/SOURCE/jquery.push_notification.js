/*!
jquery push notification V.1.0 <https://github.com/asmsuechan/jquery_push_notification>
(C) 2016 asmsuechan <https://asmsuechan.com>
License: MIT
*/
(function($, window, undefined) {
  if (!('Notification' in window) || Notification.permission === 'denied') {
    return;
  }
  if (typeof $.fn.push_notice !== 'undefined') {
    return;
  }

  /**
   * Generate a push-notice
   * @param {Object} opts notice parameters, some/all of:
   *  'title','body','closeTime','icon'
   *  or {String} notice body
   * each one overrides the corresponding default value
   * @return Object or null if no notice is possible. The object has
   *  member-functions 'click', 'show', 'close', 'error'
   */
  $.fn.push_notice = function(opts) {
    switch (Notification.permission) {
      case 'granted':
        break;
      default:
        Notification.requestPermission().then(function(permission) {
          if (permission !== 'granted') {
            return;
          }
        });
    }

    if (typeof opts === 'string') {
      opts = {'body': opts };
    }
    var options = $.extend({}, $.fn.push_notice.defaults, opts || {});
    var notification = new Notification(options.title, options);
    setTimeout(notification.close.bind(notification), options.closeTime);
    //TODO fix the following
    return {
      click: function(callback) {
        notification.addEventListener('click', function() {
          return callback();
        });
        return this;
      },
      show: function(callback) {
        notification.addEventListener('show', function() {
          return callback();
        });
        return this;
      },
      close: function(callback) {
        notification.addEventListener('close', function() {
          return callback();
        });
        return this;
      },
      error: function(callback) {
        notification.addEventListener('error', function() {
          return callback();
        });
        return this;
      }
    };
  };

  $.fn.push_notice.defaults = $.extend({
    'title': 'Notification', // notice title
    'body': 'Message', // notice content
    'closeTime': 5000, // notice timeout (mS)
    'icon' : '' // URL of icon to be displayed
  }, $.fn.notify.defaults || {});
})(jQuery, window);
