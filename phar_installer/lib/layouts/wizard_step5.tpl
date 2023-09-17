{* wizard step 5 *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = tr('title_step5')}
  {$current_step = '5'}
{/block}

{block name='contents'}
<div class="installer-form">
 {wizard_form_start}
  {if $action == 'install'}
    <h3>{tr('prompt_sitename')}</h3>
    <p>{tr('info_sitename')}</p>
    <div class="row btm-margin">
      <div class="cell col-8 must">
        <input type="text" class="form-field half-width max40 mustchild" name="sitename" value="{$sitename}" placeholder="{tr('ph_sitename')}" required>
        <div class="corner red mustchild">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
  {/if}

  {if isset($supporturl)}
    <h3{if !$verbose} class="disabled"{/if}>{tr('prompt_supporturl')}</h3>
    {if $verbose}<p>{tr('info_supporturl')}</p>{/if}
    <div class="page-row">
      <input type="text" class="form-field half-width max40{if !$verbose} disabled{/if}" name="supporturl" value="{$supporturl}"{if $verbose} placeholder="{tr('ph_supporturl')}"{else} disabled{/if}>
    </div>
  {/if}

  <h3>{tr('prompt_addlanguages')}</h3>
  <p>{tr('info_addlanguages')}</p>
  <div class="page-row">
    <select class="form-field" name="languages[]" multiple="multiple" size="8">
      {html_options options=$language_list selected=$languages}
    </select>
  </div>

  {if !empty($modules_list)}
  <h3>{tr('prompt_addmodules')}</h3>
  <p>{tr('info_addmodules')}</p>
  <div class="page-row">
    <select class="form-field" name="wantedextras[]" multiple="multiple" size="3">
      {html_options options=$modules_list selected=$modules_sel}
    </select>
  </div>
  {/if}

  {if $action == 'install'}
  <h3>{tr('prompt_installcontent')}</h3>
  <p>{tr('info_installcontent')}</p>
  <div class="page-row">
    <select id="demo" class="form-field" name="samplecontent">
      {html_options options=$yesno selected=$config.samplecontent}
    </select>
  </div>
  {/if}
  {if empty($error)}
  <div id="bottom_nav">
   <button type="submit" class="action-button positive" name="next">{if empty($lang_rtl)}<i class="icon-next-right"></i> {tr('next')}{else}{tr('next')} <i class="icon-next-left"></i>{/if}</button>
  </div>
{*  {else}<a href="{$retry_url}" class="action-button negative" title="{tr('retry')}">{if !empty($lang_rtl)}<i class="icon-refresh"></i> {tr('retry')}{else}{tr('retry')} <i class="icon-refresh"></i>{/if}</a>*}
  {/if}
 </form>
</div>
{/block}
