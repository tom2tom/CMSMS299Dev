(function(factory) {
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

  $.extend($.summernote.plugins, {
    /**
     * @param {Object} context - context object has status of editor
     */
    'plugName': function(context) {
      var self = this,
        ui = $.summernote.ui,
        options = $.extend({}, {WHATEVER defaults}, context.options),
        // events will be attached when editor is initialized
        events = {
          // called after modules are initialized
          'summernote.init': function(we, e) {
            // eslint-disable-next-line
            console.log('summernote initialized', we, e);
          },
          // called when user releases a key on editable
          'summernote.keyup': function(we, e) {
            // eslint-disable-next-line
            console.log('summernote keyup', we, e);
          }
        },
        $panel;

      // add plugName button
      context.memo('button.plugName', function() {
        // create button
        const button = ui.button({
          container: options.container,
          contents: '<i class="fa fa-child"></i> plugName',
          tooltip: 'plugName',
          click: function() {
            self.$panel.show();
            self.$panel.hide(500);
            // invoke insertText method with 'plugName' on editor module
            context.invoke('editor.insertText', 'plugName');
          }
        });
        // return jQuery object created from button instance
        return button.render();
      });

      // called when editor is initialized by $('..').summernote()
      this.initialize = function() {
        this.$panel = $('<div class="plugName-panel"></div>').css({
          position: 'absolute',
          width: 100,
          height: 100,
          left: '50%',
          top: '50%',
          background: 'red',
        }).hide();

        this.$panel.appendTo('body');
      };

      // called when editor is destroyed by $('..').summernote('destroy')
      // should remove elements generated during initialize()
      this.destroy = function() {
        this.$panel.remove();
        this.$panel = null;
      };
    },
  });
}));
