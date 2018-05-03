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
<a title="{$mod->Lang('newfolder')}" href="javascript:createNewItem()"><i class="if-folder-add"></i></a>
<a title="{$mod->Lang('search')}" href="javascript:doSearch()"><i class="if-search"></i></a>
<a title="{$mod->Lang('upload')}" href="javascript:doUpload()"><i class="if-upload" aria-hidden="true"></i></a>
  </div>{*/boxchild*}
 </div>{*/hbox*}
 <div class="hbox flow">
  <div class="boxchild">
   <p class="fm-tree-title">{$mod->Lang('browse')}</p>
   {$treeview}
  </div>{*/boxchild*}
  <div class="boxchild">
  {$form_start}
  <table id="main-table" class="pagetable">
  <thead><tr>
   <th class="center">{$mod->Lang('name')}</th>
   <th class="center">{$mod->Lang('size')}</th>
   <th class="center">{$mod->Lang('modified')}</th>
{if !$FM_IS_WIN}
   <th class="center">{$mod->Lang('perms')}</th>
   <th class="center">{$mod->Lang('owner')}</th>
{/if}
   <th></th>
{if !$FM_READONLY}
   <th><input type="checkbox" id="checkall" onclick="checkall_toggle(this);"></th>
{/if}
  </tr></thead>
  <tbody>
{foreach $items as $one}
 <tr class="{cycle values='row1,row2'}">
  <td class="filename"{if $one->is_link} title="{$pointer} {$one->realpath}"{/if}><i class="{$one->icon}"></i> {if $one->link}{$one->link}{else}{$one->name}{/if}</td>
  <td{if isset($one->rawsize)} title="{$one->rawsize} {$bytename}"{/if}>{$one->size}</td>
  <td>{$one->modat}</td>
{if !$FM_IS_WIN}
  <td style="text-align:center;">{$one->perms}</td>
  <td>{$one->owner}</td>
{/if}
  <td>{$one->acts}</td>
{if !$FM_READONLY}
  <td><input type="checkbox" name="{$actionid}sel[{$one->sel}]" value="1" /></td>
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
   <button type="submit" name="{$actionid}compress" class="adminsubmit fonticon" onclick="compressclick(this);return false;"><i class="if-resize-small"></i> {$mod->Lang('compress')}</button>
   <button type="submit" name="{$actionid}decompress" class="adminsubmit fonticon" onclick="return any_check();"><i class="if-resize-full"></i> {$mod->Lang('expand')}</button>
   <button type="submit" name="{$actionid}delete" class="adminsubmit fonticon" onclick="deleteclick(this);return false;"><i class="if-trash-empty"></i> {$mod->Lang('delete')}</button>
   <button type="button" class="adminsubmit fonticon" onclick="invert_all();return false;"><i class="if-adjust"></i> {$mod->Lang('selectother')}</button>
  </div>
  </form>
  </div>{*/boxchild*}
 </div>{*/hbox*}
</div>{*vbox*}
{*POPUP DIALOGS*}
<div id="createNewItem" title="{$mod->Lang('newitem')}" style="display:none;">
 <label for="newfile">{$mod->Lang('itemtype')}:</label>
 <input type="radio" name="{$actionid}newfile" id="newfile" value="file" />{$mod->Lang('file')}
 <input type="radio" name="{$actionid}newfile" value="folder" checked="checked" />{$mod->Lang('folder')}
 <br />
 <label for="newfilename">{$mod->Lang('itemname')}:</label>
 <input type="text" name="{$actionid}newfilename" id="newfilename" value="" />
 <br />
 <button type="button" name="{$actionid}submit" class="group-btn" onclick="newfolder();">{$mod->Lang('create')}</button>
</div>

<div id="search" title="{$mod->Lang('search')}" style="display:none;">
 <input type="search" name="{$actionid}search" value="" placeholder="{$mod->Lang('searchtip')}">
</div>

<div id="searchResult" title="{$mod->Lang('searchresults')}" style="display:none;">
</div>

<div id="upload_dlg" title="{$mod->Lang('upload')}" style="display:none;">
 <h4>Drag file(s) and drop anywhere here<h4/>
 or
 <h4>Select file(s)</h4>
 <input type="file" title="Click to add Files">
</div>
