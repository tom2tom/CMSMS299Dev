{* wizard step 2 *}

{extends file='wizard_step.tpl'}
{block name='logic'}
  {$title = tr('title_step2')}
  {$current_step = '2'}
{/block}

{block name='javascript' append}
<script>
$(function() {
  var el = document.getElementById('freshen');
  if (el) {
   el.onclick = function() {
    return confirm('{addslashes("{tr('confirm_freshen')}")}');
   };
  } else {
   el = document.getElementById('upgrade');
   if (el) {
    el.onclick = function() {
     return confirm('{addslashes("{tr('confirm_upgrade')}")}');
    };
    $('#upgrade_info .link').css('cursor','pointer').on('click',function() {
     var e = '#' + $(this).data('content');
     $(e).dialog({
       minWidth: 500,
       modal: true
     });
    });
   }
  }
});
</script>
{/block}

{block name='contents'}
<div class="message blue icon">
  <i class="icon-folder message-icon"></i>
  <div class="content"><span class="heavy">{tr('prompt_dir')}:</span><br>{$dir}</div>
</div>

<div class="installer-form">
 {wizard_form_start}
  {$label=tr('install')}
  {if $nofiles}
  <div class="message blue">{tr('step2_nofiles')}</div>
  {/if}
  {if !isset($cmsms_info)}
  <div class="message blue">{tr('step2_nocmsms')}</div>
  {if !$install_empty_dir}
  <div class="message yellow">{tr('step2_install_dirnotempty2')}
    {if !empty($existing_files)}
    <ul>
      {foreach $existing_files as $one}
      <li>{$one}</li>
      {/foreach}
    </ul>
    {/if}
  </div>
  {/if}
  {else} {* it's an upgrade or freshen *}
   {if isset($cmsms_info.error_status)}
   {if $cmsms_info.error_status == 'too_old'}
     <div class="message red">{tr('step2_cmsmsfoundnoupgrade')}</div>
   {elseif $cmsms_info.error_status == 'same_ver'}
     <div class="message yellow">{tr('step2_errorsamever')}</div>
     <div class="message blue">{tr('step2_info_freshen',$cmsms_info.config.db_prefix)}</div>
   {elseif $cmsms_info.error_status == 'too_new'}
     <div class="message red">{tr('step2_errortoonew')}</div>
   {else}
     <div class="message red">{tr('step2_errorother')}</div>
   {/if}
   {else}
     <div class="message yellow">{tr('step2_cmsmsfound')}</div>
   {/if}

   <ul class="existing-info no-list no-padding">
    <li class="row">
      <div class="cell col-4">{tr('step2_pwd')}:</div>
      <div class="cell col-6"><span class="label">{$pwd}</span></div>
    </li>
    <li class="row">
      <div class="cell col-4">{tr('step2_version')}:</div>
      <div class="cell col-6"><span class="label">{$cmsms_info.version} <span class="emphatic">({$cmsms_info.version_name})</span></span></div>
    </li>
    <li class="row">
      <div class="cell col-4">{tr('step2_schemaver')}:</div>
      <div class="cell col-6"><span class="label">{$cmsms_info.schema_version}</span></div>
    </li>
    <li class="row">
      <div class="cell col-4">{tr('step2_installdate')}:</div>
      <div class="cell col-6"><span class="label">{$cmsms_info.mdate}</span></div>
    </li>
   </ul>

  {if isset($cmsms_info.noupgrade)}
  <div class="message yellow">{tr('step2_minupgradever',$config.min_upgrade_version)}</div>
  {else}
   {$label=tr('upgrade')} {if isset($upgrade_info)}
     <div class="message blue icon">
      <div class="content"><span class="heavy">{tr('step2_hdr_upgradeinfo')}</span><br>{tr('step2_info_upgradeinfo')}</div>
     </div>
     <ul id="upgrade_info" class="no-list">
     {foreach $upgrade_info as $ver => $data}
      <li class="row upgrade-ver">
       <div class="cell col-1">{$ver}</div>
       <div class="cell col-2">
        {if $data.changelog}
        <div class="label blue link" data-content="c{$data@iteration}"><i class="icon-info"></i> {tr('changelog_uc')}</div>
        {/if}
       </div>
       {if $data.readme}
       <div class="cell col-4">
        <div class="label green link" data-content="r{$data@iteration}"><i class="icon-info"></i> {tr('readme_uc')}</div>
       </div>
       {/if}
      </li>
     {/foreach}
     </ul>
   {/if}{*upgrade_info*}
  {/if}{*noupgrade*}
  {/if}{*cms_info*}

  <div id="bottom_nav">
    {if !isset($cmsms_info)}{*installing, no error*}
     <button type="submit" class="action-button positive" id="install" name="install">{if empty($lang_rtl)}<i class="icon-next-right"></i> {tr('install')}{else}{tr('install')} <i class="icon-next-left"></i>{/if}</button>
     {elseif !isset($cmsms_info.error_status)}
     <button type="submit" class="action-button positive" id="upgrade" name="upgrade">{if empty($lang_rtl)}<i class="icon-next-right"></i> {tr('upgrade')}{else}{tr('upgrade')} <i class="icon-next-left"></i>{/if}</button>
    {elseif $cmsms_info.error_status == 'same_ver'}
     <button type="submit" class="action-button positive" id="freshen" name="freshen">{if empty($lang_rtl)}<i class="icon-next-right"></i> {tr('freshen')}{else}{tr('freshen')} <i class="icon-next-left"></i>{/if}</button>
    {/if}
  </div>
 </form>
</div>

<div class="hidden">
  {if isset($upgrade_info)} {foreach $upgrade_info as $ver => $data}
   {if $data.readme}
  <div id="r{$data@iteration}" title="{tr('readme_uc')}: {$ver}">
    <div class="bigtext">{$data.readme}</div>
  </div>
   {/if}
   {if $data.changelog}
  <div id="c{$data@iteration}" title="{tr('changelog_uc')}: {$ver}">
    <div class="bigtext">{$data.changelog}</div>
  </div>
   {/if}
  {/foreach}{/if}
</div>
{/block}
