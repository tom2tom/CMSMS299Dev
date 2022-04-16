/*!
Adminlite script v.0.8
(C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL3+
*/
/*
Admin methods for use during frontend requests, notably for file browsing/picking
Uses jQuery UI dialogs
*/
/* global $, cms_alert, cms_data */

if (typeof cms_alert !== 'function') {

//TODO get translated versions of these, from ?
if (typeof cms_data === 'undefined') {
 var cms_data;
}
cms_data = $.extend({}, {
  lang_alert: 'Alert',
  lang_confirm: 'Confirm',
  lang_yes: 'Yes',
  lang_no: 'No',
  lang_ok: 'Ok',
  lang_cancel: 'Cancel',
  lang_close: 'Close'
}, cms_data || {});

cms_lang = function(key) {
  var s = 'lang_' + key;
  if (typeof cms_data[s] !== 'undefined') {
    return cms_data[s];
  }
  return 'Missing translation for \'' + key + '\'';
};

cms_busy = function(enable) {
  var s = enable ? 'wait' : 'default';
  document.body.style.cursor = s;
};

/* jQ UI dialogs */
/* boxes:
ancestor iframe used for modalizing
div.ui-dialog etc
  div.ui-dialog-titlebar etc
  div#dialog.ui-dialog-content etc
*/
custom_classes = function(title) {
  var s1, s2;
  if (title) {
    s1 = 'popup-header';
    s2 = 'popup-title';
  } else {
    s1 = 'anon-header';
    s2 = 'visuallyhidden';
  }
  return {
   'ui-dialog': 'fp-navbar', // bg color
   'ui-dialog-titlebar': s1,
   'ui-dialog-title': s2,
   'ui-dialog-titlebar-close': 'visuallyhidden' //,
  };
};
// 'ui-dialog-buttonpane': 'myclass6',
// 'ui-dialog-buttonset': 'myclass7',
// 'ui-dialog-buttons': 'myclass8',
//useless   'ui-button': 'fp-button',
//useless   'ui-widget-overlay': 'popup-overlay'

cms_dialog = function(content, options) {
  if (options.block) {
    //setup stuff
    delete options.block;
  }
  options.classes = $.extend({}, options.classes || {}, custom_classes(options.title != false));
  return content.dialog(options);
};

cms_alert = function(msg, title, wait) {
  if (msg === undefined || msg === '') return;
  if (!title) title = cms_lang('alert');
  var lbl1 = cms_lang('close'),
    custom = custom_classes(false),
    dfd;
  if (wait) {
    dfd = $.Deferred();
  } else {
    dfd = false;
  }
  // replace js newlines for use in html
  msg = msg.replace(/\n/g, '<br/>');
  $('<div/>')
    .html(msg)
    .dialog({
      modal: true,
      title: title,
      classes: custom,
      buttons: [{
        text: lbl1,
        click: function () {
          $(this).dialog('close');
          if (dfd) {
            dfd.resolve();
          }
        }
      }],
      close: function () {
        $(this).dialog('destroy');
        $(this).remove();
        if (dfd) {
          dfd.resolve();
        }
      }
    });
  if (dfd) {
    return dfd.promise();
  }
};

cms_confirm = function(msg, title, yestxt, notxt) {
  if (!title) {
    title = cms_lang('confirm');
  }
  if (!yestxt) {
    yestxt = cms_lang('ok');
  }
  if (!notxt) {
    notxt = cms_lang('cancel');
  }
  var custom = custom_classes(true);
  // replace js newlines for use in html
  msg = msg.replace(/\n/g, '<br/>');
  var dfd = $.Deferred();
  $('<div/>')
    .html(msg)
    .dialog({
      modal: true,
      title: title,
      classes: custom,
      buttons: [{
          text: yestxt,
          click: function () {
            $(this).dialog('close');
            dfd.resolve();
          }
        },
        {
          text: notxt,
          click: function () {
            $(this).dialog('close');
            dfd.reject();
          }
        }
      ],
      close: function () {
        $(this).dialog('destroy');
        $(this).remove();
      }
    });
  return dfd.promise();
};

} // no cms_alert()
