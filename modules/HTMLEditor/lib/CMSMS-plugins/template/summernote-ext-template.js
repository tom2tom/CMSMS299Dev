(function (factory) {
    /* global define */
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
}(function ($) {
  $.extend(true, $.summernote.lang, {
    'en-US': {
      template: {
        tooltip: 'Content template'
      }
    }
  });

  $.extend($.summernote.plugins, {
    template: function(context) {
      var plugopts = {
        urlroot: null, // absolute url of template data
        manifest: null, // json file in urlroot
        tplitems: {}, // each like filename(no extension): templates-menu label
        tpltips: {} // any/all like filename(no extension): templates-menu tooltip
      };
      try {
        $.extend(true, plugopts, context.options.pluginopts.template);
      } catch(m) {}

      if (plugopts.urlroot && (plugopts.manifest || plugopts.tplitems && !$.isEmptyObject(plugopts.tplitems))) {
        var ui = $.summernote.ui,
          lang = context.options.langInfo,
          options = $.extend(true, {}, plugopts, context.options);

        context.memo('button.template', function() {
          return ui.buttonGroup({
            className: 'note-template',
            children: [
              ui.button({
                className: 'dropdown-toggle',
                container: options.container,
                contents: ui.dropdownButtonContents(
                  '<i class="note-iconc-template"></i>', context.options
                ),
                tooltip: lang.template.tooltip,
                data: {
                  toggle: 'dropdown'
                }
              }),
              ui.dropdown({
                className: 'dropdown-menu dropdown-style',
                callback: function($node) {
                  createDropdownContent($node[0]); //possibly-async setup
                }
              })
            ]
          }).render();
        });
      }

      function createDropdownContent(node) {
        var dropDowns = '';
        if (plugopts.manifest) {
          $.ajax(plugopts.urlroot + '/' + plugopts.manifest, {
            dataType: 'json'
          })
          .done(function(list) {
            //array, each member an object like {
            //"file": "basename no extension",
            //"title": "whatever" or absent,
            //"titlekey": "whatever or empty" or absent,
            //"tip": "whatever or empty" or absent,
            //"tipkey": "whatever or empty" or absent }
            list.forEach(function(data, idx) {
              dropDowns += '<a class="note-dropdown-item" href="#" role="listitem" data-item="" data-value="' + data.file + '"';
              if (data.tipkey) {
                var tp = cms_data[data.tipkey] || data.tip || '';
                if (tp) {
                  dropDowns += ' title="' + tp + '"';
                }
              } else if (data.tip) {
                dropDowns += ' title="' + data.tip + '"';
              }
              var lbl;
              if (data.titlekey) {
                lbl = cms_data[data.titlekey] || data.title || 'Template' + (idx+1);
              } else if (data.title) {
                lbl = data.title;
              } else {
                lbl = 'Template' + (idx+1);
              }
              dropDowns += ' aria-label="' + lbl + '">' + lbl + '</a>';
            });
            //TODO sort menu by label
            node.innerHTML = dropDowns;
            connectDropdownContent(node);
          })
          .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Templates list not retrieved: ' + errorThrown);
            alert('Templates list error ' + errorThrown);
          });
        } else {
          for (var fileName in plugopts.tplitems) {
            if (plugopts.tplitems.hasOwnProperty(fileName)) {
              dropDowns += '<a class="note-dropdown-item" href="#" role="listitem" data-item="" data-value="' + fileName + '"';
              if (plugopts.tpltips.hasOwnProperty(fileName)) {
                dropDowns += ' title="' + plugopts.tpltips[fileName] + '"';
              }
              dropDowns +=  ' aria-label="' + plugopts.tplitems[fileName] + '">' + plugopts.tplitems[fileName] + '</a>';
            }
          }
          node.innerHTML = dropDowns;
          connectDropdownContent(node);
        }
      }

      function connectDropdownContent(node) {
        $(node).find('a').on('click', function(ev) {
          ev.preventDefault();
          var value = $(this).attr('data-value'),
            url = options.urlroot + '/' + value + '.htpl';
          $.get(url)
            .done(function(data) {
               //TODO group all insertions for undo/redo purposes
              $(data).each(function(idx, node) {
                context.invoke('editor.insertNode', node); // TODO OK if nested e.g. select, list ?
              });
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
              alert(value + ' template not found at ' + url);
            });
        });
      }
    }
  });
}));
