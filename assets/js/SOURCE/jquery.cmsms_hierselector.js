/**
 * @name hierselector
 * @namespace cmsms
 * @example
 * $('#myinput').cmsms_hierselector();
 */
/*!
jQuery hierselector widget v.1.1
(C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL2+
*/
(function($) {
    $.widget('cmsms.hierselector', {
        options: {
            current: 0, // the numeric id of the current content item we are working on
            selected: 0, // the numeric id of the selected content item, or -1
            is_manager: false, // the current user may modify every page
            allow_current: true, // the user may select the current page. If current > -1, then also may select a child of current
            use_perms: false, // use permissions to control what is selectable
            use_simple: false, // use a simple dropdown... implied if use_perms is true
            allow_all: false, // show all content items, even those that don't have usable links
            for_child: false // we want to add a child page
        },
        /**
         * @ignore
         */
        _create: function() {
            // initialization
            this.data = {};
            // cache some properties of the target element
            this.data.orig_val = this.element.val();
            this.data.name = this.element.attr('name');
            this.data.id = this.element.attr('id');
            var self = this,
                opts = {
                    op: 'pageinfo',
                    page: this.data.orig_val
                };
            opts[cms_data.secure_param_name] = cms_data.user_key;

            return $.ajax(cms_data.ajax_hiersel_url, {
                data: opts,
                dataType: 'json'
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.debug('selector creation failed: ' + errorThrown);
            }).done(function(result) {
                if (result.status === 'success') {
                    var data = result.data;
                    self.data.orig_val = data.content_id;
                    self.data.orig_idhier = data.id_hierarchy;
                    try {
                        self.data.orig_pages = data.id_hierarchy.split('.');
                    } catch (e) {
                        self.data.orig_pages = [data.id_hierarchy];
                    }
                    self.element.hide().val('').removeAttr('name').attr('readonly', 'readonly');
                    self.data.hidden_e = $('<input type="hidden" name="' + self.data.name + '" value="' + self.data.orig_val + '" />').insertAfter(self.element);
                    self._setup_dropdowns();
                } else {
                    console.debug('selector creation failed: ' + result.message);
                }
            });
        },

        _setOption: function(k, v) {
            this.options[k] = v;
            this._setup_dropdowns();
        },

        _setup_dropdowns: function() {
            if (this.options.use_simple) {
                this._setup_simple_dropdown();
            } else {
                this._setup_smart_dropdowns();
            }
        },

        _build_simple_select: function(name, data, selected_id, hilite) {
            var str = cms_lang('hierselect_title'),
                sel = $('<select></select>').attr({
                    id: name,
                    title: str
                }).addClass('cms_selhier');
            sel.on('change', function() {
                var t = $(this),
                    v = t.val();
                t.trigger('cmsms_formchange', {
                    elem: t,
                    value: v
                });
            });
            for (var i = 0; i < data.length; i++) {
                var depth;
                try {
                    depth = data[i].hierarchy.split('.').length;
                } catch (e) {
                    depth = 1;
                }
                str = '&nbsp;&nbsp;'.repeat(depth - 1) + data[i].display;
                //NB comparisons maybe string<>int so NOT === operator
                var opt = $('<option>' + str + '</option>').attr('value', data[i].content_id);
//              if ( data[i].content_id == current ) opt.addclass('current');
                if (data[i].content_id == selected_id) {
                    opt.attr('selected', 'selected').addClass('selected');
                }
                if (data[i].content_id == hilite) {
                    opt.addClass('hilite');
                }
                if (data[i].content_id == this.options.selected && !this.options.allow_current) {
                    opt.attr('disabled', 'disabled');
                }
                if (this.options.use_perms && !data[i].can_edit) {
                    opt.attr('disabled', 'disabled').addClass('nochildren');
                }
//              else if ( this.options.for_child && !data[i].has_children && !data[i].wants_children ) { opt.attr('disabled','disabled').addClass('nochildren'); }
                sel.append(opt);
            }
            return sel;
        },

        _build_smart_select: function(name, data, selected_id, parent_selectable, hilite) {
            var self = this,
                str = cms_lang('hierselect_title');
            sel = $('<select></select>').attr({
                id: name,
                title: str
            }).addClass('cms_selhier');
            sel.on('change', function() {
                var t = $(this),
                    v = t.val();
                if (v < 1) {
                    v = t.prev('select').val();
                    if (typeof v === 'undefined') v = -1;
                }
                self.data.hidden_e.val(v).change();
                self._setup_smart_dropdowns();
                t.trigger('cmsms_formchange', {
                    elem: t,
                    value: v
                });
            });
            if (parent_selectable) {
                var opt = $('<option>' + cms_lang('none') + '</option>').attr('value', -1);
                sel.append(opt);
            }
            //NB comparisons maybe string<>int so NOT === operator
            for (i = 0; i < data.length; i++) {
                var opt = $('<option>' + data[i].display + '</option>').attr('value', data[i].content_id);
//              if ( data[i].content_id == current ) { opt.addclass('current'); }
                if (data[i].content_id == selected_id) {
                    opt.attr('selected', 'selected').addClass('selected');
                }
                if (data[i].content_id == hilite) {
                    opt.addClass('hilite');
                }
                if (data[i].content_id == this.options.selected && !this.options.allow_current) {
                    opt.attr('disabled', 'disabled');
                }
                if (!data[i].has_children && this.options.use_perms && !data[i].can_edit) {
                    opt.attr('disabled', 'disabled').addClass('nochildren');
                } else if (this.options.for_child && !data[i].has_children && !data[i].wants_children) {
                    opt.attr('disabled', 'disabled').addClass('nochildren');
                }
                sel.append(opt);
            }
            return sel;
        },

        _setup_smart_dropdowns: function() {
            var cur_val = this.data.hidden_e.val(),
                opts = {
                    op: 'here_up',
                    page: cur_val
                };
            opts[cms_data.secure_param_name] = cms_data.user_key;
            for (var pn in this.options) { // $.extend alternative: no inherited properties
                if (this.options.hasOwnProperty(pn)) {
                    opts[pn] = this.options[pn];
                }
            }

            var self = this;
            return $.ajax(cms_data.ajax_hiersel_url, {
                data: opts,
                dataType: 'json'
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.debug('smart dropdown population failed: ' + errorThrown);
            }).done(function(result) {
                if (result.status !== 'success') {
                    console.debug('smart dropdown population failed: ' + result.message);
                    return;
                }
                var data = result.data,
                    found_cur = '',
                    parent_selectable;
                if (self.options.for_child && self.options.use_perms && !self.options.is_manager) {
                    parent_selectable = false; // only managers can add child pages at top level
                } else {
                    parent_selectable = true;
                }
                for (var idx = 0; idx < data.length && !found_cur; idx++) {
                    if (!data[idx]) {
                        continue;
                    }
                    for (var x2 = 0; x2 < data[idx].length && !found_cur; x2++) {
                        if (data[idx][x2].content_id == cur_val) {
                            found_cur = data[idx][x2].id_hierarchy;
                            break;
                        }
                    }
                }
                var cur_pages = [];
                if (found_cur) {
                    try {
                        cur_pages = found_cur.split('.');
                    } catch (e) {
                        cur_pages = [found_cur];
                    }
                }
                self.element.prevAll('select.cms_selhier').remove();
                self.element.val('');
                for (idx = 0; idx < data.length; idx++) {
                    if (data[idx] == null) {
                        continue;
                    }
                    var selected = (idx < cur_pages.length) ? cur_pages[idx] : -1;
                    if (selected) {
                        for (var x2 = 0; x2 < data[idx].length; x2++) {
                            if (data[idx][x2].content_id == selected) {
                                // if we are using permissions, and not a manager, and otherwise cannot edit this page
                                // then we disable the <none> item in this item's select field.
                                if (!self.options.for_child && !data[idx][x2].has_usable_link) {
                                    parent_selectable = false;
                                } else if (self.options.for_child && self.options.use_perms && !self.options.is_manager && !data[idx][x2].can_edit) {
                                    parent_selectable = false;
                                } else {
                                    parent_selectable = data[idx][x2].wants_children;
                                }
                                break;
                            }
                        }
                    }
                    var orig_page = (self.data.orig_pages && idx < self.data.orig_pages.length) ? self.data.orig_pages[idx] : -100;
                    var sel = self._build_smart_select(self.data.id + '_' + idx, data[idx], selected, parent_selectable, orig_page);
                    sel.insertBefore(self.element);
                }
            });
        },

        _setup_simple_dropdown: function() {
            var self = this,
                opts = {
                    op: 'userpages'
                };
            opts[cms_data.secure_param_name] = cms_data.user_key;
            for (var pn in this.options) {
                if (this.options.hasOwnProperty(pn)) {
                    opts[pn] = this.options[pn];
                }
            }

            return $.ajax(cms_data.ajax_hiersel_url, {
                data: opts,
                dataType: 'json'
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.debug('simple dropdown population failed: ' + errorThrown);
            }).done(function(result) {
                if (result.status === 'success') {
                    var data = result.data,
                        v = self.data.hidden_e.val(),
                        sel = self._build_simple_select(self.data.id + '_0', data, v, true, -1);
                    sel.insertBefore(self.element);
                } else {
                    console.debug('simple dropdown population failed: ' + result.message);
                }
            });
        },

        _noop: function() {}
    });
})(jQuery);
