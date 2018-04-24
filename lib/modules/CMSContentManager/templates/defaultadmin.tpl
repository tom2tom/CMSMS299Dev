{if $ajax == 0}

<div id="useroptions" title="{$mod->Lang('title_userpageoptions')}">
  {form_start action='defaultadmin' id='myoptions_form'}
    <input type="hidden" name="{$actionid}setoptions" value="1"/>
    <div class="vbox">
    <div class="hbox flow">
      <label class="boxchild">{$mod->Lang('prompt_pagelimit')}:</label>
      <select class="boxchild" name="{$actionid}pagelimit">
        {html_options options=$pagelimits selected=$pagelimit}
      </select>
    </div>
{if $can_manage_content}
    {if $filter}{$type=$filter->type}{$expr=$filter->expr}{else}{$type=''}{$expr=''}{/if}
    <div class="hbox flow">
      <label class="boxchild">{$mod->Lang('prompt_filter_type')}:</label>
      <select class="boxchild" name="{$actionid}filter_type" id="filter_type">
        {html_options options=$opts selected=$type}
      </select>
    </div>
    <div class="hbox flow filter_fld" id="filter_design">
      <label class="boxchild">{$mod->Lang('prompt_design')}:</label>
      <select class="boxchild" name="{$actionid}filter_design">
        {html_options options=$design_list selected=$expr}
      </select>
    </div>
    <div class="hbox flow filter_fld" id="filter_template"></div>
      <label class="boxchild">{$mod->Lang('prompt_template')}:</label>
      <select class="boxchild" name="{$actionid}filter_template">
        {html_options options=$template_list selected=$expr}
      </select>
    </div>
    <div class="hbox flow filter_fld" id="filter_owner">
      <label class="boxchild">{$mod->Lang('prompt_owner')}:</label>
      <select class="boxchild" name="{$actionid}filter_owner">
        {html_options options=$user_list selected=$expr}
      </select>
    </div>
    <div class="hbox flow filter_fld" id="filter_editor">
      <label class="boxchild">{$mod->Lang('prompt_editor')}:</label>
      <select class="boxchild" name="{$actionid}filter_editor">
        {html_options options=$user_list selected=$expr}
      </select>
    </div>
{/if}
  </div>{*vbox*}
  </form>
</div>

{/if}{* ajax *}

<div id="content_area"></div>
