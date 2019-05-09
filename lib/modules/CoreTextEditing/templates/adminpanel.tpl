{if isset($info)}
<div class="pageinfo">{$info}</div><br />
{/if}
{if isset($warning)}
<div class="pagewarn">{$warning}</div><br />
{/if}

{$form_start}
 <fieldset>
 <p class="pagetext">
  {$t=$mod->Lang('ace_url')}<label class="pagetext" for="fld2">* {$t}:</label>
  {cms_help realm=$_module key=settings_acecdn title=$t}
 </p>
 <p class="pageinput">
  <input type="text" id="fld2" name="{$actionid}ace_url" value="{$ace_url}" size="50" maxlength="80" required="required" />
 </p>
 <p class="pagetext">
  {$t=$mod->Lang('ace_theme')}<label class="pagetext" for="fld3">{$t}:</label>
  {cms_help realm=$_module key=ace_helptheme title=$t}
  </p>
 <p class="pageinput">
  <input type="text" id="fld3" name="{$actionid}ace_theme" value="{$ace_theme}" size="20" />
 </p>
 </fieldset>
 <br />
 <fieldset>
 <p class="pagetext">
  {$t=$mod->Lang('codemirror_url')}<label class="pagetext" for="fld4">* {$t}:</label>
  {cms_help realm=$_module key=settings_cmcdn title=$t}
 </p>
 <p class="pageinput">
  <input type="text" id="fld4" name="{$actionid}codemirror_url" value="{$codemirror_url}" size="50" maxlength="80" required="required" />
 </p>

 <p class="pagetext">
  {$t=$mod->Lang('codemirror_theme')}<label class="pagetext" for="fld5">{$t}:</label>
  {cms_help realm=$_module key=codemirror_helptheme title=$t}
 </p>
 <p class="pageinput">
  <input type="text" id="fld5" name="{$actionid}codemirror_theme" value="{$codemirror_theme}" size="20" />
 </p>
 </fieldset>

 <div class="pregap">
  <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply ">{$mod->Lang('apply')}</button>
  <button  type="submit" name="{$actionid}cancel" class="adminsubmit icon undo" formnovalidate>{$mod->Lang('cancel')}</button>
 </div>

</form>
