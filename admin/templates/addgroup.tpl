<div class="pagecontainer">
  <div class="pagewarn">{lang('warn_addgroup')}</div>
  <form action="{$selfurl}{$urlext}" method="post">

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
    <div class="bottomsubmits">
      <p class="pageinput ">
        <button type="submit " name="addgroup" class="adminsubmit iconcheck">{lang('submit')}</button>
        <button type="submit " name="cancel" class="adminsubmit iconcancel">{lang('cancel')}</button>
      </p>
    </div>

  </form>
</div>
