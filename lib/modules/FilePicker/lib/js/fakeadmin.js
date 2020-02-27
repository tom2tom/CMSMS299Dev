/* admin js methods used in filebrowser.js */
var cms_lang = {}; // populated elsewhere

function cms_busy(enable) {
  var s = enable ? 'wait' : 'default';
  document.body.style.cursor = s;
}

/* jQui dialogs */

function cms_alert(msg, yesno) {
  var lbl1;
  if (yesno) {
    lbl1 = cms_lang.yes || 'Yes';
  } else {
    lbl1 = cms_lang.ok || 'Ok';
  }

  $('<div/>')
    .html(msg)
    .dialog({
      autoOpen: true,
      modal: true,
      buttons: [{
        text: lbl1,
        click: function () {
          $(this).dialog("close");
        }
      }],
      close: function () {
        $(this).dialog("destroy").remove();
      }
    });
}

function cms_confirm(msg, yesno) {
  var lbl1, lbl2;
  if (yesno) {
    lbl1 = cms_lang.yes || 'Yes';
    lbl2 = cms_lang.no || 'No';
  } else {
    lbl1 = cms_lang.ok || 'Ok';
    lbl2 = cms_lang.cancel || 'Cancel';
  }

  var dfd = $.Deferred();
  $('<div/>')
    .html(msg)
    .dialog({
      autoOpen: true,
      modal: true,
      title: 'Confirm',
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
  return content.dialog(options);
}
