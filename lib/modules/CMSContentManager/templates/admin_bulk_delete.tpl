<h3>{$mod->Lang('prompt_bulk_delete_content')}:</h3>
<h4>{$mod->Lang('prompt_bulk_delete_content2')}:</h4>

{form_start multicontent=$multicontent}
<div class="pageoverflow">
  <ul>
    {foreach $displaydata as $rec}
    <li>({$rec.id}) : {$rec.name} <em>({$rec.alias})</em></li>
    {/foreach}
  </ul>
</div>

<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('prompt_confirm_operation')}:</p>
  <p class="pageinput">
    <input type="checkbox" id="confirm1" value="1" name="{$actionid}confirm1" />
    &nbsp; <label for="confirm1">{$mod->Lang('prompt_confirm1')}</label>
    <br/>
    <input type="checkbox" id="confirm2" value="1" name="{$actionid}confirm2" />
    &nbsp; <label for="confirm2">{$mod->Lang('prompt_confirm2')}</label>
  </p>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" role="button" name="{$actionid}submit" value="{$mod->Lang('submit')}" class="pagebutton ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary">
     <span class="ui-button-icon-primary ui-icon ui-icon-circle-check"></span>
     <span class="ui-button-text">{$mod->Lang('submit')}</span>
    </button>
    <button type="submit" role="button" name="{$actionid}cancel" value="{$mod->Lang('cancel')}" class="pagebutton ui-button ui-widget ui-corner-all ui-button-text-icon-primary">
     <span class="ui-button-icon-primary ui-icon ui-icon-circle-close"></span>
     <span class="ui-button-text">{$mod->Lang('cancel')}</span>
    </button>
  </p>
</div>
{form_end}
