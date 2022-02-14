/*
jQuery Poller
Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
cfg file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

cfg program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

cfg program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with cfg program. If not, see <https://www.gnu.org/licenses/>.
*/
/*!
jQuery Poller v.0.8
(C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL2+
*/
/*
Usage: var P = Poller; h = P.add(opts); P.request(h)

Options:
url: must be provided
data: parameters suuplied to ajax and/or the url null
interval: repetition interval if relevant  60 (seconds)
onetime: do not repeat false
insert: insert returned data into element false
element: null | DOM element | jQ selection
start_handler: function(cfg) | null
done_handler: function(data, textStatus, jqXHR) | null

Methods:
h = add(opts) setup, don't run
h = run(opts) setup and run (once or iterate)
Deferred = oneshot(opts) run once, no opts cache
opts = settings(h) get settings
settings(h,opts) change setting(s)
stop(h) stop iterating
cycle(h) [re]start iterating
Deferred = request(h) run once

Changelog:
*/
var Poller = {
  handlid: 1,
  heap: [],
  defaults: {
    url: null,
    data: null,
    interval: 60,
    onetime: false,
    insert: false,
    element: null,
    start_handler: null,
    done_handler: null
  },
  internal: {
    timer: null,
    focus: 1,
    lastrun: null
  },

  get_config: function(h) {
    return this.heap[h] || null;
  },

  add: function(opts) {
    var cfg = $.extend({}, this.defaults, opts, this.internal);
    if (!cfg.url) throw 'An URL must be specified for the poller';
    if (!cfg.onetime) {
      var v = cfg.interval;
      if (v < 1 || v > 3600) throw 'The poller run-interval must be in the range 1 to 3600';
    }
/*  if (cfg.element) {
      //TODO validate, check document.contains(), check if native or instanceof jQuery
    }
*/
    var h = this.handlid++;
    this.heap[h] = cfg;

    if (cfg.element) {
      $.data(document, 'polldata', this);
      var el = (cfg.element instanceof jQuery) ? cfg.element[0] : cfg.element;
      if (document.contains(el)) {
        $(el).find(':input,a').on('click', function() {
          var pd = $.data(document, 'polldata');
          for (var i = 0, len = pd.heap.length; i < len; i++) {
            var cfg = pd.get_config(pd.heap[i]);
            if (cfg) {
              pd._cycle.call(pd, cfg); // resume timer TODO
            }
          }
        });
      }
      $(document).on('focus', function() {
        pd = $.data(document, 'polldata');
        for (var i = 0, len = pd.heap.length; i < len; i++) {
          var cfg = pd.get_config(pd.heap[i]);
          if (cfg && cfg.focus < 1) {
            cfg.focus = 1;
            pd._cycle.call(pd, cfg);
          }
        }
      });
      $(document).on('blur', function() {
        pd = $.data(document, 'polldata');
        for (var i = 0, len = pd.heap.length; i < len; i++) {
          var cfg = pd.get_config(pd.heap[i]);
          if (cfg && cfg.focus === 1) {
            cfg.focus = 0;
            pd._stop.call(pd, cfg);
          }
        }
      });
    } else {
      cfg.focus = 1;
    }
    return h; //so no chaining
  },

  /**
   * returns nothing | {} | Deferred object
   */
  settings: function(h, opts) {
    var cfg = this.get_config(h);
    if (typeof opts === undefined) {
      if (cfg) {
        var ret = Object.assign({}, cfg);
         x = this.internal.keys;
        for (var i = 0, len = x.length; i < len; i++) {
           delete ret[x[i]];
        }
        return ret;
      } else {
        return {};
      }
    } else if (opts) {
      x = this.internal.keys;
      for (var i = 0, len = x.length; i < len; i++) {
        delete opts[x[i]];
      }
      cfg = $.extend(cfg,opts);
      if (cfg.onetime) {
        if (cfg.timer) {
          this._stop(cfg);
        }
        return this._request(cfg);
      }
    }
  },

  oneshot: function(opts) {
    var cfg = $.extend({}, this.defaults, opts, this.internal);
    cfg.onetime = true;
    return this._request(cfg);
  },

  run: function(opts) {
    var h = this.add(opts);
    var cfg = this.get_config(h);
    if (cfg) {
      if (!cfg.onetime) {
        this._cycle(cfg);
      }
      this._request(cfg); //run immediately, no returned Deferred
      return h;
    }
    return 0;
  },

  stop: function(h) {
    var cfg = this.get_config(h);
    if (cfg) {
      this._stop(cfg);
    }
  },

  _stop: function(cfg) {
    if (cfg.timer) {
      clearInterval(cfg.timer);
      cfg.timer = null;
    }
  },

  cycle: function(h) {
    var cfg = this.get_config(h);
    if (cfg) {
      this._cycle(cfg);
    }
  },

  _cycle: function(cfg) {
    if (cfg.interval < 1) return;
    var self = this;
    cfg.timer = setInterval(function() {
      if (cfg.focus) {
        self._request(cfg);
      }
    }, cfg.interval * 1000);
  },

  /*
   * returns a jQ Deferred object
   */
  request: function(h) {
    var cfg = this.get_config(h);
    if (cfg) {
      return this._request(cfg);
    }
  },

  _request: function(cfg) {
    if (!cfg.onetime) {
      cfg.lastrun = Date.now() / 1000;
    }
    if (typeof cfg.start_handler === 'function') {
      cfg.start_handler(cfg);
    }
    cms_busy();
    return $.ajax(cfg.url, {
      data: cfg.data,
      cache: false
    }).always(function() {
      cms_busy(false);
    }).fail(function(jqXHR, textStatus, errorThrown) {
      console.debug('ajax call to ' + cfg.url + ' failed: ' + errorThrown);
    }).done(function(data, textStatus, jqXHR) {
      if (cfg.insert && cfg.element) {
        //TODO use text()|val() where relevant for element and data
        if (cfg.element instanceof jQuery) {
          cfg.element.html(data);
        } else {
          $(cfg.element).html(data);
        }
      }
      if (typeof cfg.done_handler === 'function') {
        cfg.done_handler(data, textStatus, jqXHR);
      }
    });
  }
};
