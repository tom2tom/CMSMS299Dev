/*
 * dmUploader - jQuery Ajax File Uploader Widget
 * https://github.com/danielm/uploader
 *
 * Copyright Daniel Morales <daniel85mg@gmail.com>
 * Released under the MIT license.
 * https://github.com/danielm/uploader/blob/master/LICENSE.txt
 */
/* global define, module, require, window, document, FormData */
/*!
jQuery Ajax File Uploader Widget V.1.1 <https://github.com/danielm/uploader>
(C) 2013-2020 Daniel Morales <daniel85mg@gmail.com>
License MIT
*/
(function (factory) {
  "use strict";
  if (typeof define === "function" && define.amd) {
    // AMD. Register as an anonymous module.
    define(["jquery"], factory);
  } else if (typeof exports !== "undefined") {
    module.exports = factory(require("jquery"));
  } else {
    // Browser globals
    factory(window.jQuery);
  }
}(function($) {
  "use strict";

  var pluginName = "dmUploader";
  var eventSpace = ".dmul"; // OR "." + pluginName

  var FileStatus = {
    PENDING: 0,
    UPLOADING: 1,
    COMPLETED: 2,
    FAILED: 3,
    CANCELLED: 4 //(by the user)
  };

  var DmUploaderFile = function(file, widget)
  {
    this.data = file;

    this.widget = widget; // DmUploader object

    this.jqXHR = null;

    this.status = FileStatus.PENDING;

    // This file's id doesn't have to be so special.... or does it?
    var n = Math.random();
    this.id = (n + n * 10).toString(36);
  };

  DmUploaderFile.prototype.upload = function()
  {
    var file = this;

    if (!file.canUpload()) {

      if (file.widget.queueRunning && file.status !== FileStatus.UPLOADING) {
        file.widget.processQueue();
      }

      return false;
    }

    // Form Data
    var fd = new FormData();
    fd.append(file.widget.settings.fieldName, file.data);

    // Append extra Form Data
    var customData = file.widget.settings.extraData;
    if (typeof customData === "function") {
      customData = customData.call(file.widget.element[0], file.id);
    }

    $.each(customData, function (exKey, exVal) {
      fd.append(exKey, exVal);
    });

    file.status = FileStatus.UPLOADING;
    file.widget.activeFiles++;

    if (typeof file.widget.settings.onBeforeUpload === "function") {
      file.widget.settings.onBeforeUpload.call(file.widget.element[0], file.id);
    }
    file.widget.element.trigger("before" + eventSpace, file.id);
    // Ajax submit
    file.jqXHR = $.ajax({
      url: file.widget.settings.url,
      type: file.widget.settings.method,
      dataType: file.widget.settings.dataType,
      data: fd,
      headers: file.widget.settings.headers,
      cache: false,
      timeout: 0,
      contentType: false,
      processData: false,
      forceSync: false,
      xhr: function () {
        return file.getXhr();
      }
    })
    .done(function (data, textStatus, jqXHR) {
      file.onSuccess(data);
    })
    .fail(function (jqXHR, textStatus, errorThrown) {
      file.onError(jqXHR, textStatus, errorThrown);
    })
    .always(function (data, textStatus, errorThrown) {
      file.onComplete();
    });

    return true;
  };

  DmUploaderFile.prototype.onSuccess = function(data)
  {
    this.status = FileStatus.COMPLETED;
    if (typeof this.widget.settings.onUploadSuccess === "function") {
      this.widget.settings.onUploadSuccess.call(this.widget.element[0], this.id, data);
    }
    this.widget.element.trigger("success" + eventSpace, [this.id, data]);
  };

  DmUploaderFile.prototype.onError = function(jqXHR, textStatus, errorThrown)
  {
    // If the status is: cancelled (by the user) don't invoke the error callback
    if (this.status !== FileStatus.CANCELLED) {
      this.status = FileStatus.FAILED;
      if (typeof this.widget.settings.onUploadError === "function") {
        this.widget.settings.onUploadError.call(this.widget.element[0], this.id, jqXHR, textStatus, errorThrown);
      }
      this.widget.element.trigger("error" + eventSpace, [this.id, jqXHR, textStatus, errorThrown]);
    }
  };

  DmUploaderFile.prototype.onComplete = function()
  {
    this.widget.activeFiles--;

    if (this.status !== FileStatus.CANCELLED) {
      if (typeof this.widget.settings.onUploadComplete === "function") {
        this.widget.settings.onUploadComplete.call(this.widget.element[0], this.id);
      }
      this.widget.element.trigger("complete" + eventSpace, this.id);
    }

    if (this.widget.queueRunning) {
      this.widget.processQueue();
    } else if (this.widget.settings.queue && this.widget.activeFiles === 0) {
      if (typeof this.widget.settings.onComplete === "function") {
        this.widget.settings.onComplete.call(this.widget.element[0]);
      }
      this.widget.element.trigger("complete" + eventSpace); //TODO no id === all
    }
  };

  DmUploaderFile.prototype.getXhr = function()
  {
    var file = this;
    var xhrobj = $.ajaxSettings.xhr();

    if (xhrobj.upload) {
      xhrobj.upload.addEventListener("progress", function (event) {
        var percent = 0;
        var position = event.loaded || event.position;
        var total = event.total || event.totalSize;

        if (event.lengthComputable) {
          percent = Math.ceil(position / total * 100);
        }
        if (typeof file.widget.settings.onUploadProgress === "function") {
          file.widget.settings.onUploadProgress.call(file.widget.element[0], file.id, percent);
        }
        file.widget.element.trigger("progress" + eventSpace, [file.id, percent]);
      }, false);
    }

    return xhrobj;
  };

  DmUploaderFile.prototype.cancel = function(abort)
  {
    // The abort flag is to track if we are calling this function directly (using the cancel Method, by id)
    // or the call comes from the 'global' method aka cancelAll.
    // This means that we don't want to trigger the cancel event on file that isn't uploading, UNLESS directly doing it
    // ... and yes, it could be done prettier. Review (?)
    abort = (typeof abort === "undefined" ? false : abort);

    var myStatus = this.status;

    if (myStatus === FileStatus.UPLOADING || (abort && myStatus === FileStatus.PENDING)) {
      this.status = FileStatus.CANCELLED;
    } else {
      return false;
    }

    if (typeof this.widget.settings.onUploadCanceled === "function") {
      this.widget.settings.onUploadCanceled.call(this.widget.element[0], this.id);
    }
    this.widget.element.trigger("cancel" + eventSpace, this.id);

    if (myStatus === FileStatus.UPLOADING) {
      this.jqXHR.abort();
    }

    return true;
  };

  DmUploaderFile.prototype.canUpload = function()
  {
    return (
      this.status === FileStatus.PENDING ||
      this.status === FileStatus.FAILED
    );
  };

  var DmUploader = function(element, options)
  {
    if (element instanceof $) {
      this.element = element;
    } else {
      this.element = $(element);
    }

    this.settings = $.extend({}, $.fn.dmUploader.defaults, options || {});

    if (!this.checkSupport()) {
      $.error("Browser not supported by the dmUploader plugin");

      if (typeof this.settings.onFallbackMode === "function") {
        this.settings.onFallbackMode.call(this.element[0]);
      }
      this.element.trigger("fallback" + eventSpace);

      return false;
    }

    this.init();

    return this;
  };

  DmUploader.prototype.checkSupport = function()
  {
    // This one is mandatory for all modes
    if (typeof window.FormData === "undefined") {
      return false;
    }
    // Test per Modernizr/feature-detects/forms/fileinput.js
    var ua = navigator.userAgent;
    if (ua.match(/(Android (1.0|1.1|1.5|1.6|2.0|2.1))|(Windows Phone (OS 7|8.0))|(XBLWP)|(ZuneWP)|(w(eb)?OSBrowser)|(webOS)|(Kindle\/(1.0|2.0|2.5|3.0))/) ||
        ua.match(/\swv\).+(chrome)\/([\w\.]+)/i)) {
      return false;
    }

    return !$("<input type=\"file\" />").prop("disabled");
  };

  DmUploader.prototype.getInput = function()
  {
    var input = null;
    if (this.settings.inputFile) {
      input = this.settings.inputFile;
      if (!(input instanceof $)) {
        input = $(input);
      }
      if (!input.is("input[type=file]")) {
        input = null; // Try elsewhere
      }
    }
    if (input === null) {
      if (this.element.is("input[type=file]")) {
        input = this.element;
      } else {
        input = this.element.find("input[type=file]");
      }
    }

    return input;
  }

  DmUploader.prototype.init = function()
  {
    // Queue vars
    this.queue = [];
    this.queuePos = -1;
    this.queueRunning = false;
    this.activeFiles = 0;
    this.draggingOver = 0;
    this.draggingOverDoc = 0;

    var input = this.getInput();
    if (input) {
      input.prop("multiple", this.settings.multiple);

      if (this.settings.allowedTypes !== "*" || this.settings.extFilter) {
        // input-file supports attr like accept="image/*,.pdf"
        var types = (this.settings.allowedTypes !== "*") ? this.settings.allowedTypes : '';
        if (this.settings.extFilter) {
          this.settings.extFilter.forEach(function (ext) {
            if (ext[0] === ".") {
              types += "," + ext;
            } else {
              types += ",." + ext;
            }
          });
          if (types[0] === ",") {
            types = types.substring(1);
          }
        }
        input.prop("accept", types);
      } else {
        input.prop("accept", ""); // everything goes
      }

      var widget = this;

      input.on("change", function (evt) {
        var files = evt.target && evt.target.files;

        if (!files || !files.length) {
          return;
        }

        widget.addFiles(files);

        $(this).val("");
      });
    }

    if (this.settings.dnd) {
      // Test for DnD capability per Modernizr
      var test = $("<div/>")[0];
      if ('draggable' in test || ('ondragstart' in test && 'ondrop' in test)) {
        this.initDnD();
      } else {
        this.settings.dnd = false;
      }
    }

    if (this.settings.paste) {
      // TODO test for paste capability
      this.initPaste();
    }

    if (!(input || this.settings.dnd || this.settings.paste)) {
      // Trigger an error because the plugin won't do anything.
      $.error("Markup error found by the dmUploader plugin");
      return null;
    }

    // We good to go, tell them!
    if (typeof this.settings.onInit === "function") {
      this.settings.onInit.call(this.element[0]);
    }
    this.element.trigger("init" + eventSpace);

    return this;
  };

  DmUploader.prototype.initDnD = function()
  {
    var widget = this;

    // We use vanilla js here. jQuery events can wreck dataTransfer processing

    function preventDefault(evt)
    {
      evt.preventDefault();
      evt.stopPropagation();
    }

    this.element[0].addEventListener("drop", function (evt) {
      preventDefault(evt);

      if (widget.draggingOver > 0) {
        widget.draggingOver = 0;
        if (typeof widget.settings.onDragLeave === "function") {
          widget.settings.onDragLeave.call(widget.element[0]);
        }
        widget.element.trigger("dragleave"); //OR spaced ?
      }

      var dt = evt.dataTransfer;
      if (!dt || !dt.files || dt.files.length == 0) {
        return;
      }

      var files;
      if (widget.settings.multiple) {
        files = dt.files;
      } else {
        // Take only the first file if not accepting multiple
        files = [dt.files[0]];
      }

      widget.addFiles(files);
    }, false);

    //-- These two events/callbacks are mostly to allow fancy visual stuff
    this.element[0].addEventListener("dragenter", function(evt) {
      preventDefault(evt);

      if (widget.draggingOver === 0) {
        if (typeof widget.settings.onDragEnter === "function") {
          widget.settings.onDragEnter.call(widget.element[0]);
        }
//        widget.element.trigger("dragenter");  //OR spaced ?
      }

      widget.draggingOver++;
    }, false);

    this.element[0].addEventListener("dragleave", function (evt) {
      preventDefault(evt);

      widget.draggingOver--;

      if (widget.draggingOver === 0) {
        if (typeof widget.settings.onDragLeave === "function") {
          widget.settings.onDragLeave.call(widget.element[0]);
        }
//        widget.element.trigger("dragleave"); //OR spaced ?
      }
    }, false);

    this.element[0].addEventListener('dragover', preventDefault, false);

    if (!this.settings.hookDocument || this.element[0] == document) {
      return;
    }

    // Add some off/namepacing to prevent some weird cases when there are multiple instances
    // TODO make this work better
    $(document).off("dragenter").on("dragenter" + eventSpace, function (evt) {
      preventDefault(evt);

      if (widget.draggingOverDoc === 0) {
        if (typeof widget.settings.onDocumentDragEnter === "function") {
          widget.settings.onDocumentDragEnter.call(widget.element[0]);
        }
        widget.element.trigger("docdragenter" + eventSpace);
      }

      widget.draggingOverDoc++;
    })
    .off("dragleave").on("dragleave" + eventSpace, function (evt) {
      preventDefault(evt);

      widget.draggingOverDoc--;

      if (widget.draggingOverDoc === 0) {
        if (typeof widget.settings.onDocumentDragLeave === "function") {
          widget.settings.onDocumentDragLeave.call(widget.element[0]);
        }
        widget.element.trigger("docdragleave" + eventSpace);
      }
    })
    .off("dragover").on("dragover" + eventSpace, function (evt) {
      evt.preventDefault();
    })
    .off("drop").on("drop" + eventSpace, function (evt) { //spaced ?
      preventDefault(evt);

      if (widget.draggingOverDoc > 0) {
        widget.draggingOverDoc = 0;
        if (typeof widget.settings.onDocumentDragLeave === "function") {
          widget.settings.onDocumentDragLeave.call(widget.element[0]);
        }
        widget.element.trigger("dragleave" + eventSpace); //spaced ?
      }
    });
  };

  DmUploader.prototype.initPaste = function()
  {
    var widget = this;
    document.addEventListener("paste", function (evt) {
      evt.preventDefault();

      var cd = evt.clipboardData;
      if (!cd || !cd.files || cd.files.length == 0) {
        return;
      }

      var files;
      if (widget.settings.multiple) {
        files = cd.files;
      } else {
        // Take only the first file if not accepting multiple
        files = [cd.files[0]];
      }

      widget.addFiles(files);
    }, false);
  };

  DmUploader.prototype.releaseEvents = function()
  {
    // Leave everyone ALONE ;_;

    this.element.off(eventSpace);
    var input = this.getInput();
    if (input) {
      input.off(eventSpace);
    }
    if (this.settings.hookDocument) {
      $(document).off(eventSpace);
    }
  };

  DmUploader.prototype.validateFile = function(file)
  {
    // Check file size
    if ((this.settings.maxFileSize > 0) &&
      (file.size > this.settings.maxFileSize)) {
      if (typeof this.settings.onFileSizeError === "function") {
        this.settings.onFileSizeError.call(this.element[0], file);
      }
      this.element.trigger("sizeerror" + eventSpace, [file]);

      return false;
    }

    // Check file type
    if (this.settings.allowedTypes !== "*") {
      var arr = this.settings.allowedTypes.split(","),
        m = arr.some(function (str) {
          return file.type.match(str);
        });
      if (!m) {
        if (typeof this.settings.onFileTypeError === "function") {
          this.settings.onFileTypeError.call(this.element[0], file);
        }
        this.element.trigger("typeerror" + eventSpace, [file]);

        return false;
      }
    }

    // Sofar, so good. Check file extension (too)
    if (this.settings.extFilter) {
      var str = this.settings.extFilter.join("."),
        rstr = "\\." + str.replace(/\./g, "\\."),
        ext = "\\." + file.name.split(".").pop(),
        re = new RegExp(ext, "i");
      if (!re.test(rstr)) {
        if (typeof this.settings.onFileExtError === "function") {
          this.settings.onFileExtError.call(this.element[0], file);
        }
        this.element.trigger("exterror" + eventSpace, [file]);

        return false;
      }
    }

    return new DmUploaderFile(file, this);
  };

  DmUploader.prototype.addFiles = function(files)
  {
    var nFiles = 0,
      i, n;
    for (i = 0, n = files.length; i < n; i++) {
      var file = this.validateFile(files[i]);

      if (!file) {
        continue;
      }

      if (typeof this.settings.onNewFile === "function") {
          // If the callback returns (exact) false the file will not be processed. This may allow some customization
          var can_continue = this.settings.onNewFile.call(this.element[0], file.id, file.data);
          if (can_continue === false) {
              continue;
          }
      }

      // If we are using automatic uploading, and not a file queue: go for the upload
      if (this.settings.auto && !this.settings.queue) {
        if (nFiles === 0) {
          if (typeof this.settings.onBegin === "function") {
            this.settings.onBegin.call(this.element[0]);
          }
          this.element.trigger("begin" + eventSpace);
        }
        file.upload();
      }

      this.queue.push(file);

      nFiles++;
    }

    // No files were added
    if (nFiles === 0) {
      return this;
    }

    // Are we auto-uploading files?
    if (this.settings.auto && this.settings.queue && !this.queueRunning) {
      if (typeof this.settings.onBegin === "function") {
        this.settings.onBegin.call(this.element[0]);
      }
      this.element.trigger("begin" + eventSpace);
      this.processQueue();
    }

    return this;
  };

  DmUploader.prototype.processQueue = function()
  {
    this.queuePos++;

    if (this.queuePos >= this.queue.length) {
      if (this.activeFiles === 0) {
        if (typeof this.settings.onComplete === "function") {
          this.settings.onComplete.call(this.element[0]);
        }
        this.element.trigger("complete" + eventSpace);
      }

      // Wait until new files are dropped
      this.queuePos = (this.queue.length - 1);

      this.queueRunning = false;

      return false;
    }

    this.queueRunning = true;

    // Start next file
    return this.queue[this.queuePos].upload();
  };

  DmUploader.prototype.restartQueue = function()
  {
    this.queuePos = -1;
    this.queueRunning = false;

    this.processQueue();
  };

  DmUploader.prototype.findById = function(id)
  {
    var i, n;
    for (i = 0, n = this.queue.length; i < n; i++) {
      if (this.queue[i].id === id) {
        return this.queue[i];
      }
    }
    return false;
  };

  DmUploader.prototype.cancelAll = function()
  {
    var queueWasRunning = this.queueRunning,
      i, n;
    this.queueRunning = false;

    // cancel 'em all
    for (i = 0, n = this.queue.length; i < n; i++) {
      this.queue[i].cancel();
    }

    if (queueWasRunning) {
      if (typeof this.settings.onComplete === "function") {
        this.settings.onComplete.call(this.element[0]);
      }
      this.element.trigger("complete" + eventSpace);
    }
  };

  DmUploader.prototype.startAll = function()
  {
    if (this.settings.queue) {
      // Resume queue
      this.restartQueue();
    } else {
      // or upload them all
      var i, n;
      for (i = 0, n = this.queue.length; i < n; i++) {
        this.queue[i].upload();
      }
    }
  };

  // Public API methods
  DmUploader.prototype.methods = {
    start: function (id) {
      if (this.queueRunning) {
        // Do not allow manual uploading when a queue is running
        return false;
      }

      var file = false;

      if (typeof id !== "undefined") {
        file = this.findById(id);

        if (!file) {
          // File not found in stack
          $.error("File not found in the dmUploader plugin");

          return false;
        }
      }

      // Trying to start an upload by ID
      if (file) {
        if (file.status === FileStatus.CANCELLED) {
          file.status = FileStatus.PENDING;
        }
        return file.upload();
      }

      // With no id provided...

      this.startAll();

      return true;
    },
    cancel: function (id) {
      var file = false;
      if (typeof id !== "undefined") {
        file = this.findById(id);

        if (!file) {
          // File not found in stack
          $.error("File not found in the dmUploader plugin");

          return false;
        }
      }

      if (file) {
        return file.cancel(true);
      }

      // With no id provided...

      this.cancelAll();

      return true;
    },
    reset: function () {

      this.cancelAll();

      this.queue = [];
      this.queuePos = -1;
      this.activeFiles = 0;

      return true;
    },
    destroy: function () {
      this.cancelAll();

      this.releaseEvents();

      this.element.removeData(pluginName);
    }
  };

  $.fn.dmUploader = function(options)
  {
    if (typeof options === "string") {
      var args = arguments;

      this.each(function () {
        var plugin = $.data(this, pluginName);

        if (plugin instanceof DmUploader) {
          if (typeof plugin.methods[options] === "function") {
            plugin.methods[options].apply(plugin, Array.prototype.slice.call(args, 1));
          } else {
            $.error("Method " + options + " does not exist in the dmUploader plugin");
          }
        } else {
          $.error("Unknown plugin data found by the dmUploader plugin");
        }
      });
    } else {
      return this.each(function () {
        if (!$.data(this, pluginName)) {
          $.data(this, pluginName, new DmUploader(this, options));
        }
      });
    }
  };

  // Plugin default properties
  $.fn.dmUploader.defaults = $.extend({}, {
    auto: true,
    queue: true,
    dnd: true,
    paste: true,
    hookDocument: true,
    multiple: true,
    url: document.URL,
    method: "POST",
    extraData: {},
    headers: {},
    dataType: null,
    fieldName: "file",
    maxFileSize: 0,
    allowedTypes: "*",
    extFilter: null,
    inputFile: null,
// optional back-compatible alternates to triggered events, unless a return-value is expected
//    onInit: null,
//    onBegin: null,
//    onComplete: null,
//    onFallbackMode: null,
    onNewFile: null //, //params: id, file; return: (exact) false to skip processing
//    onBeforeUpload: null, //param: id
//    onUploadProgress: null, //params: id, percent
//    onUploadSuccess: null, //params: id, data
//    onUploadCanceled: null, //param: id
//    onUploadError: null, //params: id, xhr, status, message
//    onUploadComplete: null, //param: id
//    onFileTypeError: null, //param: file
//    onFileSizeError: null, //param: file
//    onFileExtError: null, //param: file
//    onDragEnter: null,
//    onDragLeave: null,
//    onDocumentDragEnter: null,
//    onDocumentDragLeave: null
  }, $.fn.dmUploader.defaults || {});
}));
