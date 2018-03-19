<form action="{$selfurl}{$urlext}" method="post">
  <div class="pageoverflow">
    <p class="pagetext">{lang('title')}</p>
    <p class="pageinput">
      <input type="text" name="title" maxlength="255" value="{$title}" class="standard" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{lang('url')}</p>
    <p class="pageinput">
      <input type="text" name="url" size="50" maxlength="255" value="{$url}" class="standard" />
    </p>
  </div>
  <div class="bottomsubmits">
    <p class="pageinput">
      <button type="submit" name="addbookmark" class="adminsubmit icon check">{lang('submit'}</button>
      <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel'}</button>
    </p>
  </div>
</form>
