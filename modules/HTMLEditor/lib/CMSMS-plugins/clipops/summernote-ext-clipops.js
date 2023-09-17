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
/*$.extend(true, $.summernote.lang, {
    'en-US': {
      clipops: {
        copy: 'Copy',
        copytitle: 'Copy selection to clipboard',
        cut: 'Cut',
        cuttitle: 'Cut selection to clipboard',
        paste: 'Paste',
        pastetitle: 'Paste cut|copied content',
        unknownError: 'Unexpected error',
        success: 'Success',
        allElementsRemoved: 'All elements removed'
      }
    }
  });
*/
  $.extend($.summernote.plugins, {
    clipops: function(context) {
      var plugopts = {
      //default options go here
      };
      try {
        $.extend(true, plugopts, context.options.pluginopts.clipops);
      } catch(m) {}
      var ui = $.summernote.ui,
        options = $.extend(true, {}, plugopts, context.options),
//      lang = options.langInfo,
        isMac = (navigator.appVersion.indexOf('Mac') > -1),
//      $note = context.layoutInfo.note,
//      $editor = context.layoutInfo.editor,
        evtlevel; //event-recursion blocker

      context.memo('button.cut', function(context) {
        return ui.button({
          className: 'btn-cut note-codeview-keep',
          container: options.container,
          contents: '<i class="note-iconc-cut"></i>',
          tooltip: cms_data.cut_btn_title + ' (' + (isMac ? 'CMD' : 'CTRL') + '+X)',
//        tooltip: lang.clipops.cuttitle + ' (' + (isMac ? 'CMD' : 'CTRL') + '+X)',
          click: function() {
            var $el = context.layoutInfo.editable;
            if (!$el.is(':visible')) {
              $el = context.layoutInfo.codable;
            }
            sendevent($el, 'cut');
          }
        }).render();
      });

      context.memo('button.copy', function(context) {
        return ui.button({
          className: 'btn-copy note-codeview-keep',
          contents: '<i class="note-iconc-copy"></i>',
          container: options.container,
          tooltip: cms_data.copy_btn_title + ' (' + (isMac ? 'CMD' : 'CTRL') + '+V)',
//        tooltip: lang.clipops.copytitle + ' (' + (isMac ? 'CMD' : 'CTRL') + '+C)',
          click: function() {
            var $el = context.layoutInfo.editable;
            if (!$el.is(':visible')) {
              $el = context.layoutInfo.codable;
            }
            sendevent($el, 'copy');
          }
        }).render();
      });

      context.memo('button.paste', function(context) {
        return ui.button({
          className: 'btn-paste note-codeview-keep',
          contents: '<i class="note-iconc-paste"></i>',
          container: options.container,
          tooltip: cms_data.paste_btn_title + ' (' + (isMac ? 'CMD' : 'CTRL') + '+V)',
//        tooltip: lang.clipops.pastetitle + ' (' + (isMac ? 'CMD' : 'CTRL') + '+V)',
          click: function() {
            var $el = context.layoutInfo.editable;
            if (!$el.is(':visible')) {
              $el = context.layoutInfo.codable;
            }
            sendevent($el, 'paste');
          }
        }).render();
      });

      this.events = {
        'summernote.paste': function(we, e) { //NOT 'paste.summernote' as for jQ namespacing
          ++evtlevel;
          if (evtlevel > 1) {
            evtlevel = 1;
            return;
          }
          do_paste(e);
          e.preventDefault(); //TODO only if successful
          return false;
        }
        //there's no similar summernote.cut or summernote.copy
      };

      this.initialize = function() {
        evtlevel = 0;
        context.layoutInfo.editable.add(context.layoutInfo.codable)
         .on('cut', function(e) {
           ++evtlevel;
           if (evtlevel > 1) {
             evtlevel = 1;
             return;
           }
           do_cut(e);
           e.preventDefault(); //TODO only if successful
           return false;
         }).on('copy', function(e) {
           ++evtlevel;
           if (evtlevel > 1) {
             evtlevel = 1;
             return;
           }
           do_copy(e);
           e.preventDefault(); //TODO only if successful
           return false;
         }).on('paste', function(e) {
           ++evtlevel;
           if (evtlevel > 1) {
             evtlevel = 1;
             return;
           }
           do_paste(e);
           e.preventDefault(); //TODO only if successful
           return false;
         });
      };

      function sendevent($el, type) {
        setTimeout(function() {
          var e = $.Event(type, {
            originalEvent: {
              type: type,
              clipboardData: window.DataTransfer || null
            }
          });
          $el.trigger(e);
        }, 40);
      }
/*
      function fakepress($el, keychar, ctrl) {
        var ep = {
          key: keychar
        };
        if (ctrl) {
          ep.ctrlKey = true;
        }
        if (window.clipboardData) {
          ep.clipboardData = window.clipboardData;
        }
        setTimeout(function() {
//        var e = $.Event('keydown', ep);
//        $el.trigger(e);
          var e = $.Event('keyup', ep);
          $el.trigger(e);
        }, 40);
      }
*/
      function sanitize(text) {
        var clean, sub, m;
        //TODO remove non-html bumph e.g. MS/LO text-formatting data
        clean = text.replace(/<\?xml[^>]*?>/g, '');
        clean = clean.replace(/<[^ >]+:[^>]*?>/g, '');
        clean = clean.replace(/<\/[^ >]+:[^>]*?>/g, '');
        // remove / mangle unwanted js per the following OR just employ DOMPurify etc ?
        // script tags like <script or <script> or <script X> X = e.g. 'defer'
        var re = /<\s*(scrip)t([^>]*)(>?)/gi;
        do {
          m = re.exec(text);
          if (m) {
            sub = '&#60;' + m[1] + '&#116;' + (m[2] ? ' ' + m[2].trim() : '') + (m[3] ? '&#62;' : '');
            clean = clean.replace(m[0], sub);
          }
        } while (m);
        // explicit script
        re = /jav(.+)(scrip)t\s*:\s*(.+)?/gi;
        do {
          m = re.exec(text);
          if (m) {
            if (m[3]) {
              sub = 'ja&#118;' + m[1].trim() + m[2] + '&#116;&#58;' + m[3].replace('(','&#40;').replace(')','&#41;');
              clean = clean.replace(m[0], sub);
            }
          }
        } while (m);
        // inline scripts like on*="dostuff" or on*=dostuff
        re = /(on\w+)\s*=\s*(["\']?.+["\']?)/gi;
        do {
          m = re.exec(text);
          if (m) {
            sub = m[1] + '&#61;' + m[2].replace('"', '&#34;')
             .replace("'", '&#39;')
             .replace('(', '&#40;')
             .replace(')', '&#41;');
            clean = clean.replace(m[0], sub);
          }
        } while (m);
//      TODO others e.g. FSCommand(), seekSegmentTime() @ http://help.dottoro.com
        // embeds
        re = /(embe)(d)/gi;
        do {
          m = re.exec(text);
          if (m) {
            sub = m[1] + '&#' + m[2].charCodeAt(0) + ';';
            clean = clean.replace(m[0], sub);
          }
        } while (m);

        var sane = '';
        var parts = $.parseHTML(clean, null);
        parts.forEach(function(elem) {
          sane += elem.outerHTML || elem.nodeValue;
        });
        return sane;
      }

      function convert(data) {
        // js variable-types: Boolean Null Undefined Number BigInt String Symbol Object
        switch (typeof data) {
          case 'string':
            return $('<i></i>').html(data).html();
          case 'number':
          case 'bigint':
            var s = data.toString();
            if (s[0] !== '+') {
              if (s !== '-0') { return s; }
              return '0';
            }
            return s.substring(1);
          case 'boolean':
            return (data) ? 'true' : 'false';
          case 'object':
            if (!data) {
              return '';
            }
            if (data instanceof HTMLElement) {
              return data.outerHTML;
            }
            if (data instanceof Date) {
              return data.toISOString();
            }
            s = data.toString();
            if (s !== '[object Object]') {
              return s;
            }
            return JSON.stringify(data);
          case 'symbol':
            return data.toString();
          case 'null':
          case 'undefined':
            return '';
          default:
            console.log('Unexpected paste-data type: ' + typeof data);
            return data;
         }
      }

      function insert(content, e) {
        var clean = convert(content);
        clean = sanitize(clean);
        if (clean || (!isNaN(parseFloat(clean)) && isFinite(clean))) {
          var text;
          var el = e.currentTarget || e.originalTarget;
          if (el.tagName === 'DIV') { // OR $(el).hasClass('note-editable')
            //self-manage the paste operation to work around the limited s'note internal process
            //TODO block (OR wrap OR ?) nested insertions of same node-type, except for e.g. div span ... ?
            context.invoke('restoreRange');
            var rng = $.summernote.range.createFromSelection().deleteContents(); // if any
            var parts = $.parseHTML(clean, null); // no scripts (should be mangled anyway)
            $.each(parts.reverse(), function(i, nd) {
              rng.insertNode(nd);
            });
            context.layoutInfo.note.trigger('change');
          } else if (el.tagName === 'TEXTAREA') { // OR $(el).hasClass('note-codable')
            text = el.value;
            text = text.substring(0, el.selectionStart) + clean + text.substring(el.selectionEnd);
            el.value = text;
            context.layoutInfo.note.trigger('change');
          } else {
            console.error('Internal error - missing content container');
          }
        }
      }

      // remove content from its container
      function remove(content, e) {
        var el = e.currentTarget || e.originalTarget;
        if (el.tagName === 'DIV') { // OR $(el).hasClass('note-editable')
          //TODO if node(s) are partially-selected, might need to
          // manually intersect content with current range
          var rng = $.summernote.range.createFromSelection(); // if any
          rng.deleteContents();
        } else if (el.tagName === 'TEXTAREA') { // OR $(el).hasClass('note-codable')
          var o1 = el.selectionStart,
            o2 = el.selectionEnd;
          if (o1 !== o2) {
            var text = el.value;
            //TODO confirm selection-contents === content ?
            //var dbg = text.substring(o1, o2);
            text = text.substring(0, o1) + text.substring(o2);
            el.value = text;
          }
        } else {
          console.error('Internal error - missing content container');
          return;
        }
        context.layoutInfo.note.trigger('change');
      }
/*
see https://stackoverflow.com/questions/60581285/execcommand-is-now-obsolete-whats-the-alternative
for current approach to editor-content-management
navigator.clipboard (async, returns promise, rejected if no access)
  secure context (https)
  recent browsers
  .writeText limited eg from user-initiated event callbacks,  user gesture event handlers

ClipboardItems = .read()
DOMString = .readText()
undefined = .write()
undefined = .writeText()

window.clipboardData (sync)
  all IE's
  middling-old other majors
  type-descriptor used
  mixture of contraints eg. cut & copy events without a focused editable field, but not paste
   or does not fire paste with document.execCommand('paste')
   or nothing done via document.execCommand().
  security risk?
clipboardevent.clipboardData is safer
  mimetype used
  clipdata = event.clipboardData || window.clipboardData;
  console.log(clipdata.getData('text/plain'));

document.execCommand(aCommandName) here: cut|copy|paste
  deprecated, probably still supported in many browsers
  inconsistent behaviour across browsers esp. 'paste'
  must hack to tailor the content (create element, set its content, process it, remove element)
  BUT other clipboard API doesn't replace the 'insertText' command, which
    can programmatically replace text at the cursor while preserving the
    undo buffer (edit history) in plain textarea and input elements
  returns bool, false if the command is unsupported or disabled.
*/
/*
editable ==$(div) range
var rng = $.summernote.range.createFromSelection();
var V0 = rng.toString();

codable ==$(textarea) range
var o1 = this.selectionStart,
  o2 = this.selectionEnd,
  seltxt = this.value.substring(o1, o2),
*/
/* native-js promise coding:
p.then(onResolved[, onRejected]);
OR
p.then(function(value) {
  // resolution
}).catch(function(reason) {
  // rejection
});
*/
      function do_cut(e) {
        var seltext;
        var el = e.currentTarget || e.originalTarget;
        if (el.tagName === 'DIV') { // OR $(el).hasClass('note-editable')
          var rng = $.summernote.range.createFromSelection(),
            grab = rng.nativeRange().cloneContents();
          seltext = $('<p></p>').append($(grab)).html();
        } else if (el.tagName === 'TEXTAREA') { // OR $(el).hasClass('note-codable')
          var o1 = el.selectionStart,
            o2 = el.selectionEnd;
          seltext = el.value.substring(o1, o2);
        } else {
          console.error('Internal error - missing content container');
          return;
        }
        var content = '';
        var parts = $.parseHTML(seltext, null);
        parts.forEach(function(elem) {
          content += elem.outerHTML || elem.nodeValue;
        });
        if (navigator.clipboard) {
          $.when(navigator.clipboard.writeText(content))
            .done(function() {
              remove(seltext, e);
              evtlevel = 0;
            })
            .fail(function(reason) {
              sync_cut(content, e);
            });
        } else {
          sync_cut(content, e);
        }
      }

      function sync_cut(content, e) {
        if (e.originalEvent.clipboardData) {
          e.originalEvent.clipboardData.setData('text/plain', content);
          remove(content, e);
        } else if (window.clipboardData) {
          window.clipboardData.setData('Text', content);
          remove(content, e);
        } else if (document.queryCommandSupported && document.queryCommandSupported('cut')) {
          //setup for dummy cut
          var ta = document.createElement('textarea');
          ta.style.position = 'absolute';
          ta.style.left = '-10000px';
          ta.style.top = '-10000px';
          ta.textContent = content;
          document.body.appendChild(ta);
          ta.contentEditable = true;
          ta.select();
          try {
            if (document.execCommand('cut')) {
              remove(content, e);
            } else {
              console.warn('Cut to clipboard failed');
              //TODO report failure
            }
          } catch (reason) {
            console.warn('Cut to clipboard failed', reason);
            //TODO report failure
          } finally {
            document.body.removeChild(ta);
          }
        } else {
           console.warn('Cut to clipboard is not supported');
           //TODO report failure
        }
        evtlevel = 0;
      }

      function do_copy(e) {
        var seltext;
        var el = e.currentTarget || e.originalTarget;
        if (el.tagName === 'DIV') { // OR $(el).hasClass('note-editable')
          var rng = $.summernote.range.createFromSelection(),
            grab = rng.nativeRange().cloneContents();
          seltext = $('<p></p>').append($(grab)).html(); // retained structure, with some sanitizing
        } else if (el.tagName === 'TEXTAREA') { // OR $(el).hasClass('note-codable')
          var o1 = el.selectionStart,
            o2 = el.selectionEnd;
          seltext = el.value.substring(o1, o2);
        } else {
          console.error('Internal error - missing content container');
          return;
        }
        var content = '';
        var parts = $.parseHTML(seltext, null);
        parts.forEach(function(elem) {
          content += elem.outerHTML || elem.nodeValue;
        });
        if (navigator.clipboard) {
          $.when(navigator.clipboard.writeText(content))
            .done(function(value) {
              evtlevel = 0;
            })
            .fail(function(reason) {
              sync_copy(content, e);
            });
        } else {
          sync_copy(content, e);
        }
      }

      function sync_copy(content, e) {
        if (e.originalEvent.clipboardData) {
          e.originalEvent.clipboardData.setData('text/plain', content);
        } else if (window.clipboardData) {
          window.clipboardData.setData('Text', content);
        } else if (document.queryCommandSupported && document.queryCommandSupported('copy')) {
          //setup for dummy copy
          var ta = document.createElement('textarea');
          ta.style.position = 'absolute';
          ta.style.left = '-10000px';
          ta.style.top = '-10000px';
          ta.textContent = content;
          document.body.appendChild(ta);
          ta.contentEditable = true;
          ta.select();
          try {
            if (!document.execCommand('copy')) {
              console.warn('Copy to clipboard failed');
              //TODO report failure
            }
          } catch (reason) {
            console.warn('Copy to clipboard failed', reason);
            //TODO report failure
          } finally {
            document.body.removeChild(ta);
          }
        } else {
          console.warn('Copy to clipboard is not supported');
          //TODO report failure
        }
        evtlevel = 0;
      }

      function do_paste(e) {
        var el = e.currentTarget || e.originalTarget;
        if (el.tagName === 'DIV') { // OR $(el).hasClass('note-editable')
          var here = 1;
        } else if (el.tagName === 'TEXTAREA') { // OR $(el).hasClass('note-codable')
          here = 2;
        } else {
          console.error('Internal error - missing content container');
          return;
        }
        if (navigator.clipboard) {
          $.when(navigator.clipboard.read())
            .done(function(data) {
              for (var i = 0; i < data.items.length; i++) {
                var type, clipbdItem = data.items[i];
                if (typeof clipbdItem.types !== 'undefined') {
                  clipbdItem.types.forEach(function(type) {
                    //TODO use which type? and if datum is not a promise ...
                    $.when(clipbdItem.getType(type))
                      .done(function(blob) {
                        insert(blob, e);
                      })
                      .fail(function(reason){
                        var here = 444;
                      });
//                    break;
                  });
                } else {
                  if ((clipbdItem.kind === 'string') &&
                      (clipbdItem.type.match(/^text\/plain/))) {
                    clipbdItem.getAsString(function(blob) {
                      insert(blob, e);
                    });
                  }
                  //TODO also support 'file' type ?
                }
              }
              evtlevel = 0;
            })
            .fail(function(reason) {
              sync_paste(e);
            });
        } else {
          sync_paste(e);
        }
      }

      function sync_paste(e) {
        var value;
        if (e.originalEvent.clipboardData) {
          value = e.originalEvent.clipboardData.getData('text/plain'); // TODO other types?
          insert(value, e);
        } else if (window.clipboardData) {
          value = window.clipboardData.getData('Text'); // TODO other types?
          insert(value, e);
        } else if (document.queryCommandSupported && document.queryCommandSupported('paste')) {
          //setup dummy paste
          var ta = document.createElement('textarea');
          ta.style.position = 'absolute';
          ta.style.left = '-10000px';
          ta.style.top = '-10000px';
          document.body.appendChild(ta);
          ta.contentEditable = true;
          ta.select();
          try {
            if (document.execCommand('paste')) {
              insert(ta.textContent, e);
            } else {
              console.warn('Paste from system clipboard failed');
            }
          } catch (reason) {
            console.warn('Paste from system clipboard failed', reason);
          } finally {
            document.body.removeChild(ta);
          }
        } else {
          console.warn('Paste from system clipboard is not supported');
        }
        evtlevel = 0;
      }
    } // clipops
  });
}));
