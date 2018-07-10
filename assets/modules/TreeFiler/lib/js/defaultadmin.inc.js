$(document).ready(function() {
 treeinit();
 $('#treecontainer').css('margin-left', 0);
 var tbl = $('#main-table');
 if(tbl.length > 0) {
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
  tbl.SSsort({
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
  var cboxes = $(get_checkboxes());
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
 }
});

function treeinit() {
 $('#cfm-tree').treemenu({
  delay: 300,
  closeOther: true,
  activeSelector: '.active',
  openActive: true,
  menucls: 'treemenu',
  iconcls: 'toggler',
  closedcls: 'tree-closed',
  emptycls: 'tree-empty',
  hiddencls: 'tree-hidden',
  opencls: 'tree-opened'
 });
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
    var p = $(elem).parent(),
        i = rows.index(p);
    rowindx.push(i);
   },
   After: function(t) {
    rows.each(function(i) {
      if (rowindx.indexOf(i) > -1) {
        $(this).removeClass('tree-hidden');
      } else {
        $(this).addClass('tree-hidden');
      }
    });
   }
  });
 } else {
  $('.cfm-tree-title').css('display','none').before(ob);
  $('#cfm-tree').treefilter({
   selector: ob.find('input')[0],
   searchclass: 'search-root',
   matchclass: 'search-result',
   emptyclass: 'tree-empty',
   hideclass: 'tree-hidden',
   openclass: 'tree-opened',
   closedclass: 'tree-closed'
  });
  ob.css('display', 'block');
 }
 ob.find('i').on('click', function() {
  ob.remove();
  if(files) {
   rows.show();
  } else {
   $.fn.treefilter.endSession($('#cfm-tree')[0]);
   $('.cfm-tree-title').css('display','block');
  }
 });
}
function doClose() {
 $.fn.treemenu.closeAll($('#cfm-tree')[0]);
}

