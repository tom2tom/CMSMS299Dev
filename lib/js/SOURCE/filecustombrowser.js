/*!
CMSCustomFileBrowser v.0.8
(C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL3+
*/
/* This provides operations for file-browsing/picking via a container element in a dialog/popup
options: {}
cmd_url: ajax action URL for mkdire, delete
cwd: portion of displayed-folder filepath, relative to profile topmost-folder, so maybe empty
inst: unique identifier applied, or to be applied, to the target element
lang: {confirm_delete,error_problem_upload,error_failed_ajax,yes,no,ok,cancel etc}
onselect: optional callback(instance, relative-filepath) used instead of event trigger
NOT sig: former upload-script parameter (defunct)
container: jQ object representing the DOM element which contains reported data
target: optional element (in or out of DOM) to receive/be notified about a selection
type: uppercase name of CMSMS\FileType of displayed files, e.g. 'IMAGE' or 'ANY', or missing
list_url: url for retrieving content to be displayed, normally a toolbar plus table of items in the displayed folder
cd_url: url for redirecting to display parent directory, if allowed, or empty or missing
uploader-specific ...
mime: optional string e.g. 'image/*', mimetype of files acceptable to upload
extensions: optional array e.g. ['jpg','jpeg','png','gif','svg'], file-extension string(s) acceptable to upload
*/
/* jslint nomen: true , devel: true */
/* global $, jQuery, cms_busy, cms_confirm, cms_alert, cms_dialog */

