{capture assign='disable'}
  {if $css.lock && ($css.lock.uid != $userid)}disabled="disabled"{/if}
{/capture}
{*
{if $css.id > 0}
  <h3>{lang_by_realm('layout','prompt_edit_stylesheet')} {$css.name} ({$css.id})</h3>
{else}
  <h3>{lang_by_realm('layout','prompt_create_stylesheet')}</h3>
{/if}
*}
{if $css.lock}
  <div class="warning lock-warning">{lang_by_realm('layout','lock_warning')}</div>
{/if}

<form id="form_editcss" action="{$selfurl}" enctype="multipart/form-data" method="post">
{foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />{/foreach}
<fieldset>
<div class="rowbox flow">
 <div class="boxchild">
  <div class="pageinput postgap">
    <button type="submit" name="dosubmit" id="submitbtn" class="adminsubmit icon check"  {$disable|strip}>{lang('submit')}</button>
    <button type="submit" name="cancel" id="cancelbtn" class="adminsubmit icon cancel">{lang('cancel')}</button>
    {if $css.id > 0}
     <button type="submit" name="apply" id="applybtn" class="adminsubmit icon apply" {$disable|strip}>{lang('apply')}</button>
    {/if}
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="css_name">*{lang_by_realm('layout','prompt_name')}:</label>
      {cms_help realm='layout' key2=help_stylesheet_name title=lang_by_realm('layout','prompt_name')}
    </p>
    <p class="pageinput">
      <input id="css_name" type="text" name="name" size="40" maxlength="64" value="{$css.name}" placeholder="{lang_by_realm('layout','enter_name')}" />
    </p>
  </div>
 </div>{* boxchild *}
{if $css.id > 0}
 <div class="boxchild">
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="css_created">{lang_by_realm('layout','prompt_created')}:</label>
      {cms_help realm='layout' key2=help_stylesheet_created title=lang_by_realm('layout','prompt_created')}
    </p>
    <p class="pageinput">
      {$css.created|cms_date_format}
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="css_modified">{lang_by_realm('layout','prompt_modified')}:</label>
      {cms_help realm='layout' key2=help_stylesheet_modified title=lang_by_realm('layout','prompt_modified')}
    </p>
    <p class="pageinput">
      {$css.modified|cms_date_format}
    </p>
  </div>
 </div>{* boxchild *}
{/if}
</div>{* rowbox *}
</fieldset>

{tab_header name='sheet' label=lang_by_realm('layout','prompt_content')}
{tab_header name='description' label=lang_by_realm('layout','prompt_description')}
{tab_header name='media_query' label=lang_by_realm('layout','prompt_media_query')}
{tab_header name='media_type' label=lang_by_realm('layout','prompt_media_type')}
{* no design-association
{if $has_designs_right}
 {tab_header name='designs' label=lang_by_realm('layout','prompt_designs')}
{/if}
*}
{*
{if !empty($devmode)}
 {if $css.id > 0}
 {tab_header name='advanced' label=lang_by_realm('layout','prompt_advanced')}
 {/if}
{/if}
*}
{tab_start name='sheet'}
<div class="pageoverflow">
 {$t=lang_by_realm('layout','prompt_stylesheet')}<label class="pagetext" for="edit_area">{$t}:</label>
 {cms_help realm='layout' key2=help_stylesheet_content title=$t}<br />
 <textarea class="pageinput" id="edit_area" name="content" data-cms-lang="css" rows="10" cols="40" style="width:40em;min-height:2em;max-height:20em;"{if !$can_manage} readonly="readonly"{/if}>{$css.content}</textarea>
</div>
{tab_start name='description'}
<div class="pageoverflow">
  <p class="pagetext">
    {$t=lang_by_realm('layout','prompt_description')}<label for="txt_description">{$t}:</label>
    {cms_help realm='layout' key2=help_css_description title=$t}
  </p>
  <p class="pageinput">
    <textarea id="txt_description" name="description" rows="3" cols="40" style="width:40em;min-height:2em;">{$css.description}</textarea>
  </p>
</div>
{tab_start name='media_query'}
<div class="pageinfo">{lang_by_realm('layout','info_editcss_mediaquery_tab')}</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="mediaquery">{lang_by_realm('layout','prompt_media_query')}:</label>
    {cms_help realm='layout' key2=help_css_mediaquery title=lang_by_realm('layout','prompt_media_query')}
  </p>
  <p class="pageinput">
    <textarea id="mediaquery" name="media_query" rows="10" cols="80">{$css.media_query}</textarea>
  </p>
</div>
{tab_start name='media_type'}
<!-- media -->
<div class="pagewarn">{lang_by_realm('layout','info_editcss_mediatype_tab')}</div>
<div class="pageoverflow">
{*  <p class="pagetext">{lang_by_realm('layout','prompt_media_type')}:</p> *}
  <p class="pageinput media-type">
  {foreach $all_types as $type}{strip}
    <input id="media_type_{$type}" type="checkbox" name="media_type[]" value="{$type}"
     {if !empty($css.types[$type])} checked="checked"{/if} />
    &nbsp;
    {$tmp='media_type_'|cat:$type}
      <label for="media_type_{$type}">{lang_by_realm('layout',$tmp)}</label>
      {if !$type@last}<br />{/if}
  {/strip}{/foreach}
  </p>
</div>
{*
{if $has_designs_right}
  {tab_start name='designs'}
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="designlist">{lang_by_realm('layout','prompt_designs')}:</label>
      {cms_help realm='layout' key2=help_css_designs title=lang_by_realm('layout','prompt_designs')}
    </p>
    <p class="pageinput">
      <select id="designlist" name="design_list[]" multiple="multiple" size="5">
      {html_options options=$design_list selected=$css.designs} DISABLED
      </select>
    </p>
  </div>
{/if}
*}
{*
{if !empty($devmode)}
 {if $css.id > 0}
 {tab_start name='advanced'}
  <div class="pageoverflow">
  <p class="pagetext">{lang_by_realm('layout','prompt_cssfile')}:</p>
  <p class="pageinput">
    {if $css.content_file} DISABLED
      <button type="submit" name="import" id="importbtn" class="adminsubmit icon do">{lang_by_realm('layout','import')}</button>
    {else}
      <button type="submit" name="export" id="exportbtn" class="adminsubmit icon do">{lang_by_realm('layout','export')}</button>
    {/if}
  </p>
  </div>
 {/if}
{/if}
*}
{tab_end}

</form>
