{* wizard step 2 *}

{extends file='wizard_step.tpl'}
{block name='logic'}
  {$title = 'title_step2'|tr}
  {$current_step = '2'}
{/block}

{block name='javascript' append}
<script type="text/javascript">
{literal}$(function() {
  $('#upgrade_info .link').css('cursor','pointer').click(function() {
   var e = '#'+$(this).data('content');
   $(e).dialog({
     minWidth: 500,
     modal: true
   });
  });
});{/literal}
</script>
{/block}

{block name='contents'}
<div class="message blue icon">
  <i class="icon-folder message-icon"></i>
  <div class="content"><span class="heavy">{'prompt_dir'|tr}:</span><br />{$dir}</div>
</div>

<div class="installer-form">
 {wizard_form_start}
  {$label='install'|tr}
  {if $nofiles}
  <div class="message blue">{'step2_nofiles'|tr}</div>
  {/if}
  {if !isset($cmsms_info)}
  <div class="message blue">{'step2_nocmsms'|tr}</div>
  {if !$install_empty_dir}
  <div class="message yellow">{'step2_install_dirnotempty2'|tr}
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
     <div class="message red">{'step2_cmsmsfoundnoupgrade'|tr}</div>
   {elseif $cmsms_info.error_status == 'same_ver'}
     <div class="message yellow">{'step2_errorsamever'|tr}</div>
     <div class="message blue">{'step2_info_freshen'|tr:$cmsms_info.config.db_prefix}</div>
   {elseif $cmsms_info.error_status == 'too_new'}
     <div class="message red">{'step2_errortoonew'|tr}</div>
   {else}
     <div class="message red">{'step2_errorother'|tr}</div>
   {/if}
   {else}
     <div class="message yellow">{'step2_cmsmsfound'|tr}</div>
   {/if}

   <ul class="existing-info no-list no-padding">
    <li class="row">
      <div class="cell col-4">{'step2_pwd'|tr}:</div>
      <div class="cell col-6"><span class="label">{$pwd}</span></div>
    </li>
    <li class="row">
      <div class="cell col-4">{'step2_version'|tr}:</div>
      <div class="cell col-6"><span class="label">{$cmsms_info.version} <span class="emphatic">({$cmsms_info.version_name})</span></span></div>
    </li>
    <li class="row">
      <div class="cell col-4">{'step2_schemaver'|tr}:</div>
      <div class="cell col-6"><span class="label">{$cmsms_info.schema_version}</span></div>
    </li>
    <li class="row">
      <div class="cell col-4">{'step2_installdate'|tr}:</div>
      <div class="cell col-6"><span class="label">{$cmsms_info.mdate}</span></div>
    </li>
   </ul>

  {if isset($cmsms_info.noupgrade)}
  <div class="message yellow">{'step2_minupgradever'|tr:$config.min_upgrade_version}</div>
  {else}
   {$label='upgrade'|tr} {if isset($upgrade_info)}
     <div class="message blue icon">
      <div class="content"><span class="heavy">{'step2_hdr_upgradeinfo'|tr}</span><br />{'step2_info_upgradeinfo'|tr}</div>
     </div>
     <ul id="upgrade_info" class="no-list">
     {foreach $upgrade_info as $ver => $data}
      <li class="row upgrade-ver">
       <div class="cell col-1">{$ver}</div>
       <div class="cell col-2">
        {if $data.changelog}
        <div class="label blue link" data-content="c{$data@iteration}"><i class="icon-info"></i> {'changelog_uc'|tr}</div>
        {/if}
       </div>
       {if $data.readme}
       <div class="cell col-4">
        <div class="label green link" data-content="r{$data@iteration}"><i class="icon-info"></i> {'readme_uc'|tr}</div>
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
     <button type="submit" class="action-button positive" id="install" name="install">{if empty($lang_rtl)}<i class="icon-next-right"></i> {'install'|tr}{else}{'install'|tr} <i class="icon-next-left"></i>{/if}</button>
     {elseif !isset($cmsms_info.error_status)}
     <button type="submit" class="action-button positive" id="upgrade" name="upgrade">{if empty($lang_rtl)}<i class="icon-next-right"></i> {'upgrade'|tr}{else}{'upgrade'|tr} <i class="icon-next-left"></i>{/if}</button>
    {elseif $cmsms_info.error_status == 'same_ver'}
     <button type="submit" class="action-button positive" id="freshen" name="freshen">{if empty($lang_rtl)}<i class="icon-next-right"></i> {'freshen'|tr}{else}{'freshen'|tr} <i class="icon-next-left"></i>{/if}</button>
    {/if}
  </div>
 </form>
</div>

<div class="hidden">
  {if isset($upgrade_info)} {foreach $upgrade_info as $ver => $data}
   {if $data.readme}
  <div id="r{$data@iteration}" title="{'readme_uc'|tr}: {$ver}">
    <div class="bigtext">{$data.readme}</div>
  </div>
   {/if}
   {if $data.changelog}
  <div id="c{$data@iteration}" title="{'changelog_uc'|tr}: {$ver}">
    <div class="bigtext">{$data.changelog}</div>
  </div>
   {/if}
  {/foreach}{/if}
</div>
{/block}
