<div class="pagewarn">{lang('warn_addgroup')}</div>
<form action="{$selfurl}{$urlext}" enctype="multipart/form-data" method="post">
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="groupname">{lang('name')}:</label>
    </p>
    <p class="pageinput">
      <input type="text" name="group" id="groupname" maxlength="255" value="{$group}" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="description">{lang('description')}:</label>
    </p>
    <p class="pageinput">
      <input type="text" name="description" id="description" maxlength="255" size="80" value="{$description}" />
    </p>
  </div>
  <div class="pageoverflow">
    <input type="hidden" name="active" value="0" />
    <p class="pagetext">
      <label for="active">{lang('active')}:</label>
    </p>
    <p class="pageinput">
      <input type="checkbox" name="active" id="active" class="pagecheckbox"{if $active} checked="checked"{/if} />
    </p>
  </div>
  <div class="pageinput pregap">
    <button type="submit " name="addgroup" class="adminsubmit icon check">{lang('submit')}</button>
    <button type="submit " name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
