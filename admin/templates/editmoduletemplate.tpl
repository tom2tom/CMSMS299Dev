{if $title}<h3>{$title}</h3>{/if}
{form_start action=$formaction id='form_edittemplate' extraparms=$formparms}
  {$tid=$tpl_obj->get_id()}
  {if $withbuttons}
  <div class="pageoverflow postgap">
    <div class="pageinput">
    {if $can_manage}
    <button type="submit" id="submitbtn" name="{$actionid}submit" class="adminsubmit icon check">{_ld('admin','submit')}</button>
{if !empty($withcancel)}<button type="submit" id="cancelbtn" name="{$actionid}cancel" class="adminsubmit icon cancel" formnovalidate>{_ld('admin','cancel')}</button>{/if}
{if $tid > 0} <button type="submit" id="applybtn" name="{$actionid}apply" class="adminsubmit icon apply">{_ld('admin','apply')}</button>{/if}
    {else}
    <button type="submit" id="cancelbtn" name="{$actionid}cancel" class="adminsubmit icon close" formnovalidate>{_ld('admin','close')}</button>
    {/if}
    </div>
  </div>
  {/if}

  {if $warnmessage}
  <div class="pagewarn">{$warnmessage}</div>
  {/if}

  {if !empty($edit_meta)}
  <div class="pageoverflow">
    {$t=_ld('layout','prompt_name')}<label class="pagetext" for="tplname">* {$t}:</label>
    {cms_help 0='layout' key='help_template_name' title=$t}
    <div class="pageinput">
      <input type="text" id="tplname" name="{$actionid}name" size="40" maxlength="96" value="{$tpl_obj->get_name()}"{if !$can_manage} readonly="readonly"{/if} placeholder="{_ld('layout','enter_name')}" />
    </div>
  </div>

  {if $tid > 0}
  <div class="pageoverflow">
    {$t=_ld('layout','prompt_created')}<label class="pagetext" for="created">{$t}:</label>
    {cms_help 0='layout' key='help_tpl_created' title=$t}
    <p class="pageinput" id="created">
      {$tpl_obj->get_created()|cms_date_format:'timed'}
    </p>
  </div>
  <div class="pageoverflow">
    {$t=_ld('layout','prompt_modified')}<label class="pagetext" for="modified">{$t}:</label>
    {cms_help 0='layout' key='help_tpl_modified' title=$t}
    <p class="pageinput" id="modified">
      {$tpl_obj->get_modified()|cms_date_format:'timed'}
    </p>
  </div>
  {/if}
  {/if}

  {if $infomessage}
  <div class="pageinfo">{$infomessage}</div>
  {/if}

  {$usage_str=$tpl_obj->get_usage_string()} {if $usage_str}
  <div class="pageoverflow">
    {$t=_ld('layout','prompt_usage')}<label class="pagetext" for="usage">{$t}:</label>
    {cms_help 0='layout' key='help_tpl_usage' title=$t}
    <p class="pageinput" id="usage">
      {$usage_str}
    </p>
  </div>
  {/if}

  {if !empty($edit_meta)}
{tab_header name='template' label=_ld('layout','prompt_content')}
{tab_header name='description' label=_ld('layout','prompt_description')}
{if $can_manage}
  {tab_header name='options' label=_ld('layout','prompt_options')}
{/if}

