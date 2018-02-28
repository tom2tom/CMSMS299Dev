<div class="pagecontainer">

  {if $group}<h3 class="pagesubtitle">{lang('name')}:&nbsp;{$group}</h3>{/if}

  <form action="{$selfurl}{$urlext}" method="post">

    <input type="hidden" name="group_id" value="{$group_id}" />

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="group">{lang('name')}:</label>
      </p>
      <p class="pageinput">
        <input type="text" name="group" id="group" maxlength="25" value="{$group}" />
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="description">{lang('description')}:</label>
      </p>
      <p class="pageinput">
        <input type="text" name="description" id="description" size="80" maxlength="255" value="{$description}" />
      </p>
    </div>
    {if $group_id != 1 && !$useringroup}
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="active">{lang('active')}:</label>
      </p>
      <input type="hidden" name="active" value="0" />
      <p class="pageinput">
        <input type="checkbox" name="active" id="active"{if $active} checked="checked"{/if} />
      </p>
    </div>
    {else}
    <input type="hidden" name="active" value="{$active}" />
    {/if}
    <div class="bottomsubmits">
      <p class="pageinput">
        <button type="submit" name="editgroup" class="adminsubmit iconcheck">{lang('submit')}</button>
        <button type="submit" name="cancel" class="adminsubmit iconcancel">{lang('cancel')}</button>
      </p>
    </div>

  </form>
</div>
