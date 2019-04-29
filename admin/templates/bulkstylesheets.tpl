<h3>{lang_by_realm('layout',$bulk_op)}</h3>

{if isset($templates)}
<table class="pagetable">
  <thead>
   <tr>
     <th>{lang_by_realm('layout','prompt_id')}</th>
     <th>{lang_by_realm('layout','prompt_name')}</th>
     <th>{lang_by_realm('layout','prompt_modified')}</th>
   </tr>
  </thead>
  <tbody>
  {foreach $templates as $tpl}
    <tr>
      <td>{$tpl->get_id()}</td>
      <td>{$tpl->get_name()}</td>
      <td>{$tpl->get_modified()|cms_date_format}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
{/if}

<form action="{$selfurl}{$urlext}" method="post">
<input type="hidden" name="allparms" value="{$allparms}" />
{if $bulk_op == 'bulk_action_delete_css'}
  <div class="pagewarn">{lang_by_realm('layout','warn_bulk_delete_templates')}</div>
  <br />
  <div class="pageoverflow">
    <p class="pageinput">
      <input id="check1" type="checkbox" name="check1" value="1" />&nbsp;<label for="check1">{lang_by_realm('layout','confirm_bulk_css_1')}</label><br/>
      <input id="check2" type="checkbox" name="check2" value="1" />&nbsp;<label for="check2">{lang_by_realm('layout','confirm_bulk_css_2')}</label>
    </p>
  </div>
{/if}
<div class="pageinput pregap">
  <button type="submit" name="submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
</form>
