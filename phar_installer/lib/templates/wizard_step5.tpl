{* wizard step 5 *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step5'|tr}
  {$current_step = '5'}
{/block}

{block name='contents'}
<div class="installer-form">
 {wizard_form_start}
  {if $action == 'install'}
    <h3>{'prompt_sitename'|tr}</h3>
    <p>{'info_sitename'|tr}</p>
    <div class="row btm-margin">
      <div class="cell col-8 must">
        <input type="text" class="form-field half-width max40 mustchild" name="sitename" value="{$sitename}" placeholder="{'ph_sitename'|tr}" required="required" />
        <div class="corner red mustchild">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
  {/if}

  {if isset($supporturl)}
    <h3{if !$verbose} class="disabled"{/if}>{'prompt_supporturl'|tr}</h3>
    {if $verbose}<p>{'info_supporturl'|tr}</p>{/if}
    <div class="page-row">
      <input type="text" class="form-field half-width max40{if !$verbose} disabled{/if}" name="supporturl" value="{$supporturl}"{if $verbose} placeholder="{'ph_supporturl'|tr}"{else} disabled="disabled"{/if} />
    </div>
  {/if}

  <h3>{'prompt_addlanguages'|tr}</h3>
  <p>{'info_addlanguages'|tr}</p>
  <div class="page-row">
    <select class="form-field" name="languages[]" multiple="multiple" size="8">
      {html_options options=$language_list selected=$languages}
    </select>
  </div>

  {if !empty($modules_list)}
  <h3>{'prompt_addmodules'|tr}</h3>
  <p>{'info_addmodules'|tr}</p>
  <div class="page-row">
    <select class="form-field" name="wantedextras[]" multiple="multiple" size="3">
      {html_options options=$modules_list selected=$modules_sel}
    </select>
  </div>
  {/if}

  {if $action == 'install'}
  <h3>{'prompt_installcontent'|tr}</h3>
  <p>{'info_installcontent'|tr}</p>
  <div class="page-row">
    <select id="demo" class="form-field" name="samplecontent">
      {html_options options=$yesno selected=$config.samplecontent}
    </select>
  </div>
  {/if}
  {if empty($error)}
  <div id="bottom_nav">
   <button type="submit" class="action-button positive" name="next">{if empty($lang_rtl)}<i class="icon-next-right"></i> {'next'|tr}{else}{'next'|tr} <i class="icon-next-left"></i>{/if}</button>
  </div>
{*  {else}<a href="{$retry_url}" class="action-button negative" title="{'retry'|tr}">{if !empty($lang_rtl)}<i class="icon-refresh"></i> {'retry'|tr}{else}{'retry'|tr} <i class="icon-refresh"></i>{/if}</a>*}
  {/if}
 </form>
</div>
{/block}
