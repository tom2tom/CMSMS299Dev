<!DOCTYPE html>
<html lang="en"{if !empty($rtl)} dir="rtl"{/if} data-cmsfp-inst="{$inst}">
 <head>
  <meta charset="utf-8">
  <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
  <title>{_ld($_module,'filepickertitle')}</title>
  <base href="{$topurl}" />
  {$headercontent|default:''}
 </head>
 <body>
  {if $profile->can_upload}
  <div id="fp-progress">
    <span id="fp-progress-text"></span>
  </div>
  <div id="fp-dropzone" title="{_ld($_module,'droplong')}">{* TODO better layout for dropzone *}
    <span>{_ld($_module,'dropshort')}</span>
  </div>
  {/if}
  <div id="fp-navbar">
    {strip}<div id="fp-breadcrumb">
      <span id="fp-breadcrumb-text" title="{_ld($_module,'youareintext')}"><i class="fifp-folder-open fp-icon"></i> {$cwd_for_display}</span>
    </div>
    <div id="fp-navbar-inner">
     <div>{* boxchild *}
{* DEBUG ONLY <span id="view-list" class="js-trigger fp-button" title="{_ld($_module,'switchlist')}">&#9776;</span>
      <span id="view-grid" class="js-trigger fp-button" title="{_ld($_module,'switchgrid')}">&#x25A6;</span> *}
      <span id="level-up" class="fp-button{if !$cwd_up} disabled{/if}"{if $cwd_up} title="{_ld($_module,'displayup')}"{/if}>
        <i class="fifp-level-up"></i>
      </span>
      <span class="make-dir fp-button {if $profile->can_mkdir}filepicker-cmd{else}disabled{/if}"{if $profile->can_mkdir} data-cmd="mkdir" title="{_ld($_module,'create_dir')}"{/if}">
        <i class="fifp-folder-add"></i>
      </span>
    {if $profile->can_upload}
      <input type="file" id="fp-file-upload" class="visuallyhidden" name="fp-upload" multiple="" />
    {/if}
      <label class="fp-button upload-file btn-file{if !$profile->can_upload} disabled{/if}"{if $profile->can_upload} for="fp-file-upload" title="{_ld($_module,'select_upload_files')}"{/if}>
         <i class="fifp-upload"></i>
      </label>
    </div>
    {$type=$profile->typename|default:'ANY'}{if 1}{* DEBUG $type == 'ANY' *}
      <div id="fp-type-filter">{* boxchild *}
       <span id="fp-filter-title">{_ld($_module,'filterby')}:</span>
       <span class="js-trigger fp-button" data-fb-type='IMAGE' title="{_ld($_module,'switchimage')}"><i class="fifp-image"></i></span>
       <span class="js-trigger fp-button" data-fb-type='VIDEO' title="{_ld($_module,'switchvideo')}"><i class="fifp-video"></i></span>
       <span class="js-trigger fp-button" data-fb-type='AUDIO' title="{_ld($_module,'switchaudio')}"><i class="fifp-audio"></i></span>
       <span class="js-trigger fp-button" data-fb-type='ARCHIVE' title="{_ld($_module,'switcharchive')}"><i class="fifp-zip"></i></span>
       <span class="js-trigger fp-button" data-fb-type='FILE' title="{_ld($_module,'switchfiles')}"><i class="fifp-file"></i></span>
       <span class="js-trigger fp-button" data-fb-type='RESET' title="{_ld($_module,'switchreset')}"><i class="fifp-all"></i></span>
   </div>{* filter elements child *}
    {/if}
    {/strip}</div>{* fp-navbar-inner *}
  </div>{* fp-navbar *}
  <div id="fp-list">
   <div class="fpitem header inlist">{* list view title-row *}
    <div>&nbsp;</div>
    <div class="filename">{_ld($_module,'name')}</div>
    <div>{_ld($_module,'dimension')}</div>
    <div>{_ld($_module,'size')}</div>
    <div>&nbsp;</div>
   </div>
  {foreach $files as $file}{strip}
   <div class="fpitem {if $file.isdir}dir{else}{$file.filetype}{/if}"{if !$file.isdir} data-fb-ext='{$file.ext}'{/if} data-fb-fname="{$file.name}">
    <div class="fp-thumb{if ($profile->show_thumbs && isset($file.thumbnail) && $file.thumbnail != '') || $file.isdir || ($profile->show_thumbs && $file.is_thumb)} no-background{/if}">
    {if $file.isdir}
      <a class="icon-no-thumb" href="{$file.chdir_url}" title="{_ld($_module,'changedir',$file.name)}">
        <i class="fifp-folder-close"></i>
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
        <i class="fifp-image"></i>
      {elseif $file.filetype == 'VIDEO'}
        <i class="fifp-video"></i>
      {elseif $file.filetype == 'AUDIO'}
        <i class="fifp-audio"></i>
      {elseif $file.filetype == 'ARCHIVE'}
        <i class="fifp-zip"></i>
      {else}
        <i class="fifp-file"></i>
      {/if}
      </a>
    {/if}
    {if $profile->can_delete && !$file.isparent}{* TODO check parenthood *}
      <span class="ingrid fp-delete filepicker-cmd" data-cmd="del" title="{_ld($_module,'deleteit',$file.name)}">
        <i class="fifp-delete"></i>
      </span>
    {/if}
    </div>{* fp-thumb *}
    <div class="filename">
    {if $file.isdir}
     <a class="filepicker-dir-action" href="{$file.chdir_url}" title="{_ld($_module,'changedir',$file.name)}">{$file.name}</a>
    {else}
     <a class="filepicker-file-action js-trigger-insert" href="{$file.relurl}" title="{_ld($_module,'chooseit',$file.name)}" data-fb-filetype="{$file.filetype}">{$file.name}</a>
    {/if}
    </div>
    <div class="inlist">{if !$file.isdir}{$file.dimensions}{/if}</div>{* TODO only for non-scalable image files *}
    <div class="inlist">{if !$file.isdir}{$file.size}{/if}</div>
    {if $profile->can_delete && !$file.isparent}
     <div class="inlist fp-delete filepicker-cmd" data-cmd="del" title="{_ld($_module,'deleteit',$file.name)}">
       <i class="fifp-delete"></i>
     </div>
    {else}
     <div class="inlist"></div>
    {/if}
   </div>{* fpitem *}
{/strip}{/foreach}
  </div>{* fp-list *}
  {if $profile->can_mkdir}{* popup dialog for mkdir *}
  <div id="mkdir_dlg" title="{_ld($_module,'mkdir')}" style="display:none;">
   <div class="dlg-options">
    <label for="fld_mkdir">{_ld($_module,'name')}:</label> <input type="text" id="fld_mkdir" />
   </div>
  </div>
  {/if}
  {$bottomcontent|default:''}
 </body>
</html>
