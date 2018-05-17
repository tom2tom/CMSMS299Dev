var cboxes;

$(document).ready(function() {
 treeinit();
 $.fn.SSsort.addParser({
  id: 'fname',
  is: function(s, node) {
   return true; //node.getAttribute('data-sort') !== null;
  },
  format: function(s, node) {
   var dbg = node.getAttribute('data-sort');
   return $.trim(node.getAttribute('data-sort'));
  },
  watch: false,
  type: 'text'
 });
 $.fn.SSsort.addParser({
  id: 'fint',
  is: function(s, node) {
   return true; //node.getAttribute('data-sort') !== null;
  },
  format: function(s, node) {
   var dbg = node.getAttribute('data-sort');
   return $.fn.SSsort.formatInt(node.getAttribute('data-sort'));
  },
  watch: false,
  type: 'numeric'
 });
 $('#main-table').SSsort({
  sortClass: 'if-sortable',
  ascClass: 'if-sort-up',
  descClass: 'if-sort-down',
  oddClass: 'row1',
  evenClass: 'row2',
  oddsortClass: 'row1s',
  evensortClass: 'row2s'
 });
 var shifted = false,
  firstClicked = null;
 $(document).keydown(function(ev) {
  if(ev.keyCode == 16) {
   shifted = true;
  }
 }).keyup(function(ev) {
  if(ev.keyCode == 16) {
   shifted = false;
  }
 });
 cboxes = $(get_checkboxes());
 cboxes.on('click', function() {
  if(shifted && firstClicked) {
   var i,
    first = cboxes.index(firstClicked),
    last = cboxes.index(this),
    chk = firstClicked.checked;
   if(first < last) {
    for(i = first; i <= last; i++) {
     cboxes[i].checked = chk;
    }
   } else if(first > last) {
    for(i = first; i >= last; i--) {
     cboxes[i].checked = chk;
    }
   }
  }
  firstClicked = this;
 });
});

function treeinit() {
 $('#fm-tree').treemenu({
  delay: 300,
  closeOther: true,
  activeSelector: 'active',
  openActive: true
 });
}

function get_checkboxes() {
 var e = document.querySelectorAll("[name^=~%$id%~sel\\[]"),
  t = [];
  for(var n = e.length - 1; n >= 0; n--) {
   if(e[n].type === "checkbox") t.push(e[n]);
 }
 return t;
}
function change_checkboxes(e, t) {
 for(var n = e.length - 1; n >= 0; n--) e[n].checked = "boolean" == typeof t ? t : !e[n].checked;
}
function checkall_toggle(btn) {
 change_checkboxes(get_checkboxes(), btn.checked);
}
function checkbox_toggle() {
 var e = get_checkboxes();
 e.push(this);
 change_checkboxes(e);
}
function select_all() {
 change_checkboxes(get_checkboxes(), !0);
 var btn = document.getElementById("checkall");
 btn.checked = !0;
}
function unselect_all() {
 change_checkboxes(get_checkboxes(), !1);
 var btn = document.getElementById("checkall");
 btn.checked = !1;
}
function invert_all() {
 change_checkboxes(get_checkboxes());
}
function any_check() {
 var e = get_checkboxes();
 for(var n = e.length - 1; n >= 0; n--) {
  if(e[n].checked) return true;
 }
 return false;
}

function currentfolder() {
 var path = '~%$here%~';
 if(path !== '') {
  return path.replace(/^.*[/\\]/g, '');
 }
 return '';
}

function oneRename(p, f, d) {
 cms_prompt('~%lang|newname%~', d).done(function(n) {
  if(null !== n && '' !== n && d !== n) {
   //TODO ajax etc
  }
 });
}
function oneCopy(p, f, d) {
 cms_prompt('~%lang|tofolder%~').done(function(n) {
  //TODO raw(n)
  if(null !== n && '' !== n && currentfolder() !== n) {
   //TODO ajax etc
  }
 });
}
function oneLink(p, f, d) {
 var e = $('#link_dlg');
 e.find('#toname').val(d);
 cms_dialog(e, {
  buttons: [
   {
    text: '~%lang|submit%~',
    icon: 'ui-icon-check',
    click: function() {
     var frm = $(this).closest('form'),
        dir = frm.find('#tofolder').val(),
       name = frm.find('#toname').val();
     if (0) { //TODO raw(name) !== f or raw(dir) != currentfolder()
      frm.find('[name="~%$id%~target"]').val(f);
      frm.trigger('submit');
     }
     $(this).dialog('close');
    }
   },
   {
    text: '~%lang|cancel%~',
    icon: 'ui-icon-cancel',
    click: function() {
     $(this).dialog('close');
    }
   }
  ],
  modal: true,
  width: 'auto'
 });
}

