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
