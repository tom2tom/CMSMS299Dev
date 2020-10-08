{* wizard step 1 *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {capture assign='browser_title'}CMS Made Simple&trade; {$version|default:''} ({$version_name|default:''}) {'apptitle'|tr}{/capture}
  {capture assign='title'}{'title_welcome'|tr} {'to'|tr} CMS Made Simple&trade; {$version|default:''} <em>({$version_name|default:''})</em><br/>{'apptitle'|tr}{/capture}
  {$current_step = '1'}
{/block}

{block name='javascript' append}
<script type="text/javascript">
{literal}function redirect_langchange() {
 var e = document.getElementById('lang_selector');
 var v = e.options[e.selectedIndex].value;
 var url = window.location.origin + window.location.pathname + '?curlang='+v;
 window.location = url;
 return false;
}
$(function() {
 $('#lang_selector').on('change', redirect_langchange);
}{/literal}
</script>
{/block}

{block name='contents'}
<p>{'welcome_message'|tr}</p>

<div class="installer-form">
{wizard_form_start}
  {if empty($custom_destdir) && !empty($dirlist)}
    <h3>{'step1_destdir'|tr}</h3>

    <p class="message yellow">{'step1_info_destdir'|tr}</p>

    <div class="row">
    <label>{'destination_directory'|tr}:</label>
    <select class="form-field" name="destdir">
      {html_options options=$dirlist selected=$destdir|default:''}
    </select>
    </div>
    <hr />
  {/if}

  <h3>{'step1_language'|tr}</h3>
  <p>{'select_language'|tr}</p>
  <div class="row">
    <label>{'installer_language'|tr}:</label>
    <select id="lang_selector" class="form-field" name="lang">
      {html_options options=$languages selected=$curlang}
    </select>
  </div>

  <hr />

  <h3>{'step1_advanced'|tr}</h3>
  <p>{'info_advanced'|tr}</p>
  <div class="row">
    <label>{'advanced_mode'|tr}:</label>
    <select class="form-field" name="verbose">
      {html_options options=$yesno selected=$verbose}
    </select>
  </div>

  <div id="bottom_nav">
    <button type="submit" class="action-button positive" name="next"><i class='icon-cog'></i> {'next'|tr}</button>
  </div>
{wizard_form_end}
</div>
{/block}
