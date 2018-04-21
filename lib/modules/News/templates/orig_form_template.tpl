{* original form template *}
<h3>{$mod->Lang('title_fesubmit_form')}</h3>

{if !empty($error)}
  <div class="error">{$error}</div>
{elseif !empty($message)}
  <div class="message">{$message}</div>
{/if}

{form_start category_id=$category_id}
 <div class="vbox">
  <div class="hbox flow">
    <div class="boxchild"><label for="news_title">*{$mod->Lang('title')}:</label></div>
    <div class="boxchild fill"><input id="news_title" type="text" name="{$actionid}title" value="{$title}" size="30" required /></div>
  </div>
  <div class="hbox flow">
    <div class="boxchild"><label for="news_category">{$mod->Lang('category')}:</label></div>
    <div class="boxchild fill"><select id="news_category" name="{$actionid}input_category">
      {html_options options=$categorylist selected=$category_id}
    </select></div>
  </div>
{if empty($hide_summary_field)}
  <div class="hbox flow">
    <div class="boxchild"><label for="news_summary">{$mod->Lang('summary')}:</label></div>
    <div class="boxchild fill">
      {$tmp=$actionid|cat:'summary'}
      {cms_textarea enablewysiwyg=true id=news_summary name=$tmp value=$summary required=true}
    </div>
  </div>
{/if}
  <div class="hbox flow">
    <div class="boxchild"><label for="news_content">*{$mod->Lang('content')}:</label></div>
    <div class="boxchild fill">
      {$tmp=$actionid|cat:'content'}
      {cms_textarea enablewysiwyg=true id=news_content name=$tmp value=$content required=true}
    </div>
  </div>
  <div class="hbox flow">
    <div class="boxchild"><label for="news_extra">{$mod->Lang('extra')}:</label></div>
    <div class="boxchild fill"><input id="news_extra" type="text" name="{$actionid}extra" value="{$extra}" size="30" /></div>
  </div>
  <div class="hbox flow">
    <div class="boxchild">{$mod->Lang('startdate')}:</div>
    <div class="boxchild fill">
      {$tmp=$actionid|cat:'startdate_'}
      {html_select_date prefix=$tmp time=$startdate end_year="+15"}
      {html_select_time prefix=$tmp time=$startdate}
    </div>
  </div>
  <div class="hbox flow">
    <div class="boxchild">{$mod->Lang('enddate')}:</div>
    <div class="boxchild fill">
      {$tmp=$actionid|cat:'enddate_'}
      {html_select_date prefix=$tmp time=$enddate end_year="+15"}
      {html_select_time prefix=$tmp time=$enddate}
    </div>
  </div>
  {if isset($customfields)}{foreach $customfields as $field}
   <div class="hbox flow">
    <div class="boxchild"><label for="news_fld_{$field->id}">{$field->name}:</label></div>
    <div class="boxchild fill">
    {if $field->type == 'file'}
      <input id="news_fld_{$field->id}" type="file" name="{$actionid}news_customfield_{$field->id}"/>
    {elseif $field->type == 'checkbox'}
      <input id="news_fld_{$field->id}" type="checkbox" name="{$actionid}news_customfield_{$field->id}" value="1"/>
    {elseif $field->type == 'textarea'}
      {$tmp1='news_fld_'|cat:$field->id}
      {capture assign='tmp2'}{$actionid}news_customfield_{$field->id}{/capture}
      {cms_textarea id=$tmp1 name=$tmp2 enablewysiwyg=true}
    {elseif $field->type == 'textbox'}
      <input id="news_fld_{$field->id}" type="text" name="{$actionid}news_customfield_{$field->id}" maxlength="{$field->max_length}"/>
    {/if}
    </div>
  </div>
  {/foreach}{/if}
 </div>{*.vbox*}
 <div class="pageinput pregap">
   <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
   <a href="{cms_selflink href=$page_alias}">{$mod->Lang('prompt_redirecttocontent')}</a>
 </div>
</form>

