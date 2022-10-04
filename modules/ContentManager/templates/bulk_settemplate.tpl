<h3>{_ld($_module,'prompt_bulk_settemplate')}</h3>

<div class="pageoverflow">
  <ul>
   {foreach $displaydata as $rec}
    <li>({$rec.id}) : {$rec.name} <em>({$rec.alias})</em></li>
   {/foreach}
  </ul>
</div>

{form_start}
{foreach $pagelist as $pid}<input type="hidden" name="{$actionid}bulk_content[]" value="{$pid}">
{/foreach}
<div class="pageoverflow">
  <label class="pagetext" for="template_ctl">{_ld($_module,'prompt_template')}:</label>
  <div class="pageinput">
    <select id="template_ctl" name="{$actionid}template">
     {html_options options=$alltemplates selected=$dflt_tpl_id}    </select>
  </div>
</div>

<div class="pageoverflow">
  <input type="hidden" name="{$actionid}showmore" value="0">
  <div class="pageinput">
    <input type="checkbox" id="showmore_ctl" name="{$actionid}showmore" value="1"{if $showmore} checked{/if}>
    &nbsp;<label for="showmore_ctl">{_ld($_module,'prompt_showmore')}</label>
  </div>
</div>

<div class="pageoverflow">
  <p class="pagetext">{_ld($_module,'prompt_confirm_operation')}:</p>
  <div class="pageinput">
    <input type="checkbox" id="confirm1" value="1" name="{$actionid}confirm1">
    &nbsp;<label for="confirm1">{_ld($_module,'prompt_confirm1')}</label>
    <br>
    <input type="checkbox" id="confirm2" value="1" name="{$actionid}confirm2">
    &nbsp;<label for="confirm2">{_ld($_module,'prompt_confirm2')}</label>
  </div>
</div>

<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
</div>
</form>
