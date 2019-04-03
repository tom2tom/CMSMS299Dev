{$get_lock = $template->get_lock()}

{capture assign='disable'}
   {if isset($get_lock) && ({get_userid(false)} != $get_lock.uid)}disabled="disabled"{/if}
{/capture}

{if isset($get_lock)}
  <div class="pagewarn lock-warning">{$mod->Lang('lock_warning')}</div>
{/if}

{form_start id="form_edittemplate" extraparms=$extraparms}
<fieldset>
<div class="rowbox">
  <div class="boxchild">
    <div class="pageinput postgap">
      <button type="submit" name="{$actionid}submit" id="submitbtn" class="adminsubmit icon check" {$disable|strip}>{$mod->Lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" id="cancelbtn" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
      {if $template->get_id()}
      <button type="submit" name="{$actionid}apply" id="applybtn" class="adminsubmit icon apply" {$disable|strip}>{$mod->Lang('apply')}</button>
     {/if}
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
      <label for="tpl_name">*{$mod->Lang('prompt_name')}:</label>
    {cms_help realm=$_module key2=help_template_name title=$mod->Lang('prompt_name')}
      </p>
      <p class="pageinput">
        <input id="tpl_name" type="text" name="{$actionid}name" size="50" maxlength="90" value="{$template->get_name()}" {if !$has_manage_right}readonly="readonly" {/if} placeholder="{$mod->Lang('new_template')}" />
      </p>
    </div>

    {$usage_str=$template->get_usage_string()} {if !empty($usage_str)}
    <div class="pageoverflow">
      <p class="pagetext"><label>{$mod->Lang('prompt_usage')}:</label>
    {cms_help realm=$_module key2='help_tpl_usage' title=$mod->Lang('prompt_usage')}
      </p>
      <p class="pageinput">
        {$usage_str}
      </p>
    </div>
    {/if}

  </div>{* boxchild *}
  <div class="boxchild">
    {if $template->get_id()}
    <div class="pageoverflow">
      <p class="pagetext">
      <label for="tpl_created">{$mod->Lang('prompt_created')}:</label>
    {cms_help realm=$_module key2='help_tpl_created' title=$mod->Lang('prompt_created')}
      </p>
      <p class="pageinput">
        {$template->get_created()|cms_date_format}
      </p>
    </div>
    <div class="pageoverflow" id="tpl_modified_cont">
      <p class="pagetext">
      <label for="tpl_modified">{$mod->Lang('prompt_modified')}:</label>
    {cms_help realm=$_module key2='help_tpl_modified' title=$mod->Lang('prompt_modified')}
      </p>
      <p class="pageinput">
        {$template->get_modified()|cms_date_format}
      </p>
    </div>
    {/if}
  </div>{*boxchild*}
</div>{*rowbox*}
</fieldset>

{tab_header name='template' label=$mod->Lang('prompt_template')}
{tab_header name='description' label=$mod->Lang('prompt_description')}
{if $has_themes_right}
  {tab_header name='designs' label=$mod->Lang('prompt_designs')}
{/if}
{if $template->get_owner_id() == get_userid() || $has_manage_right}
  {tab_header name='permissions' label=$mod->Lang('prompt_permissions')}
{/if}
{if $has_manage_right}
  {tab_header name='advanced' label=$mod->Lang('prompt_advanced')}
{/if}

{tab_start name='template'}
<div class="pageoverflow">
  <p class="pagetext">{$t=$mod->Lang('prompt_template_content')}
    <label for="contents">{$t}:</label>
    {cms_help realm=$_module key2=help_template_contents title=$t}
  </p>
  <p class="pageinput">
    {cms_textarea id='contents' prefix=$actionid name=contents value=$template->get_content() type='smarty' rows=20}
  </p>
</div>

{tab_start name='description'}
<div class="pageoverflow">
   <p class="pagetext">
      <label for="description">{$mod->Lang('prompt_description')}:</label>
    {cms_help realm=$_module key2=help_template_description title=$mod->Lang('prompt_description')}
      </p>
   <p class="pageinput">
     <textarea id="description" name="{$actionid}description" {if !$has_manage_right}readonly="readonly"{/if}>{$template->get_description()}</textarea>
   </p>
</div>

{if $has_themes_right}
   {tab_start name='designs'}
   <div class="pageoverflow">
     <p class="pagetext">
      <label for="designlist">{$mod->Lang('prompt_designs')}:</label>
    {cms_help realm=$_module key2=help_template_designlist title=$mod->Lang('prompt_designs')}
      </p>
     <p class="pageinput">
       <select id="designlist" name="{$actionid}design_list[]" multiple="multiple" size="5">
         {html_options options=$design_list selected=$template->get_designs()}
       </select>
     </p>
   </div>
{/if}

{if $template->get_owner_id() == get_userid() or $has_manage_right}
   {tab_start name='permissions'}
   {if isset($user_list)}
   <div class="pageoverflow">
     <p class="pagetext">
      <label for="tpl_owner">{$mod->Lang('prompt_owner')}:</label>
    {cms_help realm=$_module key2=help_template_owner title=$mod->Lang('prompt_owner')}
      </p>
     <p class="pageinput">
       <select id="tpl_owner" name="{$actionid}owner_id">
         {html_options options=$user_list selected=$template->get_owner_id()}
       </select>
     </p>
   </div>
   {/if}
   {if isset($addt_editor_list)}
   <div class="pageoverflow">
     <p class="pagetext">
      <label for="tpl_addeditor">{$mod->Lang('additional_editors')}:</label>
    {cms_help realm=$_module key2=help_template_addteditors title=$mod->Lang('additional_editors')}
      </p>
     <p class="pageinput">
       <select id="tpl_addeditor" name="{$actionid}addt_editors[]" multiple="multiple" size="5">
         {html_options options=$addt_editor_list selected=$template->get_additional_editors()}
       </select>
     </p>
   </div>
   {/if}
{/if}

{if $has_manage_right}
   {tab_start name='advanced'}
     <div class="pageoverflow">
       <p class="pagetext">
         <label for="tpl_listable">{$mod->Lang('prompt_listable')}:</label>
         {cms_help realm=$_module key2=help_template_listable title=$mod->Lang('prompt_listable')}
       </p>
       <input type="hidden" name="{$actionid}listable" value="0" />
       <p class="pageinput">
         <input type="checkbox" name="{$actionid}listable" id="tpl_listable" value="1"{if $template->get_listable()} checked="checked"{/if}
         {if $type_is_readonly} disabled="disabled"{/if} />
       </p>
     </div>
     {if isset($type_list)}
       <div class="pageoverflow">
         <p class="pagetext">
      <label for="tpl_type">{$mod->Lang('prompt_type')}:</label>
    {cms_help realm=$_module key2=help_template_type title=$mod->Lang('prompt_type')}
      </p>
         <p class="pageinput">
            <select id="tpl_type" name="{$actionid}type"{if $type_is_readonly} readonly="readonly"{/if}>
              {html_options options=$type_list selected=$template->get_type_id()}
            </select>
         </p>
       </div>
       {if $type_obj->get_dflt_flag()}
       <div class="pageoverflow">
         <p class="pagetext">
           <label for="tpl_dflt">{$mod->Lang('prompt_default')}:</label>
           {cms_help realm=$_module key2=help_template_dflt title=$mod->Lang('prompt_default')}
         </p>
         <input type="hidden" name="{$actionid}default" value="0" />
         <p class="pageinput">
           <input type="checkbox" name="{$actionid}default" id="tpl_dflt" value="1"{if $template->get_type_dflt()} checked="checked"{/if} />
         </p>
       </div>
       {/if}
     {/if}
     {if isset($category_list)}
     <div class="pageoverflow">
       <p class="pagetext">
      <label for="tpl_category">{$mod->Lang('prompt_category')}:</label>
    {cms_help realm=$_module key2=help_template_category title=$mod->Lang('prompt_category')}
      </p>
       <p class="pageinput">
         <select id="tpl_category" name="{$actionid}category_id">
            {html_options options=$category_list selected=$template->get_category_id()}
         </select>
       </p>
     </div>
     {/if}
   {if !empty($devmode)}
    {if $template->get_id() > 0}
     <div class="pageoverflow">
      <p class="pagetext">{$mod->Lang('prompt_filetemplate')}:</p>
      <p class="pageinput">
      {if $template->get_content_file()}
       <button type="submit" name="{$actionid}import" id="importbtn" class="adminsubmit icon do">{$mod->Lang('import')}</button>
      {elseif $template->get_id() > 0}
       <button type="submit" name="{$actionid}export" id="exportbtn" class="adminsubmit icon do">{$mod->Lang('export')}</button>
      {/if}
      </p>
     </div>
   {/if}
  {/if}
{/if}
{tab_end}

</form>
