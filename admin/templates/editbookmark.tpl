<div class="pagecontainer">
  <form action="{$selfurl}{$urlext}" method="post">
    <input type="hidden" name="bookmark_id" value="{$bookmark_id}" />

    <div class="pageoverflow">
      <p class="pagetext">{lang('title')}:</p>
      <p class="pageinput">
        <input type="text" name="title" maxlength="255" value="{$title}" />
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">{lang('url')}:</p>
      <p class="pageinput">
        <input type="text" name="url" size="80" maxlength="255" value="{$url}" />
      </p>
    </div>
    <div class="bottomsubmits">
      <p class="pageinput">
        <button type="submit" name="editbookmark" class="adminsubmit iconcheck">{lang('submit')}</button>
        <button type="submit" name="cancel" class="adminsubmit iconcancel">{lang('cancel')}</button>
      </p>
    </div>

  </form>
</div>
