<div class="pagewarn">{_la('warn_addgroup')}</div>
<form action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
  <div class="pageoverflow">
    <label class="pagetext" for="groupname">{_la('name')}:</label>
    <div class="pageinput">
      <input type="text" name="group" id="groupname" maxlength="255" value="{$group}">
    </div>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="description">{_la('description')}:</label>
    <div class="pageinput">
      <input type="text" name="description" id="description" maxlength="255" size="80" value="{$description}">
    </div>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="active">{_la('active')}:</label>
    <input type="hidden" name="active" value="0">
    <div class="pageinput">
      <input type="checkbox" name="active" id="active" class="pagecheckbox"{if $active} checked{/if}>
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit " name="addgroup" class="adminsubmit icon check">{_la('submit')}</button>
    <button type="submit " name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
</form>
