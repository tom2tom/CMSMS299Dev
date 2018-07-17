{if $set->id != -1}
<h3>{lang_by_realm('ctrlsets', 'title_edit_cset')} <em>({$set->id})</em></h3>
{else}
<h3>{lang_by_realm('ctrlsets', 'title_add_cset')}</h3>
{/if}

<form action="{$selfurl}{$urlext}" method="POST">
<input type="hidden" name="setid" value="{$set->id}" />
<input type="hidden" name="can_delete" value="0" />
<input type="hidden" name="can_mkdir" value="0" />
<input type="hidden" name="can_mkfile" value="0" />
<input type="hidden" name="show_hidden" value="0" />
<input type="hidden" name="show_thumbs" value="0" />

<div class="vbox">
 <div class="hbox flow">
  {$t=lang('name')}<div class="boxchild"><label for="setname" class="required">* {$t}:</label>
  {cms_help realm='ctrlsets' key2='help_setname' title=$t}</div>
  <input class="boxchild" type="text" name="name" id="setname" value="{$set->name}" size="30" maxlength="48" required />
 </div>
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'topdir')}<div class="boxchild"><label for="settop">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_topdir' title=$t}</div>
  <input class="boxchild" type="text" name="top_dir" id="settop" value="{$set->reltop}" size="80" maxlength="255" />
 </div>

 <fieldset class="pregap">
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'files_type')}<div class="boxchild"><label for="filetype">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_files_type' title=$t}</div>
  <input class="boxchild" type="text" name="file_types" id="filetype" value="" />
 </div>
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'match_patterns')}<div class="boxchild"><label for="incpat">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_match_patterns' title=$t}</div>
  <input class="boxchild" type="text" name="match_patterns" id="incpat" value=""  size="50" />
 </div>
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'exclude_patterns')}<div class="boxchild"><label for="expat">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_exclude_patterns' title=$t}</div>
  <input class="boxchild" type="text" name="exclude_patterns" id="expat" value=""  size="50" />
 </div>
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'sort_field')}<div class="boxchild"><label for="sortby">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_sort_field' title=$t}</div>
  <input class="boxchild" type="text" name="sort_by" id="sortby" value="" />
 </div>
 </fieldset>

 <fieldset class="pregap">
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'show_thumbs')}<div class="boxchild"><label for="thumbs">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_show_thumbs' title=$t}</div>
  <input class="boxchild" type="checkbox" name="show_thumbs" id="thumbs" value="1"{if $set->show_thumbs} checked="checked"{/if} />
 </div>
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'show_hidden')}<div class="boxchild"><label for="hidden">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_show_hidden' title=$t}</div>
  <input class="boxchild" type="checkbox" name="show_hidden" id="hidden" value="1"{if $set->show_hidden} checked="checked"{/if} />
 </div>
 </fieldset>

 <fieldset class="pregap">
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'can_mkdir')}<div class="boxchild"><label for="mkdir">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_can_mkdir' title=$t}</div>
  <input class="boxchild" type="checkbox" name="can_mkdir" id="mkdir" value="1"{if $set->can_mkdir} checked="checked"{/if} />
 </div>
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'can_mkfile')}<div class="boxchild"><label for="mkfile">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_can_mkfile' title=$t}</div>
  <input class="boxchild" type="checkbox" name="can_mkfile" id="mkfile" value="1"{if $set->can_mkfile} checked="checked"{/if} />
 </div>
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'can_delete')}<div class="boxchild"><label for="delete">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_can_delete' title=$t}</div>
  <input class="boxchild" type="checkbox" name="can_delete" id="delete" value="1"{if $set->can_delete} checked="checked"{/if} />
 </div>
 </fieldset>

 <fieldset class="pregap">
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'match_users')}<div class="boxchild"><label for="incuser">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_match_users' title=$t}</div>
  <input class="boxchild" type="text" name="match_users" id="incuser" value="" />
 </div>
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'exclude_users')}<div class="boxchild"><label for="exuser">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_exclude_users' title=$t}</div>
  <input class="boxchild" type="text" name="exclude_users" id="exuser" value="" />
 </div>
 </fieldset>

 <fieldset class="pregap">
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'match_groups')}<div class="boxchild"><label for="incgrp">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_match_groups' title=$t}</div>
  <input class="boxchild" type="text" name="match_groups" id="incgrp" value="" />
 </div>
 <div class="hbox flow">
  {$t=lang_by_realm('ctrlsets', 'exclude_groups')}<div class="boxchild"><label for="exgrp">{$t}:</label>
  {cms_help realm='ctrlsets' key2='help_exclude_groups' title=$t}</div>
  <input class="boxchild" type="text" name="exclude_groups" id="exgrp" value="" />
 </div>
 </fieldset>

</div>{*vbox*}
<div class="pageinput pregap">
  <button type="submit" name="submit" id="submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="cancel" id="cancel" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>
</div>
</form>
