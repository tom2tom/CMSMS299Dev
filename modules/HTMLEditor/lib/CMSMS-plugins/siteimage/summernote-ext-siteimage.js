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
      siteimage: {}
    }
  });
*/
  $.extend($.summernote.plugins, {
    siteimage: function(context) {
      var plugopts = {
      //default options go here
      };
      try {
        $.extend(true, plugopts, context.options.pluginopts.siteimage);
      } catch(m) {}
      var ui = $.summernote.ui,
        options = $.extend({}, plugopts, context.options),
//      lang = options.langInfo,
        $editor = context.layoutInfo.editor, //​0: div.note-editor.note-frame
//      $editable = context.layoutInfo.editable, //​0: div.note-editable
        ajaxdata, //ajax-params cache
        browser, //loaded CMSCustomFileBrowser object, if any
        $dialog;

      context.memo('button.siteimage', function() {
        return ui.button({
          container: options.container,
          contents: '<i class="note-iconc-site"></i><i class="note-iconc-pictovr"></i>',
          tooltip: cms_data.image_btn_title,
          click: function(e) {
            context.invoke('saveRange');
            context.invoke('siteimage.show');
          }
        }).render();
      });

      this.initialize = function() {
        var $container = options.dialogsInBody ? $(document.body) : $editor;
/* TinyMCE-specific plugin element
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="siteimage_constrain">' + cms_data.constrain + '</label>\n' +
        '    <input type="checkbox" id="siteimage_constrain" class="form-control note-form-control note-input" value="1" />' +
        '   </div>\n' +
*/
        var body =
        '<div id="siteimage_tabs">\n' +
        ' <ul>\n' +
        '  <li>\n' +
        '   <a href="#tab_cmigen">'+ cms_data.tab_general + '</a>\n' +
        '  </li>\n' +
        '  <li>\n' +
        '   <a href="#tab_cmiadv">' + cms_data.tab_advanced + '</a>\n' +
        '  </li>\n' +
        ' </ul>\n' +
        ' <div>\n' +
        '  <div id="tab_cmigen">\n' +
        '   <div class="form-group oneline button">\n' +
        '    <label class="control-label note-form-label" for="siteimage_source">' + cms_data.source + '</label>\n' +
        '    <input type="text" id="siteimage_source" class="form-control note-form-control note-input" size="38" />\n' +
        '    <span id="siteimage_browse" class="form-control note-form-control action-button" title="' + cms_data.browse + '">\n' +
        '     <i class="note-iconc-search"></i>\n' +
        '    </span>\n' +
        '   </div>\n' +
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="siteimage_text">' + cms_data.description + '</label>\n' +
        '    <input type="text" id="siteimage_text" class="form-control note-form-control note-input" size="38" />\n' +
        '   </div>\n' +
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="siteimage_title">' + cms_data.title + '</label>\n' +
        '    <input type="text" id="siteimage_title" class="form-control note-form-control note-input" size="38" />\n' +
        '   </div>\n' +
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="siteimage_wd">' + cms_data.dimensions + '</label>\n' +
        '    <input type="text" id="siteimage_wd" class="form-control note-form-control note-input" title="' + cms_data.width + '" size="4" />' +
        ' x ' +
        '    <input type="text" id="siteimage_ht" class="form-control note-form-control note-input" title="' + cms_data.height + '" size="4" />\n' +
        '   </div>\n' +
        '  </div>\n' +
        '  <div id="tab_cmiadv">\n' +
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="siteimage_style">' + cms_data.style + '</label>\n' +
        '    <input type="text" id="siteimage_style" class="form-control note-form-control note-input" size="38" />\n' +
        '   </div>\n' +
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="siteimage_vert">' + cms_data.vertspace + '</label>\n' +
        '    <input type="text" id="siteimage_vert" class="form-control note-form-control note-input" size="4" />\n' +
        '    <label class="control-label note-form-label" for="siteimage_horz">' + cms_data.horzspace + '</label>\n' +
        '    <input type="text" id="siteimage_horz" class="form-control note-form-control note-input" size="4" />\n' +
        '   </div>\n' +
        '   <div class="form-group oneline">\n' +
        '    <label class="control-label note-form-label" for="siteimage_bwid">' + cms_data.bdrwidth + '</label>\n' +
        '    <input type="text" id="siteimage_bwid" class="form-control note-form-control note-input" size="4" />\n' +
        '    <label class="control-label note-form-label" for="siteimage_bstyle">' + cms_data.bdrstyle + '</label>\n' +
        '    <select id="siteimage_bstyle">\n' +
        '     <option value="" selected>' + cms_data.default + '</option>\n' +
        '     <option value="none">' + cms_data.hidden + '</option>\n' +
        '     <option value="solid">' + cms_data.solid + '</option>\n' +
        '     <option value="dotted">' + cms_data.dotted + '</option>\n' +
        '     <option value="dashed">' + cms_data.dashed + '</option>\n' +
        '     <option value="double">' + cms_data.double + '</option>\n' +
        '     <option value="groove">' + cms_data.groove + '</option>\n' +
        '     <option value="ridge">' + cms_data.ridge + '</option>\n' +
        '     <option value="inset">' + cms_data.inset + '</option>\n' +
        '     <option value="outset">' + cms_data.outset + '</option>\n' +
        '    </select>\n' +
        '   </div>\n' +
        '  </div>\n' +
        ' </div>\n' +
        '</div>\n';

        $dialog = ui.dialog({
          title: cms_data.image_dlg_title,
          body: body,
          footer: '<button id="siteimage_submit" class="note-btn note-btn-primary">' + cms_data.apply + '</button>'
        }).render().appendTo($container);
      };

      this.show = function() {
        ajaxdata = {};
        browser = null;

        var params = {},
          node = context.invoke('restoreTarget'); //OR context.layoutInfo.editable.data('target');
        if (node && $.summernote.dom.isImg(node)) {
          params.node = node;
        } else {
          params.node = null;
        }
        showDialog(params)
          .always(function() {
            ui.hideDialog($dialog);
          }).fail(function() { //jqXHR, textStatus, errorThrown
            context.invoke('restoreRange');
          }).done(function() {
            var V1 = $dialog.find('#siteimage_source').val(), // rationalized URL
              V2 = $dialog.find('#siteimage_text').val(),
              V3 = $dialog.find('#siteimage_title').val(),
              V4 = $dialog.find('#siteimage_ht').val(),
              V5 = $dialog.find('#siteimage_wd').val(),
              V6 = $dialog.find('#siteimage_style').val(),
              V7 = $dialog.find('#siteimage_vert').val(),
              V8 = $dialog.find('#siteimage_horz').val(),
              V9 = $dialog.find('#siteimage_bwid').val(),
              V10 = $dialog.find('#siteimage_bstyle').val(),
              opts, ms;
            if (!V2) {
              if (V1) {
                V2 = basename(V1);
              } else {
                V2 = 'Image descriptor needed';
              }
            }
            if (V1) {
              V1 = absurl(V1);
            }
            opts = {
              src: V1,
              alt: V2
            };
            if (V3) { opts.title = V3; }
            if (V4) { opts.height = V4; }
            if (V5) { opts.width = V5; }
            ms = mergestyle(V6, V7, V8, V9, V10);
            if (ms) { opts.style = ms; }

            if (params.node) {
              $(params.node).attr(opts);
            } else {
              var $inserter = $('<img>', opts);
              context.invoke('restoreRange');
              context.invoke('editor.insertNode', $inserter[0]);
            }
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

      function relurl(urlstr) {
        if (urlstr.indexOf(cms_data.base_url) === 0) {
          var l = cms_data.base_url.length;
          return urlstr.substring(l);
        }
        return urlstr;
      }

      function absurl(urlstr) {
        var str = urlstr.trim();
        if (str.match(/^https?/)) {
            return str;
        }
        if (str.match(/^\/\//)) {
            return str;
        }
        if (str[0] !== '/') {
          return cms_data.base_url + str;
        }
        return cms_data.base_url + str.substring(1);
      }

      function basename(str) {
        var p = str.lastIndexOf('/') + 1;
        var base = str.substring(p);
        p = base.lastIndexOf('.');
        if (p > 0) {
          return base.substring(0, p);
        }
        return base;
      }

      function scrub(str, key) {
        if (str) {
          var re = new RegExp('\\s*' + key + '\\s*:.*?(;|$)');
          return str.replace(re, '');
        }
        return str;
      }

      function parsestyle(elem, stylestr) {
//       var s1 = stylestr, //TODO if using residual styles only
         var s2 = '', //margin-top & -bottom explicit or in margin: ...
          s3 = '', //margin-left & -right explicit or in margin: ...
          s4 = '', //border width explicit or in border: ...
          s5 = '', //border style explicit or in border: ...
          cStyle = null,
          elStyle = elem.style;

        ['margin-top','margin-bottom','margin-left','margin-right','margin',
         'border-width','border-style','border'].forEach(function(prop) {
          if (elStyle.hasOwnProperty(prop)) {
            switch (prop) {
              case 'margin-top':
              case 'margin-bottom':
                if (s2) {
                  s2 = Math.max(s2, elStyle[prop]); //+ s1 = scrub (s1,prop)?
                } else {
                  s2 = elStyle[prop]; //+ scrub s1,prop?
                }
                break;
              case 'margin-left':
              case 'margin-right':
                if (s3) {
                  s3 = Math.max(s3, elStyle[prop]); //+ scrub s1,prop?
                } else {
                  s3 = elStyle[prop]; //+ scrub s1.prop?
                }
                break;
              case 'margin':
                if (!cStyle) cStyle = window.getComputedStyle(elem, null);
                s2 = Math.max(cStyle['margin-left'], cStyle['margin-right']); //scrub s1,prop ?
                s3 = Math.max(cStyle['margin-top'], cStyle['margin-bottom']); //scrub s1,prop ?
                break;
              case 'border-width':
                s4 = elStyle[prop]; //+ scrub,prop s1?
                break;
              case 'border-style':
                s5 = elStyle[prop]; //+ scrub s1,prop?
                break;
              case 'border':
                if (!cStyle) cStyle = window.getComputedStyle(elem, null);
                s4 = cStyle['border-width']; //+ scrub s1,prop?
                s5 = cStyle['border-style']; //+ scrub s1,prop?
                break;
            }
          }
        });
        return {
//        clean: s1,
          mtb: s2,
          mlr: s3,
          bdw: s4,
          bds: s5
        };
      }

      function mergestyle(stylestr, mtb, mlr, bdw, bds) {
        //remove existing content, whether or not replaced
        stylestr = scrub(stylestr, 'margin-top');
        stylestr = scrub(stylestr, 'margin-bottom');
        if (mtb) {
          stylestr += ';margin-top:' + mtb + ';margin-bottom:' + mtb;
        }
        stylestr = scrub(stylestr, 'margin-left');
        stylestr = scrub(stylestr, 'margin-right');
        if (mlr) {
          stylestr += ';margin-left:' + mlr + ';margin-right:' + mlr;
        }
        stylestr = scrub(stylestr, 'border-width');
        if (bdw) {
          stylestr += ';border-width:' + bdw;
        }
        stylestr = scrub(stylestr, 'border-style');
        if (bds) {
          stylestr += ';border-style:' + bds;
        }
        stylestr = stylestr.replace(/;;/g, ';');
        if (stylestr[0] !== ';') {
          return stylestr;
        }
        return stylestr.substring(1);
      }
/*
      function keyDownEventHandler(e) {
        var keyCode = e.keyCode;
        if (keyCode === undefined || keyCode === null) {
          return;
        } else if (keyCode === 13) {
/ *        if (sometest passes) {
            e.preventDefault();
            TODO$selector.find('.close').trigger('click');
            aka deferred.reject();
            return;
          }
* /
        }
      }
*/
      function selector(e) {
        e.preventDefault();
        return $.Deferred(function(deferred) {
          var $picked = $('<input type="text"/>').addClass('cmsfp_elem').css('display','none').appendTo($(document.body));
          var $selector = ui.dialog({
            title: cms_data.image_select_title,
            body: '',
            footer: '',
            callback: function($node) {
              var container = $node.find('.note-modal-body');
              browser = getbrowser({
                container: container,
                target: $picked,
                type: 'IMAGE',
                mime: 'image/*'
              });
              browser.refresh(true); //async
            }
          }).render().appendTo($(document.body));
//footer: '<button id="siteimage_select" class="note-btn note-btn-primary">' + 'Select' + '</button>', //TODO cms_data.select
          ui.onDialogShown($selector, function() {
            $dialog.css('left', '-9999em'); // NOT hide() ! revert by .css('left','0')
            $selector.find('.close').on('click activate', function(e) {
              e.preventDefault();
              ui.hideDialog($selector);
              deferred.reject();
            });
            //ETC
            //process an upstream selection
            $picked.on('change', function(e) {
              e.preventDefault();
              var rurl = $picked.val(), // should be a site-relative URL-path
                url = relurl(rurl),
                alt = basename(url) + ' image';
              $dialog.find('#siteimage_source').val(url);
              $dialog.find('#siteimage_text').val(alt);
              //TODO suggest other props e.g. size(s)
              ui.hideDialog($selector);
              deferred.resolve();
            });
          });

          ui.onDialogHidden($selector, function() {
            $selector.remove();
            //ETC TODO
            $dialog.css('left', 0);
          });
          //ETC TODO
          ui.showDialog($selector);
        });
      }

      function showDialog(params) {
        return $.Deferred(function(deferred) {
          var $submitBtn = $dialog.find('#siteimage_submit'),
           $closeBtn = $dialog.find('.close'),
           $browseBtn = $dialog.find('#siteimage_browse'),
           $tabswrapper = $dialog.find('#siteimage_tabs'),
           $E1 = $dialog.find('#siteimage_source'), // rationalized URL
           $E2 = $dialog.find('#siteimage_text'), // alt
           $E3 = $dialog.find('#siteimage_title'),
           $E4 = $dialog.find('#siteimage_ht'),
           $E5 = $dialog.find('#siteimage_wd'),
           $E6 = $dialog.find('#siteimage_style'),
           $E7 = $dialog.find('#siteimage_vert'),
           $E8 = $dialog.find('#siteimage_horz'),
           $E9 = $dialog.find('#siteimage_bwid'),
           $E10 = $dialog.find('#siteimage_bstyle');
          if (params.node) {
            // node attributes is array of attribute-nodes which contain name and value
            $.each(params.node.attributes, function(idx, att) {
              if (att.specified) {
                var v = att.value.trim();
                switch (att.name) {
                  case 'src':
                    $E1.val(relurl(v));
                    break;
                  case 'alt':
                    $E2.val(v);
                    break;
                  case 'title':
                    $E3.val(v);
                    break;
                  case 'height':
                    $E4.val(v);
                    break;
                  case 'width':
                    $E5.val(v);
                    break;
                  case 'style':
                    var props = parsestyle(params.node, v);
//                  if (props.str1) {
//                    $E6.val(props.str1); //original|residual styles ?
//                  }
                    $E6.val(v);
                    if (props.mtb) {
                      $E7.val(props.mtb); //margin-top/btm
                    }
                    if (props.mlr) {
                      $E3.val(props.mlr); //margin-left/rt
                    }
                    if (props.bdw) {
                      $E9.val(props.bdw); //border width
                    }
                    if (props.bds) {
                      $E10.prop('selected', props.bds); //border style TODO
                    }
                    break;
                }
              }
            });
          }

          ui.onDialogShown($dialog, function() {
            tabify($tabswrapper); // setup tabs
            context.triggerEvent('dialog.shown');
            // initial focus
            //$some-element.trigger('focus');
            // bind buttons
            $submitBtn.on('click activate', function(e) {
              e.preventDefault();
              //retrieve & report wanted values
              deferred.resolve({
//per params.node & dialog UI values
              });
            });
            $closeBtn.on('click activate', function(e) {
              e.preventDefault();
              deferred.reject();
            });
            $browseBtn.on('click activate', function(e) {
              $.when(selector(e)).done(function() {
                var here = 1; // TODO anything here?
              }).fail(function() { //jqXHR, textStatus, errorThrown
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
