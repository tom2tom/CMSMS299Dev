{capture assign='disable'}
  {if $tpl.lock && ($tpl.lock.uid != $userid)}disabled="disabled"{/if}
{/capture}

{if $tpl.lock}
  <div class="pagewarn lock-warning">{lang_by_realm('layout','lock_warning')}</div>
{/if}

<form id="form_edittemplate" action="{$selfurl}" enctype="multipart/form-data" method="post">
{foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />{/foreach}
<fieldset>
<div class="rowbox">
  <div class="boxchild">
    <div class="pageinput postgap">
      <button type="submit" name="dosubmit" id="submitbtn" class="adminsubmit icon check" {$disable|strip}>{lang('submit')}</button>
      <button type="submit" name="cancel" id="cancelbtn" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>
      {if $tpl.id > 0}
      <button type="submit" name="apply" id="applybtn" class="adminsubmit icon apply" {$disable|strip}>{lang('apply')}</button>
      {/if}
    </div>

    <div class="pageoverflow">
    {$t=lang_by_realm('layout','prompt_name')}<label class="pagetext" for="tpl_name">* {$t}:</label>
    {cms_help realm='layout' key2=help_template_name title=$t}
    <div class="pageinput">
      <input id="tpl_name" type="text" name="name" size="40" maxlength="96" value="{$tpl.name}"{if !$can_manage} readonly="readonly"{/if} placeholder="{lang_by_realm('layout','enter_name')}" />
    </div>
    </div>

    {if $tpl.usage_string}
    <div class="pageoverflow">
      {$t=lang_by_realm('layout','prompt_usage')}<label class="pagetext" for="tpl_usage">{$t}:</label>
      {cms_help realm='layout' key2='help_tpl_usage' title=$t}
      <p class="pageinput" id="tpl_usage">
        {$tpl.usage_string}
      </p>
    </div>
    {/if}
  </div>{* boxchild *}

  <div class="boxchild">
    {if $tpl.id > 0}
    <div class="pageoverflow">
      {$t=lang_by_realm('layout','prompt_created')}<label class="pagetext" for="tpl_created">{$t}:</label>
      {cms_help realm='layout' key2='help_tpl_created' title=$t}
      <p class="pageinput" id="tpl_created">
        {$tpl.created|cms_date_format|cms_escape}
      </p>
    </div>
    <div class="pageoverflow">
      {$t=lang_by_realm('layout','prompt_modified')}<label class="pagetext" for="tpl_modified">{$t}:</label>
      {cms_help realm='layout' key2='help_tpl_modified' title=$t}
      <p class="pageinput" id="tpl_modified">
        {$tpl.modified|cms_date_format|cms_escape}
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
{if $can_manage || $tpl.owner_id == $userid}
  {tab_header name='options' label=lang_by_realm('layout','prompt_options')}
{/if}

{tab_start name='template'}
 <div class="pageoverflow">
  {$t=lang_by_realm('layout','prompt_template_content')}<label class="pagetext" for="edit_area">{$t}:</label>
  {cms_help realm='layout' key2=help_template_contents title=$t}<br />
  <textarea class="pageinput" id="edit_area" name="content" data-cms-lang="smarty" rows="10" cols="40" style="width:40em;min-height:2em;max-height:12em;"{if !$can_manage} readonly="readonly"{/if}>{$tpl.content}</textarea>
 </div>
 <div class="pageinput pregap">
   <button type="submit" name="dosubmit" id="submitbtn" class="adminsubmit icon check" {$disable|strip}>{lang('submit')}</button>
   <button type="submit" name="cancel" id="cancelbtn" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>
   {if $tpl.id > 0}
   <button type="submit" name="apply" id="applybtn" class="adminsubmit icon apply" {$disable|strip}>{lang('apply')}</button>
   {/if}
 </div>

{tab_start name='description'}
 <div class="pageoverflow">
   {$t=lang_by_realm('layout','prompt_description')}<label class="pagetext" for="description">{$t}:</label>
   {cms_help realm='layout' key2=help_template_description title=$t}<br />
   <textarea class="pageinput" id="description" name="description" style="width:40em;min-height:2em;"{if !$can_manage} readonly="readonly"{/if}>{$tpl.description}</textarea>
 </div>
{*
{if $has_themes_right}
{tab_start name='designs'}
  <div class="pageoverflow">
    {$t=lang_by_realm('layout','prompt_designs')}<label class="pagetext" for="designlist">{$t}:</label>
    {cms_help realm='layout' key2=help_template_designlist title=$t}<br />
    <select class="pageinput" id="designlist" name="design_list[]" multiple="multiple" size="5">
     {html_options options=$design_list selected=$tpl.designs} DISABLED
    </select>
  </div>
{/if}
*}
   {if $can_manage || $tpl.owner_id == $userid}
   {tab_start name='options'}
   {if $can_manage}
     {if isset($type_list)}
       <div class="pageoverflow postgap">
         {$t=lang_by_realm('layout','prompt_type')}<label class="pagetext" for="tpl_type">{$t}:</label>
         {cms_help realm='layout' key2=help_template_type title=$t}<br />
         <select class="pageinput" id="tpl_type" name="type">
         {html_options options=$type_list selected=$tpl.type_id}
         </select>
       </div>
       {if isset($tpl_candefault)}
       <div class="pageoverflow postgap">
         {$t=lang_by_realm('layout','prompt_default')}<label class="pagetext" for="tpl_dflt">{$t}:</label>
         {cms_help realm='layout' key2=help_template_dflt title=$t}
         <div class="pageinput">
           <input type="hidden" name="default" value="0" />
           <input type="checkbox" name="default" id="tpl_dflt" value="1"{if $tpl_candefault} checked="checked"{/if} />
         </div>
       </div>
       {/if}
     {/if}
     <div class="pageoverflow postgap">
       {$t=lang_by_realm('layout','prompt_listable')}<label class="pagetext" for="tpl_listable">{$t}:</label>
       {cms_help realm='layout' key2=help_template_listable title=$t}
       <div class="pageinput">
         <input type="hidden" name="listable" value="0" />
         <input type="checkbox" name="listable" id="tpl_listable" value="1"{if $tpl.listable} checked="checked"{/if} />
       </div>
     </div>
{* multi groups allowed
     {if isset($category_list)}
     <div class="pageoverflow postgap">
      {$t=lang_by_realm('layout','prompt_group')}<label class="pagetext" for="tpl_category">{$t}:</label>
      {cms_help realm='layout' key2=help_template_category title=$t}<br />
      <select class="pageinput" id="tpl_category" name="category_id">
       {html_options options=$category_list selected=$tpl.category_id}
      </select>
     </div>
     {/if}
*}
{*  {if !empty($devmode)}
    {if $tpl.id > 0}
     <div class="pageoverflow postgap">
      <p class="pagetext">{lang_by_realm('layout','prompt_filetemplate')}:</p>
      <p class="pageinput">
      {if $tpl.content_file}
       <button type="submit" name="import" id="importbtn" class="adminsubmit icon do">{lang_by_realm('layout','import')}</button>
      {else}
       <button type="submit" name="export" id="exportbtn" class="adminsubmit icon do">{lang_by_realm('layout','export')}</button>
      {/if}
      </p>
     </div>
   {/if}
  {/if}
*}
   {/if}{* can manage *}
   {if isset($user_list)}
   <div class="pageoverflow postgap">
     {$t=lang_by_realm('layout','prompt_owner')}<label class="pagetext" for="tpl_owner">{$t}:</label>
     {cms_help realm='layout' key2=help_template_owner title=$t}<br />
     <select class="pageinput" id="tpl_owner" name="owner_id">
     {html_options options=$user_list selected=$tpl.owner_id}
     </select>
   </div>
   {/if}
   {if isset($addt_editor_list)}
   <div class="pageoverflow">
     {$t=lang_by_realm('layout','additional_editors')}<label class="pagetext" for="tpl_addeditor">{$t}:</label>
     {cms_help realm='layout' key2=help_template_addteditors title=$t}<br />
     <select class="pageinput" id="tpl_addeditor" name="addt_editors[]" multiple="multiple" size="5">
     {html_options options=$addt_editor_list selected=$tpl.additional_editors}
     </select>
   </div>
   {/if}
   {/if}{* can manage etc *}

{tab_end}

</form>
