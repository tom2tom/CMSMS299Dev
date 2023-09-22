<form action="{$smarty.server.PHP_SELF}" method="post" enctype="multipart/form-data">
<div class="hidden">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
  <input type="hidden" name="action" value="upload">
</div>
<fieldset>
  <legend>{_la('perform_validation')}</legend>
  <div class="pageoverflow">
  <p>{_la('info_validation')}</p>
  </div>
  <div class="pageoverflow">
  <p class="pagetext">{_la('upload_cksum_file')}</p>
  <p class="pageinput"><input type="file" name="cksumdat" size="30" maxlength="255"></p>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="upload" class="adminsubmit icon do">{_la('validate')}</button>
  </div>
</fieldset>
</form>
<br>
<form action="{$smarty.server.PHP_SELF}" method="post" enctype="multipart/form-data">
<div class="hidden">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
  <input type="hidden" name="action" value="download">
</div>
<fieldset>
  <legend>{_la('download_cksum_file')}</legend>
  <div class="pageoverflow">
  <p>{_la('info_generate_cksum_file')}</p>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="download" class="adminsubmit icon add">{_la('create')}</button>
  </div>
</fieldset>
</form>