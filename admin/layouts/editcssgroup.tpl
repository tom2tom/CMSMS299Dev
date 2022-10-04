{if $group.id > 0}
<h3>{_ld('layout','prompt_edit_group')}: {$group.name} <em>({$group.id})</em></h3>
{else}
<h3>{_ld('layout','create_group')}</h3>
{/if}
<form id="edit_group" action="{$selfurl}" enctype="multipart/form-data" method="post">
{foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
<div class="pageoverflow">
  {$t=_ld('layout','prompt_name')}<label class="pagetext" for="grp_name">* {$t}:</label>
  {cms_help 0='layout' key='help_group_name' title=$t}
  <div class="pageinput">
    <input type="text" id="grp_name" name="name" value="{$group.name}" size="40" maxlength="64" placeholder="{_ld('layout','enter_name')}">
  </div>
</div>
<div class="pageoverflow">
  {$t=_ld('layout','prompt_description')}<label class="pagetext" for="description">{$t}:</label>
  {cms_help 0='layout' key='help_group_desc' title=$t}
  <div class="pageinput">
    <textarea id="description" name="description" rows="3" cols="40" style="width:40em;min-height:2em;">{$group.description}</textarea>
  </div>
</div>

<p class="pageinfo postgap">{_ld('layout','info_css_groupdragdrop')}</p>

<div class="pageoverflow">
{include file='groupmembers.tpl'}
</div>

<div class="pageinput pregap">
  <button type="submit" name="dosubmit" class="adminsubmit icon check">{_la('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
</div>
</form>