{tab_start name='template'}
  {/if}{* edit_meta *}

  <div class="pageoverflow">
    {$t=_ld('admin','content')}<label class="pagetext" for="edit_area">{$t}:</label>
    {cms_help 0='layout' key='help_template_contents' title=$t}
    <div class="pageinput">
      <textarea id="edit_area" name="{$actionid}content" data-cms-lang="smarty" rows="10" cols="40" style="width:40em;min-height:2em;"{if !$can_manage} readonly="readonly"{/if}>{$tpl_obj->get_content()}</textarea>
    </div>
  </div>
  {if $withbuttons}
  <div class="pageoverflow pregap">
    <div class="pageinput">
    {if $can_manage}
    <button type="submit" id="submitbtn" name="{$actionid}submit" class="adminsubmit icon check">{_ld('admin','submit')}</button>
{if !empty($withcancel)}<button type="submit" id="cancelbtn" name="{$actionid}cancel" class="adminsubmit icon cancel" formnovalidate>{_ld('admin','cancel')}</button>{/if}
{if $tid > 0} <button type="submit" id="applybtn" name="{$actionid}apply" class="adminsubmit icon apply">{_ld('admin','apply')}</button>{/if}
    {else}
    <button type="submit" id="cancelbtn" name="{$actionid}cancel" class="adminsubmit icon close" formnovalidate>{_ld('admin','close')}</button>
    {/if}
    </div>
  </div>
  {/if}

  {if !empty($edit_meta)}
{tab_start name='description'}
  <div class="pageoverflow">
    {$t=_ld('layout','prompt_description')}<label class="pagetext" for="tpldesc">{$t}:</label>
    {cms_help 0='layout' key='help_template_description' title=$t}
    <div class="pageinput">
      <textarea id="tpldesc" name="{$actionid}description" style="width:40em;min-height:2em;"{if !$can_manage} readonly="readonly"{/if}>{$tpl_obj->get_description()}</textarea>
    </div>
  </div>

  {if $can_manage || $tpl_obj->get_owner_id() == $userid}
  {tab_start name='options'}
  {if $can_manage}
    {if isset($type_list)}
      <div class="pageoverflow">
        {$t=_ld('layout','prompt_type')}<label class="pagetext" for="type">{$t}:</label>
        {cms_help 0='layout' key='help_template_type' title=$t}
        <div class="pageinput">
        <select id="type" name="{$actionid}type">
          {html_options options=$type_list selected=$tpl_obj->get_type_id()}     </select>
        </div>
      </div>
      {if $tpl_candefault}
        <div class="pageoverflow pregap">
          {$t=_ld('layout','prompt_default')}<label class="pagetext" for="deflt">{$t}:</label>
          {cms_help 0='layout' key='help_template_dflt' title=$t}
          <input type="hidden" name="{$actionid}default" value="0" />
          <div class="pageinput">
            <input type="checkbox" id="deflt" name="{$actionid}default" value="1"{if $tpl_obj->get_type_dflt()} checked="checked"{/if} />
          </div>
        </div>
      {/if}{* can be type-default *}
    {/if}{* type_list *}
    <div class="pageoverflow pregap">
      {$t=_ld('layout','prompt_listable')}<label class="pagetext" for="listable">{$t}:</label>
      {cms_help 0='layout' key='help_template_listable' title=$t}
      <input type="hidden" name="{$actionid}listable" value="0" />
      <div class="pageinput">
        <input type="checkbox" id="listable" name="{$actionid}listable" value="1"{if $tpl_obj->get_listable()} checked="checked"{/if} />
      </div>
    </div>
   {/if}

   {if isset($user_list)}
   <div class="pageoverflow pregap">
     {$t=_ld('layout','prompt_owner')}<label class="pagetext" for="owner">{$t}:</label>
     {cms_help 0='layout' key='help_template_owner' title=$t}
     <div class="pageinput">
     <select id="owner" name="{$actionid}owner_id">
       {html_options options=$user_list selected=$tpl_obj->get_owner_id()}   </select>
     </div>
   </div>
   {/if}
   {if isset($addt_editor_list)}
   <div class="pageoverflow pregap">
     {$t=_ld('layout','additional_editors')}<label class="pagetext" for="addeditor">{$t}:</label>
     {cms_help 0='layout' key='help_template_addteditors' title=$t}
     <div class="pageinput">
     <select id="addeditor" name="{$actionid}addt_editors[]" multiple="multiple" size="5">
       {html_options options=$addt_editor_list selected=$tpl_obj->get_additional_editors()}    </select>
     </div>
   </div>
   {/if}
  {/if}{* can_manage etc *}
  {tab_end}
{/if}{* edit_meta *}
</form>
