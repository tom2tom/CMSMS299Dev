{* stylesheets tab for edit template *}
<div class="pageinfo">{$mod->Lang('info_edittemplate_stylesheets_tab')}</div>
{if !isset($all_stylesheets)}
<div class="pagewarn">{$mod->Lang('warning_editdesign_nostylesheets')}</div>
{else} {$cssl = $design->get_stylesheets()}
  <div class="draggable-area">
    <fieldset>
      <legend>{$mod->Lang('available_stylesheets')}</legend>
      <div id="available-stylesheets">
        <ul class="sortable-stylesheets sortable-list available-items available-stylesheets">
          {foreach $all_stylesheets as $css} {if !$cssl or !in_array($css->get_id(),$cssl)}
          <li class="ui-state-default" data-cmsms-item-id="{$css->get_id()}" tabindex="0">
            <span>{$css->get_name()}</span>
            <input class="hidden" type="checkbox" name="{$actionid}assoc_css[]" value="{$css->get_id()}" tabindex="-1" />
          </li>
          {/if} {/foreach}
        </ul>
      </div>
    </fieldset>
  </div>
    <fieldset>
      <legend>{$mod->Lang('attached_stylesheets')}</legend>
      <div id="selected-stylesheets">
        <ul class="sortable-stylesheets sortable-list selected-stylesheets">
          {if $design->get_stylesheets()|count == 0}
          <li class="placeholder">{$mod->Lang('drop_items')}</li>{/if} {foreach $design->get_stylesheets() as $one}
          <li class="ui-state-default cf sortable-item" data-cmsms-item-id="{$one}">
            <a href="{cms_action_url action=admin_edit_css css=$one}" class="edit_css" title="{$mod->Lang('edit_stylesheet')}">{$list_stylesheets.$one}</a>
            <a href="#" title="{$mod->Lang('remove')}" class="ui-icon ui-icon-trash sortable-remove" title="{$mod->Lang('remove')}">{$mod->Lang('remove')}</a>
            <input class="hidden" type="checkbox" name="{$actionid}assoc_css[]" value="{$one}" checked="checked" tabindex="-1" />
          </li>
          {/foreach}
        </ul>
      </div>
    </fieldset>
{/if}