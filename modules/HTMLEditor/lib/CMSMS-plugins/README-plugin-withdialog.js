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

$.extend(true, $.summernote.lang, {
  'en-US': {
    plugName: {
      dialogTitle: 'Whatever',
      tooltip: 'Whatever',
      //ETC
    }
  }
});

$.extend($.summernote.plugins, {
  'plugName': function(context) {
    var ui = $.summernote.ui,
      options = $.extend({}, this.defaults, context.options),
      lang = options.langInfo,
      $editor = context.layoutInfo.editor, //​0: div.note-editor.note-frame
      $editable = context.layoutInfo.editable, //​0: div.note-editable
      $dialog;

    // add plugName button
    context.memo('button.plugName', function() {
      const button = ui.button({
        container: options.container,
        contents: options.icon, //ETC
        tooltip: lang.plugName.tooltip,
        click: function(e) {
          //ETC
          context.invoke('saveRange');
          context.invoke('plugName.show'); // aka self:show()?
        }
      });
      return button.render();
    });

    // called when editor is initialized by $('..').summernote()
    this.initialize = function() {
      const $container = options.dialogsInBody ? $(document.body) : $editor;
      const body = '<div>relevant html</div>';
      /* body-elements often have custom &| bootstrap &| summernote-lite classes e.g.
        form-group
        form-control
        control-label
        input-group
        checkbox
        checkbox-success
        clearfix
        Some might be for DOM-interrogation rather than, or as well as, styling
      */

      $dialog = ui.dialog({
        title: lang.plugName.dialogTitle,
        body: body,
        footer: '<button class="btn note-btn btn-primary note-btn-primary" ETC>' + lang.plugName.WHATEVER + '</button>'
      }).render().appendTo($container);
    };

    // called when editor is destroyed by $('..').summernote('destroy')
    // should remove elements generated during initialize()
    this.destroy = function() {
      ui.hideDialog($dialog);
      $dialog.remove();
    };

    this.show = function() {
      const $node = $($editable.data('target')); //IS WHAT? c.f. $editable.restoreTarget()
      let params = {
        //WHATEVER
      };
      showDialog(params)
        .always(function() {
          ui.hideDialog($dialog);
        })
        .fail(function() {
          context.invoke('restoreRange');
        })
        .done(function(params) {
          const $inserter = $('<div>'),
            $collector = $('whatever');
          //interrogate params and $dialog element values
          // to populate and style $inserter and/or $collector
          $inserter.html($collector);
          context.invoke('restoreRange');
          context.invoke('editor.insertNode', $inserter[0]);
        });
    };

    // private methods etc
    function showDialog(params) {
      return $.Deferred(function(deferred) {
        //interrogate params and setup $dialog element values
        const $videoHref = $dialog.find('.note-video-attributes-href'),
            $submitBtn = $dialog.find('.note-video-attributes-btn');

        ui.onDialogShown($dialog, function() {
          context.triggerEvent('dialog.shown');
          // sumbit-button click handling
          $submitBtn.on('click', function(e) {
            e.preventDefault();
            deferred.resolve((MAYBE-UPDATED)-params);
          });
          // initial focus
          // e.g. $some-element[.val(whatever)].trigger('focus');
          // e.g. $dialog.find('.form-control').eq(0).trigger('focus').trigger('select');
          // bind enter-presses
          $dialog.find('whatever').on('keypress', function(e) {
            if (e.keyCode === 13) {
              $submitBtn.trigger('click');
            }
          });
          // bind labels
          $dialog.find('label').on('click', function() {
            $(this).parent().find('.form-control').eq(0).trigger('focus');
          });
        });

        ui.onDialogHidden($dialog, function() {
          $submitBtn.off('click');
          $dialog.find('whatever').off('keypress');
          $dialog.find('label').off('click');
          if (deferred.state() === 'pending') {
            deferred.reject();
          }
        });

        ui.showDialog($dialog);
      });
    }
  }
});

$.summernote.plugins.plugName.defaults = $.extend({}, {
  //ETC
  icon: '<i class="note-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 N N" width="N" height="N"><path d="whatever" /></svg></i>'
  },
  $.summernote.plugins.plugName.defaults || {});

}));
