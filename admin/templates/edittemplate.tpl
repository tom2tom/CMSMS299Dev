{$get_lock = $template->get_lock()}

{capture assign='disable'}
   {if isset($get_lock) && ({get_userid(false)} != $get_lock.uid)}disabled="disabled"{/if}
{/capture}

{if isset($get_lock)}
  <div class="pagewarn lock-warning">{lang_by_realm('layout','lock_warning')}</div>
{/if}

<form id="form_edittemplate" action="{$selfurl}" method="post">
{foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />{/foreach}
<fieldset>
<div class="rowbox">
  <div class="boxchild">
    <div class="pageinput postgap">
      <button type="submit" name="dosubmit" id="submitbtn" class="adminsubmit icon check" {$disable|strip}>{lang('submit')}</button>
      <button type="submit" name="cancel" id="cancelbtn" class="adminsubmit icon cancel">{lang('cancel')}</button>
      {if $template->get_id()}
      <button type="submit" name="apply" id="applybtn" class="adminsubmit icon apply" {$disable|strip}>{lang('apply')}</button>
     {/if}
    </div>

    <div class="pageoverflow">
    <label class="pagetext" for="tpl_name">*{lang_by_realm('layout','prompt_name')}:</label>
    {cms_help realm='layout' key2=help_template_name title=lang_by_realm('layout','prompt_name')}
    <div class="pageinput">
      <input id="tpl_name" type="text" name="name" size="40" maxlength="96" value="{$template->get_name()}"{if !$has_manage_right} readonly="readonly"{/if} placeholder="{lang_by_realm('layout','enter_name')}" />
    </div>
    </div>

    {$usage_str=$template->get_usage_string()} {if $usage_str}
    <div class="pageoverflow">
      <label class="pagetext" for="tpl_use">{lang_by_realm('layout','prompt_usage')}:</label>
      {cms_help realm='layout' key2='help_tpl_usage' title=lang_by_realm('layout','prompt_usage')}
      <p class="pageinput" id="tpl_use">
        {$usage_str}
      </p>
    </div>
    {/if}

  </div>{* boxchild *}
  <div class="boxchild">
    {if $template->get_id()}
    <div class="pageoverflow">
      <label class="pagetext" for="tpl_created">{lang_by_realm('layout','prompt_created')}:</label>
      {cms_help realm='layout' key2='help_tpl_created' title=lang_by_realm('layout','prompt_created')}
      <p class="pageinput">
        {$template->get_created()|cms_date_format|cms_escape}
      </p>
    </div>
    <div class="pageoverflow" id="tpl_modified_cont">
      <label class="pagetext" for="tpl_modified">{lang_by_realm('layout','prompt_modified')}:</label>
      {cms_help realm='layout' key2='help_tpl_modified' title=lang_by_realm('layout','prompt_modified')}
      <p class="pageinput">
        {$template->get_modified()|cms_date_format|cms_escape}
      </p>
    </div>
    {/if}
  </div>{*boxchild*}
</div>{*rowbox*}
</fieldset>

{tab_header name='template' label=lang_by_realm('layout','prompt_content')}
{tab_header name='description' label=lang_by_realm('layout','prompt_description')}
{*
{if $has_themes_right}
  {tab_header name='designs' label=lang_by_realm('layout','prompt_designs')}
{/if}
*}
{if $has_manage_right || $template->get_owner_id() == get_userid()}
  {tab_header name='permissions' label=lang_by_realm('layout','prompt_permissions')}
{/if}
{if $has_manage_right}
  {tab_header name='options' label=lang_by_realm('layout','prompt_options')}
{/if}

{tab_start name='template'}
<div class="pageoverflow">
  {$t=lang_by_realm('layout','prompt_template_content')}
  <label class="pagetext" for="content">{$t}:</label>
  {cms_help realm='layout' key2=help_template_contents title=$t}
  <div class="pageinput">
    {cms_textarea id='content' name=content value=$template->get_content() type='smarty' rows=20}
  </div>
</div>

{tab_start name='description'}
<div class="pageoverflow">
    <label class="pagetext" for="description">{lang_by_realm('layout','prompt_description')}:</label>
    {cms_help realm='layout' key2=help_template_description title=lang_by_realm('layout','prompt_description')}<br />
     <textarea class="pageinput" id="description" name="description" style="width:40em;min-height:2em;" {if !$has_manage_right}readonly="readonly"{/if}>{$template->get_description()}</textarea>
</div>
{*
{if $has_themes_right}
   {tab_start name='designs'}
   <div class="pageoverflow">
     <p class="pagetext">
      <label for="designlist">{lang_by_realm('layout','prompt_designs')}:</label>
    {cms_help realm='layout' key2=help_template_designlist title=lang_by_realm('layout','prompt_designs')}
      </p>
     <p class="pageinput">
       <select id="designlist" name="design_list[]" multiple="multiple" size="5">
         {html_options options=$design_list selected=$template->get_designs()} DISABLED
       </select>
     </p>
   </div>
{/if}
*}
{if $has_manage_right || $template->get_owner_id() == get_userid()}
   {tab_start name='permissions'}
   {if isset($user_list)}
   <div class="pageoverflow">
     <label class="pagetext" for="tpl_owner">{lang_by_realm('layout','prompt_owner')}:</label>
     {cms_help realm='layout' key2=help_template_owner title=lang_by_realm('layout','prompt_owner')}<br />
     <select id="tpl_owner" class="pageinput" name="owner_id">
     {html_options options=$user_list selected=$template->get_owner_id()}
     </select>
   </div>
   {/if}
   {if isset($addt_editor_list)}
   <div class="pageoverflow">
     <label class="pagetext" for="tpl_addeditor">{lang_by_realm('layout','additional_editors')}:</label>
     {cms_help realm='layout' key2=help_template_addteditors title=lang_by_realm('layout','additional_editors')}<br />
     <select id="tpl_addeditor" class="pageinput" name="addt_editors[]" multiple="multiple" size="5">
     {html_options options=$addt_editor_list selected=$template->get_additional_editors()}
     </select>
   </div>
   {/if}
{/if}

{if $has_manage_right}
   {tab_start name='options'}
     <div class="pageoverflow">
       <label class="pagetext" for="tpl_listable">{lang_by_realm('layout','prompt_listable')}:</label>
       {cms_help realm='layout' key2=help_template_listable title=lang_by_realm('layout','prompt_listable')}
       <div class="pageinput">
         <input type="hidden" name="listable" value="0" />
         <input type="checkbox" name="listable" id="tpl_listable" value="1"{if $template->get_listable()} checked="checked"{/if}
         {if $type_is_readonly} disabled="disabled"{/if} />
       </div>
     </div>
     {if isset($type_list)}
       <div class="pageoverflow">
         <label class="pagetext" for="tpl_type">{lang_by_realm('layout','prompt_type')}:</label>
         {cms_help realm='layout' key2=help_template_type title=lang_by_realm('layout','prompt_type')}<br />
         <select id="tpl_type" class="pageinput" name="type"{if $type_is_readonly} readonly="readonly"{/if}>
         {html_options options=$type_list selected=$template->get_type_id()}
         </select>
       </div>
       {if $type_obj && $type_obj->get_dflt_flag()}
       <div class="pageoverflow">
         <label class="pagetext" for="tpl_dflt">{lang_by_realm('layout','prompt_default')}:</label>
         {cms_help realm='layout' key2=help_template_dflt title=lang_by_realm('layout','prompt_default')}
         <div class="pageinput">
           <input type="hidden" name="default" value="0" />
           <input type="checkbox" name="default" id="tpl_dflt" value="1"{if $template->get_type_dflt()} checked="checked"{/if} />
         </div>
       </div>
       {/if}
     {/if}
{* multi groups allowed
     {if isset($category_list)}
     <div class="pageoverflow">
       <p class="pagetext">
      <label for="tpl_category">{lang_by_realm('layout','prompt_group')}:</label>
    {cms_help realm='layout' key2=help_template_category title=lang_by_realm('layout','prompt_group')}
      </p>
       <p class="pageinput">
         <select id="tpl_category" name="category_id">
            {html_options options=$category_list selected=$template->get_category_id()}
         </select>
       </p>
     </div>
     {/if}
*}
{*  {if !empty($devmode)}
    {if $template->get_id() > 0}
     <div class="pageoverflow">
      <p class="pagetext">{lang_by_realm('layout','prompt_filetemplate')}:</p>
      <p class="pageinput">
      {if $template->get_content_file()}
       <button type="submit" name="import" id="importbtn" class="adminsubmit icon do">{lang_by_realm('layout','import')}</button>
      {elseif $template->get_id() > 0}
       <button type="submit" name="export" id="exportbtn" class="adminsubmit icon do">{lang_by_realm('layout','export')}</button>
      {/if}
      </p>
     </div>
   {/if}
  {/if}
*}
{/if}
{tab_end}

</form>
