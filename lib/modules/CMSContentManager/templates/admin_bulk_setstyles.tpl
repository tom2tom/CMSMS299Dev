<h3>{$mod->Lang('prompt_bulk_setstyles')}</h3>
<div class="pageoverflow">
  <ul>
   {foreach $displaydata as $rec}
    <li>({$rec.id}) : {$rec.name} <em>({$rec.alias})</em></li>
   {/foreach}
  </ul>
</div>
<p class="pageinfo">{$mod->Lang('info_styles')}</p>

{form_start}
{foreach $pagelist as $pid}<input type="hidden" name="{$actionid}bulk_content[]" value="{$pid}" />
{/foreach}
{include  file='module_file_tpl:CMSContentManager;page_stylesheets.tpl'}
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
</div>
</form>
