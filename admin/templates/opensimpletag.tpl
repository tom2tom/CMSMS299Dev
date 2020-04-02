<h3 class="pagesubtitle">{if $name!='-1'} {lang('edit_splg')}{else}{lang('add_splg')}{/if}</h3>
<form id="userplugin" action="{$selfurl}" enctype="multipart/form-data" method="post">
<div class="hidden">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <input type="hidden" name="oldtagname" value="{$name}" />
</div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('name')}* <label for="name">{$t}:</label>
      {cms_help realm='tags' key2='help_tagname' title=$t}
    </p>
    <p class="pageinput">
      <input type="text" id="name" name="tagname" value="{if $name!='-1'}{$name}{/if}" size="50" maxlength="50" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('description')}<label for="description">{$t}:</label>
      {cms_help realm='tags' key2='help_tagdesc' title=$t}
    </p>
    <p class="pageinput">
      <textarea id="description" name="description" rows="3" cols="80">{$description}</textarea>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('parameters')}<label for="parameters">{$t}:</label>
      {cms_help realm='tags' key2='help_tagparams' title=$t}
    </p>
    <p class="pageinput">
      <textarea id="parameters" name="parameters" rows="3" cols="80">{$parameters}</textarea>
    </p>
  </div>
  {if $license !== null}{* processing a dB-stored plugin *}
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang_by_realm('tags','license')}<label for="license">{$t}:</label>
      {cms_help realm='tags' key2='help_taglicense' title=$t}
    </p>
    <p class="pageinput">
      <textarea id="license" name="license" rows="5" cols="80">{$license}</textarea>
    </p>
  </div>
  {/if}
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('code')}* <label for="code">{$t}:</label>
      {cms_help realm='tags' key2='help_tagcode' title=$t}
    </p>
    <p class="pageinput">
      <textarea id="code" name="code" rows="10" cols="80">{$code}</textarea>
    </p>
  </div>
  <div class="pregap">
    <button type="submit" name="submit" id="submitme" class="adminsubmit icon check">{lang('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
    <button type="submit" name="apply" id="applybtn" title="{lang('title_apply_splg')}" class="adminsubmit icon apply">{lang('apply')}</button>
  </div>
</form>
