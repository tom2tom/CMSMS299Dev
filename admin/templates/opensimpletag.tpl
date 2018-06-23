<h3 class="pagesubtitle">{if $name!='-1'} {lang('editusertag')}{else}{lang('addusertag')}{/if}</h3>
<form id="userplugin" action="{$selfurl}{$urlext}" method="post">
<div class="hidden">
  <input type="hidden" name="oldtagname=" value="{$name}" />
  <textarea id="reporter" name="code" style="display:none;"></textarea>
</div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('name')}* <label for="name">{$t}:</label>
      {cms_help key1=tagname_tip title={$t}}
    </p>
    <p class="pageinput">
      <input type="text" id="name" name="tagname" value="{if $name!='-1'}{$name}{/if}" size="50" maxlength="50" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('description')}<label for="description">{$t}:</label>
      {cms_help key1=tagdesc_tip title={$t}}
    </p>
    <p class="pageinput">
      <textarea id="description" name="description" rows="3" cols="80">{$description}</textarea>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('parameters')}<label for="parameters">{$t}:</label>
      {cms_help key1=tagparams_tip title={$t}}
    </p>
    <p class="pageinput">
      <textarea id="parameters" name="parameters" rows="5" cols="80">{$parameters}</textarea>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('license')}<label for="license">{$t}:</label>
      {cms_help key1=taglicense_tip title={$t}}
    </p>
    <p class="pageinput">
      <textarea id="license" name="license" rows="3" cols="80">{$license}</textarea>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('code')}* <label for="Editor">{$t}:</label>
      {cms_help key1=tagcode_tip title={$t}}
    </p>
    <div id="Editor" class="pageinput">{$code}</div>
  </div>
  <div class="pregap">
    <button type="submit" name="submit" id="submitme" class="adminsubmit icon check">{lang('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
    <button type="submit" name="apply" id="applybtn" title="{lang('title_applyusertag')}" class="adminsubmit icon apply">{lang('apply')}</button>
  </div>
</form>
