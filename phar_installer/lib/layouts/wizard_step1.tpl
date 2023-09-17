{* wizard step 1 *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {capture assign='browser_title'}CMS Made Simple&trade; {$version|default:''} ({$version_name|default:''}) {tr('apptitle')}{/capture}
  {capture assign='title'}{tr('title_welcome')} {tr('to')} CMS Made Simple&trade; {$version|default:''} <span class="emphatic">({$version_name|default:''})</span><br>{tr('apptitle2')}{/capture}
  {$current_step = '1'}
{/block}

{block name='javascript' append}
<script>
function redirect_langchange() {
 var e = document.getElementById('lang_selector');
 var v = e.options[e.selectedIndex].value;
 var url = window.location.origin + window.location.pathname + '?curlang='+v;
 window.location.href = url;
 return false;
}
$(function() {
 $('#lang_selector').on('change', redirect_langchange);
});
</script>
{/block}

{block name='contents'}
<p>{tr('welcome_message')}</p>

<div class="installer-form">
 {wizard_form_start}
  {if empty($custom_destdir) && !empty($dirlist)}
    <h3>{tr('step1_destdir')}</h3>

    <p class="message yellow">{tr('step1_info_destdir')}</p>

    <div class="page-row">
    <label>{tr('destination_directory')}:</label>
    <select class="form-field" name="destdir">
      {html_options options=$dirlist selected=$destdir|default:''}
    </select>
    </div>
  {/if}

  <h3>{tr('step1_language')}</h3>
  <p>{tr('select_language')}</p>
  <div class="page-row">
    <label>{tr('installer_language')}:</label>
    <select id="lang_selector" class="form-field" name="lang">
      {html_options options=$languages selected=$curlang}
    </select>
  </div>

  <h3>{tr('step1_advanced')}</h3>
  <p>{tr('info_advanced')}</p>
  <div class="page-row">
    <label>{tr('advanced_mode')}:</label>
    <select class="form-field" name="verbose">
      {html_options options=$yesno selected=$verbose}
    </select>
  </div>
{if empty($error)}
  <div id="bottom_nav">
    <button type="submit" class="action-button positive" name="next">{if empty($lang_rtl)}<i class="icon-next-right"></i> {tr('next')}{else}{tr('next')} <i class="icon-next-left"></i>{/if}</button>{*TODO lang direction selected on this page *}
  </div>
{/if}
 </form>
</div>
{/block}
