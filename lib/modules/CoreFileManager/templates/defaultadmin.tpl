<div class="vbox">
 <div class="hbox expand flow">
  <div id="main-nav" class="boxchild">
{if !empty($crumbs)}{foreach $crumbs as $one}
{if $one@first}
 <a href="{$one->url}">
 <i class="if-home-outline" aria-hidden="true" title="{$mod->Lang('goto_named',{$one->name})}"></i>
 </a>
{elseif $one@last}
 <i class="{$crumbjoiner}"></i> {$one->name}
{else}
 <i class="{$crumbjoiner}"></i> <a href="{$one->url}" title="{$mod->Lang('goto_named',{$one->name})}">{$one->name}</a>
{/if}
{/foreach}{/if}
  </div>{*/boxchild*}
  <div id="main-actions" class="boxchild">
{if !empty($crumbs)}
<a title="{$mod->Lang('goto_parent')}" href="{$parent_url}"><i class="if-level-up"></i></a>
{/if}
<a title="{$mod->Lang('newfolder')}" href="javascript:newFolder()"><i class="if-folder-add"></i></a>
<a title="{$mod->Lang('search1')}" href="javascript:doSearch(true)"><i class="if-search"></i></a>
<a title="{$mod->Lang('upload')}" href="javascript:doUpload()"><i class="if-upload" aria-hidden="true"></i></a>
  </div>{*/boxchild*}
 </div>{*/hbox*}
 <div class="hbox flow">
  <div class="boxchild">
   <p class="fm-tree-title">{$mod->Lang('browse')} <a title="{$mod->Lang('search2')}" href="javascript:doSearch(false)"><i class="if-search"></i></a></p>
   {$treeview}
  </div>{*/boxchild*}
  <div class="boxchild">
  {$form_start}
  <table id="main-table" class="pagetable">
  <thead><tr>
   <th class="{ldelim}sss:'fname'{rdelim}"></th>
   <th class="center {ldelim}sss:'fname'{rdelim}">{$mod->Lang('name')}</th>
   <th class="center {ldelim}sss:'fint'{rdelim}">{$mod->Lang('size')}</th>
   <th class="center {ldelim}sss:'fint'{rdelim}">{$mod->Lang('modified')}</th>
{if !$FM_IS_WIN}
   <th class="center">{$mod->Lang('perms')}</th>
{/if}
   <th class="{ldelim}sss:false{rdelim}"></th>
{if !$FM_READONLY}
   <th class="{ldelim}sss:false{rdelim}"><input type="checkbox" id="checkall" onclick="checkall_toggle(this);"></th>
{/if}
  </tr></thead>
  <tbody>
{foreach $items as $one}
 <tr class="{cycle values='row1,row2'}">
  <td class="icon" data-sort="{if $one->dir}.{/if}{$one->icon}"><i class="{$one->icon}"></i></td>
  <td class="filename" data-sort="{if $one->dir}.{/if}{$one->name}"{if $one->is_link} title="{$pointer} {$one->realpath}"{/if}>{if $one->link}{$one->link}{else}{$one->name}{/if}</td>
  <td data-sort="{if $one->dir}0"{else}{$one->rawsize}" title="{$one->rawsize} {$bytename}"{/if}>{$one->size}</td>
  <td data-sort="{$one->rawtime}">{$one->modat}</td>
{if !$FM_IS_WIN}
  <td style="text-align:center;">{$one->perms}</td>
{/if}
  <td>{$one->acts}</td>
{if !$FM_READONLY}
  <td><input type="checkbox" name="{$actionid}sel['{$one->sel}']" value="1" /></td>
{/if}
 </tr>
{/foreach}
  </tbody>
  </table>
  <br />
  {$mod->Lang('summary', $filescount, $folderscount, $totalcount)}
  <br /><br />
  <div class="path footer-links">
   <button type="submit" name="{$actionid}copy" class="adminsubmit fonticon" onclick="return any_check();"><i class="if-docs"></i> {$mod->Lang('copy')}</button>
   <button type="submit" name="{$actionid}move" class="adminsubmit fonticon" onclick="return any_check();"><i class="if-direction"></i> {$mod->Lang('move')}</button>
   <button type="submit" name="{$actionid}delete" class="adminsubmit fonticon" onclick="deleteclick(this);return false;"><i class="if-trash-empty"></i> {$mod->Lang('delete')}</button>
   <button type="button" name="compress" class="adminsubmit fonticon" onclick="compressclick(this);return false;"><i class="if-resize-small"></i> {$mod->Lang('compress')}</button>
   <button type="submit" name="{$actionid}decompress" class="adminsubmit fonticon" onclick="return any_check();"><i class="if-resize-full"></i> {$mod->Lang('expand')}</button>
   <button type="button" class="adminsubmit fonticon" title="{$mod->Lang('selecttip')}" onclick="invert_all();return false;"><i class="if-switch"></i> {$mod->Lang('selectother')}</button>
  </div>
  </form>
  </div>{*/boxchild*}
 </div>{*/hbox*}
</div>{*vbox*}
{*POPUP DIALOGS*}
<div id="create_dlg" title="{$mod->Lang('newitem')}" style="display:none;">
 <label for="newfile">{$mod->Lang('itemtype')}:</label>
 <input type="radio" name="{$actionid}newfile" id="newfile" value="file" />{$mod->Lang('file')}
 <input type="radio" name="{$actionid}newfile" value="folder" checked="checked" />{$mod->Lang('folder')}
 <br />
 <label for="newfilename">{$mod->Lang('itemname')}:</label>
 <input type="text" name="{$actionid}newfilename" id="newfilename" value="" />
 <br />
 <button type="button" name="{$actionid}submit" class="group-btn" onclick="newfolder();">{$mod->Lang('create')}</button>
</div>

<div id="searchbox" style="display:none;">
<input type="text" id="searchinput" placeholder="{$mod->Lang('searchfor')} ..." /><i class="if-cancel"></i>
</div>'

<div id="upload_dlg" title="{$mod->Lang('title_upload')}" style="display:none;">
 <div title="{$mod->Lang('tip_upload')}">
 <h4>{$mod->Lang('title_dnd')}</h4>
 {$mod->Lang('alternate')}
 <h4><input type="file" title="{$mod->Lang('select')}" multiple /></h4>
 </div>
</div>

<div id="compress_dlg" title="{$title_compress}" style="display:none;">
<input type="hidden" name="{$actionid}compress" value="1" />
<p id="namer">
 {$mod->Lang('namecompressed')}:<br /><input type="text" name="{$actionid}archname" value="" />
</p>
{if count($archtypes) > 1}<br />
<p>{$mod->Lang('typecompressed')}:<br />
{foreach $archtypes as $val=>$one}
<label for="{$val}compress">{$one.label}:</label>
<input type="radio" name="{$actionid}archiver" id="{$val}compress" value="{$val}"{if !empty($one.check)} checked="checked"{/if} />{if !$one@last}&nbsp;{/if}
{/foreach}</p>
{else}
<input type="hidden" name="{$actionid}archiver" value="{key($archtypes)}" />
{/if}
</div>
