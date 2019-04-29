
/**
 * @name cmsms.hierselector
 * @namespace cmsms.hierselector
 *
 * @example
 * $('#myinput').cmsms_hierselector();
 */
(function($) {
    $.widget('cmsms.hierselector', {
        options: {
            current: 0, // the content id of the current content item we are working on
            value: 0, // the content id of current value
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
            this.data.orig_val = this.element.val();
            this.data.name = this.element.attr('name');
            this.data.id = this.element.attr('id');
            this.data.hidden_e = $('<input type="hidden" name="' + this.data.name + '" value="' + this.data.orig_val + '" />').insertAfter(this.element);
            this.data.ajax_url = cms_data.ajax_hiersel_url;
            this.element.val('').removeAttr('name').attr('readonly', 'readonly').hide();
            var self = this,
             id = 'm1_',
             opts = {};
            opts[id + 'op'] = 'pageinfo';
//            opts[id + 't'] = $.now();
            opts[id + 'page'] = this.data.orig_val;
            $.ajax(this.data.ajax_url, {
                dataType: 'json',
                data: opts
            }).done(function(result) {
                if (result.status === 'success') {
                    var data = result.data;
                    self.data.orig_idhier = data.id_hierarchy;
                    try {
                        self.data.orig_pages = data.id_hierarchy.split('.');
                    } catch(e) {
                        self.data.orig_pages = [data.id_hierarchy];
                    }
                    self._setup_dropdowns();
                }
            }).fail(function(result) {
var x = 1; //debug
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
            var self = this;
            var sel = $('<select></select>').attr('id', name).addClass('cms_selhier').attr('title', cms_lang('hierselect_title'));
            sel.on('change', function() {
                var v = $(this).val();
                $(this).trigger('cmsms_formchange', {
                    'elem': $(this),
                    'value': v
                });
            });
            for (var i = 0; i < data.length; i++) {
                var depth;
                try {
                    depth = data[i].hierarchy.split('.').length;
                } catch(e) {
                    depth = 1;
                }
                var str = '&nbsp;&nbsp;'.repeat(depth - 1) + data[i].display;
                var opt = $('<option>' + str + '</option>').attr('value', data[i].content_id);
                //if ( data[i].content_id == current ) opt.addclass('current');
                if (data[i].content_id === selected_id) opt.attr('selected', 'selected').addClass('selected');
                if (data[i].content_id === hilite) opt.addClass('hilite');
                if (data[i].content_id === this.options.value && !this.options.allow_current) opt.attr('disabled', 'disabled');
                if (this.options.use_perms && !data[i].can_edit) opt.attr('disabled', 'disabled').addClass('nochildren');
                //if ( this.options.for_child && !data[i].has_children && !data[i].wants_children ) opt.attr('disabled','disabled').addClass('nochildren');
                sel.append(opt);
            }
            return sel;
        },
        _build_smart_select: function(name, data, selected_id, parent_selectable, hilite) {
            var self = this;
            var sel = $('<select></select>').attr('id', name).addClass('cms_selhier').attr('title', cms_lang('hierselect_title'));
            sel.on('change', function() {
                var v = $(this).val();
                if (v < 1) {
                    v = $(this).prev('select').val();
                    if (typeof v === 'undefined') v = -1;
                }
                self.data.hidden_e.val(v).change();
                self._setup_smart_dropdowns();
                $(this).trigger('cmsms_formchange', {
                    'elem': $(this),
                    'value': v
                });
            });
            if (parent_selectable) {
                var opt = $('<option>' + cms_lang('none') + '</option>').attr('value', -1);
                sel.append(opt);
            }
            for (var i = 0; i < data.length; i++) {
                var opt = $('<option>' + data[i].display + '</option>').attr('value', data[i].content_id);
                //if ( data[i].content_id == current ) opt.addclass('current');
                if (data[i].content_id === selected_id) opt.attr('selected', 'selected').addClass('selected');
                if (data[i].content_id === hilite) opt.addClass('hilite');
                if (data[i].content_id === this.options.value && !this.options.allow_current) opt.attr('disabled', 'disabled');
                if (!data[i].has_children && this.options.use_perms && !data[i].can_edit) opt.attr('disabled', 'disabled').addClass('nochildren');
                if (this.options.for_child && !data[i].has_children && !data[i].wants_children) opt.attr('disabled', 'disabled').addClass('nochildren');
                sel.append(opt);
            }
            return sel;
        },
        _setup_smart_dropdowns: function() {
            var self = this;
            self.element.prevAll('select.cms_selhier').remove();
            self.element.val('');
            var cur_val = this.data.hidden_e.val(),
             id = 'm1_',
             opts = {};
            opts[id + 'op'] = 'here_up';
//            opts[id + 't'] = $.now();
            opts[id + 'page'] = cur_val;
            for (var x1 in this.options) {
                if (this.options.hasOwnProperty(x1)) {
                    opts[id + x1] = this.options[x1];
                }
            }
            $.ajax(this.data.ajax_url, {
                dataType: 'json',
                data: opts
            }).done(function(result) {
                if (result.status !== 'success') {
                    return;
                }
                var cur_pages = false,
                 parent_selectable = true, // root level
                 data = result.data,
                 found_cur;
                if (self.options.for_child && self.options.use_perms && !self.options.is_manager) parent_selectable = false; // only managers can add child pages at top level.
                for (var x1 = 0; x1 < data.length && !found_cur; x1++) {
                    for (var x2 = 0; x2 < data[x1].length && !found_cur; x2++) {
                        if (data[x1][x2].content_id === cur_val) {
                            found_cur = data[x1][x2].id_hierarchy;
                            break;
                        }
                    }
                }
                if (found_cur) {
                    try {
                        cur_pages = found_cur.split('.');
                    } catch (e) {
                        cur_pages = [found_cur];
                    }
                }
                for (var idx = 0; idx < data.length; idx++) {
                    if (data[idx] === null) break;
                    var selected = (idx < cur_pages.length) ? cur_pages[idx] : -1;
                    var orig_page = (self.data.orig_pages && idx < self.data.orig_pages.length) ? self.data.orig_pages[idx] : -100;
                    var sel = self._build_smart_select(self.data.id + '_' + idx, data[idx], selected, parent_selectable, orig_page);
                    if (selected) {
                        for (var x2 = 0; x2 < data[idx].length; x2++) {
                            if (data[idx][x2].content_id === selected) {
                                parent_selectable = data[idx][x2].wants_children;
                                // if we are using permissions, and not a manager, and otherwise cannot edit this page
                                // then we disable the <none> item in this items select field.
                                if (!self.options.for_child && !data[idx][x2].has_usable_link) parent_selectable = false;
                                if (self.options.for_child && self.options.use_perms && !self.options.is_manager && !data[idx][x2].can_edit) parent_selectable = false;
                                break;
                            }
                        }
                    }
                    sel.insertBefore(self.element);
                }
            }).fail(function(result) {
var y = 1; //debug
            });
        },
        _setup_simple_dropdown: function() {
            var self = this,
             cur_val = this.data.hidden_e.val(),
             id = 'm1_',
             opts = {};
            opts[id + 'op'] = 'userpages';
//            opts[id + 't'] = $.now();
            for (var x1 in this.options) {
                if (this.options.hasOwnProperty(x1)) {
                    opts[id + x1] = this.options[x1];
                }
            }
            $.ajax(this.data.ajax_url, {
                dataType: 'json',
                data: opts
            }).done(function(result) {
                if (result.status === 'success') {
                    var data = result.data,
                     sel = self._build_simple_select(self.data.id + '_0', data, cur_val, true, -1);
                    sel.insertBefore(self.element);
                }
            }).fail(function(result) {
var z = 1; //debug
            });
        },
        _noop: function() {}
    });
})(jQuery);
