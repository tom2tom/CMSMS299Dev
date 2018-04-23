<div class="pageinfo">{$mod->Lang('info_edittemplate_templates_tab')}</div>
{if !isset($all_templates)}
<div class="pagewarn">{$mod->Lang('warning_edittemplate_notemplates')}</div>
{else} {$tmpl=$design->get_templates()}
<div id="template_sel">
  <div class="draggable-area">
    <fieldset>
      <legend>{$mod->Lang('available_templates')}</legend>
      <div id="available-templates">
        <ul class="sortable-templates sortable-list available-items available-templates">
          {foreach $all_templates as $tpl} {if !$tmpl || !in_array($tpl->get_id(),$tmpl)}
          <li class="ui-state-default" data-cmsms-item-id="{$tpl->get_id()}" tabindex="0">
            <span>{$tpl->get_name()}</span>
            <input class="hidden" type="checkbox" name="{$actionid}assoc_tpl[]" value="{$tpl->get_id()}" />
          </li>
          {/if} {/foreach}
        </ul>
      </div>
    </fieldset>
  </div>
    <fieldset>
      <legend>{$mod->Lang('attached_templates')}</legend>
      <div id="selected-templates">
        <ul class="sortable-templates sortable-list selected-templates">
          {if $design->get_templates()|@count == 0}
          <li class="placeholder no-sort">{$mod->Lang('drop_items')}</li>
          {/if} {foreach $all_templates as $tpl}
           {if $tmpl && in_array($tpl->get_id(),$tmpl)}
          <li class="ui-state-default cf sortable-item no-sort" data-cmsms-item-id="{$tpl->get_id()}" tabindex="-1">
            {if $manage_templates}
            <a href="{cms_action_url action=admin_edit_template tpl=$tpl->get_id()}" class="edit_tpl" title="{$mod->Lang('edit_template')}">{$tpl->get_name()}</a>
            <a href="#" title="{$mod->Lang('remove')}" class="ui-icon ui-icon-trash sortable-remove">{$mod->Lang('remove')}</a>
            <input class="hidden" type="checkbox" name="{$actionid}assoc_tpl[]" value="{$tpl->get_id()}" checked="checked" />
            {else}
            <span>{$tpl->get_name()}</span>
            {/if}
          </li>
          {/if} {/foreach}
        </ul>
      </div>
    </fieldset>
</div>
{/if}

