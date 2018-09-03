{* wizard step 5 *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step5'|tr}
  {$current_step = '5'}
{/block}

{block name='contents'}

<div class="installer-form">
{wizard_form_start}
  {if $action != 'freshen'}
    <h3>{'prompt_sitename'|tr}</h3>
    <p>{'info_sitename'|tr}</p>

    <div class="row form-row">
      <div class="twelve-col">
        <input class="form-field required full-width" type="text" name="sitename" value="{$sitename}" placeholder="{'ph_sitename'|tr}" required="required" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
  {/if}

  <h3>{'prompt_addlanguages'|tr}</h3>
  <p>{'info_addlanguages'|tr}</p>

  <div class="row form-row">
    <select class="form-field" name="languages[]" multiple="multiple" size="8">
      {html_options options=$language_list selected=$languages}
    </select>
  </div>

  <div id="bottom_nav">
  <button class="action-button positive" type="submit" name="next">{'next'|tr} <i class='icon-right'></i></button>
  </div>

{wizard_form_end}
</div>

{/block}
