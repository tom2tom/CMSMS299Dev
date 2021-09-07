/*
jQuery dirtyForm widget
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
jQuery dirtyForm widget v.0.2
(C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL2+
*/
/**
@fileOverview code for CMSMS dirtyForm jQuery-widget
@version 0.2
@author Robert Campbell
@name dirtyForm
@namespace cmsms
@example $('#myform').dirtyForm();  $('#myform').dirtyForm({options});

Options:
 <dl>
   <dt>unloadmsg <em>(string)</em></dt>
   <dd>Optional text to display before the dirty form is unloaded. The default value is a translated internal string</dd>

   <dt>disabled <em>(boolean)</em></dt>
   <dd>Flag indicating if the plugin behaviour is disabled. The default value is false.</dd>

   <dt>formclass <em>(string)</em></dt>
   <dd>An optional string indicating the name of a CSS class to apply to the dirty form.
     The default value is 'dirtyForm'.
     If the dirty flag is set to false, this class will also be removed from the form.
   </dd>

   <dt>onDirty <em>function(jqobject, form)</em></dt>
   <dd>An optional function to be called when the form is first marked as dirty. The default value is null.</dd>

   <dt>beforeUnload <em>function(form-is-dirty)</em></dt>
   <dd>An optional function to be called before the form is unloaded. The default value is null.</dd>

   <dt>onUnload <em>function(form-is-dirty)</em></dt>
   <dd>An optional function to be called after the form is unloaded. The default value is null.</dd>

   <dt>unloadCancel <em>function()</em></dt>
   <dd>An optional function to be called after the form-unload has been aborted. The default value is null.</dd>
 </dl>
 @name $.cmsms.dirtyForm.options

Changelog: TODO
*/
(function($, window, document, undefined) {
    $.widget('cmsms.dirtyForm', {
        options: {
            unloadmsg: '',
            disabled: false,
            dirty: false,
            formClass: 'dirtyForm',
            onDirty: null,
            beforeUnload: null,
            onUnload: null,
            unloadCancel: null
        },
        /**
         * @ignore
         */
        _create: function() {
            if (!this.options.unloadmsg) {
                this.options.unloadmsg = cms_lang('confirm_leave');
            }

            var self = this;
            this.element.on('keyup', 'input[type!="submit"], select, textarea', function(e) {
                if (!(self.options.disabled || self.options.dirty)) {
                    self._setOption('dirty', true);
                }
            });
            this.element.on('change, cmsms_textchange', 'input[type!="submit"], select, textarea', function() {
                if (!(self.options.disabled || self.options.dirty)) {
                    self._setOption('dirty', true);
                }
            });
            this.element.on('cmsms_formchange', function(event) {
                if (!(self.options.disabled || self.options.dirty)) {
                    self._setOption('dirty', true);
                }
            });

            $(window).on('beforeunload', function() {
                console.debug('dirtyform beforeunload');
                if (self.options.disabled) return;
                var msg, res;
                if (typeof self.options.beforeUnload === 'function') {
                    res = self.options.beforeUnload(self.options.dirty);
                } else {
                    res = null;
                }
                if (res && res.length > 0) {
                    msg = res.trim();
                } else if (self.options.dirty) {
                    msg = self.options.unloadmsg.trim();
                } else {
                    msg = '';
                }

                if (msg && msg.length > 0) {
                    if (typeof self.options.unloadCancel === 'function') {
                        setTimeout(function() {
                            console.debug('in outer timer');
                            setTimeout(function() {
                                console.debug('dirtyform unloadCancel');
                                self.options.unloadCancel();
                            }, 1000);
                        }, 1);
                    }
                    return msg;
                }
            });

            $(window).on('unload', function() {
                if (self.options.disabled) return;
                console.debug('dirtyform unload');
                if (typeof self.options.onUnload === 'function') {
                    self.options.onUnload(self.options.dirty);
                }
            });
        },
        /**
         * @ignore
         */
        _setOption: function(k, v) {
            this.options[k] = v;
            if (k == 'disabled') {
                this.options.disabled = v;
            }
            if (k == 'dirty') {
                if (!v) {
                    console.debug('dirtyform dirty = false');
                    this.options.dirty = false;
                    this.element.find('form').removeClass(this.options.formClass);
                } else {
                    console.debug('dirtyform dirty = true');
                    var form = this.element.find('form').addClass(this.options.formClass);
                    this.options.dirty = true;
                    if (typeof this.options.onDirty === 'function') {
                      this.options.onDirty(this, form);
                    }
                }
            }
        }
    });
})(jQuery, window, document);
