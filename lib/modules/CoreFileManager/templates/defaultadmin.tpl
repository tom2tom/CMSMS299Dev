<div class="vbox">
 <div class="hbox expand flow">
  <div id="main-nav" class="boxchild">
{if !empty($crumbs)}{foreach $crumbs as $one}
{if $one@first}
 <a href="{$one->url}">
 <i class="if-home" aria-hidden="true" title="{$mod->Lang('goto_named',{$one->name})}"></i>
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
<a title="{$mod->Lang('title_upload')}" href="javascript:doUpload()"><i class="if-upload" aria-hidden="true"></i></a>
  </div>{*/boxchild*}
 </div>{*/hbox*}
 <div class="hbox flow">
  <div class="boxchild">
   <p class="fm-tree-title">{$mod->Lang('browse')} <a title="{$mod->Lang('search2')}" href="javascript:doSearch(false)"><i class="if-search"></i></a></p>
   {$treeview}
  </div>{*/boxchild*}
  <div class="boxchild">
  {$form_start}
  <div id="display">
  {include file='module_file_tpl:CoreFileManager;getlist.tpl' nocache}
  </div>
  {if count($items) > 0}
  <div class="pregap path footer-links">
   <button type="submit" name="{$actionid}copy" class="adminsubmit fonticon" onclick="doCopy();return false;"><i class="if-docs"></i> {$mod->Lang('copy')}</button>
   <button type="submit" name="{$actionid}move" class="adminsubmit fonticon" onclick="doMove();return false;"><i class="if-move"></i> {$mod->Lang('move')}</button>
   <button type="submit" name="{$actionid}delete" class="adminsubmit fonticon" onclick="doDelete(this);return false;"><i class="if-trash-empty"></i> {$mod->Lang('delete')}</button>
   <button type="button" name="compress" class="adminsubmit fonticon" onclick="doCompress();return false;"><i class="if-resize-small"></i> {$mod->Lang('compress')}</button>
   <button type="submit" name="{$actionid}decompress" class="adminsubmit fonticon" onclick="return any_check();"><i class="if-resize-full"></i> {$mod->Lang('expand')}</button>
   <button type="button" class="adminsubmit fonticon" title="{$mod->Lang('selecttip')}" onclick="invert_all();return false;"><i class="if-switch"></i> {$mod->Lang('selectother')}</button>
  </div>
  {/if}
  </form>
  </div>{*/boxchild*}
 </div>{*/hbox*}
</div>{*vbox*}
<div style="display:none;">{*TRANSIENT ELEMENTS*}
<div id="upload_dlg" title="{$mod->Lang('title_upload')}">
 <div title="{$mod->Lang('tip_upload')}">
 <h4>{$mod->Lang('title_dnd')}</h4>
 {$mod->Lang('alternate')}
 <h4><input type="file" title="{$mod->Lang('select')}" multiple /></h4>
 </div>
</div>
{if count($items) > 0}
<div id="searchbox">
<input type="text" id="searchinput" placeholder="{$mod->Lang('searchfor')} ..." /><i class="if-cancel"></i>
</div>

<div id="link_dlg" title="{$mod->Lang('linktitle')}">
{$form_start}
<input type="hidden" name="{$actionid}linkto" value="1" />
<input type="hidden" name="{$actionid}target" value="" />
<p><label for="tofolder">{$mod->Lang('linkfolder')}:</label><br /><input type="text" id="tofolder" name="{$actionid}tofolder" title="{$mod->Lang('folder_tip2')}" value="" /></p>
<br />
<p><label for="toname">{$mod->Lang('linkname')}:</label><br /><input type="text" id="toname" name="{$actionid}toname" value="" /></p>
</form>
</div>

<div id="chmod_dlg" title="{$mod->Lang('changeperms')}">
  <p><span id="filetitle"></span></p><br />
  {$form_start}
    <input type="hidden" name="{$actionid}file" value="" />
    <table class="compact-table">
      <tr>
        <td></td>
        <td style="text-align:center;">{$mod->Lang('owner')}&nbsp</td>
        <td style="text-align:center;">{$mod->Lang('group')}&nbsp</td>
        <td style="text-align:center;">{$mod->Lang('others')}</td>
      </tr>
      <tr>
        <td>{$mod->Lang('read')}</td>
        <td style="text-align:center;"><input type="checkbox" id="ur" name="{$actionid}ur" value="1" /></td>
        <td style="text-align:center;"><input type="checkbox" id="gr" name="{$actionid}gr" value="1" /></td>
        <td style="text-align:center;"><input type="checkbox" id="or" name="{$actionid}or" value="1" /></td>
      </tr>
      <tr>
        <td>{$mod->Lang('write')}</td>
        <td style="text-align:center;"><input type="checkbox" id="uw" name="{$actionid}uw" value="1" /></td>
        <td style="text-align:center;"><input type="checkbox" id="gw" name="{$actionid}gw" value="1" /></td>
        <td style="text-align:center;"><input type="checkbox" id="ow" name="{$actionid}ow" value="1" /></td>
      </tr>
      <tr>
        <td id="exectitle">{$mod->Lang('exec')}</td>
        <td style="text-align:center;"><input type="checkbox" id="ux" name="{$actionid}ux" value="1" /></td>
        <td style="text-align:center;"><input type="checkbox" id="gx" name="{$actionid}gx" value="1" /></td>
        <td style="text-align:center;"><input type="checkbox" id="ox" name="{$actionid}ox" value="1" /></td>
      </tr>
    </table>
  </form>
</div>

<div id="compress_dlg" title="{$title_compress}">
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
{/if}
</div>{*TRANSIENTS*}
