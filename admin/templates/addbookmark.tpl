<form action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <div class="pageoverflow">
    <p class="pagetext">{_la('title')}</p>
    <p class="pageinput">
      <input type="text" name="title" maxlength="255" value="{$title}" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{_la('url')}</p>
    <p class="pageinput">
      <input type="text" name="url" size="50" maxlength="255" value="{$url}" />
    </p>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="addbookmark" class="adminsubmit icon check">{_la('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
</form>
