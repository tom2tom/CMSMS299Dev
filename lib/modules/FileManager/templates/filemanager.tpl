{if !isset($noform)}
<style type="text/css">
{literal}
a.filelink:visited {
   color: #000;
}
{/literal}
</style>
<script type="text/javascript">
{literal}//<![CDATA[
var refresh_url = {/literal}'{$refresh_url}'{literal} + '&cmsjobtype=1';
refresh_url = refresh_url.replace(/amp;/g, '');
function enable_button(idlist) {
  $(idlist).removeAttr('disabled').removeClass('ui-state-disabled ui-button-disabled');
}

function disable_button(idlist) {
  $(idlist).attr('disabled', 'disabled').addClass('ui-state-disabled ui-button-disabled');
}

function enable_action_buttons() {
  var files = $("#filesarea input[type='checkbox'].fileselect").filter(':checked').length,
    dirs = $("#filesarea input[type='checkbox'].dir").filter(':checked').length,
    arch = $("#filesarea input[type='checkbox'].archive").filter(':checked').length,
    text = $("#filesarea input[type='checkbox'].text").filter(':checked').length,
    imgs = $("#filesarea input[type='checkbox'].image").filter(':checked').length;
  disable_button('button.filebtn');
  $('button.filebtn').attr('disabled', 'disabled');
  if(files === 0 && dirs === 0) {
    // nothing selected, enable anything with select_none
    enable_button('#btn_newdir');
  } else if(files == 1) {
    // 1 selected, enable anything with select_one
    enable_button('#btn_rename');
    enable_button('#btn_move');
    enable_button('#btn_delete');
    if(dirs === 0) enable_button('#btn_copy');
    if(arch == 1) enable_button('#btn_unpack');
    if(imgs == 1) enable_button('#btn_view,#btn_thumb,#btn_resizecrop,#btn_rotate');
    if(text == 1) enable_button('#btn_view');
  } else if(files > 1 && dirs === 0) {
    // multiple files selected
    enable_button('#btn_delete,#btn_copy,#btn_move');
  } else if(files > 1 && dirs > 0) {
    // multiple selected, at least one dir
    enable_button('#btn_delete,#btn_move');
  }
}

$(document).ready(function() {
  enable_action_buttons();
  $('#refresh').off('click').on('click', function() {
    // ajaxy reload for the files area.
    $('#filesarea').load(refresh_url);
    return false;
  });
  $(document).on('dropzone_chdir', $(this), function(e, data) {
    // if change dir via the dropzone, make sure filemanager refreshes.
    location.reload();
  });
  $(document).on('dropzone_stop', $(this), function(e, data) {
    // if change dir via the dropzone, make sure filemanager refreshes.
    location.reload();
  });
  $('#filesarea input[type="checkbox"].fileselect').on('change', function(e) {
    // find the parent row
    var t = $(this).attr('checked');
    if(t) {
      $(this).closest('tr').addClass('selected');
    } else {
      $(this).closest('tr').removeClass('selected');
    }
    enable_action_buttons();
    return false;
  });
  $('#tagall').on('change', function() {
    if($(this).is(':checked')) {
      $('#filesarea input:checkbox.fileselect').attr('checked', true).trigger('change');
    } else {
      $('#filesarea input:checkbox.fileselect').attr('checked', false).trigger('change');
    }
  });
  $('#btn_view').on('click', function() {
    // find the selected item.
    var tmp = $("#filesarea input[type='checkbox']").filter(':checked').val();
    var url = {/literal}'{$viewfile_url}&cmsjobtype=1&{$actionid}viewfile='{literal} + tmp;
    url = url.replace(/amp;/g, '');
    $('#popup_contents').load(url);
    cms_dialog($('#popup'), {
      minWidth: 380,
      maxHeight: 600
    });
    return false;
  });
  $('td.clickable').on('click', function() {
    var t = $(this).parent().find(':checkbox').attr('checked');
    if(t !== 'checked') {
      $(this).parent().find(':checkbox').attr('checked', true).trigger('change');
    } else {
      $(this).parent().find(':checkbox').attr('checked', false).trigger('change');
    }
  });
});
{/literal}//]]>
</script>

{function filebtn}
{if isset($text) && $text}
  {if isset($icon) && $icon}{$addclass=' icon'|cat:$icon}{else}{$addclass=''}{/if}
  {if !isset($title) || $title == ''}{$title=$text}{/if}
{/if}
  <button type="submit" name="{$iname}" id="{$id}" title="{$title|default:''}" class="filebtn adminsubmit{$addclass}">{$text}</button>
{/function}

<div id="popup" style="display: none;">
  <div id="popup_contents" style="min-width: 500px; max-height: 600px;"></div>
