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
      columns: {
        label: 'Columns',
        tooltip: 'Flexible columns',
        header: 'Column #'
      }
    }
  });

  $.extend($.summernote.plugins, {
    columns: function(context) {
      var plugopts = {
      // styling for flex-row containing 1-4 flex-column(s)
/*        wrapper: 'row',
        columns: [
         'col-md-12',
         'col-md-6',
         'col-md-4',
         'col-md-3'
        ],
        columnsInsert: null,
        onColumnsInsert: false
*/
      };
      try {
        $.extend(true, plugopts, context.options.pluginopts.columns);
      } catch(m) {}
      var options = $.extend({}, plugopts, context.options);
      var lang = options.langInfo;
//    var callbacks = options.callbacks;
      var ui = $.summernote.ui;

      context.memo('button.columns', function() {
        return ui.buttonGroup({
          className: 'note-columns',
          children: [
            ui.button({
              className: 'dropdown-toggle',
              container: options.container,
              contents: ui.dropdownButtonContents(
               '<i class="note-iconc-columns"></i>', options
              ),
              tooltip: lang.columns.tooltip, //TODO suitable container
              data: {
                toggle: 'dropdown'
              }
            }),
            ui.dropdown({
              className: 'dropdown-menu dropdown-style',
              callback: function($node) {
                for (var i = 0; i < options.columns.length; i++) {
                  if (options.columns[i]) {
                    var h = '<a class="note-dropdown-item" href="#" role="listitem" data-item="" data-value="' +
                     (i + 1) + '" aria-label="' + (i + 1) + ' ' + lang.columns.label + '">' +
                     (i + 1) + ' ' + lang.columns.label + '</a>';
                    $(h).appendTo($node).on('click', function(ev) {
                      ev.preventDefault();
                      var count = $(this).attr('data-value'),
                        wrap = createColumnsNode(count);
                      if (typeof options.columnsInsert  === 'function') {
                        columnsInsert(wrap);
                      } else if (options.onColumnsInsert) {
                        context.triggerEvent('columns.insert', wrap);
                      } else {
                        context.invoke('editor.insertNode', wrap);
                      }
                    });
                  }
                }
              }
            })
          ]
        }).render();
      });

      function createColumnsNode(count) {
        var wrap = document.createElement('div');

        wrap.className = options.wrapper;

        for (var i = 0; i < count; i++) {
          var col = document.createElement('div');
          var p = document.createElement('p');

          col.className = options.columns[count-1];
          p.innerHTML = lang.columns.header + (i + 1);

          col.appendChild(p);
          wrap.appendChild(col);
        }
        return wrap;
      }
    }
  });
}));
