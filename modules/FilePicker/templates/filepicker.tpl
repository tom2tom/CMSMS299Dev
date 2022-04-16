{if empty($getcontent)}
<!DOCTYPE html>
<html lang="en"{if !empty($rtl)} dir="rtl"{/if} data-cmsfp-inst="{$inst}">
 <head>
  <meta charset="utf-8">
  <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
  <title>{_ld($_module,'filepickertitle')}</title>
  <base href="{$topurl}" />
  {$headercontent|default:''}
 </head>
 <body id="fp-body">
{else}
<div class="cmsfp cmsfp_dlg">
{/if}
  {if $profile->can_upload}
  <div id="fp-progress">
    <span id="fp-progress-text"></span>
  </div>
  <div id="fp-dropzone" title="{_ld($_module,'droplong')}">
    <span>{_ld($_module,'dropshort')}</span>
  </div>
  {/if}
  <div id="fp-navbar">
    {strip}<div id="fp-breadcrumb">
      <span id="fp-breadcrumb-text" title="{_ld($_module,'youareintext')}"><i class="ifm-folder-open fp-icon"></i> {$cwd_for_display}</span>
    </div>
    <div id="fp-navbar-inner">
     <div>{* boxchild *}
      <span class="fp-button{if !$cwd_up} disabled{/if}"{if $cwd_up} id="level-up" title="{_ld($_module,'displayup')}" data-chdir="m1__enc={$cwd_updata}"{/if}>
        <i class="ifm-level-up-left"></i>
      </span>
      {$do=$profile->can_mkdir}
      <span class="fp-button make-dir {if $do}filepicker-cmd{else}disabled{/if}"{if $do} data-cmd="mkdir" title="{_ld($_module,'create_dir')}"{/if}">
        <i class="ifm-folder-add"></i>
      </span>
      {$do=$profile->can_upload}
      <label class="fp-button upload-file{if !$do} disabled{/if}" {if $do}id="btn-upload" for="fp-file-upload" title="{_ld($_module,'select_upload_files')}"{else}style="pointer-events:none"{/if}>
        <i class="ifm-upload"></i>
      </label>
    {if $do}
      <input type="file" id="fp-file-upload" class="visuallyhidden" name="fp-upload" multiple="" />
    {/if}
    </div>
    <div id="fp-type-filter">{* boxchild *}
    {if empty($type)}{$type=$profile->typename|default:'ANY'}{/if}{if $type == 'ANY'}
      <span id="fp-filter-title">{_ld($_module,'filterby')}:</span>
      <span class="js-trigger fp-button" data-fb-type='IMAGE' title="{_ld($_module,'switchimage')}"><i class="ifm-image"></i></span>
      <span class="js-trigger fp-button" data-fb-type='VIDEO' title="{_ld($_module,'switchvideo')}"><i class="ifm-video"></i></span>
      <span class="js-trigger fp-button" data-fb-type='AUDIO' title="{_ld($_module,'switchaudio')}"><i class="ifm-audio"></i></span>
      <span class="js-trigger fp-button" data-fb-type='ARCHIVE' title="{_ld($_module,'switcharchive')}"><i class="ifm-archive"></i></span>
      <span class="js-trigger fp-button" data-fb-type='FILE' title="{_ld($_module,'switchfiles')}"><i class="ifm-file"></i></span>
      <span class="js-trigger fp-button" data-fb-type='RESET' title="{_ld($_module,'switchreset')}"><i class="ifm-all"></i></span>
    {/if}
    </div>{* filter elements child *}
    {/strip}</div>{* fp-navbar-inner *}
  </div>{* fp-navbar *}
  <div id="fp-wrap">
   <table id="fp-list">
    <thead>
     <tr class="fpitem header">{* title-row *}
       <th></th>
       <th class="filename">{_ld($_module,'name')}</th>
       <th>{_ld($_module,'dimension')}</th>
       <th>{_ld($_module,'size')}</th>
       <th></th>
     </tr>
    </thead>
    <tbody>
   {foreach $files as $file}{strip}
     <tr class="fpitem {if $file.isdir}dir{else}{$file.filetype}{/if}"{if !$file.isdir} data-fb-ext='{$file.ext}'{/if} data-fb-fname="{$file.name}">
       <td class="fp-thumb{if ($profile->show_thumbs && isset($file.thumbnail) && $file.thumbnail != '') || $file.isdir || ($profile->show_thumbs && $file.is_thumb)} no-background{/if}">
      {if $file.isdir}
        <a class="filepicker-dir-action icon-no-thumb" href="{$file.chdir_url}" title="{_ld($_module,'changedir',$file.name)}">
          <i class="ifm-folder"></i>
        </a>
      {elseif !empty($file.is_small)}
        <a class="filepicker-file-action js-trigger-insert" href="{$file.relurl}" title="{_ld($_module,'chooseit',$file.name)}">
          <img src="{$file.fullurl}" alt="{$file.name}" title="{_ld($_module,'chooseit',$file.name)}" />
        </a>
      {elseif !empty($file.is_svg)}
        <a class="filepicker-file-action js-trigger-insert" href="{$file.relurl}" title="{_ld($_module,'chooseit',$file.name)}">
          <img class="svgimg" src="{$file.fullurl}" alt="{$file.name}" title="{_ld($_module,'chooseit',$file.name)}" />
        </a>
      {elseif $profile->show_thumbs && !empty($file.thumbnail)}
        <a class="filepicker-file-action js-trigger-insert" href="{$file.relurl}" title="{_ld($_module,'chooseit',$file.name)}">
          <img src="thumb_{$file.name}" alt="{$file.name}" title="{_ld($_module,'chooseit',$file.name)}" />
        </a>
      {elseif $profile->show_thumbs && $file.is_thumb}
        <a class="filepicker-file-action js-trigger-insert" href="{$file.relurl}" title="{_ld($_module,'chooseit',$file.name)}">
          <img src="{$file.fullurl}" alt="{$file.name}" title="{_ld($_module,'displayit',$file.name)}" />
        </a>
      {else}
        <a class="filepicker-file-action js-trigger-insert icon-no-thumb" href="{$file.relurl}" title="{_ld($_module,'chooseit',$file.name)}">
        {if $file.filetype == 'IMAGE'}
          <i class="ifm-image"></i>
        {elseif $file.filetype == 'VIDEO'}
          <i class="ifm-video"></i>
        {elseif $file.filetype == 'AUDIO'}
          <i class="ifm-audio"></i>
        {elseif $file.filetype == 'ARCHIVE'}
          <i class="ifm-archive"></i>
        {else}
          <i class="ifm-file"></i>{*TODO other recognized file-types*}
        {/if}
        </a>
      {/if}
       </td>{* fp-thumb *}
       <td class="filename">
      {if $file.isdir}
        <a class="filepicker-dir-action" href="{$file.chdir_url}" title="{_ld($_module,'changedir',$file.name)}">{$file.name}</a>
      {else}
        <a class="filepicker-file-action js-trigger-insert" href="{$file.relurl}" title="{_ld($_module,'chooseit',$file.name)}" data-fb-filetype="{$file.filetype}">{$file.name}</a>
      {/if}
       </td>
       <td>{if !$file.isdir}{$file.dimensions}{/if}</td>{* TODO only for non-scalable image files *}
       <td>{if !$file.isdir}{$file.size}{/if}</td>
     {if $profile->can_delete && !$file.isparent}
       <td class="fp-delete filepicker-cmd" data-cmd="del" title="{_ld($_module,'deleteit',$file.name)}">
         <i class="ifm-delete"></i>
       </td>
     {else}
       <td></td>
     {/if}
     </tr>{* fpitem *}
{/strip}{/foreach}
    </tbody>
   </table>{* fp-list *}
  </div>{* fp-wrap *}
  {if $profile->can_mkdir}{* popup dialog for mkdir *}
  <div id="mkdir_dlg" title="{_ld($_module,'mkdir')}" style="display:none;">
   <div class="dlg-options">
    <label for="fld_mkdir">{_ld($_module,'name')}:</label> <input type="text" id="fld_mkdir" />
   </div>
  </div>
  {/if}
{if empty($getcontent)}
  {$bottomcontent|default:''}
 </body>
</html>
{else}
</div>
{/if}
