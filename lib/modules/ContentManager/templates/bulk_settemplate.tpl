<h3>{$mod->Lang('prompt_bulk_settemplate')}</h3>

<div class="pageoverflow">
  <ul>
   {foreach $displaydata as $rec}
    <li>({$rec.id}) : {$rec.name} <em>({$rec.alias})</em></li>
   {/foreach}
  </ul>
</div>

{form_start}
{foreach $pagelist as $pid}<input type="hidden" name="{$actionid}bulk_content[]" value="{$pid}" />
{/foreach}
<div class="pageoverflow">
  <p class="pagetext">
  <label for="template_ctl">{$mod->Lang('prompt_template')}:</label>
  </p>
  <p class="pageinput"><select id="template_ctl" name="{$actionid}template">
  {html_options options=$alltemplates selected=$dflt_tpl_id}
  </select></p>
</div>

<div class="pageoverflow">
  <input type="hidden" name="{$actionid}showmore" value="0" />
  <p class="pageinput">
    <input type="checkbox" id="showmore_ctl" name="{$actionid}showmore" value="1"{if $showmore} checked="checked"{/if} />
    &nbsp;<label for="showmore_ctl">{$mod->Lang('prompt_showmore')}</label>
  </p>
</div>

<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('prompt_confirm_operation')}:</p>
  <p class="pageinput">
    <input type="checkbox" id="confirm1" value="1" name="{$actionid}confirm1" />
    &nbsp;<label for="confirm1">{$mod->Lang('prompt_confirm1')}</label>
    <br />
    <input type="checkbox" id="confirm2" value="1" name="{$actionid}confirm2" />
    &nbsp;<label for="confirm2">{$mod->Lang('prompt_confirm2')}</label>
   </p>
</div>

<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
</div>
</form>
