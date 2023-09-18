/*!
CMSMS filepicker widget v.1.1
(C) 2014-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL3+
*/
/*
jQuery UI widget to interface (usually) FilePicker module action.filePicker
This indirectly generates page-content (html and js) for a stand-alone
popup dialog for use during the current request. It should be used during
the initial content-generation for the request i.e. not for async or
event-driven file-picking
Usage:
$('#someinput').filepicker(options);
options: {}
 btn_label: optional, 'Choose' or something like that
 remove_label: optional button label, 'Clear' or something like that
 remove_title: optional, title for clear-button
 required: optional bool, whether a choice (hence the input-element value) is mandatory
 start_path: optional url-relativizer, default cms_data.root_url
 title: optional dialog title, default cms_lang('select_file') or 'Select a File'
 url: optional where to request to generate the browse/pick UI, default cms_data.filepicker_url
      supplemented here by extra parameters per supplied options
 value: optional, initial value of the input-element which is being processed?

Additional url-parameter(s) may be included among the supplied options,
 by prepending 'param_' to their key. action.filepicker recognises:
'_enc' base-64-encoded change-directory parameters (used by associated FileBrowser)
'seldir' folder path (normally included in '_enc', used by associated FileBrowser)
'nosub' TBA (normally included in '_enc', used by associated FileBrowser)
'subdir' TBA (normally included in '_enc', used by associated FileBrowser)
'type' FileType-class constant-value (int) or constant-name 'IMAGE'...'ANY'
file-uploader parameters:
'extensions' optional rawurlencoded string, comma-separated extensions of files which may be uploaded
'mime' optional string rawurlencoded mimetype of files which may be uploaded
Corresponding values are processed verbatim i.e. no encoding etc

Deprecated element-classes are still used here:
cmsfp-choose >> cmsfp_choose
cmsms-fp_dlg >> cmsfp_dlg
*/
(function ($) {
  $.widget('cmsms.filepicker', {
    options: {},

    _destroy: function () {
      this._settings.button.remove();
      this._settings.iframe.remove();
      this._settings.iframe = this._settings.container = this._settings.popup = this._settings.button = null;
      this.element.removeAttr('data-cmsms-i').removeAttr('readonly').removeClass('cmsfp cmsfp_elem');
    },

    _create: function () {
      if (!(this.element[0] instanceof HTMLInputElement)) {
        throw 'filepicker: may only be applied to input elements';
      }
      // initialization
      this._settings = {};
      var self = this;
      if (typeof this.options.start_path === 'undefined') {
        this.options.start_path = cms_data.root_url;
      }
      this._settings.btn_label = (typeof this.options.btn_label !== 'undefined') ? this.options.btn_label : cms_lang('choose') + '...';
      // this creates the layout (deprecated class cmsfp-choose)
      this._settings.button = $('<button></button>',{text:this._settings.btn_label,'class':'adminsubmit cmsfp cmsfp_choose cmsfp-choose'});
      this.element.attr('readonly', 'readonly').addClass('cmsfp cmsfp_elem');
      this.element.after(this._settings.button);
      this.element.after(' ');
      if (typeof this.options.required === 'undefined' || !this.options.required) {
        var lbl;
        if (this.options.remove_label) {
          lbl = this.options.remove_label;
        } else if (typeof cms_data.lang_clear !== 'undefined') {
          lbl = cms_lang('clear');
        } else {
          lbl = 'Clear';
        }
        var el = this._settings.clear = $('<button></button>',{text:lbl,'class':'adminsubmit cmsfp cmsfp_clear'});
        if (this.options.remove_title) {
          el.attr('title', this.options.remove_title);
        }
        this._settings.button.after(el);
        this._settings.button.after(' ');
        this.element.prop('required', false);
      } else {
        this.element.prop('required', true);
      }
      // the cmsfp-instance data attribute contains our target element.
      this._settings.inst = this.element.attr('data-cmsfp-instance');
      if (!this._settings.inst) {
        // make sure we have an instance
        var inst = this._uniqid();
        this._settings.inst = inst;
        this.element.attr('data-cmsfp-instance', inst);
      }
      // make sure our iframe src url has all of the info we need, including the target element reference.
      this._settings.url = this.options.url;
      if (!this._settings.url) {
        if (typeof cms_data.filepicker_url !== 'undefined' && cms_data.filepicker_url) {
          this._settings.url = cms_data.filepicker_url;
        } else {
          throw "No filepicker_url in the cms_data values-bank";
        }
      }
      this._settings.url += '&inst=' + this._settings.inst;
      for (var prop in this.options) {
        if (prop.startsWith('param_')) {
          var val = this.options[prop];
          prop = prop.substr(6);
          this._settings.url += '&m1_' + prop + '=' + val;
        }
      }
      // when we click on the 'change' button or the element itself.
      this.element.on('click', function (ev) {
        ev.preventDefault();
        self.open();
      });
      this._settings.button.on('click', function (ev) {
        ev.preventDefault();
        self.open();
      });
      if (this._settings.clear) {
        // click on the 'clear' button.
        this._settings.clear.on('click', function (ev) {
          ev.preventDefault();
          self.element.val('');
          self._about_clear();
          self.element.trigger('change');
        });
      }
      // when a file is selected
      this.element.on('cmsfp:change', function (ev, file) {
        self._setOption('value', file);
        self._about_clear();
        self.close();
        self.element.trigger('change');
      });
      if (this.options.value) {
        this._setOption('value', this.options.value);
      } else {
        this.render();
      }
    },

    _uniqid: function () {
      return 'i' + (new Date().getTime()).toString(36);
    },

    _about_clear: function () {
      var v = this.element.val();
      if (v.length > 0) {
        this._settings.clear.show();
      } else {
        this._settings.clear.hide();
      }
    },

    _setOption: function (key, value) {
      if (key === 'value') {
//      value = this._relativePath( value, this.options.start_path );
        this.element.val(value);
      }
      this.render();
    },

    _create_popup: function () {
      if (this._settings.container) {
        return;
      }
      // build the container, with an iframe to the processor-action URL
      this._settings.iframe = $('<iframe></iframe>',{
        'class':'cmsfp cmsfp_frame',
        src: this._settings.url,
        name: 'x' + Date.now(),
        'data-cmsfp-inst': this._settings.inst
       });
      var title = this.options.title;
      if (!title) {
        if (typeof cms_data.lang_select_file !== 'undefined') {
          title = cms_lang('select_file');
        }
        if (!title) {
          title = 'Select a File';
        }
      }
      // deprecated class cmsms-fp_dlg
      this._settings.container = $('<div></div>',{
        'class':'cmsfp cmsfp_dlg cmsms-fp_dlg',
        title: title})
       .append(this._settings.iframe);
      $('body', document).append(this._settings.container);

      var self = this,
          wide = Math.min(window.innerWidth, 600),
          high = Math.min(window.innerHeight, 400);
      // put it into a dialog
      this._settings.popup = cms_dialog(this._settings.container, {
        autoOpen: false,
        width: wide,
        height: high,
//      modal: true, NO: may need supplementary mkdir dialog
        dialogClass: 'cmsfp_dlg cmsms-fp_dlg', // deprecated class cmsms-fp_dlg
        draggable: true,
        resizable: true,
        close: function (ev, ui) {
          self.close();
        }
      });
    },

    _relativePath: function (instr, relative_to) {
      if (typeof relative_to === 'undefined') {
        if (instr.startsWith(cms_data.uploads_url)) {
          relative_to = cms_data.uploads_url;
        } else if (instr.startsWith(cms_data.root_url)) {
          relative_to = cms_data.root_url;
        }
      }
      if (!instr.startsWith(relative_to)) {
        return;
      }
      var out = instr.substr(relative_to.length);
      if (out.startsWith('/')) {
        out = out.substr(1);
      }
      return out;
    },

    render: function () {
      this._about_clear();
    },

    close: function () {
      cms_dialog(this._settings.popup, 'destroy');
      this._settings.container.remove();
      this._settings.popup = null;
      this._settings.container = null;
    },

    open: function () {
      this._create_popup();
      cms_dialog(this._settings.popup, 'open');
    },

    _noop: function () {}
  });
})(jQuery);
