<div class="pagecontainer">
  <form action="{$smarty.server.PHP_SELF}" method="post" enctype="multipart/form-data">
  <div class="hidden">
    <input type="hidden" name="{$cms_secure_param_name}" value="{$cms_user_key}" />
    <input type="hidden" name="action" value="upload" />
  </div>
  <fieldset>
    <legend>{lang('perform_validation')}</legend>
    <div class="pageoverflow">
    <p>{lang('info_validation')}</p>
    </div>
    <div class="pageoverflow">
    <p class="pagetext">{lang('upload_cksum_file')}</p>
    <p class="pageinput"><input type="file" name="cksumdat" size="30" maxlength="255" /></p>
    </div>
    <div class="bottomsubmits">
      <p class="pageinput">
       <button type="submit" name="upload" class="adminsubmit icon do">{lang('validate')}</button>
      </p>
    </div>
  </fieldset>
  </form>
  <br />
  <form action="{$smarty.server.PHP_SELF}" method="post" enctype="multipart/form-data">
  <div class="hidden">
    <input type="hidden" name="{$cms_secure_param_name}" value="{$cms_user_key}" />
    <input type="hidden" name="action" value="download" />
  </div>
  <fieldset>
    <legend>{lang('download_cksum_file')}</legend>
    <div class="pageoverflow">
    <p>{lang('info_generate_cksum_file')}</p>
    </div>
    <div class="bottomsubmits">
      <p class="pageinput">
        <button type="submit" name="download" class="adminsubmit icon add">{lang('create')}</button>
      </p>
    </div>
  </fieldset>
  </form>
</div>

