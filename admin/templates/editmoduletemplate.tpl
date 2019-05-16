{if $title}<h3>{$title}</h3>{/if}
{form_start action=$formaction id='form_edittemplate' extraparms=$formparms}
  {$tid=$tpl_obj->get_id()}
  {if $withbuttons}
  <div class="pageoverflow postgap">
    <div class="pageinput">
    {if $can_manage}
    <button type="submit" name="{$actionid}submit" id="submitbtn" class="adminsubmit icon check">{lang('submit')}</button>
{if !empty($withcancel)}<button type="submit" name="{$actionid}cancel" id="cancelbtn" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>{/if}
{if $tid > 0}<button type="submit" name="{$actionid}apply" id="applybtn" class="adminsubmit icon apply">{lang('apply')}</button>{/if}
    {else}
    <button type="submit" name="{$actionid}cancel" id="cancelbtn" class="adminsubmit icon close" formnovalidate>{lang('close')}</button>
    {/if}
    </div>
  </div>
  {/if}

  {if $warnmessage}
  <div class="pagewarn">{$warnmessage}</div>
  {/if}

  {if !empty($edit_meta)}
  <div class="pageoverflow">
    {$t=lang_by_realm('layout','prompt_name')}<label class="pagetext" for="tpl_name">* {$t}:</label>
    {cms_help realm='layout' key2=help_template_name title=$t}
    <div class="pageinput">
      <input id="tpl_name" type="text" name="{$actionid}name" size="40" maxlength="96" value="{$tpl_obj->get_name()}"{if !$can_manage} readonly="readonly"{/if} placeholder="{lang_by_realm('layout','enter_name')}" />
    </div>
  </div>

  {if $tid > 0}
  <div class="pageoverflow">
    {$t=lang_by_realm('layout','prompt_created')}<label class="pagetext" for="tpl_created">{$t}:</label>
    {cms_help realm='layout' key2='help_tpl_created' title=$t}
    <p class="pageinput" id="tpl_created">
      {$tpl_obj->get_created()|cms_date_format|cms_escape}
    </p>
  </div>
  <div class="pageoverflow">
    {$t=lang_by_realm('layout','prompt_modified')}<label class="pagetext" for="tpl_modified">{$t}:</label>
    {cms_help realm='layout' key2='help_tpl_modified' title=$t}
    <p class="pageinput" id="tpl_modified">
      {$tpl_obj->get_modified()|cms_date_format|cms_escape}
    </p>
  </div>
  {/if}
  {/if}

  {if $infomessage}
  <div class="pageinfo">{$infomessage}</div>
  {/if}

  {$usage_str=$tpl_obj->get_usage_string()} {if $usage_str}
  <div class="pageoverflow">
    {$t=lang_by_realm('layout','prompt_usage')}<label class="pagetext" for="tpl_usage">{$t}:</label>
    {cms_help realm='layout' key2='help_tpl_usage' title=$t}
    <p class="pageinput" id="tpl_usage">
    {$usage_str}
    </p>
  </div>
  {/if}

  {if !empty($edit_meta)}
{tab_header name='template' label=lang_by_realm('layout','prompt_content')}
{tab_header name='description' label=lang_by_realm('layout','prompt_description')}
{if $can_manage}
  {tab_header name='options' label=lang_by_realm('layout','prompt_options')}
{/if}

