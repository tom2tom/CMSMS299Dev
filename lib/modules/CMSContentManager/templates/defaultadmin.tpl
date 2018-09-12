<div id="content_area"></div>

{if $ajax == 0}

<div id="filterdialog" style="display:none;" title="{$mod->Lang('title_filterpages')}">
  {form_start action='defaultadmin' id='myoptions_form'}
    <input type="hidden" name="{$actionid}setoptions" value="1" />
    <div class="vbox">
    <div class="hbox flow">
      <label class="boxchild" for="pcount">{$mod->Lang('prompt_pagelimit')}:</label>
      <select class="boxchild" name="{$actionid}pagelimit" id="pcount">
        {html_options options=$pagelimits selected=$pagelimit}
      </select>
    </div>
{if $can_manage_content}
    {if $filter}{$type=$filter->type}{$expr=$filter->expr}{else}{$type=''}{$expr=''}{/if}
    <div class="hbox flow">
      <label class="boxchild" for="filter_type">{$mod->Lang('prompt_filter_type')}:</label>
      <select class="boxchild" name="{$actionid}filter_type" id="filter_type">
        {html_options options=$opts selected=$type}
      </select>
    </div>
    <div class="hbox flow filter_fld" id="filter_design">
      <label class="boxchild" for="fdes">{$mod->Lang('prompt_design')}:</label>
      <select class="boxchild" name="{$actionid}filter_design" id="fdes">
        {html_options options=$design_list selected=$expr}
      </select>
    </div>
    <div class="hbox flow filter_fld" id="filter_template">
      <label class="boxchild" for="ftpl">{$mod->Lang('prompt_template')}:</label>
      <select class="boxchild" name="{$actionid}filter_template" id="ftpl">
        {html_options options=$template_list selected=$expr}
      </select>
    </div>
    <div class="hbox flow filter_fld" id="filter_owner">
      <label class="boxchild" for="fown">{$mod->Lang('prompt_owner')}:</label>
      <select class="boxchild" name="{$actionid}filter_owner" id="fown">
        {html_options options=$user_list selected=$expr}
      </select>
    </div>
    <div class="hbox flow filter_fld" id="filter_editor">
      <label class="boxchild" for="ffld">{$mod->Lang('prompt_editor')}:</label>
      <select class="boxchild" name="{$actionid}filter_editor" id="ffld">
        {html_options options=$user_list selected=$expr}
      </select>
    </div>
{/if}
  </div>{* vbox *}
  </form>
</div>{* #filterdialog *}

{/if}{* ajax *}
