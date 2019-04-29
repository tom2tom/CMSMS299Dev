/*
jQuery autoRefresh widget
Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/
/*!
jQuery autoRefresh widget v.1.1
(C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL2+
*/
/*
Usage: TODO
Changelog: TODO
*/
(function($, window, document, undefined) {
  $.widget('cmsms.autoRefresh', {
    options: {
      url: null,
      data: null,
      interval: 30,
      start_handler: null,
      done_handler: null
    },
    settings: {
      timer: null,
      focus: -1,
      lastrefresh: null
    },
    _create: function() {
      if(!this.options.url) throw 'An URL must be specified for the autoRefresh widget';
      var v = this.options.interval;
      if(v < 1 || v > 3600) throw 'The autoRefresh widget refresh-interval must be in the range 1 to 3600';
      if(document.contains(this.element[0])) {
        this.element.find(':input,a').on('click', function() {
          self.start();
        });
      }
      var self = this;
      $(document).on('focus', function() {
        if(self.settings.focus < 1) {
          self.settings.focus = 1;
          var v = Date.now() / 1000;
          var n = v - self.settings.lastrefresh;
          if(n >= self.options.interval) {
            self.start();
            self.refresh();
          }
        }
      });
      $(document).on('blur', function() {
        if(self.settings.focus === 1) {
          self.settings.focus = 0;
        }
      });
      this.start();
      this.refresh();
    },
    _setOption: function(key, val) {
      switch(key) {
        case 'url':
          if(typeof val === 'string' && val.length > 0) {
            this.options.url = val;
          }
          this.start();
          break;
        case 'data':
          this.options.data = val;
          this.start();
          return this.refresh();
        case 'interval':
          var v = parseInt(val);
          if(v > 0) {
            this.options.url = Math.min(v, 3600);
          }
          this.start();
          break;
        case 'start_handler':
          if(typeof val === 'function') {
            this.options.start_handler = val;
          } else {
            this.options.start_handler = null;
          }
          break;
        case 'done_handler':
          if(typeof val === 'function') {
            this.options.done_handler = val;
          } else {
            this.options.done_handler = null;
          }
      }
    },
    stop: function() {
      var self = this;
      if(self.settings.timer) {
        clearInterval(this.settings.timer);
        self.settings.timer = null;
      }
    },
    start: function() {
      var self = this;
      self.stop();
      self.settings.timer = setInterval(function() {
        self.refresh();
      }, self.options.interval * 1000);
    },
    reset: function() {
      // alias for start
      this.start();
    },
    refresh: function() {
      var self = this;
      if(!self.settings.focus) {
        return;
      }
      var v = Date.now() / 1000;
      self.settings.lastrefresh = v;
      if(self.options.start_handler) {
        self.options.start_handler();
      }
      cms_busy();
      $.ajax(self.options.url, {
        data: self.options.data,
        cache: false
      }).fail(function(jqXHR, textStatus, errorThrown) {
        console.debug('autorefresh failed');
      }).done(function(data) {
        console.debug('autorefresh success');
        if(document.contains(self.element[0])) {
          self.element.html(data);
        }
        if(typeof self.options.done_handler === 'function') {
          self.options.done_handler(data);
        }
      }).always(function() {
        cms_busy(false);
      });
    }
  });
})(jQuery, window, document);
