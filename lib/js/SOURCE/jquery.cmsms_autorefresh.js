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
jQuery autoRefresh widget v.1.2
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
      var self = this;
      if(document.contains(this.element[0])) {
        this.element.find(':input,a').on('click', function() {
          self.reset(); // restart timer
        });
      }
      $(document).on('focus', function() {
        if(self.settings.focus < 1) {
          self.settings.focus = 1;
          self.reset();
        }
      });
      $(document).on('blur', function() {
        if(self.settings.focus === 1) {
          self.settings.focus = 0;
          self.stop();
        }
      });
      this.reset();
      this.refresh();
      return this.element; //allow chaining
    },
    _setOption: function(key, val) {
      switch(key) {
        case 'url':
          if(typeof val === 'string' && val.length > 0) {
            this.options.url = val;
            this.reset();
            return this.refresh(); //run immediately
          } else {
            this.stop();
          }
          break;
        case 'data':
          this.options.data = val;
          this.reset();
          return this.refresh();
        case 'interval':
          var v = parseInt(val);
          if(v > 0) {
            this.options.interval = Math.min(v, 3600);
            this.reset();
            return this.refresh();
          } else {
            this.stop();
          }
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
      if(this.settings.timer) {
        clearInterval(this.settings.timer);
        this.settings.timer = null;
      }
    },
    start: function() {
      // alias for reset
      this.reset();
    },
    reset: function() {
      this.stop();
      if (this.options.interval < 1) return;
      var self = this;
      this.settings.timer = setInterval(function() {
        self.refresh();
      }, this.options.interval * 1000);
    },
    refresh: function() {
      if(!this.settings.focus) {
        return;
      }
      var v = Date.now() / 1000;
      this.settings.lastrefresh = v;
      if(typeof this.options.start_handler === 'function') {
        this.options.start_handler();
      }
      cms_busy();
      var self = this;
      return $.ajax(this.options.url, {
        data: this.options.data,
        cache: false
      }).fail(function(jqXHR, textStatus, errorThrown) {
        console.debug('ajax call to ' + self.options.url + ' failed: ' + errorThrown);
      }).done(function(data) {
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
