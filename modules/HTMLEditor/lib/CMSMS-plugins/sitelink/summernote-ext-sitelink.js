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
/*
  $.extend(true, $.summernote.lang, {
    'en-US': {
      sitelink: {}
    }
  });
*/
  $.extend($.summernote.plugins, {
    sitelink: function(context) {
      var plugopts = {
      //default options go here
      };
      try {
        $.extend(true, plugopts, context.options.pluginopts.sitelink);
      } catch(m) {}
      var ui = $.summernote.ui,
        options = $.extend({}, plugopts, context.options),
//      lang = options.langInfo,
        $editor = context.layoutInfo.editor, //​0: div.note-editor.note-frame
//      $editable = context.layoutInfo.editable, //​0: div.note-editable
        tagparams, // parameters of existing tag, if any
        uidata, // some of tagparams used here in the popup dialog
        nocache, // whether a nocache param is present in the tag
        relpage, // whether the tag represents a relative page
        tagmatch, //regex match-data array or false
        originaltext,
        $dialog;

      context.memo('button.sitelink', function() {
        return ui.button({
          container: options.container,
          contents: '<i class="note-iconc-site"></i><i class="note-iconc-pageovr"></i>',
          tooltip: cms_data.linker_btn_title,
          click: function(e) {
            context.invoke('saveRange');
            context.invoke('sitelink.show');
          }
        }).render();
      });

      this.initialize = function() {
        var $container = options.dialogsInBody ? $(document.body) : $editor;
        var body =
        '<div id="sitelink_tabs">\n' +
        ' <ul>\n' +
        '  <li>\n' +
        '   <a href="#tab_slgen">'+ cms_data.tab_general + '</a>\n' +
        '  </li>\n' +
        '  <li>\n' +
        '   <a href="#tab_sladv">' + cms_data.tab_advanced + '</a>\n' +
        '  </li>\n' +
        ' </ul>\n' +
        ' <div>\n' +
        '  <div id="tab_slgen">\n' +
        '   <div class="form-group oneline button">\n' +
        '    <label class="control-label note-form-label" for="sitelink_title">' + cms_data.pgtitle + '</label>\n' +
        '    <input type="text" id="sitelink_title" class="form-control note-form-control note-input" size="38">\n' +
        '    <span id="sitelink_browse" class="form-control note-form-control note-input action-button" title="' + cms_data.browse + '">\n' +
        '     <i class="note-iconc-search"></i>\n' +
        '    </span>\n' +
        '   </div>\n' +
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="sitelink_alias">' + cms_data.alias + '</label>\n' +
        '    <input type="text" id="sitelink_alias" class="form-control note-form-control note-input" size="38" readonly>\n' +
        '   </div>\n' +
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="sitelink_text">' + cms_data.texttodisplay + '</label>\n' +
        '    <input type="text" id="sitelink_text" class="form-control note-form-control note-input" size="38">\n' +
        '   </div>\n' +
        '  </div>\n' +
        '  <div id="tab_sladv">\n' +
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="sitelink_tgt">' + cms_data.target + '</label>\n' +
        '    <select id="sitelink_tgt">\n' +
        '     <option value="" selected>' + cms_data.none + '</option>\n' +
        '     <option value="_blank">' + cms_data.blank + '</option>\n' +
        '     <option value="_self">' + cms_data.self + '</option>\n' +
        '     <option value="_parent">' + cms_data.parent + '</option>\n' +
        '     <option value="_top">' + cms_data.top + '</option>\n' +
        '     <option value="framename">' + cms_data.framename + '</option>\n' +
        '    </select>\n' +
        '   </div>\n' +
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="sitelink_class">' + cms_data.attrclass + '</label>\n' +
        '    <input type="text" id="sitelink_class" class="form-control note-form-control note-input" size="38">\n' +
        '   </div>\n' +
        '  </div>\n' +
        ' </div>\n' +
        '</div>\n';

        $dialog = ui.dialog({
          title: cms_data.linker_dlg_title,
          body: body,
          footer: '<button id="sitelink_submit" class="note-btn note-btn-primary">' + cms_data.apply + '</button>'
        }).render().appendTo($container);
      };

      this.show = function() {
        tagparams = {};
        uidata = {};
        nocache = false;
        relpage = false;
        tagmatch = false;

        var rng = $.summernote.range.create(),
          found = locate(rng);
        if (found) {
          parsetag(tagmatch[0]);
        }

        showDialog(uidata).always(function() {
            ui.hideDialog($dialog);
          }).fail(function() {
            context.invoke('restoreRange');
          }).done(function() {
//          tagparams.page = already [re]set in dialog processor
            tagparams.text = $dialog.find('#sitelink_text').val();
            tagparams.class = $dialog.find('#sitelink_class').val();
            tagparams.target = $dialog.find('#sitelink_tgt').val();
            var tag = '{cms_selflink';
            for (var prop in tagparams) {
              if (tagparams.hasOwnProperty(prop)) {
                if (tagparams[prop] === null) {
                  tag += ' ' + prop;
                } else if (tagparams[prop] !== '') {
                  tag += ' ' + prop + '=\'' + tagparams[prop] + '\'';
                }
              }
            }
            if (nocache) {
              tag += ' nocache';
            }
            tag += '}';
//          var adbg = rng;
            context.invoke('restoreRange'); // just in case ...
//          var bdbg = rng;
            var newtext;
            if (found) {
              newtext = originaltext.replace(tagmatch[0],tag);
            } else {
              newtext = originaltext.substring(0,rng.so) + tag +
                originaltext.substring(rng.so);
            }
            rng.sc.textContent = newtext;
            context.layoutInfo.note.change();
          });
      };

      this.destroy = function() {
        ui.hideDialog($dialog);
        $dialog.remove();
      };

      /**
       * Light equivalent to jQuery UI's tabs processing.
       * Adapted to use Summernote-compatible element-attributes.
       */
      function tabify(wrapper) {
        var tabs = wrapper.children('ul').children('li'),
          panels = wrapper.children('div').children('div');

        wrapper.addClass('note-tabs-container')
         .children('ul').addClass('note-nav-tabs');
        wrapper.children('div').addClass('note-tabs-content');
        tabs
         .addClass('note-nav-item')
         .on('click activate', function() {
           panels
            .hide()
            .removeClass('active');
           tabs.removeClass('active')
            .children('a')
            .removeClass('active');
           var lnk = $(this).children('a'),
             pid = lnk.attr('href');
           $(pid).addClass('active').show();
           $(this).addClass('active');
           lnk.addClass('active');
        })
        .children('a')
         .addClass('note-nav-link')
         .on('click activate', function(event) {
           event.preventDefault();
         });
        panels
         .addClass('note-tab-content')
         .hide()
         .first()
         .addClass('active')
         .show();
        tabs
         .first()
         .addClass('active')
         .children('a')
         .addClass('active');
      }

      function untabify(wrapper) {
        var tabs = wrapper.children('ul').children('li');
        tabs.removeClass('active').off('click activate')
         .children('a').removeClass('active').off('click activate');
      }

      function xtrim(str) {
        var s = str.trim(),
          c = s.charAt(0);
        if ((c === '"' || c === '\'') && c === s.charAt(s.length - 1)) {
          s = s.substring(1, s.length - 1).trim(); //TODO allow quoted surrounding whitespace?
        }
        if (s.indexOf('nocache') > -1) {
          //record for later reconstruction
          nocache = true;
          s = s.replace('nocache', '').trim();
        }
        return s;
      }

      /**
       * Try to find an existing tag to be processed
       * Returns boolean indicating success
       */
      function locate(rng) {
        var mode;
        if (rng.so === rng.eo) {
          // no selection
          originaltext = rng.sc.parentNode.textContent;
          // process content around rng.so
          mode = 1;
        } else if (rng.sc === rng.ec) {
          // single-node selection
          originaltext = rng.sc.parentNode.textContent;
          // process node content around rng.so .. rng.eo
          mode = 2;
        } else {
          // multi-node selection
          originaltext = rng.sc.textContent.substring(rng.so).trim();
          // process first-node content around rng.so .. node-end
          mode = 3;
        }
        //scan content 'around' selection or cursor, for a selflink tag
        var re = /\{\s*cms_selflink.*?\}/gi;
        var m;
        do {
          m = re.exec(originaltext);
          if (m) {
            //check match is compatible with rng.so[, rng.eo]
            //m[0] == whole match
//          //m[1] == param(s)
            //m['index'] == start-offset of whole match
            var use = false,
              sm = m['index'],
              em = sm + m[0].length;
            switch (mode) {
                case 1:
                  use = (rng.so >= sm - 1 && rng.so <= em + 1);
                  break;
                case 2:
                  use = (rng.so >= sm - 1 && rng.so <= em + 1) ||
                    (rng.eo >= sm - 1 && rng.eo <= em + 1);
                  break;
                case 3:
                  var l = rng.sc.textContent.length;
                  use = (rng.so >= sm - 1 && rng.so <= l &&
                    rng.eo >= sm - 1 && rng.eo <= l);
                  break;
            }
            if (use) {
              tagmatch = m;
              return true; //TODO ensure lower-case 'cms_selflink'
            }
          }
        } while (m);
        return false;
      }

      function parsetag(str) {
        //process param(s)
/*      var valid = [
        'alt',
        'anchorlink',
        'assign',
        'class',
        'dir', //>>  relative-link direction i.e. no page-id etc
        'fragment',
        'height',
        'href', //>> like page, but href-only
        'id',
        'image',
        'imageonly',
        'label_side',
        'label',
        'menu',
        'more',
        'page',
        'rellink', //relative link
        'tabindex',
        'target', //link target
        'text',
        'title',
        'urlonly', //url for the page i.e. no surrounding node
        'urlparam',
        'width'
plus smarty-generics like 'nocache'
       ];
of which, the ones usable here are:
'page' >> page identifier, int id or string alias
'text' >> displayed text
'target' >> link target
'class' >> link class
*/
        var usable = ['page','text','class','target'];
        // pre-parse tag string
        var p = str.indexOf('selflink'), //TODO caseless match
          s = str.substring(p+5);
//      var s = 'ink a = b  "C"="D" nocache }';
//      var s = "ink a = b  'C'='D' nocache e= ' F' }";
        var re = /(["'].+?["']|\w+?)(\s+(.*?))?(\s*=\s*|\})/g;
        var nm = '', val, m;
        do {
          m = re.exec(s);
          if (m) {
            val = xtrim(m[1]);
            if (nm) {
//            if (valid.indexOf(nm) != -1) {
              tagparams[nm] = val;
              if (usable.indexOf(nm) != -1) {
                //record for dialog use
                uidata[nm] = val;
              } else if (['rellink','dir'].indexOf(nm) != -1) {
                relpage = true;
              }
              //OTHER SPECIAL CASES?
//            }
            }
            if (typeof m[2] !== 'undefined') {
              nm = xtrim(m[2]); //next name
            } else {
              break;
            }
          }
        } while (m);
        if (relpage) {
          delete tagparams.page; // if any
          delete uidata.page;
        }
      }
/*
      function keyDownEventHandler(e) {
        var keyCode = e.keyCode;
        if (keyCode === undefined || keyCode === null) {
          return;
        } else if (keyCode === 13) {
/ *        if (some test passes) {
            e.preventDefault();
            deferred.reject(uidata);
            return;
          }
* /
        }
      }
*/
      function selector(e) {
        e.preventDefault();
        return $.Deferred(function(deferred) {
          $.ajax(cms_data.linker_fill_url, {
            method: 'POST',
            dataType: 'json',
            data: {
              page: (uidata.page || null)//,
//            rtl: false // TODO get this from ?
              //ETC
            }
          }).fail(function(jqXHR, textStatus, errorThrown) {
            cms_notify('error', errorThrown); // TODO if cancellation? ignore if so
          }).done(function(sent) {
            var sbody =
             '   <div class="form-group">\n' +
             '    <nav id="menuwrap">\n' +
             '    ' + sent.body + '\n' +
             '    </nav>\n' +
             '   </div>\n';
            var $selector = ui.dialog({
              title: cms_data.linker_select_title,
              body: sbody,
              footer: '<button id="sitelink_select" class="note-btn note-btn-primary">' + cms_data.select + '</button>'
            }).render().appendTo($(document.body));

// done before $dialog.find('#sitelink_title').val(sent.title);
// ditto    $dialog.find('#sitelink_alias').val(sent.alias);

            ui.onDialogShown($selector, function() {
              $dialog.css('left','-9999em'); // NOT hide() ! revert by .css('left','0')
              $selector.css({'height':'auto','max-height':'20em'})
              .find("#menuwrap").stackMenu();
              var $picked = $selector.find('.stack-menu__link--active');
              if ($picked.length > 0) {
                $picked.trigger('click'); //show with parent (li) & siblings
                var $backer = $picked.siblings('ul').find('.stack-menu__link--back');
                if ($backer.length > 0) {
                  $backer.addClass('stack-menu__link--active'); // migrate active-indicator
                }
              }
              var $nopick = $selector.find('.close');
              $nopick.on('click activate', function(e) {
                e.preventDefault();
                ui.hideDialog($selector);
                deferred.reject();
              });
              $selector.find('#sitelink_select').on('click activate', function(e) {
                e.preventDefault();
                $picked = $selector.find('.stack-menu__link--active');
                if ($picked.length > 0) {
                  if ($picked.length > 1) {
                    $picked = $picked.filter('.stack-menu__link--hidden');
                  }
                  //report wanted values
                  var pid = $picked.parent('li').attr('data-pid');
                  tagparams.page = pid;
                  var titl = $picked[0].innerHTML;
                  $dialog.find('#sitelink_title').val(titl);
                  $.ajax(cms_data.linker_fill_url, {
                    method: 'POST',
                    dataType: 'json',
                    data: {
                      page: pid,
                      infoonly: 1
                    }
                  }).done(function(sent) {
                    $dialog.find('#sitelink_alias').val(sent.alias);
                  }).fail(function(jqXHR, textStatus, errorThrown) {
                    cms_notify('error', errorThrown);
                  });
                  ui.hideDialog($selector);
                  deferred.resolve();
                } else {
                  ui.hideDialog($selector);
                  deferred.reject();
                }
              });
              //ETC TODO
            });
            ui.onDialogHidden($selector, function() {
              $selector.remove();
              //ETC TODO
              $dialog.css('left', 0);
            });
            //ETC TODO
            ui.showDialog($selector);
          });
        });
      }

      function showDialog(uidata) {
        return $.Deferred(function(deferred) {
          var $submitBtn = $dialog.find('#sitelink_submit'),
           $closeBtn = $dialog.find('.close'),
           $browseBtn = $dialog.find('#sitelink_browse'),
           $tabswrapper = $dialog.find('#sitelink_tabs');
//         var $elurl = $dialog.find('#sitelink_page'),
//           $eltext = $dialog.find('#sitelink_text'),
          ui.onDialogShown($dialog, function() {
            tabify($tabswrapper); // setup tabs
            context.triggerEvent('dialog.shown');
            // initial focus
            //$some-element.trigger('focus');
            if (relpage) {
              var titl = cms_data.relpage;
              if (tagparams.dir) {
                titl += ' - ' + tagparams.dir;
              }
              $dialog.find('#sitelink_title').val(titl);
            } else {
              $.ajax(cms_data.linker_fill_url, {
                method: 'POST',
                dataType: 'json',
                data: {
                  page: (uidata.page || null),
                  infoonly: 1
                }
              }).done(function(sent) {
                $dialog.find('#sitelink_title').val(sent.title);
                $dialog.find('#sitelink_alias').val(sent.alias);
              }).fail(function(jqXHR, textStatus, errorThrown) {
                cms_notify('error', errorThrown);
              });
            }
            // bind buttons
            $submitBtn.on('click activate', function(e) {
              e.preventDefault();
              //report wanted values
              deferred.resolve({
//per supplied uidata & $dialog-element values
              });
            });
            $closeBtn.on('click activate', function(e) {
              e.preventDefault();
              deferred.reject();
            });
            $browseBtn.on('click activate', function(e) {
              $.when(selector(e))
              .done(function() {
                var here = 1; // TODO nothing here ?
              })
              .fail(function() { //jqXHR, textStatus, errorThrown
               // nothing here
              });
            });
            // bind keypresses
//          $dialog.on('keydown', keyDownEventHandler);
            // bind labels
            $dialog.find('label').on('click', function() {
              $(this).parent().find('.form-control').eq(0).trigger('focus');
            });
          });

          ui.onDialogHidden($dialog, function() {
            untabify($tabswrapper);
            $submitBtn.off('click activate');
            $closeBtn.off('click activate');
            $browseBtn.off('click activate');
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
