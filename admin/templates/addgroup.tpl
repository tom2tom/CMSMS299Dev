<div class="pagewarn">{_ld('admin','warn_addgroup')}</div>
<form action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <div class="pageoverflow">
    <label class="pagetext" for="groupname">{_ld('admin','name')}:</label>
    <div class="pageinput">
      <input type="text" name="group" id="groupname" maxlength="255" value="{$group}" />
    </div>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="description">{_ld('admin','description')}:</label>
    <div class="pageinput">
      <input type="text" name="description" id="description" maxlength="255" size="80" value="{$description}" />
    </div>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="active">{_ld('admin','active')}:</label>
    <input type="hidden" name="active" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="active" id="active" class="pagecheckbox"{if $active} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit " name="addgroup" class="adminsubmit icon check">{_ld('admin','submit')}</button>
    <button type="submit " name="cancel" class="adminsubmit icon cancel">{_ld('admin','cancel')}</button>
  </div>
</form>