function get_checkboxes() {
 var e = document.querySelectorAll("[name=isel]"),
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
function refreshList() {
 $('#display').load('~%$relist_url%~');
}
function refreshTree() {
 //TODO
 $('#cfm-tree').load('~%$retree_url%~');
 treeinit();
}
function selectedNames() {
 var e = get_checkboxes(),
   n = e.length,
   t = [];
 if (n > 0) {
  n--;
  for(var i=0; i<n; i++) {
   if(e[i].checked) {
    t.push(e[i].value);
   }
  }
 }
 return JSON.stringify(t);
}

var actionid = '~%$id%~',
 actionurl = '~%$action_url%~',
 badparm = '~%lang|err_parm%~';

function makeurl(parms) {
 var u = actionurl;
 for (var key in parms) {
  if(parms.hasOwnProperty(key)) {
   u += '&' + actionid + key + '=' + encodeURIComponent(parms[key]);
  }
 }
 return u;
}
function doajax(parms, passfunc, failfunc) {
 var u = makeurl(parms);
 $.ajax({
  url: u,
  method: 'POST',
  dataType: 'json',
  success: function (data, textStatus, jqXHR) {
   if(typeof passfunc === 'function') {
    passfunc(data, textStatus, jqXHR);
   } else {
    refreshList();
    //if it's a folder, refreshTree();
    cms_notify(data[0], data[1]);
   }
  },
  error: function(jqXHR, textStatus) {
   if(typeof failfunc === 'function') {
    failfunc(jqXHR, textStatus);
   } else {
    cms_notify('error', jqXHR.responseText);
   }
  }
 });
}

function oneDelete(p, f) {
 cms_confirm('~%lang|del_confirm%~').done(function() {
  doajax({del:f}); //TODO if f is a folder, passfunc() including refreshTree();
 });
}
function oneRename(p, f, df) {
 cms_prompt('~%lang|newname%~', df).done(function(n) {
  if(n !== null && n !== '' && df !== n) {
   doajax({ren:f,to:n}); //TODO if it's a folder, passfunc() including refreshTree();
  } else {
   cms_notify('error', badparm);
  }
 });
}
function oneCopy(p, f, df) {
 cms_prompt('~%lang|tofolder%~').done(function(n) {
  //TODO raw(n)
  if(n !== null && n !== '' && currentfolder() !== n) {
   doajax({oneto:1,from:f,to:n}); //TODO pass()
     //if it's to current folder refreshList();
     //if it's any folder, refreshTree();
  } else {
   cms_notify('error', badparm);
  }
 });
}
function oneLink(p, f, df) {
 var e = $('#link_dlg');
 e.find('#toname').val(df);
 cms_dialog(e, {
  buttons: [
   {
    text: '~%lang|submit%~',
    icon: 'ui-icon-check',
    click: function() {
     var d = e.find('#tofolder').val(),
       n = e.find('#toname').val();
     $(this).dialog('close');
     //TODO raw(n), raw(d)
     if ((n !== null && n !== '' && n != df) || 0)  { //TODO  or raw(d) != currentfolder()
      doajax({target:f,folder:d,name:n}); //TODO if it's to current folder refreshList(); or any folder, pass() including refreshTree();
     } else {
      cms_notify('error', badparm);
     }
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
function oneChmod(p, f, df, isdir, m) {
 var s = (isdir) ? '~%lang|folder%~' : '~%lang|file%~',
     e = $('#chmod_dlg');
 e.find('#filetitle').html(s + ':' + p + ' ' + df);
 s = (isdir) ? '~%lang|access%~' : '~%lang|exec%~';
 e.find('#exectitle').html(s);
 var modes = {
   ur: 0400,
   uw: 0200,
   ux: 0100,
   gr: 040,
   gw: 020,
   gx: 010,
   or: 04,
   ow: 02,
   ox: 01
 };
 e.find(':checkbox').each(function() {
  var flags = modes[this.id] || 0;
  this.checked = (m & flags) > 0;
 });
 cms_dialog(e, {
  buttons: [
   {
    text: '~%lang|submit%~',
    icon: 'ui-icon-check',
    click: function() {
     var nm = 0;
     e.find(':checkbox').each(function() {
      if(this.checked) {
       nm += (modes[this.id] || 0);
      }
     });
     $(this).dialog('destroy');
     if(nm != (m & 07777)) {
       doajax({chmod:f,mode:nm});
     }
    }
   },
   {
    text: '~%lang|cancel%~',
    icon: 'ui-icon-cancel',
    click: function() {
     $(this).dialog('destroy');
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
  if(n !== null && n !== '' && currentfolder() !== n) {
   doajax({create:n,type:'folder'}); //TODO pass() including refreshTree();
  } else {
   cms_notify('error', badparm);
  }
 });
}
function doDelete() {
// ev.preventDefault();
 if(any_check()) {
  cms_confirm('~%lang|delete_confirm%~').done(function() {
   var names = selectedNames();
   doajax({del:1,sel:names}); //TODO if any sel is a folder, pass() including refreshTree();
  });
 }
 return false;
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
      var n = $('[name="~%$id%~archname"]').val(),
        t = $('[name="~%$id%~archiver"]:checked').val(),
        names = selectedNames();
      $(this).dialog('close');
      doajax({compress:1,archname:n,archtype:t,sel:names}); //TODO if it's multi-item, pass() including refreshTree();
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
function doExpand() {
 if(any_check()) {
   var names = selectedNames();
   doajax({decompress:1,sel:names}); //TODO pass() including refreshTree(); in case arhive(s) include dirs
 }
}
function doLocate(copy) {
 if(any_check()) {
  cms_prompt('~%lang|tofolder%~').done(function(n) {
   //TODO raw(n)
   if(n !== null && n !== '' && currentfolder() !== n) {
    if(typeof copy === 'undefined') copy = true;
    var names = selectedNames(),
     args = (copy) ? {copy:1,todir:n,sel:names} : {move:1,todir:n,sel:names};
    doajax(args); //TODO if it's a folder, pass() including refreshTree();
   } else {
    cms_notify('error', badparm);
   }
  });
 }
}
function doUpload() {
 var errs = 0,
  ups = 0,
  u = makeurl({ul:1}),
  e = $('#upload_dlg');
 e.dmUploader({
  url: u,
/*
 maxFileSize: 3000000, // 3 Megs
 onInit: function() {
  // Plugin is ready to use
 },
*/
 onComplete: function() {
  // All files in the queue are processed (success or error)
  cms_dialog(e, 'destroy');
  refreshList();
  if(ups > 0) {
   cms_notify('success', 'Uploaded ' + ups + 'items'); //TODO lang
  }
  if(errs > 0) {
   cms_notify('error',  errs + 'upload(s) failed');
  }
 },
/* onBeforeUpload: function(id) {
  // About to start uploading a file
 },
 onUploadCanceled: function(id) {
  // An upload was directly canceled by the user.
 },
 onUploadProgress: function(id, percent) {
  // Updating file progress
 },
 */
 onUploadSuccess: function(id, data) {
  // A file was successfully uploaded, data = whatever upstream outputs
  ups++;
 },
 onUploadError: function(id, xhr, status, message) {
  // A file upload failed
  errs++;
 },
 onFileSizeError: function(file) {
  // A file is too big
  errs++;
 }
 /*,
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
