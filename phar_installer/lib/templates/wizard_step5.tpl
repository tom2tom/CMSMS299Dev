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
    <div class="row">
      <input class="form-field required half-width" type="text" name="sitename" value="{$sitename}" placeholder="{'ph_sitename'|tr}" required="required" />
      <div class="corner red">
        <i class="icon-asterisk"></i>
      </div>
    </div>
  {/if}

  {if isset($supporturl)}
    <h3{if !$verbose} class="disabled"{/if}>{'prompt_supporturl'|tr}</h3>
    {if $verbose}<p>{'info_supporturl'|tr}</p>{/if}
    <div class="row">
      <input class="form-field half-width{if !$verbose} disabled{/if}" type="text" name="supporturl" value="{$supporturl}"{if $verbose} placeholder="{'ph_supporturl'|tr}"{else} disabled="disabled"{/if} />
    </div>
  {/if}

  <h3>{'prompt_addlanguages'|tr}</h3>
  <p>{'info_addlanguages'|tr}</p>
  <div class="row">
    <select class="form-field" name="languages[]" multiple="multiple" size="8">
      {html_options options=$language_list selected=$languages}
    </select>
  </div>

  {if !empty($modules_list)}
  <h3>{'prompt_addmodules'|tr}</h3>
  <p>{'info_addmodules'|tr}</p>
  <div class="row">
    <select class="form-field" name="wantedextras[]" multiple="multiple" size="3">
      {html_options options=$modules_list selected=$modules_sel}
    </select>
  </div>
  {/if}

  {if $action == 'install'}
  <h3>{'prompt_installcontent'|tr}</h3>
  <p>{'info_installcontent'|tr}</p>
  <div class="row">
    <select id="demo" class="form-field" name="samplecontent">
      {html_options options=$yesno selected=$config.samplecontent}
    </select>
  </div>
  {/if}
  {if empty($error)}
  <div id="bottom_nav">
   <button class="action-button positive" type="submit" name="next"><i class='icon-next-{if empty($lang_rtl)}right{else}left{/if}'></i> {'next'|tr}</button>
  </div>
  {/if}
 </form>
</div>
{/block}
