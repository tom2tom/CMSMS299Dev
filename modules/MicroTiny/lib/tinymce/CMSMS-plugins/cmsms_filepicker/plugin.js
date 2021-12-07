(function() {
  var cmsms_filepicker = (function() {
    'use strict';

    function picker_callback(callback, value, meta) {
//  if (typeof top.filepicker != 'undefined') alert('woot');
      var height, width, mywin;
      if (window.innerHeight < 650) {
        height = Math.max(window.innerHeight * 0.8, 250);
      } else {
        height = 600;
      }
      if (window.innerWidth < 950) {
        width = Math.max(window.innerWidth * 0.8, 250);
      } else {
        width = 900;
      }
      // generate a unique id for the active editor so we can access it later.
      var inst = 'i' + (new Date().getTime()).toString(16);
      tinymce.activeEditor.dom.setAttrib(tinymce.activeEditor.dom.select('html'), 'data-cmsfp-instance', inst);
      if (!top.document.CMSFileBrowser) top.document.CMSFileBrowser = {};
      top.document.CMSFileBrowser.onselect = function(inst, file) {
        file = cms_data.root_url + '/' + file;

        function basename(str) {
          var p = str.lastIndexOf('/') + 1,
           base = str.substring(p);
          p = base.lastIndexOf('.');
          if (p !== -1) base = base.substring(0, p);
          return base;
        }
        var opts = {};
        if (meta.filetype === 'file') {
          opts.text = basename(file);
        } else if (meta.filetype === 'image') {
          opts.alt = basename(file);
/*        opts.height = 50;
          opts.width = 75;
*/
        }
        callback(file, opts);
        top.document.CMSFileBrowser.onselect = null;
        mywin.close();
      };
      // here we open the filepicker window.
      var url = cmsms_tiny.filepicker_url + '&inst=' + inst + '&type=' + meta.filetype;
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
        onFileSelected: function(filename) {
          console.debug('woot got callback with ' + filename);
        }
      });
    }
    tinymce.util.Tools.resolve('tinymce.PluginManager').add('cmsms_filepicker', function(editor, pluginUrl) {
      editor.settings.file_picker_type = 'file image media'; // TODO
      editor.settings.file_picker_callback = picker_callback; // TODO
    });

    function Plugin() {}
    return Plugin;
  }());
})();
