{if isset($info)}
<div class="pageinfo">{$info}</div><br />
{/if}
{if isset($warning)}
<div class="pagewarn">{$warning}</div><br />
{/if}

{if !empty($items)}
{function get_editor_help_icon}
{strip}
{/strip}
{/function}
{$form_start}

<label class="pagetext" for="">{$mod->Lang('optionTODO')}:</label>
{*get_editor_help_icon('TODOtype')*}
<p class="pageinput">
</p>

<label class="pagetext" for="">{$mod->Lang('selectTODO')}:</label>
{*get_editor_help_icon('TODOtype')*}
<div class="pageinput">
{foreach $items as $one}
   <label for="huey">Huey</label>
   <input type="radio" id="huey" name="{$actionid}drone" checked /> <br />
{/foreach}
</div>
<div class="pregap">
  <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply ">{$mod->Lang('apply')}</button>
  <button  type="submit" name="{$actionid}cancel" class="adminsubmit icon undo">{$mod->Lang('cancel')}</button>
</div>
</form>
{else}{*no items*}
<p class="pageinfo">No WYSIWYG editor is installed</p>
{/if}
