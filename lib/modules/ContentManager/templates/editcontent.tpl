{$extra_content|default:''}

{if $content_id < 1}
    <h3>{$mod->Lang('prompt_editpage_addcontent')}</h3>
{else}
    <h3>{$mod->Lang('prompt_editpage_editcontent')}&nbsp;<em>({$content_id})</em></h3>
{/if}

{function submit_buttons}
  <button type="submit" name="{$actionid}submit" title="{$mod->Lang('title_editpage_submit')}" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" title="{$mod->Lang('title_editpage_cancel')}" class="adminsubmit icon cancel" formnovalidate>{$mod->Lang('cancel')}</button>
  {if $content_id}
    <button type="submit" name="{$actionid}apply" title="{$mod->Lang('title_editpage_apply')}" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
  {/if}
  {if ($content_id != '') && $content_obj->IsViewable() && $content_obj->Active()}
    <a id="viewpage" rel="external" href="{$content_obj->GetURL()}" title="{$mod->Lang('title_editpage_view')}">{admin_icon icon='view.gif' alt=$mod->Lang('view_page')}</a>
  {/if}
{/function}

<div id="Edit_Content_Result"></div>
<div id="Edit_Content">
<div class="pregap"></div>
{form_start content_id=$content_id}
  <input type="hidden" id="active_tab" name="{$actionid}active_tab"/>
  <div class="pageinput postgap">
  {submit_buttons}
  </div>
  {* tab headers *}
  {foreach $tab_names as $key => $tabname}
    {tab_header name=$key label=$tabname active=$active_tab}
  {/foreach}
  {if $content_obj->HasPreview()}
    {tab_header name='_preview_' label=$mod->Lang('prompt_preview')}
  {/if}
  {* tab content *}
  {foreach $tab_names as $key => $tabname}
    {tab_start name=$key}
      {if isset($tab_message_array[$key])}{$tab_message_array[$key]}{/if}
      {if isset($tab_contents_array.$key) && is_array($tab_contents_array.$key)}
        {foreach $tab_contents_array.$key as $fld}
        <div class="pageoverflow">
          <p class="pagetext">{$fld[0]}</p>
          <p class="pageinput">{$fld[1]}{if count($fld) == 3}<br />{$fld[2]}{/if}</p>
        </div>
        {/foreach}
      {/if}
  {/foreach}
  {if $content_obj->HasPreview()}
    {tab_start name='_preview_'}
      <div class="pagewarn">{$mod->Lang('warn_preview')}</div>
      <iframe name="_previewframe_" class="preview" id="previewframe"></iframe>
      <div id="previewerror" style="display:none;">
        <fieldset>
          <legend>{$mod->Lang('report')}</legend>
          <ul id="preview_errors" class="pageerror"></ul>
        </fieldset>
      </div>
  {/if}
  {tab_end}
</form>
</div>{* #Edit_Content *}
