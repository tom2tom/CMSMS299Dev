<h3>{$mod->Lang($bulk_op)}</h3>

{if isset($templates)}
<table class="pagetable">
  <thead>
   <tr>
     <th>{$mod->Lang('prompt_id')}</th>
     <th>{$mod->Lang('prompt_name')}</th>
     <th>{$mod->Lang('prompt_type')}</th>
     <th>{$mod->Lang('prompt_owner')}</th>
     <th>{$mod->Lang('prompt_modified')}</th>
   </tr>
  </thead>
  <tbody>
  {foreach $templates as $tpl}
    <tr>
      <td>{$tpl->get_id()}</td>
      <td>{$tpl->get_name()}</td>
      <td>{$tpl->get_type_id()}</td>
      <td>{$tpl->get_owner_id()}</td>
      <td>{$tpl->get_modified()|date_format:'%x %X'}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
{/if}

{form_start allparms=$allparms}
{if $bulk_op == 'bulk_action_delete'}
  <div class="pagewarn">{$mod->Lang('warn_bulk_delete_templates')}</div>
  <br />
  <div class="pageoverflow">
    <p class="pageinput">
      <input id="check1" type="checkbox" name="{$actionid}check1" value="1" />&nbsp;<label for="check1">{$mod->Lang('confirm_bulk_template_1')}</label><br/>
      <input id="check2" type="checkbox" name="{$actionid}check2" value="1" />&nbsp;<label for="check2">{$mod->Lang('confirm_bulk_template_2')}</label>
    </p>
  </div>
{/if}
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
</div>
</form>