</div>
<!-- TODO custom icons for buttons newdir view rename delete move copy unpack thumbnail size rotate -->
<div>
  {$formstart}
  {$hiddenpath}
  <div>
    <fieldset>
      {filebtn id='btn_newdir' iname="{$actionid}fileactionnewdir" icon='plus' text=$mod->Lang('newdir') title=$mod->Lang('title_newdir')}
      {filebtn id='btn_view' iname="{$actionid}fileactionview" icon='zoomin' text=$mod->Lang('view') title=$mod->Lang('title_view')}
      {filebtn id='btn_rename' iname="{$actionid}fileactionrename" icon='TODO' text=$mod->Lang('rename') title=$mod->Lang('title_rename')}
      {filebtn id='btn_delete' iname="{$actionid}fileactiondelete" icon='trash' text=$mod->Lang('delete') title=$mod->Lang('title_delete')}
      {filebtn id='btn_move' iname="{$actionid}fileactionmove" icon='TODO' text=$mod->Lang('move') title=$mod->Lang('title_move')}
      {filebtn id='btn_copy' iname="{$actionid}fileactioncopy" icon='copy' text=$mod->Lang('copy') title=$mod->Lang('title_copy')}
      {filebtn id='btn_unpack' iname="{$actionid}fileactionunpack" icon='suitcase' text=$mod->Lang('unpack') title=$mod->Lang('title_unpack')}
      {filebtn id='btn_thumb' iname="{$actionid}fileactionthumb" icon='star' text=$mod->Lang('thumbnail') title=$mod->Lang('title_thumbnail')}
      {filebtn id='btn_resizecrop' iname="{$actionid}fileactionresizecrop" icon='image' text=$mod->Lang('resizecrop') title=$mod->Lang('title_resizecrop')}
      {filebtn id='btn_rotate' iname="{$actionid}fileactionrotate" icon='image' text=$mod->Lang('rotate') title=$mod->Lang('title_rotate')}
    </fieldset>
  </div>
{/if} {* !isset($noform) *}

  <div id="filesarea">
    <table style="width:100%;" class="pagetable scrollable">
      <thead>
        <tr>
          <th class="pageicon">&nbsp;</th>
          <th>{$filenametext}</th>
          <th class="pageicon">{$mod->Lang('mimetype')}</th>
          <th class="pageicon">{$fileinfotext}</th>
          <th class="pageicon" title="{$mod->Lang('title_col_fileowner')}">{$fileownertext}</th>
          <th class="pageicon" title="{$mod->Lang('title_col_fileperms')}">{$filepermstext}</th>
          <th class="pageicon" title="{$mod->Lang('title_col_filesize')}" style="text-align:right;">{$filesizetext}</th>
          <th class="pageicon"></th>
          <th class="pageicon" title="{$mod->Lang('title_col_filedate')}">{$filedatetext}</th>
          <th class="pageicon">
            <input type="checkbox" name="tagall" value="tagall" id="tagall" title="{$mod->Lang('title_tagall')}" />
          </th>
        </tr>
      </thead>
      <tbody>
        {foreach $files as $file} {$thedate=str_replace(' ','&nbsp;',$file->filedate|cms_date_format)}{$thedate=str_replace('-','&minus;',$thedate)}
        <tr class="{cycle values='row1,row2'}">
          <td style="vertical-align:middle;">{if isset($file->thumbnail) && $file->thumbnail}{$file->thumbnail}{else}{$file->iconlink}{/if}</td>
          <td class="clickable" style="vertical-align:middle;">{$file->txtlink}</td>
          <td class="clickable" style="vertical-align:middle;">{$file->mime}</td>
          <td class="clickable" style="vertical-align:middle;padding-right:8px;white-space:pre;">{$file->fileinfo}</td>
          <td class="clickable" style="vertical-align:middle;padding-right:8px;white-space:pre;">{if isset($file->fileowner)}{$file->fileowner}{else}&nbsp;{/if}</td>
          <td class="clickable" style="vertical-align:middle;padding-right:8px;">{$file->filepermissions}</td>
          <td class="clickable" style="vertical-align:middle;padding-right:8px;white-space:pre;text-align:right;">{$file->filesize}</td>
          <td class="clickable" style="vertical-align:middle;padding-right:8px;">{if isset($file->filesizeunit)}{$file->filesizeunit}{else}&nbsp;{/if}</td>
          <td class="clickable" style="vertical-align:middle;padding-right:8px;white-space:pre;">{$thedate}</td>
          <td>{if !isset($file->noCheckbox)}
            <label for="x_{$file->urlname}" style="display: none;">{$mod->Lang('toggle')}</label>
            <input type="checkbox" name="{$actionid}selall[]" id="x_{$file->urlname}" value="{$file->urlname}" title="{$mod->Lang('toggle')}" class="fileselect {implode(' ',$file->type)}"{if isset($file->checked)} checked="checked"{/if} />
          {/if}</td>
        </tr>
        {/foreach}
      </tbody>
      <tfoot>
        <tr>
          <td>&nbsp;</td>
          <td colspan="7">{$countstext}</td>
        </tr>
      </tfoot>
    </table>
  </div>
{if !isset($noform)}
  {*{$actiondropdown}{$targetdir}{$okinput}*}
  {$formend}
</div>
{/if}
