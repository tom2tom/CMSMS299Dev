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
/*$.extend(true, $.summernote.lang, {
    'en-US': {
      mailto: {
       dlgTitle: cms_data.mailto_dlg_title,
       btnTitle: cms_data.mailto_btn_title,
       submitLabel: cms_data.apply,
       address: cms_dataemailaddr,
       text: cms_datatexttodisplay
      }
    }
  });
*/
  $.extend($.summernote.plugins, {
    mailto: function(context) {
      var plugopts = {
      //default options go here
      };
      try {
        $.extend(true, plugopts, context.options.pluginopts.mailto);
      } catch(m) {}
      var ui = $.summernote.ui,
        options = $.extend({}, plugopts, context.options),
//      lang = options.langInfo,
        $editor = context.layoutInfo.editor, //​0: div.note-editor.note-frame
//      $editable = context.layoutInfo.editable, //​0: div.note-editable
        $dialog;

      context.memo('button.mailto', function() {
        return ui.button({
          className: 'note-mailto',
          container: options.container,
          contents: '<i class="note-iconc-mailto"></i>',
          tooltip: cms_data.mailto_btn_title,
          click: function(e) {
            context.invoke('saveRange');
            context.invoke('mailto.show');
          }
        }).render();
      });

      this.initialize = function() {
        var $container = options.dialogsInBody ? $(document.body) : $editor;
        var body =
          '<div class="form-group oneline">\n' +
          ' <label class="control-label note-form-label" for="mailto_addr">' + cms_data.emailaddr + '</label>\n' +
          ' <input type="text" id="mailto_addr" class="form-control note-form-control note-input" size="38" />\n' +
          '</div>\n' +
          '<div class="form-group oneline">\n' +
          ' <label class="control-label note-form-label" for="mailto_text">' + cms_data.texttodisplay + '</label>\n' +
          ' <input type="text" id="mailto_text" class="form-control note-form-control note-input" size="38" />\n' +
          '</div>\n';
 /* N/A    +
          '<div class="form-group oneline">\n' +
          ' <label class="control-label note-form-label" for="mailto_obscure">' + 'Obscured' + '</label>\n' +
          ' <input type="checkbox" id="mailto_obscure" class="form-control note-form-control note-input" value="1" />\n' +
          '</div>\n';
*/
        $dialog = ui.dialog({
          title: cms_data.mailto_dlg_title,
          body: body,
          footer: '<button id="mailto_submit" class="note-btn note-btn-primary">' + cms_data.apply + '</button>'
        }).render().appendTo($container);
      };

      this.show = function() {
        var email_val = '',
          text_val = '',
//        obscure_val = false,
          rng = $.summernote.range.create(),
          pnode = rng.sc.parentNode || null,
          params;

        //TODO check for & process obscured URL then obscure_val = true

        if (pnode && pnode.nodeName === 'A') {
          var mu = pnode.href;
          if (mu.startsWith('mailto:')) {
            email_val = mu.replace('mailto:', '');
            text_val = pnode.innerHTML; // NOT innerText ?
          }
        }
        if (!email_val) {
          text_val = rng.sc.textContent.substring(rng.so, rng.eo).trim();
        }
        params = {
          email: email_val,
          text: text_val//,
//        obscured: obscure_val
        };
        showDialog(params)
          .always(function() {
            ui.hideDialog($dialog);
          })
          .done(function(params) {
            var $inserter = $('<a>').attr('href', 'mailto::' + params.email).html(params.text);
            context.invoke('restoreRange');
            context.invoke('editor.insertNode', $inserter[0]);
          })
          .fail(function() {
            context.invoke('restoreRange');
          });
      };

      this.destroy = function() {
        ui.hideDialog($dialog);
        $dialog.remove();
      };
/*
      function keyDownEventHandler(e) {
        var keyCode = e.keyCode;
        if (keyCode === undefined || keyCode === null) {
          return;
        } else if (keyCode === 13) {
/ *
          if (some relevant test passes) {
            e.preventDefault();
            $closeBtn.trigger('click');
            return;
          }
* /
        }
      }
*/

      function showDialog(params) {
        return $.Deferred(function(deferred) {
          var $eladdr = $dialog.find('#mailto_addr'),
           $eltext = $dialog.find('#mailto_text'),
//         $el3 = $dialog.find('#mailto_obscure'),
           $submitBtn = $dialog.find('#mailto_submit'),
           $closeBtn = $dialog.find('.close');

          $eladdr.val(params.email);
          $eltext.val(params.text);
//        $el3.prop('checked', params.obscured);

          ui.onDialogShown($dialog, function() {
            context.triggerEvent('dialog.shown');
            // initial focus
            //$some-element.trigger('focus');
            // bind buttons
            $submitBtn.on('click activate', function(e) {
              e.preventDefault();
              //report wanted values
              deferred.resolve({
                email: $eladdr.val(),
                text: $eltext.val()//,
//              obscured: $el3.prop('checked')
              });
            });
            $closeBtn.on('click activate', function(e) {
              e.preventDefault();
              deferred.reject();
            });
            // bind keypresses
//          $dialog.on('keydown', keyDownEventHandler);
            // bind labels
            $dialog.find('label').on('click', function() {
              $(this).parent().find('.form-control').eq(0).trigger('focus');
            });
          });

          ui.onDialogHidden($dialog, function() {
            $submitBtn.off('click activate');
            $closeBtn.off('click activate');
//          $dialog.off('keydown', keyDownEventHandler);
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
}));
