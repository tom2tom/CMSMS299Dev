{tab_header name='templates' label=$mod->Lang('prompt_templates')}
{if $manage_templates}
  {tab_header name='groups' label=$mod->Lang('prompt_tpl_groups')}
  {tab_header name='types' label=$mod->Lang('prompt_templatetypes')}
{/if}

{tab_start name='templates'}
<div id="template_area"></div>

<div id="filterdialog" style="display:none;" title="{$mod->Lang('tpl_filter')|escape:'javascript'}">
  {form_start action='defaultadmin' id='filterdialog_form' __activetab='templates'}
  <input type="hidden" id="submit_filter_tpl" name="{$actionid}submit_filter_tpl" value="1" />
  <div class="colbox">
  <div class="rowbox flow">
    <label class="boxchild" for="filter_tpl">{$mod->Lang('prompt_options')}:</label>
    <select class="boxchild" id="filter_tpl" name="{$actionid}filter_tpl" title="{$mod->Lang('title_filter')}">
      {html_options options=$filter_tpl_options selected=$tpl_filter.tpl}
    </select>
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="filter_sortby">{$mod->Lang('prompt_sortby')}:</label>
    <select class="boxchild" id="filter_sortby" name="{$actionid}filter_sortby" title="{$mod->Lang('title_sortby')}">
      <option value="name"{if $tpl_filter.sortby == 'name'} selected="selected"{/if}>{$mod->Lang('name')}</option>
      <option value="type"{if $tpl_filter.sortby == 'type'} selected="selected"{/if}>{$mod->Lang('type')}</option>
      <option value="created"{if $tpl_filter.sortby == 'created'} selected="selected"{/if}>{$mod->Lang('created')}</option>
      <option value="modified"{if $tpl_filter.sortby == 'modified'} selected="selected"{/if}>{$mod->Lang('modified')}</option>
    </select>
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="filter_sortorder">{$mod->Lang('prompt_sortorder')}:</label>
    <select class="boxchild" id="filter_sortorder" name="{$actionid}filter_sortorder" title="{$mod->Lang('title_sortorder')}">
      <option value="asc"{if $tpl_filter.sortorder == 'asc'} selected="selected"{/if}>{$mod->Lang('asc')}</option>
      <option value="desc"{if $tpl_filter.sortorder == 'desc'} selected="selected"{/if}>{$mod->Lang('desc')}</option>
    </select>
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="filter_limit">{$mod->Lang('prompt_limit')}:</label>
    <select class="boxchild" id="filter_limit" name="{$actionid}filter_limit_tpl" title="{$mod->Lang('title_filterlimit')}">
      <option value="10"{if $tpl_filter.limit == 10} selected="selected"{/if}>10</option>
      <option value="25"{if $tpl_filter.limit == 25} selected="selected"{/if}>25</option>
      <option value="50"{if $tpl_filter.limit == 50} selected="selected"{/if}>50</option>
      <option value="100"{if $tpl_filter.limit == 100} selected="selected"{/if}>100</option>
    </select>
  </div>
  </div>
  </form>
</div>{* #filterdialog *}

{if $has_add_right}
 {if $list_types}
  <div id="addtemplatedialog" style="display: none;" title="{$mod->Lang('create_template')}">
    {form_start id="addtemplate_form"}
    <input type="hidden" name="{$actionid}submit_create" value="1" />
    <div class="pageoverflow">
    <p class="pagetext">
      <label for="tpl_import_type">{$mod->Lang('tpl_type')}:</label>
    </p>
    <p class="pageinput">
      <select name="{$actionid}import_type" id="tpl_import_type" title="{$mod->Lang('title_tpl_import_type')}">
      {html_options options=$list_types}
      </select>
    </p>
    </div>
    </form>
  </div>{* #addtemplatedialog *}
 {/if}
{/if}

{if $manage_templates}
  {tab_start name='groups'}
  {if isset($list_categories) && count($list_categories) > 1}
    <div class="pagewarn">{$mod->Lang('warning_group_dragdrop')}</div>
  {/if}{* list_categories *}

  <div class="pageinfo">{$mod->Lang('info_tpl_groups')}</div>
  <div class="pageoptions">
    {cms_action_url action='edit_category' assign='url'}
    <a href="{$url}" title="{$mod->Lang('create_group')}">{admin_icon icon='newobject.gif'}</a>
    <a href="{$url}">{$mod->Lang('create_group')}</a>
  </div>

  {if isset($list_categories)}
  <table id="categorylist" class="pagetable" style="width:auto;">
    <thead>
    <tr>
      <th title="{$mod->Lang('title_group_id')}">{$mod->Lang('prompt_id')}</th>
      <th title="{$mod->Lang('title_group_name')}">{$mod->Lang('prompt_name')}</th>
      <th class="pageicon"></th>
    </tr>
    </thead>
    <tbody>
    {foreach $list_categories as $category}{$cid=$category->get_id()}
    {cms_action_url action='edit_category' cat=$cid assign='edit_url'}
    <tr class="{cycle values='row1,row2'} sortable-table" id="cat_{$cid}">
      <td><a href="{$edit_url}" title="{$mod->Lang('prompt_edit')}">{$cid}</a></td>
      <td><a href="{$edit_url}" title="{$mod->Lang('prompt_edit')}">{$category->get_name()}</a></td>
      <td>
      <a href="{$edit_url}" title="{$mod->Lang('prompt_edit')}">{admin_icon icon='edit.gif'}</a>
      <a href="{cms_action_url action='delete_category' cat=$cid}" class="del_cat" title="{$mod->Lang('prompt_delete')}">{admin_icon icon='delete.gif'}</a>
      </td>
    </tr>
    {/foreach}
    </tbody>
  </table>
  {/if}

  {tab_start name='types'}
  {if $list_all_types}
  <table class="pagetable" style="width:auto;">
    <thead>
    <tr>
      <th>{$mod->Lang('prompt_id')}</th>
      <th>{$mod->Lang('prompt_name')}</th>
      <th class="pageicon"></th>
    </tr>
    </thead>
    <tbody>
    {foreach $list_all_types as $type}
     {cycle values="row1,row2" assign='rowclass'}
     {$reset_url=''}
     {if $type->get_dflt_flag()}
       {cms_action_url action='reset_type' type=$type->get_id() assign='reset_url'}
     {/if}
     {cms_action_url action='edit_type' type=$type->get_id() assign='edit_url'}
     <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
      <td>{$type->get_id()}</td>
      <td>
        <a href="{$edit_url}" title="{$mod->Lang('prompt_edit')}">{$type->get_langified_display_value()}</a>
      </td>
      <td>
        <a href="{$edit_url}" title="{$mod->Lang('prompt_edit')}">{admin_icon icon='edit.gif'}</a>
      {if $has_add_right}
        <a href="{cms_action_url action=edit_template import_type=$type->get_id()}" title="{$mod->Lang('prompt_import')}">{admin_icon icon='import.gif'}</a>
      {/if}
      </td>
    </tr>
    {/foreach}
    </tbody>
  </table>
  {/if}

{/if}
{tab_end}
