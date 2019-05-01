/*
jQuery lockManager widget
Copyright (C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
jQuery lockManager widget v.0.2.1
(C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL2+
*/
/**
 * @fileOverview CMSMS lockManager widget.
 * @version 0.2.1
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @name lockManager
 * @namespace cmsms
 *
 * @example
 * $('myobject').lockManager();   $('myobject').lockManager({options});
 * Options:
 *  touch_handler: default null
 *  lostlock_handler: default null
 *  error_handler: default null
 *  lock_timeout: default 60 (seconds)
 *  lock_refresh: default 60 (seconds)
 */
(function($) {
    $.widget('cmsms.lockManager', {
        options: {
            touch_handler: null,
            lostlock_handler: null,
            error_handler: null,
            lock_timeout: 60,
            lock_refresh: 60
        },
        _settings: {
            'locked': 0,
            'lock_id': -1,
            'lock_expires': -1
        },
        _error_handler: function(error) {
            if (typeof error === 'string') {
                var key = 'error_lock_' + error;
                var msg = 'Unknown Error';
                if (typeof this._settings.lang[key] != 'undefined') msg = this._settings.lang[key];
                error = {
                    'type': error,
                    'msg': msg
                };
            }
            if (typeof this.options.error_handler === 'function') {
                this.options.error_handler(error);
            } else {
                console.debug('Error: ' + error.type + ' - ' + error.msg);
            }
        },
        _lostlock_handler: function(error) {
            if (typeof this.options.lostlock_handler === 'function') {
                this.options.lostlock_handler(error);
            }
            console.debug('Error: ' + error.type + ' - ' + error.msg);
        },
        _create: function() {
            // do initial error checking (user key)
            if (typeof cms_data.admin_url !== 'undefined') this.options.admin_url = cms_data.admin_url;
            if (!this.options.admin_url) throw 'The admin_url parameter is not available for the lockManager widget';
            if (typeof cms_data.secure_param_name !== 'undefined') this.options.secure_param = cms_data.secure_param_name;
            if (!this.options.secure_param) throw 'The secure_param parameter is not available for the lockManager widget';
            if (typeof cms_data.user_key !== 'undefined') this.options.user_key = cms_data.user_key;
            if (!this.options.user_key) throw 'The user_key parameter is not available for the lockManager widget';
            if (!this.options.type) throw 'The lock type option (string) must be set for the lockManager widget';
            if (!this.options.oid) throw 'The object id (oid) option (integer) must be set for the lockManager widget';
            if (!this.options.uid) throw 'The user id (uid) option (string) must be set for the lockManager widget';
            this.options.lock_refresh = Math.max(this.options.lock_refresh, 30);
            this.options.lock_refresh = Math.min(this.options.lock_refresh, 3600);
            // do initial ajax connection to fill settings
            var self = this,
            ajax_url = this.options.admin_url + '/ajax_lock.php',
              params = {
                opt: 'setup',
                uid: this.options.uid
            };
            params[this.options.secure_param] = this.options.user_key;
            $.ajax(ajax_url, {
                data: params
            }).always(function(data, textStatus, jqXHR) {
                if (textStatus !== 'success') {
                    throw 'Problem communicating with ajax url ' + ajax_url;
                }
                if (data.status === 'error') {
                    self._error_handler(data.error);
                }
                self._settings = data;
                self._settings.ajax_url = ajax_url;
                self.options.change_noticed = false;
                if (typeof data.uid === 'undefined' || self.options.uid != data.uid) {
                    // for the first time, we can use the onError callback
                    self._error_handler('useridmismatch');
                    return;
                }
                if (self.options.lock_timeout) {
                    // setup our event handlers
                    self._setup_handlers();
                    // do our initial lock.
                    self._lock();
                }
            });
        },
        _setup_touch: function() {
            var self = this;
            var interval = self.options.lock_refresh;
            interval = Math.min(3600, Math.max(5, interval));
            if (typeof self._settings.touch_timer !== 'undefined') {
                clearTimeout(self._settings.touch_timer);
            }
            self._settings.touch_timer = setTimeout(function() {
                self._touch();
            }, interval * 1000);
        },
        _setup_handlers: function() {
            var self = this;
            this._settings.touch_skipped = 0;
            this.element.on('change', 'input:not([type=submit]), select, textarea', function() {
                self.options.change_noticed = true;
                if (self._settings.touch_skipped) {
                    self._touch();
                }
            });
            if (this.options.lock_refresh > 0) this._setup_touch();
        },
        _touch: function() {
            var self = this;
            if (self.options.change_noticed && self._settings.locked && self._settings.lock_id > 0) {
                // do ajax touch
                console.debug('lockmanager: touching lock');
                this._settings.touch_skipped = 0;
                var params = {
                    opt: 'touch',
                    type: this.options.type,
                    oid: this.options.oid,
                    uid: this.options.uid,
                    lock_id: this._settings.lock_id
                };
                params[this.options.secure_param] = this.options.user_key;
                $.ajax(self._settings.ajax_url, {
                    data: params
                }).always(function(data, textStatus, jqXHR) {
                    if (textStatus != 'success') throw 'Problem communicating with ajax url ' + self._settings.ajax_url;
                    if (data.status == 'error') {
                        if (data.error.type == 'cmsnolockexception') {
                            self._lostlock_handler(data.error);
                        } else {
                            self._error_handler(data.error);
                        }
                        // assume we are no longer locked...
                        self._settings.locked = 0;
                        self._settings.lock_id = -1;
                        self._settings.lock_expires = -1;
                        return;
                    }
                    if (typeof self.options.touch_handler === 'function') {
                        self.options.touch_handler();
                    }
                    self._settings.lock_expires = data.lock_expires;
                    self.options.change_noticed = false;
                });
            } else {
                this._settings.touch_skipped = 1;
            }
            this._setup_touch();
        },
        _lock: function() {
            var self = this;
            if (!self._settings.locked) {
                // do ajax lock
                var params = {
                    opt: 'lock',
                    type: this.options.type,
                    oid: this.options.oid,
                    uid: this.options.uid,
                    lifetime: this.options.lock_timeout
                };
                params[this.options.secure_param] = this.options.user_key;
                $.ajax(self._settings.ajax_url, {
                    data: params
                }).always(function(data, textStatus, jqXHR) {
                    if (textStatus != 'success') {
                        throw 'Problem communicating with ajax url ' + self._settings.ajax_url;
                    }
                    if (data.status == 'error') {
                        // todo: here handle the type of error.
                        self._error_handler(data.error);
                        return;
                    }
                    if (self.options.lock_handler) self.options.lock_handler();
                    self._settings.lock_id = data.lock_id;
                    self._settings.lock_expires = data.lock_expires;
                    self._settings.locked = 1;
                });
            }
        },
        relock: function() {
            this._lock();
        },
        unlock: function(ajax) {
            var self = this;
            if (self._settings.locked && self._settings.lock_id > 0) {
                // do ajax unlock
                var params = {
                    opt: 'unlock',
                    type: this.options.type,
                    oid: this.options.oid,
                    uid: this.options.uid,
                    lock_id: this._settings.lock_id
                };
                params[this.options.secure_param] = this.options.user_key;
                if (!ajax && navigator.sendBeacon) {
                    // woot, we can try a beacon (unlocking probably happens around request-end)
                    var data = new FormData();
                    for (var o in params) {
                        if (params.hasOwnProperty(o)) {
                            data.append(o, params[o]);
                        }
                    }
                    var r = navigator.sendBeacon(self._settings.ajax_url, data);
                    if (r) {
                        self._settings.locked = 0;
                        self._settings.lock_id = -1;
                        self._settings.lock_expires = -1;
                        return $.Deferred().resolve({
                            status: 'success'
                        }, '');
                    }
                    console.debug('unlock beacon failed... fallback to ajax');
                }
                return $.ajax(self._settings.ajax_url, {
                    type: 'POST',
                    cache: false,
                    data: params
                }).then(function(data, textStatus, xhr) {
                    if (xhr.status != 200) {
                        return $.Deferred().reject(data, textStatus);
                    }
                    if (textStatus != 'success') {
                        return $.Deferred().reject(data, textStatus);
                    }
                    if (data.status == 'error') {
                        return $.Deferred().reject(data, textStatus);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.debug('unlock failed: ' + textStatus + ' // ' + errorThrown + ' // ' + jqXHR.status);
                    setTimeout(function() {
                        // nothing here
                    }, 3000);
                }).done(function(data, textStatus) {
                    if (self.options.unlock_handler) self.options.unlock_handler();
                    self._settings.locked = 0;
                    self._settings.lock_id = -1;
                    self._settings.lock_expires = -1;
                });
            } else {
                return $.Deferred().resolve({
                    status: 'success'
                }, '');
            }
        },
        // mark the end of the functions
        _noop: function() {}
    });
})(jQuery);
