<form action="{$selfurl}" enctype="multipart/form-data" method="post">
  <div class="hidden">
   {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
   <input type="hidden" name="bookmark_id" value="{$bookmark_id}">
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{_la('title')}:</p>
    <p class="pageinput">
      <input type="text" name="title" maxlength="255" value="{$title}">
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{_la('url')}:</p>
    <p class="pageinput">
      <input type="text" name="url" size="80" maxlength="255" value="{$url}">
    </p>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="editbookmark" class="adminsubmit icon check">{_la('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
</form>
