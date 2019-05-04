<div id="content_area">
{include file='module_file_tpl:CMSContentManager;ajax_get_content.tpl'}
</div><!-- end #content_area -->

<div id="filterdialog" style="display:none;" title="{$mod->Lang('title_filterpages')}">
  {form_start action='defaultadmin' id='filter_form'}
    <input type="hidden" name="{$actionid}setoptions" value="1" />
    <div class="colbox">
    <div class="rowbox flow">
      <label class="boxchild" for="pcount">{$mod->Lang('prompt_pagelimit')}:</label>
      <select class="boxchild" name="{$actionid}pagelimit" id="pcount">
        {html_options options=$pagelimits selected=$pagelimit}
      </select>
    </div>
{if $can_manage_content}
    {if $filter}{$type=$filter->type}{$expr=$filter->expr}{else}{$type=''}{$expr=''}{/if}
    <div class="rowbox flow">
      <label class="boxchild" for="filter_type">{$mod->Lang('prompt_filter_type')}:</label>
      <select class="boxchild" name="{$actionid}filter_type" id="filter_type">
        {html_options options=$opts selected=$type}
      </select>
    </div>
    {if $template_list}
    <div class="rowbox flow filter_fld" id="filter_template">
      <label class="boxchild" for="ftpl">{$mod->Lang('prompt_template')}:</label>
      <select class="boxchild" name="{$actionid}filter_template" id="ftpl">
        {html_options options=$template_list selected=$expr}
      </select>
    </div>
    {/if}
    {if $user_list}
    <div class="rowbox flow filter_fld" id="filter_owner">
      <label class="boxchild" for="fown">{$mod->Lang('prompt_owner')}:</label>
      <select class="boxchild" name="{$actionid}filter_owner" id="fown">
        {html_options options=$user_list selected=$expr}
      </select>
    </div>
    <div class="rowbox flow filter_fld" id="filter_editor">
      <label class="boxchild" for="ffld">{$mod->Lang('prompt_editor')}:</label>
      <select class="boxchild" name="{$actionid}filter_editor" id="ffld">
        {html_options options=$user_list selected=$expr}
      </select>
    </div>
    {/if}
{/if}
  </div>{* colbox *}
  </form>
</div>{* #filterdialog *}
