{if isset($info)}
<div class="pageinfo">{$info}</div><br />
{/if}
{if isset($warning)}
<div class="pagewarn">{$warning}</div><br />
{/if}
{$form_start}{$i = 1}
{foreach $editors as $item}
 <fieldset>
{if isset($item->urlkey)}
 <p class="pagetext">{$i = $i+1}
  {$t=$mod->Lang({$item->urlkey})}<label class="pagetext" for="fld{$i}">* {$t}:</label>
  {cms_help realm=$_module key=$item->urlhelp title=$t}
 </p>
 <p class="pageinput">
  <input type="text" id="fld{$i}" name="{$actionid}{$item->urlkey}" value="{$item->urlval}" size="50" maxlength="80" required="required" />
 </p>
{/if}
{if isset($item->themekey)}
 <p class="pagetext">{$i = $i+1}
  {$t=$mod->Lang({$item->themekey})}<label class="pagetext" for="fld{$i}">{$t}:</label>
  {cms_help realm=$_module key=$item->themehelp title=$t}
 </p>
 <p class="pageinput">
  <input type="text" id="fld{$i}" name="{$actionid}{$item->themekey}" value="{$item->themeval}" size="20" />
 </p>
{/if}
 </fieldset>
{if !$item@last}<br />{/if}
{/foreach}
 <div class="pregap">
  <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply ">{lang('apply')}</button>
  <button  type="submit" name="{$actionid}cancel" class="adminsubmit icon undo" formnovalidate>{lang('cancel')}</button>
 </div>

</form>
