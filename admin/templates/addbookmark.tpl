<form action="{$selfurl}{$urlext}" enctype="multipart/form-data" method="post">
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
  <div class="pageinput pregap">
    <button type="submit" name="addbookmark" class="adminsubmit icon check">{lang('submit'}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel'}</button>
  </div>
</form>
