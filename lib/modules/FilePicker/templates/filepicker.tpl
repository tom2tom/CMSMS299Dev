<!doctype html>
<html lang="en" data-cmsfp-inst="{$inst}">
 <head>
  <meta charset="utf-8">
  <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
  <title>{$mod->Lang('filepickertitle')}</title>
  <base href="{$topurl}" />
  {$headercontent|default:''}
 </head>
 <body class="cmsms-filepicker">
 <div id="full-fp">
  <div class="filepicker-navbar">
    <div id="filepicker-progress" class="filepicker-breadcrumb">
      <span class="filepicker-breadcrumb-text" title="{$mod->Lang('youareintext')}:"><i class="cmsms-fp-folder-open filepicker-icon"></i> {$cwd_for_display}</span>
      <p id="filepicker-progress-text" style="display: none;"></p>
      <span class="js-trigger filepicker-button level-up"><i class="cmsms-fp-level-up"></i></span>
    </div>
    <div class="filepicker-navbar-inner">
          <span class="js-trigger view-list filepicker-button" title="{$mod->Lang('switchlist')}"><i class="cmsms-fp-th-list"></i></span>
          <span class="js-trigger view-grid filepicker-button active" title="{$mod->Lang('switchgrid')}"><i class="cmsms-fp-th"></i></span>
          {if $profile->can_mkdir}
          <span class="filepicker-button make-dir filepicker-cmd" data-cmd="mkdir" title="{$mod->Lang('create_dir')}">
            <i class="cmsms-fp-folder-add"></i>
          </span>
          {/if}
          {if $profile->can_upload}
          <span class="filepicker-button upload-file btn-file">
           <i class="cmsms-fp-upload"></i> {$mod->Lang('upload')}
           <input id="filepicker-file-upload" type="file" multiple="" title="{$mod->Lang('select_upload_files')}" />
          </span>
          {/if}
      {$type=$profile->type|default:'ANY'}{if $type == 'ANY'}
          <span class="filepicker-option-title">{$mod->Lang('filterby')}:&nbsp;</span>
          <span class="js-trigger filepicker-button" data-fb-type='image' title="{$mod->Lang('switchimage')}"><i class="cmsms-fp-picture"></i></span>&nbsp;
          <span class="js-trigger filepicker-button" data-fb-type='video' title="{$mod->Lang('switchvideo')}"><i class="cmsms-fp-film"></i></span>&nbsp;
          <span class="js-trigger filepicker-button" data-fb-type='audio' title="{$mod->Lang('switchaudio')}"><i class="cmsms-fp-music"></i></span>&nbsp;
          <span class="js-trigger filepicker-button" data-fb-type='archive' title="{$mod->Lang('switcharchive')}"><i class="cmsms-fp-zip"></i></span>&nbsp;
          <span class="js-trigger filepicker-button" data-fb-type='file' title="{$mod->Lang('switchfiles')}"><i class="cmsms-fp-file"></i></span>&nbsp;
          <span class="js-trigger filepicker-button active" data-fb-type='reset' title="{$mod->Lang('switchreset')}"><i class="cmsms-fp-reorder"></i></span>
      {/if}
    </div>
  </div>
  <div class="filepicker-container">
    <div id="filelist">
      <ul class="filepicker-list" id="filepicker-items">
        <li class="filepicker-item filepicker-item-heading">
          <div class="filepicker-thumb no-background">&nbsp;</div>
          <div class="filepicker-file-information">
            <h4 class="filepicker-file-title">{$mod->Lang('name')}</h4>
          </div>
          <div class="filepicker-file-details">
            <span class="filepicker-file-dimension">
            {$mod->Lang('dimension')}
            </span>
            <span class="filepicker-file-size">
            {$mod->Lang('size')}
            </span>
{*            <span class="filepicker-file-ext">
            {$mod->Lang('type')}
            </span>
*}
          </div>
        </li>
        {foreach $files as $file}
        {strip}<li class="filepicker-item{if $file.isdir} dir{else} {$file.filetype}{/if}" title="{if $file.isdir}{$mod->Lang('changedir')}: {/if}{$file.name}" data-fb-ext='{$file.ext}' data-fb-fname="{$file.name}">
          <div class="filepicker-thumb{if ($profile->show_thumbs && isset($file.thumbnail) && $file.thumbnail != '') || $file.isdir || ($profile->show_thumbs && $file.is_thumb)} no-background{/if}">
            {if !$file.isdir && $profile->can_delete && !$file.isparent}
            <span class="filepicker-delete filepicker-cmd" data-cmd="del" title="{$mod->Lang('delete')}">
            <i class="cmsms-fp-delete"></i>
            </span>{/if}
            {if $file.isdir}
              <a class="icon-no-thumb" href="{$file.chdir_url}" title="{if $file.isdir}{$mod->Lang('changedir')}: {/if}{$file.name}"><i class="cmsms-fp-folder-close"></i></a>
            {elseif $profile->show_thumbs && isset($file.thumbnail) && $file.thumbnail != ''}
              <a class="filepicker-file-action js-trigger-insert" href="{$file.relurl}" title="{$mod->Lang('displayit',$file.name)}">{$file.thumbnail}</a>
            {elseif $profile->show_thumbs && $file.is_thumb}
              <a class="filepicker-file-action js-trigger-insert" href="{$file.relurl}" title="{$mod->Lang('displayit',$file.name)}"><img src="{$file.fullurl}" alt="{$file.name}" /></a>
            {else}
              <a class="filepicker-file-action js-trigger-insert icon-no-thumb" title="{$file.name}" href="{$file.relurl}">
              {if $file.filetype == 'IMAGE'}
                <i class="cmsms-fp-picture"></i>
              {elseif $file.filetype == 'VIDEO'}
                <i class="cmsms-fp-video"></i>
              {elseif $file.filetype == 'AUDIO'}
                <i class="cmsms-fp-music"></i>
              {elseif $file.filetype == 'ARCHIVE'}
                <i class="cmsms-fp-zip"></i>
              {else}
                <i class="cmsms-fp-file"></i>
              {/if}
              </a>
            {/if}
          </div>
          <div class="filepicker-file-information">
            <h4 class="filepicker-file-title">
              {if $file.isdir}
                <a class="filepicker-dir-action" href="{$file.chdir_url}" title="{if $file.isdir}{$mod->Lang('changedir')}: {/if}{$file.name}">{$file.name}</a>
              {else}
                <a class="filepicker-file-action js-trigger-insert" href="{$file.relurl}" title="{if $file.isdir}{$mod->Lang('changedir')}: {/if}{$file.name}" data-fb-filetype='{$file.filetype}'>{$file.name}</a>
              {/if}
            </h4>
          </div>
          <div class="filepicker-file-details visuallyhidden">
            <span class="filepicker-file-dimension">{$file.dimensions}</span>
            <span class="filepicker-file-size">{if !$file.isdir}{$file.size}{/if}</span>
            <span class="filepicker-file-ext">{if $file.isdir}dir{/if}</span>
            {if !$file.isdir && $profile->can_delete && !$file.isparent}
            <span class="filepicker-delete filepicker-cmd" data-cmd="del" title="{$mod->Lang('delete')}">
            <i class="cmsms-fp-delete"></i>
            </span>
            {/if}
          </div>
        {/strip}</li>
        {/foreach}
      </ul>
    </div>
  </div>
 </div>
{*popup dialog*}
 <div id="mkdir_dlg" title="{$mod->Lang('title_mkdir')}" style="display:none;" data-oklbl="{$mod->Lang('ok')}">
  <div class="dlg-options">
    <label for="fld_mkdir">{$mod->Lang('name')}:</label> <input type="text" id="fld_mkdir" size="40" />
  </div>
 </div>
 </body>
{$bottomcontent|default:''}
</html>
