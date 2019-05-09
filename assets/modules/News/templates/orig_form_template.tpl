<script type="text/javascript">
{literal}//<![CDATA[
 cms_equalWidth($('label.boxchild'));
{/literal}//]]>
</script>

{* original form template *}
<h3>{$mod->Lang('title_fesubmit_form')}</h3>

{if !empty($error)}
  <div class="error">{$error}</div>
{elseif !empty($message)}
  <div class="message">{$message}</div>
{/if}

{form_start category_id=$category_id}
 <div class="colbox">
  <div class="rowbox flow">
    <label class="boxchild" for="news_title">*{$mod->Lang('title')}:</label>
    <input type="text" class="boxchild" id="news_title" name="{$actionid}title" value="{$title}" size="30" required="required" />
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="news_category">{$mod->Lang('category')}:</label>
    <select class="boxchild" id="news_category" name="{$actionid}input_category">
      {html_options options=$categorylist selected=$category_id}
    </select>
  </div>
{if empty($hide_summary_field)}
  <div class="rowbox flow">
    <label class="boxchild" for="news_summary">{$mod->Lang('summary')}:</label>
    <div class="boxchild">
      {$tmp=$actionid|cat:'summary'}
      {cms_textarea enablewysiwyg=true id=news_summary name=$tmp value=$summary required=true}
    </div>
  </div>
{/if}
  <div class="rowbox flow">
    <label class="boxchild" for="news_content">*{$mod->Lang('content')}:</label>
    <div class="boxchild">
      {$tmp=$actionid|cat:'content'}
      {cms_textarea enablewysiwyg=true id=news_content name=$tmp value=$content required=true}
    </div>
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="news_extra">{$mod->Lang('extra')}:</label>
    <input class="boxchild" id="news_extra" type="text" name="{$actionid}extra" value="{$extra}" size="30" />
  </div>
  <div class="rowbox flow">
    <label class="boxchild">{$mod->Lang('startdate')}:</label>
    <div class="boxchild">
      {$tmp=$actionid|cat:'startdate_'}
      {html_select_date prefix=$tmp time=$startdate end_year="+15"}
      {html_select_time prefix=$tmp time=$startdate}
    </div>
  </div>
  <div class="rowbox flow">
    <label class="boxchild">{$mod->Lang('enddate')}:</label>
    <div class="boxchild">
      {$tmp=$actionid|cat:'enddate_'}
      {html_select_date prefix=$tmp time=$enddate end_year="+15"}
      {html_select_time prefix=$tmp time=$enddate}
    </div>
  </div>
  {if isset($customfields)}{foreach $customfields as $field}
   <div class="rowbox flow">
    <label class="boxchild" for="news_fld_{$field->id}">{$field->name}:</label>
    <div class="boxchild">
    {if $field->type == 'file'}
      <input id="news_fld_{$field->id}" type="file" name="{$actionid}news_customfield_{$field->id}" />
    {elseif $field->type == 'checkbox'}
      <input id="news_fld_{$field->id}" type="checkbox" name="{$actionid}news_customfield_{$field->id}" value="1" />
    {elseif $field->type == 'textarea'}
      {$tmp1='news_fld_'|cat:$field->id}
      {capture assign='tmp2'}{$actionid}news_customfield_{$field->id}{/capture}
      {cms_textarea id=$tmp1 name=$tmp2 enablewysiwyg=true}
    {elseif $field->type == 'textbox'}
      <input id="news_fld_{$field->id}" type="text" name="{$actionid}news_customfield_{$field->id}" maxlength="{$field->max_length}" />
    {/if}
    </div>
   </div>
  {/foreach}{/if}
 </div>{*.colbox*}
 <div class="pageinput pregap">
   <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
   <a href="{cms_selflink href=$page_alias}">{$mod->Lang('prompt_redirecttocontent')}</a>
 </div>
</form>
