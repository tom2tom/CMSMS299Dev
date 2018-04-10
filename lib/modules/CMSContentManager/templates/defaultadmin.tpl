{if $ajax == 0}

<div id="useroptions" title="{$mod->Lang('title_userpageoptions')}">
  {form_start action='defaultadmin' id='myoptions_form'}
    <div class="pageoverflow">
      <input type="hidden" name="{$actionid}setoptions" value="1"/>
      <label>{$mod->Lang('prompt_pagelimit')}:</label>
      <select name="{$actionid}pagelimit">
        {html_options options=$pagelimits selected=$pagelimit}
      </select>
    </div>
{if $can_manage_content}
    {$type=''}{$expr=''}{$opts=[]}
    {$opts['']=$mod->Lang('none')}
    {$opts['DESIGN_ID']=$mod->Lang('prompt_design')}
    {$opts['TEMPLATE_ID']=$mod->Lang('prompt_template')}
    {$opts['OWNER_UID']=$mod->Lang('prompt_owner')}
    {$opts['EDITOR_UID']=$mod->Lang('prompt_editor')}
    {if $filter}{$type=$filter->type}{$expr=$filter->expr}{/if}
    <div class="pageoverflow">
      <label>{$mod->Lang('prompt_filter_type')}:</label>
      <select name="{$actionid}filter_type" id="filter_type">
        {html_options options=$opts selected=$type}
      </select>
    </div>
    <div class="pageoverflow filter_fld" id="filter_design">
      <label>{$mod->Lang('prompt_design')}:</label>
      <select name="{$actionid}filter_design">
        {html_options options=$design_list selected=$expr}
      </select>
    </div>
    <div class="pageoverflow filter_fld" id="filter_template">
      <label>{$mod->Lang('prompt_template')}:</label>
      <select name="{$actionid}filter_template">
        {html_options options=$template_list selected=$expr}
      </select>
    </div>
    <div class="pageoverflow filter_fld" id="filter_owner">
      <label>{$mod->Lang('prompt_owner')}:</label>
      <select name="{$actionid}filter_owner">
        {html_options options=$user_list selected=$expr}
      </select>
    </div>
    <div class="pageoverflow filter_fld" id="filter_editor">
      <label>{$mod->Lang('prompt_editor')}:</label>
      <select name="{$actionid}filter_editor">
        {html_options options=$user_list selected=$expr}
      </select>
    </div>
{/if}
  </form>
</div>
<div class="clearb"></div>

{/if}{* ajax *}

<div id="content_area"></div>
