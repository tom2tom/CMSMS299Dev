<h3>{_ld($_module,'prompt_bulk_delete_content')}</h3>
<h4>{_ld($_module,'prompt_bulk_delete_content2')}</h4>

{form_start}
{foreach $pagelist as $pid}<input type="hidden" name="{$actionid}bulk_content[]" value="{$pid}" />
{/foreach}

<div class="pageoverflow">
  <ul>
    {foreach $displaydata as $rec}
    <li>({$rec.id}) : {$rec.name} <em>({$rec.alias})</em></li>
    {/foreach}
  </ul>
</div>

<div class="pageoverflow">
  <p class="pagetext">{_ld($_module,'prompt_confirm_operation')}:</p>
  <p class="pageinput">
    <input type="checkbox" id="confirm1" value="1" name="{$actionid}confirm1" />
    &nbsp; <label for="confirm1">{_ld($_module,'prompt_confirm1')}</label>
    <br/>
    <input type="checkbox" id="confirm2" value="1" name="{$actionid}confirm2" />
    &nbsp; <label for="confirm2">{_ld($_module,'prompt_confirm2')}</label>
  </p>
</div>
<div  class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
</div>
</form>
