(function(factory) {
/* Global define */
if (typeof define === 'function' && define.amd) {
  // AMD. Register as an anonymous module.
  define(['jquery'], factory);
} else if (typeof module === 'object' && module.exports) {
  // Node/CommonJS
  module.exports = factory(require('jquery'));
} else {
  // Browser globals
  factory(window.jQuery);
}
} (function($) {
  $.extend(true, $.summernote.lang, {
    'en-US': {
      seeblocks: {
        tooltip: 'Element boundaries',
      }
    }
  });

  $.extend($.summernote.plugins, {
    seeblocks: function(context) {
/*    var plugopts = {};
      try {
        $.extend(true, plugopts, context.options.pluginopts.seeblocks);
      } catch(m) {}
      var options = $.extend({}, plugopts, context.options);
*/
      var options = context.options;
      var lang = options.langInfo;
      var ui = $.summernote.ui;
      var shown = false;

      context.memo('button.seeblocks', function() {
        return ui.button({
          className: 'note-seeblocks',
          container: options.container,
          contents: '<i class="note-iconc-seeblocks"></i>',
          tooltip: lang.seeblocks.tooltip, //TODO suitable container
          click: function(e) {
            context.layoutInfo.editable.toggleClass('visblock');
          }
        }).render();
      });
    }
  });
}));