{tab_start name='template'}
  {/if}{* edit_meta *}

  <div class="pageoverflow">
    {$t=lang('content')}<label class="pagetext" for="edit_area">{$t}:</label>
    {cms_help realm='layout' key2=help_template_contents title=$t}<br />
    <textarea class="pageinput" id="edit_area" name="{$actionid}content" data-cms-lang="smarty" rows="10" cols="40" style="width:40em;min-height:2em;"{if !$can_manage} readonly="readonly"{/if}>{$tpl_obj->get_content()}</textarea>
  </div>
  {if $withbuttons}
  <div class="pageoverflow pregap">
    <div class="pageinput">
    {if $can_manage}
    <button type="submit" name="{$actionid}submit" id="submitbtn" class="adminsubmit icon check">{lang('submit')}</button>
{if !empty($withcancel)}<button type="submit" name="{$actionid}cancel" id="cancelbtn" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>{/if}
{if $tid > 0}<button type="submit" name="{$actionid}apply" id="applybtn" class="adminsubmit icon apply">{lang('apply')}</button>{/if}
    {else}
    <button type="submit" name="{$actionid}cancel" id="cancelbtn" class="adminsubmit icon close" formnovalidate>{lang('close')}</button>
    {/if}
    </div>
  </div>
  {/if}

  {if !empty($edit_meta)}
{tab_start name='description'}
  <div class="pageoverflow">
    <label class="pagetext" for="tpl_desc">{lang_by_realm('layout','prompt_description')}:</label>
    {cms_help realm='layout' key2=help_template_description title=lang_by_realm('layout','prompt_description')}<br />
    <textarea class="pageinput" id="tpl_desc" name="{$actionid}description" style="width:40em;min-height:2em;"{if !$can_manage} readonly="readonly"{/if}>{$tpl_obj->get_description()}</textarea>
  </div>

  {if $can_manage || $tpl_obj->get_owner_id() == $userid}
  {tab_start name='options'}
  {if $can_manage}
    {if isset($type_list)}
      <div class="pageoverflow">
         {$t=lang_by_realm('layout','prompt_type')}<label class="pagetext" for="tpl_type">{$t}:</label>
         {cms_help realm='layout' key2=help_template_type title=$t}<br />
         <select class="pageinput" id="tpl_type" name="{$actionid}type">
         {html_options options=$type_list selected=$tpl_obj->get_type_id()}
         </select>
      </div>
      {if $tpl_candefault}
        <div class="pageoverflow pregap">
         <label class="pagetext" for="tpl_dflt">{lang_by_realm('layout','prompt_default')}:</label>
         {cms_help realm='layout' key2=help_template_dflt title=lang_by_realm('layout','prompt_default')}
         <div class="pageinput">
           <input type="hidden" name="{$actionid}default" value="0" />
           <input type="checkbox" name="{$actionid}default" id="tpl_dflt" value="1"{if $tpl_obj->get_type_dflt()} checked="checked"{/if} />
         </div>
        </div>
      {/if}{* can be type-default *}
    {/if}{* type_list *}
    <div class="pageoverflow pregap">
       {$t=lang_by_realm('layout','prompt_listable')}<label class="pagetext" for="tpl_listable">{$t}:</label>
       {cms_help realm='layout' key2=help_template_listable title=$t}
       <div class="pageinput">
         <input type="hidden" name="{$actionid}listable" value="0" />
         <input type="checkbox" name="{$actionid}listable" id="tpl_listable" value="1"{if $tpl_obj->get_listable()} checked="checked"{/if} />
       </div>
    </div>
   {/if}

   {if isset($user_list)}
   <div class="pageoverflow pregap">
     {$t=lang_by_realm('layout','prompt_owner')}<label class="pagetext" for="tpl_owner">{$t}:</label>
     {cms_help realm='layout' key2=help_template_owner title=$t}<br />
     <select class="pageinput" id="tpl_owner" name="{$actionid}owner_id">
     {html_options options=$user_list selected=$tpl_obj->get_owner_id()}
     </select>
   </div>
   {/if}
   {if isset($addt_editor_list)}
   <div class="pageoverflow pregap">
     {$t=lang_by_realm('layout','additional_editors')}<label class="pagetext" for="tpl_addeditor">{$t}:</label>
     {cms_help realm='layout' key2=help_template_addteditors title=$t}<br />
      <select class="pageinput" id="tpl_addeditor" name="{$actionid}addt_editors[]" multiple="multiple" size="5">
      {html_options options=$addt_editor_list selected=$tpl_obj->get_additional_editors()}
      </select>
   </div>
   {/if}
  {/if}{* can_manage etc *}

  {tab_end}

{/if}{* edit_meta *}

</form>
