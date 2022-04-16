<h3 class="pagesubtitle">{if $name!='-1'} {_la('edit_usrplg')}{else}{_la('add_usrplg')}{/if}</h3>
<form id="userplugin" action="{$selfurl}" enctype="multipart/form-data" method="post">
<div class="hidden">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <input type="hidden" name="oldtagname" value="{$name}" />
</div>
  <div class="pageoverflow">
    {$t=_la('name')}* <label class="pagetext" for="name">{$t}:</label>
    {cms_help 0='tags' key='help_tagname' title=$t}
    <div class="pageinput">
      <input type="text" id="name" name="tagname" value="{if $name!='-1'}{$name}{/if}" size="50" maxlength="50" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('description')}<label class="pagetext" for="description">{$t}:</label>
    {cms_help 0='tags' key='help_tagdesc' title=$t}
    <div class="pageinput">
      <textarea id="description" name="description" rows="3" cols="80">{$description}</textarea>
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('parameters')}<label class="pagetext" for="parameters">{$t}:</label>
    {cms_help 0='tags' key='help_tagparams' title=$t}
    <div class="pageinput">
      <textarea id="parameters" name="parameters" rows="3" cols="80">{$parameters}</textarea>
    </div>
  </div>
  {if $license !== null}{* processing a dB-stored plugin *}
  <div class="pageoverflow">
    {$t=_ld('tags','license')}<label class="pagetext" for="license">{$t}:</label>
    {cms_help 0='tags' key='help_taglicense' title=$t}
    <div class="pageinput">
      <textarea id="license" name="license" rows="5" cols="80">{$license}</textarea>
    </div>
  </div>
  {/if}
  <div class="pageoverflow">
    {$t=_la('code')}* <label class="pagetext" for="code">{$t}:</label>
    {cms_help 0='tags' key='help_tagcode' title=$t}
    <div class="pageinput">
      <textarea id="code" name="code" rows="10" cols="80">{$code}</textarea>
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="submit" id="submitme" class="adminsubmit icon check">{_la('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
    <button type="submit" name="apply" id="applybtn" title="{_la('title_apply_usrplg')}" class="adminsubmit icon apply">{_la('apply')}</button>
  </div>
</form>
