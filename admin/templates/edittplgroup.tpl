{if $group.id > 0}
<h3>{lang_by_realm('layout','prompt_edit_group')}: {$group.name} <em>({$group.id})</em></h3>
{else}
<h3>{lang_by_realm('layout','create_group')}</h3>
{/if}

<form id="edit_tplgroup" action="{$selfurl}" enctype="multipart/form-data" method="post">
{foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />{/foreach}
<div class="pageoverflow">
  <p class="pagetext">
    {$t=lang_by_realm('layout','prompt_name')}<label for="grp_name">* {$t}:</label>
    {cms_help realm='layout' key2='help_group_name' title=$t}
  </p>
  <p class="pageinput">
    <input type="text" id="grp_name" name="name" value="{$group.name}" size="40" maxlength="64" placeholder="{lang_by_realm('layout','enter_name')}" />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    {$t=lang_by_realm('layout','prompt_description')}<label for="description">{$t}:</label>
    {cms_help realm='layout' key2='help_group_desc' title=$t}
  </p>
  <p class="pageinput">
    <textarea id="description" name="description" rows="3" cols="40" style="width:40em;min-height:2em;">{$group.description}</textarea>
  </p>
</div>

<p class="pageinfo postgap">{lang_by_realm('layout','info_tpl_groupdragdrop')}</p>

<div class="pageoverflow">
{include file='groupmembers.tpl'}
</div>

<div class="pageinput pregap">
  <button type="submit" name="dosubmit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
</form>
