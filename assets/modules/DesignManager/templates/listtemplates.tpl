{tab_header name='templates' label=_ld($_module,'prompt_templates')}
{if $manage_templates}
  {tab_header name='groups' label=_ld($_module,'prompt_tpl_groups')}
  {tab_header name='types' label=_ld($_module,'prompt_templatetypes')}
{/if}

{tab_start name='templates'}
<div id="template_area"></div>

<div id="filterdialog" style="display:none;" title="{_ld($_module,'tpl_filter')|escape:'javascript'}">
  {form_start action='defaultadmin' id='filterdialog_form' __activetab='templates'}
  <input type="hidden" id="submit_filter_tpl" name="{$actionid}submit_filter_tpl" value="1" />
  <div class="colbox">
  <div class="rowbox flow">
    <label class="boxchild" for="filter_tpl">{_ld($_module,'prompt_options')}:</label>
    <select class="boxchild" id="filter_tpl" name="{$actionid}filter_tpl" title="{_ld($_module,'title_filter')}">
      {html_options options=$filter_tpl_options selected=$tpl_filter.tpl}
    </select>
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="filter_sortby">{_ld($_module,'prompt_sortby')}:</label>
    <select class="boxchild" id="filter_sortby" name="{$actionid}filter_sortby" title="{_ld($_module,'title_sortby')}">
      <option value="name"{if $tpl_filter.sortby == 'name'} selected="selected"{/if}>{_ld($_module,'name')}</option>
      <option value="type"{if $tpl_filter.sortby == 'type'} selected="selected"{/if}>{_ld($_module,'type')}</option>
      <option value="created"{if $tpl_filter.sortby == 'created'} selected="selected"{/if}>{_ld($_module,'created')}</option>
      <option value="modified"{if $tpl_filter.sortby == 'modified'} selected="selected"{/if}>{_ld($_module,'modified')}</option>
    </select>
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="filter_sortorder">{_ld($_module,'prompt_sortorder')}:</label>
    <select class="boxchild" id="filter_sortorder" name="{$actionid}filter_sortorder" title="{_ld($_module,'title_sortorder')}">
      <option value="asc"{if $tpl_filter.sortorder == 'asc'} selected="selected"{/if}>{_ld($_module,'asc')}</option>
      <option value="desc"{if $tpl_filter.sortorder == 'desc'} selected="selected"{/if}>{_ld($_module,'desc')}</option>
    </select>
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="filter_limit">{_ld($_module,'prompt_limit')}:</label>
    <select class="boxchild" id="filter_limit" name="{$actionid}filter_limit_tpl" title="{_ld($_module,'title_filterlimit')}">
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
  <div id="addtemplatedialog" style="display: none;" title="{_ld($_module,'create_template')}">
    {form_start id="addtemplate_form"}
    <input type="hidden" name="{$actionid}submit_create" value="1" />
    <div class="pageoverflow">
      <label class="pagetext" for="tpl_import_type">{_ld($_module,'tpl_type')}:</label>
      <div class="pageinput">
        <select name="{$actionid}import_type" id="tpl_import_type" title="{_ld($_module,'title_tpl_import_type')}">
        {html_options options=$list_types}
        </select>
      </div>
    </div>
    </form>
  </div>{* #addtemplatedialog *}
 {/if}
{/if}

{if $manage_templates}
  {tab_start name='groups'}
  {if isset($list_categories) && count($list_categories) > 1}
    <div class="pagewarn">{_ld($_module,'warning_group_dragdrop')}</div>
  {/if}{* list_categories *}

  <div class="pageinfo">{_ld($_module,'info_tpl_groups')}</div>
  <div class="pageoptions">
    {cms_action_url action='edit_category' assign='url'}
    <a href="{$url}" title="{_ld($_module,'create_group')}">{admin_icon icon='newobject.gif'}</a>
    <a href="{$url}">{_ld($_module,'create_group')}</a>
  </div>

  {if isset($list_categories)}
  <table class="pagetable" id="categorylist">
    <thead>
    <tr>
      <th title="{_ld($_module,'title_group_id')}">{_ld($_module,'prompt_id')}</th>
      <th title="{_ld($_module,'title_group_name')}">{_ld($_module,'prompt_name')}</th>
      <th class="pageicon"></th>
      <th class="pageicon"></th>
    </tr>
    </thead>
    <tbody>
    {foreach $list_categories as $category}{$cid=$category->get_id()}
    {cms_action_url action='edit_category' cat=$cid assign='edit_url'}
    <tr class="{cycle values='row1,row2'} sortable-table" id="cat_{$cid}">
      <td><a href="{$edit_url}" title="{_ld($_module,'prompt_edit')}">{$cid}</a></td>
      <td><a href="{$edit_url}" title="{_ld($_module,'prompt_edit')}">{$category->get_name()}</a></td>
      <td class="pagepos icons_wide">
      <a href="{$edit_url}" title="{_ld($_module,'prompt_edit')}">{admin_icon icon='edit.gif'}</a>
      </td>
      <td class="pagepos icons_wide">
      <a href="{cms_action_url action='delete_category' cat=$cid}" class="del_cat" title="{_ld($_module,'prompt_delete')}">{admin_icon icon='delete.gif'}</a>
      </td>
    </tr>
    {/foreach}
    </tbody>
  </table>
  {/if}

  {tab_start name='types'}
  {if $list_all_types}
  <table class="pagetable">
    <thead>
    <tr>
      <th>{_ld($_module,'prompt_id')}</th>
      <th>{_ld($_module,'prompt_name')}</th>
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
        <a href="{$edit_url}" title="{_ld($_module,'prompt_edit')}">{$type->get_langified_display_value()}</a>
      </td>
      <td>
        <a href="{$edit_url}" title="{_ld($_module,'prompt_edit')}">{admin_icon icon='edit.gif'}</a>
      {if $has_add_right}
        <a href="{cms_action_url action=edit_template import_type=$type->get_id()}" title="{_ld($_module,'prompt_import')}">{admin_icon icon='import.gif'}</a>
      {/if}
      </td>
    </tr>
    {/foreach}
    </tbody>
  </table>
  {/if}

{/if}
{tab_end}
