{if $ajax == 0}

<div id="useroptions" title="{$mod->Lang('title_userpageoptions')}">
  {form_start action='defaultadmin' id='myoptions_form'}
    <div class="c_full cf">
      <input type="hidden" name="{$actionid}setoptions" value="1"/>
      <label class="grid_4">{$mod->Lang('prompt_pagelimit')}:</label>
      <select name="{$actionid}pagelimit" class="grid_7">
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
    <div class="c_full cf">
      <label class="grid_4">{$mod->Lang('prompt_filter_type')}:</label>
      <select name="{$actionid}filter_type" class="grid_7" id="filter_type">
        {html_options options=$opts selected=$type}
      </select>
    </div>
    <div class="c_full cf filter_fld" id="filter_design">
      <label class="grid_4">{$mod->Lang('prompt_design')}:</label>
      <select name="{$actionid}filter_design" class="grid_7">
        {html_options options=$design_list selected=$expr}
      </select>
    </div>
    <div class="c_full cf filter_fld" id="filter_template">
      <label class="grid_4">{$mod->Lang('prompt_template')}:</label>
      <select name="{$actionid}filter_template" class="grid_7">
        {html_options options=$template_list selected=$expr}
      </select>
    </div>
    <div class="c_full cf filter_fld" id="filter_owner">
      <label class="grid_4">{$mod->Lang('prompt_owner')}:</label>
      <select name="{$actionid}filter_owner" class="grid_7">
        {html_options options=$user_list selected=$expr}
      </select>
    </div>
    <div class="c_full cf filter_fld" id="filter_editor">
      <label class="grid_4">{$mod->Lang('prompt_editor')}:</label>
      <select name="{$actionid}filter_editor" class="grid_7">
        {html_options options=$user_list selected=$expr}
      </select>
    </div>
{/if}
  </form>
</div>
<div class="clearb"></div>

{/if}{* ajax *}

<div id="content_area"></div>