function CMSCustomFileBrowser(options) {
  var target, $cnt, progressBar, progressText, dropZone, colorBar,
    upLbl, inputFile, list, settings, errmsg;

  function set_options(options) {
    options = $.extend({}, settings || {}, options || {});
    init(options);
  }

  function init(options) {
    settings = options || {};
    target = settings.target || null;
    $cnt = settings.container || null;
    if ($cnt) {
      if (!($cnt instanceof $)) {
        $cnt = $($cnt);
      }
      if (!$cnt[0].innerHTML) {
        return; // not yet ready to roll
      }
    } else {
      return; // not yet ready to roll
    }
    // cache UI elements - refer to filepicker.tpl
    progressBar = $cnt.find('#fp-progress');
    progressText = $cnt.find('#fp-progress-text');
    dropZone = $cnt.find('#fp-dropzone');
    upLbl = $cnt.find('#btn-upload');
    inputFile = $cnt.find('#fp-file-upload');
    list = $cnt.find('#fp-list'); // table
    colorBar = null;

    enable_filetypeFilter();
    if (settings.type && settings.type !== 'ANY') { // 'ALL'?
      $cnt.find('#fp-type-filter [data-fb-type="' + settings.type + '"]').trigger('click');
    } else {
      $cnt.find('#fp-type-filter [data-fb-type="RESET"]').addClass('active'); // prob useless
    }

    var tid = 0;
    $(window).on('close', function() {
      if (tid != 0) {
        clearInterval(tid);
      }
    })
    .on('resize', function() {
      // 4Hz re-format checks
      var block = true;
      if (tid === 0) {
        tid = setInterval(function() {
          if (block) {
            block = false;
          } else {
            clearInterval(tid);
            tid = 0;
          }
          // size-threshold is ?
          // dropZone width c.f. css small-screen behaviour
          if (window.innerWidth < 380) {
            dropZone.css('width', '3em');
          } else {
            dropZone.css('width', '9em');
          }
        }, 250);
      }
    })
    .trigger('resize');

    setTimeout(function() {
      enable_sendValue();
      enable_commands();
      enable_upload();
    }, 250);
  }

  function enable_sendValue() {
    list.find('a.js-trigger-insert').on('click activate', function(ev) {
      ev.preventDefault();
      var $this = $(this),
        file = $this.attr('href'),
        instance = settings.inst || $cnt.data('cmsfp-inst');

      if (settings && settings.onselect) {
        settings.onselect(instance, file);
        return false;
      }
      // the target-element might be anywhere in DOM, or not in it
      if (target) {
        if (!(target instanceof $)) {
          target = $(target);
        }
      } else {
        var selector = '[data-cmsfp-instance="' + instance + '"]';
        target = $(selector) || $cnt.find(selector);
      }
      if (target.length > 0) {
        if (target.is(':input')) {
          target.val(file).trigger('change');
        }
        target.trigger('cmsfp:change', file);
      }
      return false;
    });
  }

  function enable_filetypeFilter() {
    if ($cnt.find('#fp-type-filter').length < 1) {
      return;
    }
    var $items = list.find('.fpitem').not('.header,.dir'); //all except the heading- and directory-items

    $cnt.find('#fp-type-filter .js-trigger').on('click activate', function(ev) {
      var $trigger = $(this),
        type = $trigger.attr('data-fb-type');

      if (!(type === 'RESET' || $trigger.hasClass('active'))) {
        $items.hide(50).filter('div.' + type).show(100);
      } else {
        $items.show(150);
      }
      $('#fp-type-filter .js-trigger').removeClass('active');
      $trigger.addClass('active');
    });
  }

  function enable_upload() {
    var name = inputFile.attr('name');
    var upperopts = {
      url: settings.cmd_url,
      dataType: 'json',
      extraData: { //c.f. ajax_data()
        cmd: 'upload',
        val: '',
        cwd: settings.cwd,
        inst: settings.inst,
        filefield: name
      },
      fieldName: name,
//    maxFileSize: $config['max_file_size'] ? , upstream check ATM
      inputFile: inputFile[0],
      allowedTypes: settings.mime || '*',
      extFilter: settings.extensions || null,
/*    onInit: function() { //widget is ready to use
      },
      onDragEnter: function() { //user has dragged file(s) into the dropZone
      },
      onDragLeave: function() { //drag has left the dropZone, or file(s) have been dropped
      },
      onDocumentDragEnter: function() {}, //user is dragging files anywhere over the $(document)
      onDocumentDragLeave: function() {}, //drag has left the $(document) area
*/
      onBegin: function() { //the [first] upload is about to start
        cms_busy();
        errmsg = [];
        if (dropZone.length > 0) {
          dropZone.addClass('visuallyhidden');
        }
        progressText.show();
        colorBar = $('<div/>',{id:'fp-progress-inner'});
        progressBar.prepend(colorBar).show();
        upLbl.addClass('disabled').css('pointer-events','none');
      },
      onComplete: function() { //all pending files are completed
        upload_finish(true);
      },
/*    onNewFile: function(id, file) { //a[nother] file was selected or dropped by the user
        //record data about id, file (array if multiple files)
      },
*/
      onBeforeUpload: function(id) { // file upload request is about to be executed
        progressText.text('');
        colorBar.width(0);
      },
      onUploadProgress: function(id, percent) { // the current upload % for the file
        colorBar.animate({
          width: percent + '%'
        }, 200, function() {
          $(this).attr('aria-valuenow', percent);
          progressText.text(percent + '%');
        });
      },
/*    onUploadSuccess: function(id, data) { //file was successfully uploaded and got a response form the server
      },
      onUploadComplete: function(id) //the current upload is complete
      },
*/
      onUploadCanceled: function(id) { //upload was cancelled by the user
        upload_finish(true);
      },
      onUploadError: function(id, jqXHR, textStatus, errorThrown) { //an error happened during the upload or on the server
        if (jqXHR.responseJSON) {
          errmsg.push(jqXHR.responseJSON.message);
        } else {
          errmsg.push(errorThrown);
        }
      },
      onFileTypeError: function(file) { //file type validation failed
        errmsg.push(langin('error_type', file.data.name || 'File'));
      },
      onFileExtError: function(file) { //file extension validation failed
        errmsg.push(langin('error_ext', file.data.name || 'File'));
      },
      onFileSizeError: function(file) { //file size validation failed
        errmsg.push(langin('error_size', file.data.name || 'File'));
      }
    };
    inputFile.dmUploader(upperopts);

    if (dropZone.length > 0) {
      upperopts.extraData.filefield = 'dropzone'; // will be index in uploaded $_FILES
      upperopts.fieldName = 'dropzone'; // ditto
      upperopts.inputFile = null;
      upperopts.hookDocument = false; // once is enough
      dropZone.dmUploader(upperopts);

      var inzone = false;
      dropZone.on('dragenter dragover', function(ev) {
        $(this).addClass('dragging');
        inzone = true;
      })
      .on('dragleave', function(ev) {
        $(this).removeClass('dragging');
        inzone = false;
      })
      .on('mouseup', function(ev) {
        $(this).removeClass('dragging');
        // TODO for right-btn prevent context menu e.g. if some dragmotion event recorded
        if (inzone) {
//        ev.preventDefault();
          inzone = false;
        }
      });
    }
  }

  function upload_finish(restart) {
    if (errmsg.length > 0) {
      var msg = settings.lang.error_title + ':\n&nbsp;' + errmsg.join('\n&nbsp;');
      cms_alert(msg, '', true).always(function() {
        if (dropZone.length > 0) {
          dropZone.removeClass('visuallyhidden');
        }
        upLbl.removeClass('disabled').css('pointer-events','inherit');
        refresh(restart);
      });
    } else {
      setTimeout(function() {
        if (dropZone.length > 0) {
          dropZone.removeClass('visuallyhidden');
        }
        upLbl.removeClass('disabled').css('pointer-events','inherit');
        refresh(restart);
      }, 2000);
    }
  }

  function refresh(restart) {
    cms_busy(false);
    if (restart) {
      // re-display current directory
      populate(false);
    } else {
      progressBar.hide().remove('#fp-progress-inner');
      colorBar = null;
      progressText.hide().text('');
      if (dropZone.length > 0) {
        dropZone.removeClass('visuallyhidden');
      }
      inputFile.blur();
    }
  }

  function enable_commands() {
    $cnt.find('#level-up').on('click activate', function(ev) {
      ev.preventDefault();
      var search = $(this).attr('data-chdir');
      populate(search);
    });

    $cnt.find('.filepicker-dir-action').on('click activate', function(ev) {
      ev.preventDefault();
      var url = $(this).attr('href'),
        clean = decodeURIComponent(url.replace(/\+/g, ' ')),
        pos = clean.indexOf('?'),
        search = clean.substring(pos + 1);
      populate(search);
    });

    $cnt.find('.filepicker-cmd').on('click activate', function(ev) {
      ev.preventDefault();
//    var dbg = $(this).data(),
//       name = dbg.cmd;
      var name = $(this).attr('data-cmd');
      switch(name) {
        case 'del':
          cmd_del(ev);
          break;
        case 'mkdir':
          cmd_mkdir(ev);
          break;
      }
    });
  }

  function ajax_data(cmd, val) {
    return {
      method: 'POST',
      url: settings.cmd_url,
      data: {
        cmd: cmd,
        val: val,
        cwd: settings.cwd,
        inst: settings.inst
      }
    };
  }

  //.filepicker-cmd elements' click handlers

  function cmd_del(ev) {
    ev.preventDefault();
    var target = ev.target.closest('.fpitem');
    var file = $(target).data('fb-fname');
    // nested dialog: ok ?
    cms_confirm(settings.lang.confirm_delete, '', cms_lang('yes'), cms_lang('no'))
      .done(function() {
        // ajax call to delete it
        var params = ajax_data('del', file);
        $.ajax(params)
          .done(function() {
            // re-display current directory
            populate(false);
          })
          .fail(function(jqXHR, textStatus, errorThrown) {
            console.debug('FileBrowser deletion failed: ' + errorThrown);
            cms_alert(settings.lang.error_failed_ajax + ': ' + errorThrown);
          });
      });
  }

  function cmd_mkdir(ev) {
    ev.preventDefault();
    cms_dialog($('#mkdir_dlg'), {
      modal: true,
      buttons: [
       {
        text: settings.lang.ok,
        click: function() {
          var val = $('#fld_mkdir').val().trim();
          // ajax call to create the directory
          var params = ajax_data('mkdir', val);
          $.ajax(params)
          .done(function() {
            // re-display current directory
            populate(false);
          })
          .fail(function(jqXHR, textStatus, errorThrown) {
            console.debug('FileBrowser mkdir failed: ' + errorThrown);
            cms_alert(settings.lang.error_failed_ajax + ': ' + errorThrown);
          });
          cms_dialog($(this), 'close');
        }
       },
       {
        text: settings.lang.cancel,
        click: function() {
          cms_dialog($(this), 'close');
        }
       }
      ]
    });
  }

  function populate(urlparms) {
    var params = {
        content: true,
        cwd: settings.cwd || '',
        inst: settings.inst || '',
        type: options.type
        //TODO ETC
    };
    if (urlparms) {
      //url search e.g. ?_sk_=31ybu-C-v&_sk_jobtype=1&mact=FilePicker,m1_,filepicker,0&m1__enc=eyJzdWJkaXIiOiJhZG1pbiIsImluc3QiOiItODdjMTI2NjAtIn0=
      //parse for details to load specified folder: at least 'm1__enc'
      var ex = urlparms.split('&');
      for (var i = 0; i < ex.length; i++) {
        var v = ex[i].split('=', 2);
        if (v[0].match(/_enc/)) {
          var k = v[0].trim();
          params[k] = v[1].trim();
        }
      }
    }

    $.ajax(settings.list_url, {
      method: 'POST',
      cache: false,
      dataType: 'json',
      data: params
    })
    .done(function(sent, textStatus, jqXHR) {
      settings.cwd = sent.cwd;
      settings.inst = sent.inst;
      $cnt.html(sent.body);
      progressBar = $cnt.find('#fp-progress');
      progressText = $cnt.find('#fp-progress-text');
      dropZone = $cnt.find('#fp-dropzone');
      upLbl = $cnt.find('#btn-upload');
      inputFile = $cnt.find('#fp-file-upload');
      list = $cnt.find('#fp-list');
      colorBar = null;
      if (target) {
        target.attr('data-cmsfp-instance', sent.inst);
      }
      enable_sendValue();
      enable_commands();
      enable_upload();
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
      var msg = '<p style="color:red">' + settings.lang.error_failed_ajax + ': ' + errorThrown + '</p>';
      list.empty().append(msg);
      console.error('File-pick ajax error: ' + errorThrown);
    });
  }

  function langin(key, detail) {
    if (typeof settings.lang[key] !== 'undefined') {
      if (detail === undefined) {
        return settings.lang[key];
      }
      return settings.lang[key].replace('%s', detail);
    }
    return 'Missing translation for ' + key;
  }

  if (options !== undefined) {
    set_options(options);
  } else {
    init({});
  }
  // public API
  this.settings = settings;
  this.set_options = set_options;
  this.refresh = refresh;
}
