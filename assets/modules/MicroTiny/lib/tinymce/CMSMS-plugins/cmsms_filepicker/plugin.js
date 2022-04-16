(function() {
  var cmsms_filepicker = (function() {
    'use strict';

    function picker_callback(callback, value, meta) {
//      if (typeof top.filepicker != 'undefined') alert('wtf');
      var i, height, width, mywin;
      i = window.innerHeight;
      if (i < 650) {
        i = (i * 0.8) | 0;
        height = Math.max(i, 250);
      } else {
        height = 600;
      }
      i = window.innerWidth;
      if (i < 950) {
        i = (i * 0.8) | 0;
        width = Math.max(i, 250);
      } else {
        width = 900;
      }
      var uctype = meta.filetype.toUpperCase();
      // generate a unique id for the active editor so we can access it later
      var inst = 'i' + (new Date().getTime()).toString(16);
      tinymce.activeEditor.dom.setAttrib(tinymce.activeEditor.dom.select('html'), 'data-cmsfp-instance', inst);
      if (!top.document.CMSFileBrowser) {
//        alert('wtf');
        top.document.CMSFileBrowser = { settings:{} };
      }
      top.document.CMSFileBrowser.settings.onselect = function(inst, file) {
        file = cms_data.root_url + '/' + file;

        function basename(str) {
          var p = str.lastIndexOf('/') + 1,
           base = str.substring(p);
          p = base.lastIndexOf('.');
          if (p !== -1) base = base.substring(0, p);
          return base;
        }

        var opts = {};
        if (uctype === 'ANY') {
          opts.text = basename(file);
        } else if (uctype === 'IMAGE') {
          opts.alt = basename(file);
        }
        callback(file, opts);
        top.document.CMSFileBrowser.settings.onselect = null;
        mywin.close();
      };
      // open the filepicker window
      var url = cmsms_tiny.filepicker_url + '&inst=' + inst + '&type=' + uctype;
      mywin = tinymce.activeEditor.windowManager.open({
        title: cmsms_tiny.filepicker_title,
        file: url,
        classes: 'filepicker',
        height: height,
        width: width,
        inline: 1,
        resizable: true,
        maximizable: true
      }, {
/* on show, resize, maximize, unmazimize ...
    val = $('.mce-container-body')[0].innerHeight - $('#fp-navbar)[0].outerHeight;
    $('#fp-wrap').css('max-height', val + 'px');
*/
        onFileSelected: function(filename) {
          console.debug('woot got callback with ' + filename);
        }
      });
    }
    tinymce.util.Tools.resolve('tinymce.PluginManager').add('cmsms_filepicker', function(editor, pluginUrl) {
      editor.settings.file_picker_type = 'file image media';
      editor.settings.file_picker_callback = picker_callback;
    });

    function Plugin() {}
    return Plugin;
  }());
})();
