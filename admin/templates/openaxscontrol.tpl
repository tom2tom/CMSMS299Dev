<h3>{strip}
{if $pmod}
{if $cset.id}
{_ld('controlsets','pagetitle_edit_set')} <em>({$cset.id})</em>
{else}
{_ld('controlsets','pagetitle_add_set')}
{/if}
{else}
{_ld('controlsets','pagetitle_see_set')}
{/if}
{/strip}</h3>
<form action="{$selfurl}" enctype="multipart/form-data" method="post">
<div class="hidden">
  {foreach $extras as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
</div>
<div class="colbox">
 <fieldset style="border-width:0;">
 <div class="rowbox flow">
  {$t=_ld('admin','name')}<div class="boxchild"><label for="set_name" class="required">* {$t}:</label>
  {cms_help 0='controlsets' key='help_set_name' title=$t}</div>
  {if $pmod}
  <input class="boxchild" type="text" size="40" maxlength="80" id="set_name" name="name" value="{$cset.name}" required="required" />
  {else}
  <p class="boxchild" id="set_name">{if $cset.name}{$cset.name}{else}{_ld('admin','none')}{/if}</p>
  {/if}
 </div>
 <div class="rowbox flow">
  {$t=_ld('controlsets','reltop')}<div class="boxchild"><label for="reldir">{$t}:</label>
  {cms_help 0='controlsets' key='help_set_reltop' title=$t}</div>{* TODO support help_set_reltop2 when appropriate *}
  {if $pmod}
  <div class="boxchild">
  <input type="text" size="80" id="reldir" name="reltop" value="{$cset.reltop}" /><br />
  <button class="adminsubmit icon do" id="selectbtn" title="{_ld('controlsets','title_select')}">{_ld('controlsets','select')}</button>
  </div>
  {else}
  <p class="boxchild" id="reldir">{if $cset.reltop}{$cset.reltop}{else}{_ld('admin','none')}{/if}</p>
  {/if}
 </div>
 </fieldset>

 <fieldset>
 <div class="rowbox flow">
  {$t=_ld('controlsets','show_thumbs')}<div class="boxchild"><label for="seethumbs">{$t}:</label>
  {cms_help 0='controlsets' key='help_show_thumbs' title=$t}</div>
  {if $pmod}
  <input class="boxchild" type="checkbox" id="seethumbs" name="show_thumbs" value="1"{if $cset.show_thumbs} checked="checked"{/if} />
  {else}
  <p class="boxchild" id="seethumbs">{if $cset.show_thumbs}{$yes}{else}{$no}{/if}</p>
  {/if}
 </div>
 <div class="rowbox flow">
  {$t=_ld('controlsets','show_hidden')}<div class="boxchild"><label for="seehidden">{$t}:</label>
  {cms_help 0='controlsets' key='help_show_hidden' title=$t}</div>
  {if $pmod}
  <input class="boxchild" type="checkbox" id="seehidden" name="show_hidden" value="1"{if $cset.show_hidden} checked="checked"{/if} />
  {else}
  <p class="boxchild" id="seehidden">{if $cset.show_hidden}{$yes}{else}{$no}{/if}</p>
  {/if}
 </div>
 <div class="rowbox flow">
  {$t=_ld('controlsets','sort_field')}<div class="boxchild"><label for="sortby">{$t}:</label>
  {cms_help 0='controlsets' key='help_sort_field' title=$t}</div>
  {if $pmod}
  <div class="boxchild">
  {$sorts}
  {_ld('controlsets','ascorder')}&nbsp;<input type="checkbox" id="sortup" name="sort_asc" value="1"{if $cset.sort_asc} checked="checked"{/if} />
  </div>
  {else}
  <p class="boxchild" id="sortup">{$sorts}</p>
  {/if}
 </div>
 </fieldset>

 <fieldset>
 <div class="rowbox flow">
  {$t=_ld('controlsets','can_mkfile')}<div class="boxchild"><label for="mkfile">{$t}:</label>
  {cms_help 0='controlsets' key='help_can_mkfile' title=$t}</div>
  {if $pmod}
  <input class="boxchild" type="checkbox" id="mkfile" name="can_mkfile" value="1"{if $cset.can_mkfile} checked="checked"{/if} />
  {else}
  <p class="boxchild" id="mkfile">{if $cset.can_mkfile}{$yes}{else}{$no}{/if}</p>
  {/if}
 </div>
 <div class="rowbox flow">
  {$t=_ld('controlsets','can_mkdir')}<div class="boxchild"><label for="mkdir">{$t}:</label>
  {cms_help 0='controlsets' key='help_can_mkdir' title=$t}</div>
  {if $pmod}
  <input class="boxchild" type="checkbox" id="mkdir" name="can_mkdir" value="1"{if $cset.can_mkdir} checked="checked"{/if} />
  {else}
  <p class="boxchild" id="mkdir">{if $cset.can_mkdir}{$yes}{else}{$no}{/if}</p>
  {/if}
 </div>
 <div class="rowbox flow">
  {$t=_ld('controlsets','can_upload')}<div class="boxchild"><label for="upload">{$t}:</label>
  {cms_help 0='controlsets' key='help_can_upload' title=$t}</div>
  {if $pmod}
  <input class="boxchild" type="checkbox" id="upload" name="can_upload" value="1"{if $cset.can_upload} checked="checked"{/if} />
  {else}
  <p class="boxchild" id="upload">{if $cset.can_upload}{$yes}{else}{$no}{/if}</p>
  {/if}
 </div>
 <div class="rowbox flow">
  {$t=_ld('controlsets','can_delete')}<div class="boxchild"><label for="delete">{$t}:</label>
  {cms_help 0='controlsets' key='help_can_delete' title=$t}</div>
  {if $pmod}
  <input class="boxchild" type="checkbox" id="delete" name="can_delete" value="1"{if $cset.can_delete} checked="checked"{/if} />
  {else}
  <p class="boxchild" id="delete">{if $cset.can_delete}{$yes}{else}{$no}{/if}</p>
  {/if}
 </div>
 </fieldset>

 <fieldset>
 <div class="rowbox flow">
  {$t=_ld('controlsets','match_patterns')}<div class="boxchild"><label for="incpat">{$t}:</label>
  {cms_help 0='controlsets' key='help_match_patterns' title=$t}</div>
  {if $pmod}
  <input class="boxchild" type="text" size="50" id="incpat" name="match_patterns" value="{$incpatns}" />
  {else}
  <p class="boxchild" id="incpat">{$incpatns}</p>
  {/if}
 </div>
 <div class="rowbox flow">
  {$t=_ld('controlsets','exclude_patterns')}<div class="boxchild"><label for="excpat">{$t}:</label>
  {cms_help 0='controlsets' key='help_exclude_patterns' title=$t}</div>
  {if $pmod}
  <input class="boxchild" type="text" size="50" id="excpat" name="exclude_patterns" value="{$excpatns}" />
  {else}
  <p class="boxchild" id="excpat">{$excpatns}</p>
  {/if}
 </div>
 <div class="rowbox flow">
  {$t=_ld('controlsets','file_types')}<div class="boxchild"><label for="filetypes">{$t}:</label>
  {cms_help 0='controlsets' key='help_file_types' title=$t}</div>
  {if $pmod}
  <div class="boxchild">{$types}</div>
  {else}
  <p class="boxchild" id="filetypes">{$types}</p>
  {/if}
 </div>
 </fieldset>

 <fieldset>
 <div class="rowbox flow">
  {$t=_ld('controlsets','match_users')}<div class="boxchild"><label for="incusers">{$t}:</label>
  {cms_help 0='controlsets' key='help_match_users' title=$t}</div>
  {if $pmod}
  <div class="boxchild">{$incusers}</div>
  {else}
  <p class="boxchild" id="incusers">{$incusers}</p>
  {/if}
 </div>
 <div class="rowbox flow">
  {$t=_ld('controlsets','exclude_users')}<div class="boxchild"><label for="excusers">{$t}:</label>
  {cms_help 0='controlsets' key='help_exclude_users' title=$t}</div>
  {if $pmod}
  <div class="boxchild">{$excusers}</div>
  {else}
  <p class="boxchild" id="excusers">{$excusers}</p>
  {/if}
 </div>
 </fieldset>

 <fieldset>
 <div class="rowbox flow">
  {$t=_ld('controlsets','match_groups')}<div class="boxchild"><label for="incgrps">{$t}:</label>
  {cms_help 0='controlsets' key='help_match_groups' title=$t}</div>
  {if $pmod}
  <div class="boxchild">{$incgroups}</div>
  {else}
  <p class="boxchild" id="incgrps">{$incgroups}</p>
  {/if}
 </div>
 <div class="rowbox flow">
  {$t=_ld('controlsets','exclude_groups')}<div class="boxchild"><label for="excgrps">{$t}:</label>
  {cms_help 0='controlsets' key='help_exclude_groups' title=$t}</div>
  {if $pmod}
  <div class="boxchild">{$excgroups}</div>
  {else}
  <p class="boxchild" id="excgrps">{$excgroups}</p>
  {/if}
 </div>
 </fieldset>
</div>{*colbox*}
<div class="pageinput pregap">
{if $pmod}
 <button type="submit" class="adminsubmit icon check" id="submit" name="submit">{_ld('admin','submit')}</button>
 <button type="submit" class="adminsubmit icon cancel" id="cancel" name="cancel" formnovalidate>{_ld('admin','cancel')}</button>
{else}
 <button type="submit" class="adminsubmit icon close" id="close" name="cancel" formnovalidate>{_ld('admin','close')}</button>
{/if}
</div>
</form>

<div id="popup" title="{_ld('controlsets','dialog_title')}" style="display:none;">
 <p class="info">{_ld('controlsets','info_selector')}</p>
 <div id="treecontainer">
  <li>{_ld('controlsets','root_name')}
  {$folders}
  </li>
 </div>
</div>
