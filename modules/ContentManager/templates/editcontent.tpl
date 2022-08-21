{$extra_content|default:''}

{if $content_id < 1}
    <h3>{_ld($_module,'prompt_editpage_addcontent')}</h3>
{else}
    <h3>{_ld($_module,'prompt_editpage_editcontent')}&nbsp;<em>({$content_id})</em></h3>
{/if}

{function submit_buttons}
  <button type="submit" name="{$actionid}submit" title="{_ld($_module,'title_editpage_submit')}" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
  <button type="submit" name="{$actionid}cancel" title="{_ld($_module,'title_editpage_cancel')}" class="adminsubmit icon cancel" formnovalidate>{_ld($_module,'cancel')}</button>
  {if $content_id}
    <button type="submit" name="{$actionid}apply" title="{_ld($_module,'title_editpage_apply')}" class="adminsubmit icon apply">{_ld($_module,'apply')}</button>
  {/if}
  {if ($content_id > 0) && $content_obj->IsViewable() && $content_obj->Active()}
    <a id="viewpage" rel="external" href="{$content_obj->GetURL()}" title="{_ld($_module,'title_editpage_view')}">{admin_icon icon='view.gif' alt=_ld($_module,'view_page')}</a>
  {/if}
{/function}

<div id="Edit_Content_Result"></div>
<div id="Edit_Content">
<div class="pregap"></div>
{form_start content_id=$content_id}
  <input type="hidden" id="active_tab" name="{$actionid}active_tab" />
  <div class="pageinput postgap">
  {submit_buttons}
  </div>
  {* tab headers *}
  {foreach $tab_names as $key => $tabname}
    {tab_header name=$key label=$tabname active=$active_tab}
  {/foreach}
  {if $content_obj->HasPreview()}
    {tab_header name='_preview_' label=_ld($_module,'prompt_preview')}
  {/if}
  {* tab content *}
  {foreach $tab_names as $key => $tabname}
    {tab_start name=$key}
      {if isset($tab_message_array[$key])}
      <p class="pageinfo">{$tab_message_array[$key]}</p>
      {/if}
      {if isset($tab_contents_array.$key) && is_array($tab_contents_array.$key)}
        {foreach $tab_contents_array.$key as $fld}{if $fld}
        <div class="pageoverflow">
          {if $fld.0}<label class="pagetext"{if (strpos($fld.0,'>') !== false)} {$fld.0}{else}>{$fld.0}{/if}:</label>{/if}
          {if $fld.1}{$fld.1}{/if}
          <div class="pageinput">{$fld.2}{if !empty($fld.3)}<br />{$fld.3}{/if}</div>
        </div>
        {/if}{/foreach}
      {/if}
  {/foreach}
  {if $content_obj->HasPreview()}
    {tab_start name='_preview_'}
      <div class="pagewarn">{_ld($_module,'warn_preview')}</div>
      <iframe name="_previewframe_" class="preview" id="previewframe"></iframe>
      <div id="previewerror" style="display:none;">
        <fieldset>
          <legend>{_ld($_module,'report')}</legend>
          <ul id="preview_errors" class="pageerror"></ul>
        </fieldset>
      </div>
  {/if}
  {tab_end}
</form>
</div>{* #Edit_Content *}
