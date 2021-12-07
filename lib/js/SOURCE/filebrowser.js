/* options: {}
cmd_url: ajax action URL
cwd: portion of displayed-folder filepath, relative to profile topmost-folder, so maybe empty
inst: unique identifier
lang: {confirm_delete,error_problem_upload,error_failed_ajax,yes,no,ok,cancel etc}
onselect: optional callback({instance:, file:})
NOT sig: former upload-script parameter (defunct)
SINCE 2.99
type: uppercase name of profile-type of displayed files, or 'ALL', or missing
cd_url: url for redirecting to display parent directory, if allowed, or empty or missing
uploader-specific ...
mime: optional mimetype string e.g. 'image/*', for files acceptable to upload
extensions: string(s) array eg ['jpg','jpeg','png','gif','svg'] of files acceptable to upload
*/
/* jslint nomen: true , devel: true */
/* global $, cms_busy, cms_confirm, cms_alert, cms_dialog, cms_lang */

function CMSFileBrowser(options) {
//  var self = this;
  // cache UI elements - refer to filepicker.tpl
/* DEBUG ONLY
    var gridviewBtn = $('#fp-navbar-inner #view-grid'),
    listviewBtn = $('#fp-navbar-inner #view-list'); */
  var progressBar = $('#fp-progress'),
    colorBar = null,
    progressText = $('#fp-progress-text'),
    dropZone = $('#fp-dropzone'),
    inputFile = $('#fp-file-upload'),
    container = $('#fp-list'),
    upper, settings, errmsg;

  if(top.document.CMSFileBrowser) {
    settings = $.extend({}, top.document.CMSFileBrowser, options || {});
  } else {
    settings = options || {};
  }

  function enable_sendValue() {
    $('a.js-trigger-insert').on('click', function(ev) {
      ev.preventDefault();
      var $this = $(this),
//        $elm = $this.closest('li'),
//        $data = $elm.data(),
//        $ext = $data.fbExt,
        file = $this.attr('href'),
        instance = settings.inst || $('html').data('cmsfp-inst');
/*      o = {
          name: 'cmsfp:change',
          target: instance,
          file: file
        };
*/
      if(settings && settings.onselect) {
        settings.onselect(instance, file);
        return false;
      }
      var selector = '[data-cmsfp-instance="' + instance + '"]';
      var target = parent.$(selector); //parent is Window ?
      if(target && target.length) {
        if(target.is(':input')) {
          target.val(file);
          target.trigger('change');
        }
        target.trigger('cmsfp:change', file);
      }
      return false;
    });
  }

  function show_grid() {
    if (!container.hasClass('grid-view')) {
      container.removeClass('list-view').addClass('grid-view');
      //$('.fp-file-details').addClass('visuallyhidden');
      //localStorage.setItem('view-type', 'grid'); // TODO browsers since ~2011, admin js supports presence check, with cookie fallback
    }
  }

  function show_list() {
    if (!container.hasClass('list-view')) {
      container.removeClass('grid-view').addClass('list-view');
      //$('.fp-file-details').removeClass('visuallyhidden');
      //localStorage.setItem('view-type', 'list');
    }
  }

/* DEBUG ONLY layout-change is normally automatic, window-size-dependant
  function enable_toggleGrid() {
    gridviewBtn.on('click', function() {
      show_grid();
      listviewBtn.removeClass('active');
      $(this).addClass('active');
    });
    listviewBtn.on('click', function() {
      show_list();
      gridviewBtn.removeClass('active');
      $(this).addClass('active');
    });
/ *
    $('.fp-view-option .js-trigger').on('click', function(ev) {
      var $trigger = $(this),
        container = $('#fp-list'),
        $info = $('.fp-file-details');

      $('.fp-view-option .js-trigger').removeClass('active');
      $trigger.addClass('active');
      if ($trigger.hasClass('view-grid')) {
        container.removeClass('list-view').addClass('grid-view');
        $info.addClass('visuallyhidden');
      } else if ($trigger.hasClass('view-list')) {
        container.removeClass('grid-view').addClass('list-view');
        $info.removeClass('visuallyhidden');
      }
    });
* /
  }

  function setup_view() {
    var view_type = localStorage.getItem('view-type');
    if (!view_type) {
      view_type = 'grid';
    }
    if(view_type === 'list') {
      listviewBtn.trigger('click');
    } else {
      gridviewBtn.trigger('click');
    }
  }
*/

  function enable_filetypeFilter() {
    if ($('#fp-type-filter').length < 1) {
      return;
    }
    var $items = container.find('.fpitem').not('.header,.dir'); //all except the heading and directoryitems

    $('#fp-type-filter .js-trigger').on('click', function(ev) {
      var $trigger = $(this),
        type = $trigger.attr('data-fb-type');

      if(!(type === 'RESET' || $trigger.hasClass('active'))) {
        $items.hide(50).filter('div.' + type).show(100);
      } else {
        $items.show(150);
      }
      $('#fp-type-filter .js-trigger').removeClass('active');
      $trigger.addClass('active');
    });
  }

  function enable_upload() {
    if (dropZone.length > 0) {
      upper = dropZone;
      var inzone = false;
      dropZone.on('dragenter, dragover', function(ev) {
        $(this).addClass('dragging');
        inzone = true;
      }).on('dragleave', function(ev) {
        $(this).removeClass('dragging');
        inzone = false;
      }).on('mouseup', function(ev) {
        $(this).removeClass('dragging');
        // TODO for right-btn prevent context menu eg if some dragmotion event recorded
        if (inzone) {
//          ev.preventDefault();
          inzone = false;
        }
      });
    } else {
      upper = inputFile;
    }

    var name = inputFile.attr('name');
    upper.dmUploader({
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
//    onInit: function() { //widget is ready to use
//    },
//    onDragEnter: function() { //user has dragged file(s) into the dropZone
//    },
//    onDragLeave: function() { //drag has left the dropZone, or file(s) have been dropped
//    },
//    onDocumentDragEnter: function() {}, //user is dragging files anywhere over the $(document)
//    onDocumentDragLeave: function() {}, //drag has left the $(document) area
      onBegin: function() { //the [first] upload is about to start
        cms_busy();
        errmsg = [];
        if (dropZone.length > 0) {
          dropZone.addClass('visuallyhidden');
        }
        progressText.show();
        colorBar = $('<div/>',{id:'fp-progress-inner'});
        progressBar.prepend(colorBar).show();
      },
      onComplete: function() { //all pending files are completed
          upload_finish(true);
      },
//    onNewFile: function(id, file) { //a[nother] file was selected or dropped by the user
        //record data about id, file (array if multiple files)
//    },
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
//    onUploadSuccess: function(id, data) { //file was successfully uploaded and got a response form the server
//    },
//    onUploadComplete: function(id) //the current upload is complete
//    },
      onUploadCanceled: function(id) { //upload was cancelled by the user
        upload_finish(true);
      },
      onUploadError: function(id, jqXHR, textStatus, errorThrown) { //an error happened during the upload or on the server
        if (jqXHR.responseJSON) {
          errmsg.push(jqXHR.responseJSON.message);
        } else {
          errmsg.push(errorThrown.message);
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
    });
  }

  function upload_finish(restart) {
    if (errmsg.length > 0) {
      var msg = cms_lang.error_title + ':\n&nbsp;' + errmsg.join('\n&nbsp;');
      cms_alert(msg, false, true).then(function() {
        refresh(restart);
      });
    } else {
      setTimeout(function() {
        refresh(restart);
      }, 2000);
    }
  }

  function refresh(restart) {
    cms_busy(false);
    if (restart) {
      window.location.reload(true);
    } else {
      progressBar.hide().remove('fp-progress-inner');
      colorBar = null;
      progressText.hide().text('');
      if (dropZone.length > 0) {
        dropZone.removeClass('visuallyhidden');
      }
      inputFile.blur();
    }
  }

  function enable_commands() {
    if (settings.cd_url) {
      // goto parent dir is enabled
      $('#level-up').on('click', function(ev) {
        ev.preventDefault();
        window.location.href = settings.cd_url;
      });
    }

    var $items = $('.filepicker-cmd');
    if ($items.length > 0) {
      $items.on('click', function(ev) {
//        var dbg = $(this).data();
//        name = data.cmd;
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
    $items = null; //OK?
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
    cms_confirm(settings.lang.confirm_delete, true)
      .done(function() {
        // ajax call to delete it
        var params = ajax_data('del', file);
        $.ajax(params)
        .done(function() {
          // then re-display the current directory OK ??
          window.location.reload(true);
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
            // then re-display the current directory
            window.location.reload(true);
          })
          .fail(function(jqXHR, textStatus, errorThrown) {
            console.debug('FileBrowser mkdir failed: ' + errorThrown);
            cms_alert(settings.lang.error_failed_ajax + ': ' + errorThrown);
          });
          $(this).dialog("close");
        }
       },
       {
        text: settings.lang.cancel,
        click: function() {
          $(this).dialog("close");
        }
       }
      ]
    });
  }

  function langin(key, detail) {
    if (cms_lang[key]) {
      return cms_lang[key].replace('%s', detail);
    }
    return detail + ' error';
  }

  // translated strings
  cms_lang = $.extend({}, cms_lang || {}, settings.lang);
  // init
  enable_filetypeFilter();
  if (settings.type && settings.type !== 'ALL') {
    $('#fp-type-filter [data-fb-type="' + settings.type + '"]').trigger('click');
  } else {
    $('#fp-type-filter [data-fb-type="RESET"]').addClass('active');
  }
/* DEBUG ONLY
  enable_toggleGrid();
  setup_view();
*/
  show_list();
  var ID = 0; //TODO when to explicitly kill this?
  // 4Hz re-format checks
  $(window).on('resize', function() {
    var block = true;
    if (ID === 0) {
      ID = setInterval(function() {
        if (block) {
          block = false;
        } else {
          clearInterval(ID);
          ID = 0;
        }
        // size-threshold is gridbox css width * 2
        // dropZone width c.f. css small-screen behaviour
        if (window.innerWidth < 380) {
          show_grid();
          dropZone.css('width', '3em');
        } else {
          show_list();
          dropZone.css('width', '9em');
        }
      }, 250);
    }
  }).on('close', function() {
    if (ID != 0) {
      clearInterval(ID);
    }
  });

  $(window).trigger('resize');
  setTimeout(function() {
    enable_sendValue();
    enable_commands();
    enable_upload();
  }, 250);
} /* object */
