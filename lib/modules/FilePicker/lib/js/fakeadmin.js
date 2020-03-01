/* admin js methods used in filebrowser.js */
/* global $ */

// normally re-populated elsewhere
var cms_lang = {
 yes: 'Yes',
 no: 'No',
 ok: 'Ok',
 cancel: 'Cancel',
 confirm: 'Confirm'
};

function cms_busy(enable) {
  var s = enable ? 'wait' : 'default';
  document.body.style.cursor = s;
}

/* jQui dialogs */

function custom_classes(title) {
  var s1, s2;
  if (title) {
    s1 = 'popup-header'
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
//   'ui-dialog-buttonpane': 'myclass6',
//   'ui-dialog-buttonset': 'myclass7',
//   'ui-dialog-buttons': 'myclass8',
//useless   'ui-button': 'fp-button',
//useless   'ui-widget-overlay': 'popup-overlay'
  };
}

function cms_alert(msg, yesno, block) {
  var lbl1, custom, dfd;
  if (yesno) {
    lbl1 = cms_lang.yes;
  } else {
    lbl1 = cms_lang.ok;
  }
  custom = custom_classes(false);
  if (block) {
    dfd = $.Deferred();
  }
  // replace js newlines for use in html
  msg = msg.replace(/\n/g, '<br/>');
  $('<div/>')
    .html(msg)
    .dialog({
      modal: true,
      classes: custom,
      buttons: [{
        text: lbl1,
        click: function () {
          $(this).dialog("close");
        }
      }],
      close: function () {
        if (typeof dfd !== 'undefined') {
          dfd.resolve(true);
        }
        $(this).dialog("destroy").remove();
      }
    });
  if (block) {
    return dfd.promise();
  }
}

function cms_confirm(msg, yesno) {
  var lbl1, lbl2, custom, dfd;
  if (yesno) {
    lbl1 = cms_lang.yes;
    lbl2 = cms_lang.no;
  } else {
    lbl1 = cms_lang.ok;
    lbl2 = cms_lang.cancel;
  }
  custom = custom_classes(true);
  dfd = $.Deferred();
  // replace js newlines for use in html
  msg = msg.replace(/\n/g, '<br/>');
  $('<div/>')
    .html(msg)
    .dialog({
      modal: true,
      title: cms_lang.confirm, 
      classes: custom,
      buttons: [{
          text: lbl1,
          click: function () {
            dfd.resolve(true);
            $(this).dialog("close");
          }
        },
        {
          text: lbl2,
          click: function () {
            $(this).dialog("close");
          }
        }
      ],
      close: function () {
        $(this).dialog("destroy").remove();
        if (dfd.promise.isPending()) {
          dfd.resolve(false);
        }
      }
    });
  return dfd.promise();
}

function cms_dialog(content, options) {
  if (options.block) {
    //setup stuff
    delete options.block;
  }
  options.classes = $.extend({}, options.classes || {}, custom_classes(options.title != false));
  return content.dialog(options);
}