function newFolder() {
 cms_prompt('~%lang|newfoldername%~').done(function(n) {
   //TODO raw(n)
  if(null !== n && '' !== n && currentfolder() !== n) {
   //TODO ajax etc
  }
 });
}
function doCompress() {
 if(any_check()) {
  //TODO if 1 selected, basename= ; $('[name=~%$id%~archname]').val(basename);
  cms_dialog($('#compress_dlg'), {
   buttons: [
    {
     text: '~%lang|submit%~',
     icon: 'ui-icon-check',
     click: function() {
     var fn = $('[name="~%$id%~archname"]').val(),
        ft = $('[name="~%$id%~archiver"]:checked').val();
     $(this).dialog('close');
     var fm = $('form'),
        ex = '<input type="hidden" name="~%$id%~compress" value"1"/><input type="hidden" name="~%$id%~archname" value"' + fn + '"/><input type="hidden" name="~%$id%~archtype" value"' + ft + '"/>';
     fm.prepend(ex).trigger('submit');
     }
    },
    {
     text:'~%lang|cancel%~',
     icon: 'ui-icon-cancel',
     click: function() {
      $(this).dialog('close');
     }
    }
   ],
   modal: true
  });
 }
}
function doDelete(btn) {
 if(any_check()) {
  cms_confirm_btnclick(btn, '~%lang|delete_confirm%~');
 }
}
function doCopy() {
 if(any_check()) {
  cms_prompt('~%lang|tofolder%~').done(function(n) {
   //TODO raw(n)
   if(null !== n && '' !== n  && currentfolder() !== n) {
   //TODO ajax etc
   }
  });
 }
}
function doMove() {
 if(any_check()) {
  cms_prompt('~%lang|tofolder%~').done(function(n) {
   //TODO raw(n)
   if(null !== n && '' !== n && currentfolder() !== n) {
   //TODO ajax etc
   }
  });
 }
}
function doSearch(files) {
 var ob = $('#searchbox').clone(true);
 if(files) {
  var rows = null, rowindx;
  $('#main-table').before(ob);
  ob.css('display', 'block').find('input').jSearch({
   selector: '#main-table',
   child: 'tr > td',
   minValLength: 0,
   Before: function(t) {
    if (rows === null) {
      rows = $('#main-table tr').has('td');
    }
    rowindx = [];
   },
   Found: function(elem, idx) {
    var p = $(elem).parent();
       i = rows.index(p);
    rowindx.push(i);
   },
   After: function(t) {
    rows.each(function(i) {
      if (rowindx.indexOf(i) > -1) {
         $(this).show();
      } else {
         $(this).hide();
      }
    });
   }
  });
 } else {
  $('.fm-tree-title').before(ob);
  ob.css('display', 'block').find('input').jSearch({
   selector: '#fm-tree',
   child: 'li a',
   minValLength: 0,
   Found: function(elem, idx) {
    $(elem).parent().parent().show();
   },
   NotFound: function(elem, idx) {
    $(elem).parent().parent().hide();
   },
   After: function(t) {
    if(!t.val().length) $('ul li').show();
   }
  });
 }
 ob.find('i').on('click', function() {
  ob.remove();
  if(files) {
    rows.show();
  } else {
    treeinit();
  }
 });
}
function doUpload() {
 var e = $('#upload_dlg');
 //TODO onetime only ...
 e.dmUploader({
  url: '~%$upload_url%~',
/*
 maxFileSize: 3000000, // 3 Megs
 onInit: function() {
  // Plugin is ready to use
 },
 onComplete: function() {
  // All files in the queue are processed (success or error)
 },
*/
 onBeforeUpload: function(id) {
  // about to start uploading a file
   cms_dialog(e, 'close');
 }
/*,
 onUploadCanceled: function(id) {
  // An upload was directly canceled by the user.
 },
 onUploadProgress: function(id, percent) {
  // Updating file progress
 },
 onUploadSuccess: function(id, data) {
  // A file was successfully uploaded
 },
 onUploadError: function(id, xhr, status, message) {
  // A file upload failed
 },
 onFileSizeError: function(file) {
  // When the file is too big
 },
 onFallbackMode: function() {
  // When the browser doesn't support this plugin
 }
*/
 });
 cms_dialog(e, {
  modal: true,
  width: 'auto'
 });
}
