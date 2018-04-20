{if $ajax == 0}

<div id="useroptions" title="{$mod->Lang('title_userpageoptions')}">
  {form_start action='defaultadmin' id='myoptions_form'}
    <input type="hidden" name="{$actionid}setoptions" value="1"/>
    <table class="responsive">
    <tbody>
    <tr>
      <td><label>{$mod->Lang('prompt_pagelimit')}:</label></td>
      <td><select name="{$actionid}pagelimit">
        {html_options options=$pagelimits selected=$pagelimit}
      </select></td>
    </tr>
{if $can_manage_content}
    {$type=''}{$expr=''}{$opts=[]}
    {$opts['']=$mod->Lang('none')}
    {$opts['DESIGN_ID']=$mod->Lang('prompt_design')}
    {$opts['TEMPLATE_ID']=$mod->Lang('prompt_template')}
    {$opts['OWNER_UID']=$mod->Lang('prompt_owner')}
    {$opts['EDITOR_UID']=$mod->Lang('prompt_editor')}
    {if $filter}{$type=$filter->type}{$expr=$filter->expr}{/if}
    <tr>
      <td><label>{$mod->Lang('prompt_filter_type')}:</label></td>
      <td><select name="{$actionid}filter_type" id="filter_type">
        {html_options options=$opts selected=$type}
      </select></td>
    </tr>
    <tr class="filter_fld" id="filter_design">
      <td><label>{$mod->Lang('prompt_design')}:</label></td>
      <td><select name="{$actionid}filter_design">
        {html_options options=$design_list selected=$expr}
      </select></td>
    </tr>
    <tr class="filter_fld" id="filter_template">
      <td><label>{$mod->Lang('prompt_template')}:</label></td>
      <td><select name="{$actionid}filter_template">
        {html_options options=$template_list selected=$expr}
      </select></td>
    </tr>
    <tr class="filter_fld" id="filter_owner">
      <td><label>{$mod->Lang('prompt_owner')}:</label></td>
      <td><select name="{$actionid}filter_owner">
        {html_options options=$user_list selected=$expr}
      </select></td>
    </tr>
    <tr class="filter_fld" id="filter_editor">
      <td><label>{$mod->Lang('prompt_editor')}:</label></td>
      <td><select name="{$actionid}filter_editor">
        {html_options options=$user_list selected=$expr}
      </select></td>
    </tr>
  </tbody>
  </table>
{/if}
  </form>
</div>

{/if}{* ajax *}

<div id="content_area"></div>
