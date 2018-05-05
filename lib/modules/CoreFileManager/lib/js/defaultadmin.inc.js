var cboxes;

$(document).ready(function() {
 $('#fm-tree').treemenu({
  delay: 300,
  closeOther: true,
  activeSelector: 'active',
  openActive: true
 });
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

function newfolder() {
 var t = document.getElementById("newfilename").value,
  n = document.querySelector('input[name="newfile"]:checked').value;
 if(null !== t && '' !== t && n) {
  window.location.hash = "#";
  window.location.search = "p=" + encodeURIComponent(e) + "&new=" + encodeURIComponent(t) + "&type=" + encodeURIComponent(n);
 }
}

function rename(e, t) {
 cms_prompt('~%lang|newname%~', t).done(function(n) {
  if(null !== n && "" !== n && n != t) {
   window.location.search = "p=" + encodeURIComponent(e) + "&ren=" + encodeURIComponent(t) + "&to=" + encodeURIComponent(n);
  }
 });
}

function compressclick() {
 if(any_check()) {
  cms_dialog($('#compress_dlg'), {
   modal: true,
   buttons: {
    '~%lang|ok%~': function() {
     var fn = $('[name="~%$id%~archname"]').val(),
      ft = $('[name="~%$id%~archiver"]:checked').val();
     cms_dialog($(this), 'close');
     var fm = $('form'),
      ex = '<input type="hidden" name="~%$id%~compress" value"1"/><input type="hidden" name="~%$id%~archname" value"' + fn + '"/><input type="hidden" name="~%$id%~archtype" value"' + ft + '"/>';
     fm.prepend(ex).trigger('submit');
    },
    '~%lang|cancel%~': function() {
     cms_dialog($(this), 'close');
    }
   }
  });
 }
 return false;
}

function deleteclick(el) {
 if(any_check()) {
  cms_confirm_btnclick(el, '~%lang|delete_confirm%~');
 }
 return false;
}

function get_checkboxes() {
 var e = document.querySelectorAll("[name^=~%$id%~sel\\[]"),
  t = [];
  for(var n = e.length - 1; n >= 0; n--) {
   if(e[n].type === "checkbox") t.push(e[n]);
 }
 return t;
}

function any_check() {
 var e = get_checkboxes();
 for(var n = e.length - 1; n >= 0; n--) {
  if(e[n].checked) return true;
 }
 return false;
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

function doSearch(files) {
 var ob = $('#searchbox').clone(true);
 if(files) {
  $('#main-table').before(ob);
  ob.css('display', 'block').find('inout').jSearch({
   selector: 'table',
   child: 'tr > td',
   minValLength: 0,
   Before: function() {
    $('table tr').data('find', '');
   },
   Found: function(elem, ev) {
    $(elem).parent().data('find', 'true');
    $(elem).parent().show();
   },
   NotFound: function(elem, ev) {
    if(!$(elem).parent().data('find'))
     $(elem).parent().hide();
   },
   After: function(t) {
    if(!t.val().length) $('table tr').show();
   }
  });
 } else {
  $('.fm-tree-title').before(ob);
  ob.css('display', 'block').find('input').jSearch({
   selector: 'ul',
   child: 'li a',
   minValLength: 0,
   Found: function(elem, ev) {
    $(elem).parent().parent().show();
   },
   NotFound: function(elem, ev) {
    $(elem).parent().parent().hide();
   },
   After: function(t) {
    if(!t.val().length) $('ul li').show();
   }
  });
 }
 ob.find('i').on('click', function() {
  ob.remove();
  if(files) {} else {}
 });
}

function doUpload(url) {
 var e = $('#upload_dlg');
 //onetime only ...
 e.dmUploader({
  url: '~%$upload_url%~'
/*
 maxFileSize: 3000000, // 3 Megs
 onInit: function() {
  // Plugin is ready to use
 },
 onComplete: function() {
  // All files in the queue are processed (success or error)
 },
 onBeforeUpload: function(id) {
  // about tho start uploading a file
 },
 onUploadCanceled: function(id) {
  // Happens when a file is directly canceled by the user.
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
  open: function(ev, ui) {
   cms_equalWidth($('#upload_dlg label.boxchild'));
  },
  modal: true
 });
}
