<div id="stylesheet_area"></div>

<div id="filtercssdlg" style="display:none;" title="{$mod->Lang('css_filter')}">
  {form_start id='filtercssdlg_form'}{*strip*}
    <input type="hidden" name="{$actionid}submit_filter_css" id="submit_filter_css" value="1" />
    <div class="vbox">
    <div class="hbox flow">
      <label class="boxchild" for="filter_css_design">{$mod->Lang('prompt_design')}:</label>
      <select class="boxchild" name="{$actionid}filter_css_design" id="filter_css_design" title="{$mod->Lang('title_filter_design')}">
        <option value="">{$mod->Lang('any')}</option>
        {html_options options=$design_names selected=$css_filter.design}
      </select>
    </div>
    <div class="hbox flow">
      <label class="boxchild" for="filter_css_sortby">{$mod->Lang('prompt_sortby')}:</label>
      <select class="boxchild" name="{$actionid}filter_css_sortby" id="filter_css_sortby" title="{$mod->Lang('title_sortby')}">
        <option value="name"{if $css_filter.sortby == 'name'} selected="selected"{/if}>{$mod->Lang('name')}</option>
        <option value="created"{if $css_filter.sortby == 'created'} selected="selected"{/if}>{$mod->Lang('created')}</option>
        <option value="modified"{if $css_filter.sortby == 'modified'} selected="selected"{/if}>{$mod->Lang('modified')}</option>
      </select>
    </div>
    <div class="hbox flow">
      <label class="boxchild" for="filter_css_sortorder">{$mod->Lang('prompt_sortorder')}:</label>
      <select class="boxchild" name="{$actionid}filter_css_sortorder" id="filter_css_sortorder" title="{$mod->Lang('title_sortorder')}">
        <option value="asc"{if $css_filter.sortorder == 'asc'} selected="selected"{/if}>{$mod->Lang('asc')}</option>
        <option value="desc"{if $css_filter.sortorder == 'desc'} selected="selected"{/if}>{$mod->Lang('desc')}</option>
      </select>
    </div>
    <div class="hbox flow">
      <label class="boxchild" for="filter_limit_css">{$mod->Lang('prompt_limit')}:</label>
      <select class="boxchild" name="{$actionid}filter_limit_css" id="filter_limit_css">
        <option value="10"{if isset($css_filter.limit) && $css_filter.limit == 10} selected="selected"{/if}>10</option>
        <option value="25"{if isset($css_filter.limit) && $css_filter.limit == 25} selected="selected"{/if}>25</option>
        <option value="50"{if isset($css_filter.limit) && $css_filter.limit == 50} selected="selected"{/if}>50</option>
        <option value="100"{if isset($css_filter.limit) && $css_filter.limit == 100} selected="selected"{/if}>100</option>
      </select>
    </div>
  </div>{*vbox*}
  </form>
</div>
