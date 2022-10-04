{if !isset($ajax)}

{function filebtn}
{strip}{if isset($text) && $text}
  {if !empty($icon)}{$addclass=' icon '|cat:$icon}{else}{$addclass=''}{/if}
  {if !isset($title) || $title == ''}{$title=$text}{/if}
{/if}{/strip}
  <button type="submit" name="{$iname}" id="{$id}" title="{$title|default:''}" class="filebtn adminsubmit{$addclass}">{$text}</button>
{/function}

<div id="popup" style="display: none;">
  <div id="popup_contents" style="min-width: 500px; max-height: 600px;"></div>
</div>
<!-- TODO custom icons for buttons newdir view rename delete move copy unpack thumbnail size rotate -->
<div>
  {$formstart}
  {$hiddenpath}
  <div class="postgap">
    {filebtn id='btn_newdir' iname="{$actionid}newdir" icon='plus' text=_ld($_module,'newdir') title=_ld($_module,'title_newdir')}
    {filebtn id='btn_view' iname="{$actionid}view" icon='' text=_ld($_module,'view') title=_ld($_module,'title_view')}
    {filebtn id='btn_rename' iname="{$actionid}rename" icon='' text=_ld($_module,'rename') title=_ld($_module,'title_rename')}
    {filebtn id='btn_delete' iname="{$actionid}delete" icon='delete' text=_ld($_module,'delete') title=_ld($_module,'title_delete')}
    {filebtn id='btn_move' iname="{$actionid}move" icon='' text=_ld($_module,'move') title=_ld($_module,'title_move')}
    {filebtn id='btn_copy' iname="{$actionid}copy" icon='' text=_ld($_module,'copy') title=_ld($_module,'title_copy')}
    {filebtn id='btn_unpack' iname="{$actionid}unpack" icon='' text=_ld($_module,'unpack') title=_ld($_module,'title_unpack')}
    {filebtn id='btn_thumb' iname="{$actionid}thumb" icon='' text=_ld($_module,'thumbnail') title=_ld($_module,'title_thumbnail')}
    {filebtn id='btn_resizecrop' iname="{$actionid}resizecrop" icon='' text=_ld($_module,'resizecrop') title=_ld($_module,'title_resizecrop')}
    {filebtn id='btn_rotate' iname="{$actionid}rotate" icon='' text=_ld($_module,'rotate') title=_ld($_module,'title_rotate')}
  </div>

  <div id="filesarea">
{/if} {* !isset($ajax) *}
    <table class="pagetable scrollable" style="width:100%;">
      <thead>
        <tr>
          <th class="pageicon"></th>
          <th>{$filenametext}</th>
          <th class="pageicon">{_ld($_module,'mimetype')}</th>
          <th class="pageicon">{$fileinfotext}</th>
          <th class="pageicon" title="{_ld($_module,'title_col_fileowner')}">{$fileownertext}</th>
          <th class="pageicon" title="{_ld($_module,'title_col_fileperms')}">{$filepermstext}</th>
          <th class="pageicon" title="{_ld($_module,'title_col_filesize')}" style="text-align:right;">{$filesizetext}</th>
          <th class="pageicon"></th>
          <th class="pageicon" title="{_ld($_module,'title_col_filedate')}">{$filedatetext}</th>
          <th class="pageicon">
            <input type="checkbox" name="tagall" value="tagall" id="tagall" title="{_ld($_module,'title_tagall')}">
          </th>
        </tr>
      </thead>
      <tbody>
        {foreach $files as $file} {$thedate=str_replace(' ','&nbsp;',$file->filedate|cms_date_format:'timed')}{$thedate=str_replace('-','&minus;',$thedate)}
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
            <label for="x_{$file->urlname}" style="display: none;">{_ld($_module,'toggle')}</label>
            <input type="checkbox" name="{$actionid}sel[]" id="x_{$file->urlname}" value="{$file->urlname}" title="{_ld($_module,'toggle')}" class="fileselect {implode(' ',$file->type)}"{if isset($file->checked)} checked{/if}>
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
{if !isset($ajax)}
  </div>
  {*{$actiondropdown}{$targetdir}{$okinput}*}
  </form>
</div>
{/if}
