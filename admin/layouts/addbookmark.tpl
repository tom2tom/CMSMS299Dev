<h3 class="pagesubtitle">{_la('addbookmark2')}</h3>
<form action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
  <div class="pageoverflow">
    <label class="pagetext" for="marktitl">{_la('title')}:</label><br>
    <input type="text" id="marktitl" class="pageinput" name="title" maxlength="255" value="{$title}">
  </div>
  <div class="pageoverflow pregap">
    {$t=_la('url')}<label class="pagetext" for="markurl">{$t}:</label> {cms_help key2='help_bookmark_url' title=$t}<br>
    <input type="text" id="markurl" class="pageinput" name="url" size="70" maxlength="255" value="{$url}">
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="addbookmark" class="adminsubmit icon check">{_la('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
</form>
