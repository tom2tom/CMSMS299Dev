<div id="main-nav">
{if !empty($crumbs)}{foreach $crumbs as $one}
{if $one@first}
 <a href="?p={$one->url}">
 <i class="fa fa-home" aria-hidden="true" title="{$mod->Lang('goto_named',{$one->name})}"></i>
 </a>
{else}
 <i class="{$crumbjoiner}"></i> <a href="?p={$one->url}" title="{$mod->Lang('goto_named',{$one->name})}">{$one->name}</a>
{/if}
{/foreach}{/if}
</div>
<div id="main-actions">
<a title="{$mod->Lang('goto_parent')}" href="#TODO"><i class="fa fa-level-up"></i></a>
<a title="{$mod->Lang('newfolder')}" href="#createNewItem"><i class="fa fa-folder-open"></i></a>
<a title="{$mod->Lang('search')}" href="javascript:showSearch('{$rooturl}')"><i class="fa fa-search"></i></a>
<a title="{$mod->Lang('upload')}" href="?p={$rooturl}&amp;{$actionid}upload"><i class="fa fa-upload" aria-hidden="true"></i></a>
</div>

<div style="clear:both;">{*hbox*}

{if !empty($FM_TREEVIEW)}
<div class="file-tree-view" id="file-tree-view">{*boxchild*}
 <div class="tree-title">Browse</div>
TREEVIEW HERE
</div>{*/boxchild*}
{/if}

<div>{*boxchild*}
{$form_start}
<input type="hidden" name="{$actionid}p" value="{$rooturl}" />
<input type="hidden" name="{$actionid}task" id="settask" value="" />
{*<input type="hidden" name="{$actionid}group" value="1" />*}

<table class="pagetable" id="main-table">
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
  <td><input type="checkbox" name="{$actionid}file[]" value="{$one->sel}" /></td>
{/if}
 </tr>
{/foreach}
</tbody>
</table>
</form>
<br />
{$mod->Lang('summary', $filescount, $folderscount, $totalcount)}
<br /><br />
<div class="path footer-links">
<a href="javascript:copyclick()" class="link_button fonticon"><i class="fa fa-files-o"></i> {$mod->Lang('copy')}</a>
<a href="javascript:moveclick()" class="link_button fonticon"><i class="fa fa-location-arrow"></i> {$mod->Lang('move')}</a>
<a href="javascript:compressclick()" class="link_button fonticon"><i class="fa fa-compress"></i> {$mod->Lang('compress')}</a>
<a href="javascript:decompressclick()" class="link_button fonticon"><i class="fa fa-expand"></i> {$mod->Lang('expand')}</a>
<a href="javascript:deleteclick()" class="link_button fonticon"><i class="fa fa-trash"></i> {$mod->Lang('delete')}</a>
<a href="javascript:invert_all()" class="link_button fonticon"><i class="fa fa-adjust"></i> {$mod->Lang('selectother')}</a>
</div>

</div>{*/boxchild*}
</div>{*/hbox*}

{* POPUP DIALOGS *}
<div id="createNewItem" title="{$mod->Lang('newitem')}" style="display:none;">
 <label for="newfile">{$mod->Lang('itemtype')}:</label>
 <input type="radio" name="{$actionid}newfile" id="newfile" value="file" />{$mod->Lang('file')}
 <input type="radio" name="{$actionid}newfile" value="folder" checked="checked" />{$mod->Lang('folder')}
 <br />
 <label for="newfilename">{$mod->Lang('itemname')}:</label>
 <input type="text" name="{$actionid}newfilename" id="newfilename" value="" />
 <br >
 <button type="button" name="{$actionid}submit" class="group-btn" onclick="newfolder('{$parenturl}');return false;">{$mod->Lang('create')}</button>
</div>

<div id="search" title="{$mod->Lang('search')}" style="display:none;">
 <input type="search" name="{$actionid}search" value="" placeholder="{$mod->Lang('searchtip')}">
</div>

<div id="searchResult" title="{$mod->Lang('searchresults')}" style="display:none;">
</div>

