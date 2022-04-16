<form action="{$selfurl}" enctype="multipart/form-data" method="post">
<div class="hidden">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <input type="hidden" name="old_default_cms_lang" value="{$old_default_cms_lang}" />
</div>
  <div class="pageinput postgap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>

  <fieldset>
    <legend>{_la('lang_settings_legend')}:</legend>
    <div class="pageoverflow">
      {$t=_la('language')}<label class="pagetext" for="language">{$t}:</label>
      {cms_help 0='help' key='user_language' title=$t}
      <div class="pageinput">
      <select id="language" name="default_cms_language">
        {html_options options=$language_opts selected=$default_cms_language}     </select>
      </div>

      {$t=_la('date_format')}<label class="pagetext" for="dateformat">{$t}:</label>
      {cms_help 0='help' key='user_dateformat' title=$t}
      <div class="pageinput">
        <input type="text" id="dateformat" name="date_format" class="pagenb" size="20" maxlength="30" value="{$date_format}" />
      </div>

      {$t=_la('datetime_format')}<label class="pagetext" for="dtformat">{$t}:</label>
      {cms_help 0='help' key='user_datetimeformat' title=$t}
      <div class="pageinput">
        <input type="text" id="dtformat" name="datetime_format" class="pagenb" size="20" maxlength="30" value="{$datetime_format}" />
      </div>
    </div>
  </fieldset>

  <fieldset class="pregap">
    <legend>{_la('content_editor_legend')}:</legend>
    <div class="pageoverflow">
      {$t=_la('ce_navdisplay')}<label class="pagetext" for="ce_navdisplay">{$t}:</label>
      {cms_help 0='help' key='user_ce_navdisplay' title=$t}
      <div class="pageinput">
      <select id="ce_navdisplay" name="ce_navdisplay">
        {html_options options=$ce_navopts selected=$ce_navdisplay}    </select>
      </div>

      {$t=_la('adminindent')}<label class="pagetext" for="indent">{$t}:</label>
      {cms_help 0='help' key='user_indent' title=$t}
      <div class="pageinput">
        <input type="checkbox" id="indent" name="indent" class="pagenb"{if $indent} checked="checked"{/if} />
      </div>

      {$t=_la('defaultparentpage')}<label class="pagetext" for="parent_id">{$t}:</label>
      {cms_help 0='help' key='user_dfltparent' title=$t}
      <div class="pageinput">{$default_parent}</div>
    </div>
  </fieldset>

  <fieldset class="pregap">
    <legend>{_la('general_operation_settings')}:</legend>
    <div class="pageoverflow">
     {if empty($themes_opts)}
     <input type="hidden" name="admintheme" value="{$admintheme}" />
     {else}
      {$t=_la('admintheme')}<label class="pagetext" for="admintheme">{$t}:</label>
      {cms_help 0='help' key='user_admintheme' title=$t}
      <div class="pageinput">
      <select id="admintheme" name="admintheme">
         {html_options options=$themes_opts selected=$admintheme}    </select>
      </div>
     {/if}
     {if !empty($wysiwyg_opts)}
      {$t=_la('wysiwygtouse')}<label class="pagetext" for="wysiwygtype">{$t}:</label>
      {cms_help 0='help' key='settings_wysiwyg' title=$t}
      <div class="pageinput">
      {foreach $wysiwyg_opts as $i=>$one}
       <input type="radio" name="wysiwygtype" id="edt{$i}"{if !empty($one->themekey)} data-themehelp-key="{$one->themekey}"{/if} value="{$one->value}"{if !empty($one->checked)} checked="checked"{/if} />
       <label class="pagetext" for="edt{$i}">{$one->label}</label>
       {if !empty($one->mainkey)}
       <span class="cms_help" data-cmshelp-key="{$one->mainkey}" data-cmshelp-title="{$t} {$one->label}">{$helpicon}</span>
       {/if}{if !$one@last}<br />{/if}
      {/foreach}
      </div>

      {$t=_la('wysiwyg_theme')}<label class="pagetext" for="wysiwygtheme">{$t}:</label>
      {cms_help 0='help' key='user_wysiwygtheme' title=$t}
      <div class="pageinput">
        <input id="wysiwygtheme" type="text" name="wysiwygtheme" size="30" value="{$wysiwygtheme}" maxlength="40" />
      </div>
     {/if}
     {if !empty($syntax_opts)}

      {$t=_la('syntax_editor_touse')}<label class="pagetext" for="syntaxtype">{$t}:</label>
      {cms_help 0='help' key='settings_syntax' title=$t}
      <div class="pageinput">{$t=_la('about')}
      {foreach $syntax_opts as $i=>$one}
       <input type="radio" name="syntaxtype" id="edt{$i}"{if !empty($one->themekey)} data-themehelp-key="{$one->themekey}"{/if} value="{$one->value}"{if !empty($one->checked)} checked="checked"{/if} />
       <label class="pagetext" for="edt{$i}">{$one->label}</label>
       {if !empty($one->mainkey)}
       <span class="cms_help" data-cmshelp-key="{$one->mainkey}" data-cmshelp-title="{$t} {$one->label}">{$helpicon}</span>
       {/if}<br />
      {/foreach}
      </div>

      {$t=_la('syntax_editor_theme')}<label class="pagetext" for="syntaxtheme">{$t}:</label>
      {cms_help 0='help' key='user_syntaxtheme' title=$t}
      <div class="pageinput">
        <input id="syntaxtheme" type="text" name="syntaxtheme" size="30" value="{$syntaxtheme}" maxlength="40" />
      </div>
     {/if}

      {$t=_la('homepage')}<label class="pagetext" for="homepage">{$t}:</label>
      {cms_help 0='help' key='user_homepage' title=$t}
      <div class="pageinput">
      <select id="homepage" name="homepage">
        {html_options options=$home_opts selected=$homepage}     </select>
      </div>

      {$t=_la('admincallout')}<label class="pagetext" for="admincallout">{$t}:</label>
      {cms_help 0='help' key='user_admincallout' title=$t}
      <div class="pageinput">
        <input class="pagenb" id="admincallout" type="checkbox" name="bookmarks"{if $bookmarks} checked="checked"{/if} />
      </div>

      {$t=_la('hide_help_links')}<label class="pagetext" for="hidehelp">{$t}:</label>
      {cms_help 0='help' key='user_hidehelp' title=$t}
      <div class="pageinput">
        <input class="pagenb" id="hidehelp" type="checkbox" name="hide_help_links"{if $hide_help_links} checked="checked"{/if} />
      </div>
    </div>
  </fieldset>

  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
</form>
