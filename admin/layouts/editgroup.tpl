{if $group}<h3 class="pagesubtitle">{_la('name')}:&nbsp;{$group}</h3>{/if}

<form action="{$selfurl}" enctype="multipart/form-data" method="post">
<div class="hidden">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
  <input type="hidden" name="group_id" value="{$group_id}">
</div>
  <div class="pageoverflow">
    <label class="pagetext" for="group">{_la('name')}:</label>
    <div class="pageinput">
      <input type="text" name="group" id="group" maxlength="25" value="{$group}">
    </div>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="description">{_la('description')}:</label>
    <div class="pageinput">
      <input type="text" name="description" id="description" size="80" maxlength="255" value="{$description}">
    </div>
  </div>
  {if $group_id != 1 && !$useringroup}
  <div class="pageoverflow">
    <label class="pagetext" for="active">{_la('active')}:</label>
    <input type="hidden" name="active" value="0">
    <div class="pageinput">
      <input type="checkbox" name="active" id="active"{if $active} checked{/if}>
    </div>
  </div>
  {else}
  <input type="hidden" name="active" value="{$active}">
  {/if}
  <div class="pageinput pregap">
    <button type="submit" name="editgroup" class="adminsubmit icon check">{_la('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
</form>
