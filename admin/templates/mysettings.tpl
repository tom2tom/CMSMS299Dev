<form action="{$selfurl}{$urlext}" method="post">
  <input type="hidden" name="old_default_cms_lang" value="{$old_default_cms_lang}" />
  <div class="pageinput postgap">
    <button type="submit" name="submit" class="adminsubmit icon check">{lang('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
  <fieldset>
    <legend>{lang('lang_settings_legend')}:</legend>
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="language">{lang('language')}:</label>
        {cms_help key2='help_myaccount_language' title=lang('language')}
      </p>
      <p class="pageinput">
        <select id="language" name="default_cms_language">
          {html_options options=$language_opts selected=$default_cms_language}
        </select>
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="dateformat">{lang('date_format_string')}:</label>
        {cms_help key2='help_myaccount_dateformat' title=lang('date_format_string')}
      </p>
      <p class="pageinput">
        <input class="pagenb" size="20" maxlength="255" type="text" name="date_format_string" value="{$date_format_string}" />
      </p>
    </div>
  </fieldset>

  <fieldset>
    <legend>{lang('content_editor_legend')}:</legend>
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="wysiwyg">{lang('wysiwygtouse')}:</label>
        {cms_help key2='help_myaccount_wysiwyg' title=lang('wysiwygtouse')}
      </p>
      <p class="pageinput">
        <select id="wysiwyg" name="wysiwyg">
          {html_options options=$wysiwyg_opts selected=$wysiwyg}
        </select>
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
         <label for="syntaxh">{lang('syntaxhighlightertouse')}:</label>
         {cms_help key2='help_myaccount_syntax' title=lang('syntaxhighlightertouse')}
      </p>
      <p class="pageinput">
        <select id="syntaxh" name="syntaxhighlighter">
         {html_options options=$syntax_opts selected=$syntaxhighlighter}
        </select>
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="ce_navdisplay">{lang('ce_navdisplay')}:</label>
        {cms_help key2='help_myaccount_ce_navdisplay' title=lang('ce_navdisplay')}
      </p>
      <p class="pageinput">
        {$opts['']=lang('none')} {$opts['menutext']=lang('menutext')} {$opts['title']=lang('title')}
        <select id="ce_navdisplay" name="ce_navdisplay">
          {html_options options=$opts selected=$ce_navdisplay}
        </select>
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="indent">{lang('adminindent')}:</label>
        {cms_help key2='help_myaccount_indent' title=lang('adminindent')}
      </p>
      <p class="pageinput">
        <input class="pagenb" type="checkbox" id="indent" name="indent"{if $indent} checked="checked"{/if} />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="parent_id">{lang('defaultparentpage')}:</label>
        {cms_help key2='help_myaccount_dfltparent' title=lang('defaultparentpage')}
      </p>
      <p class="pageinput">{$default_parent}</p>
    </div>
    <!-- content display //-->
  </fieldset>

  <fieldset>
    <legend>{lang('admin_layout_legend')}:</legend>
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="admintheme">{lang('admintheme')}:</label>
        {cms_help key2='help_myaccount_admintheme' title=lang('admintheme')}
      </p>
      <p class="pageinput">
        <select id="admintheme" name="admintheme">
         {html_options options=$themes_opts selected=$admintheme}
        </select>
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="homepage">{lang('homepage')}:</label>
        {cms_help key2='help_myaccount_homepage' title=lang('homepage')}
      </p>
      <p class="pageinput">
        {$homepage}
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="admincallout">{lang('admincallout')}:</label>
        {cms_help key2='help_myaccount_admincallout' title=lang('admincallout')}
      </p>
      <p class="pageinput">
        <input class="pagenb" id="admincallout" type="checkbox" name="bookmarks"{if $bookmarks} checked="checked"{/if} />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="hidehelp">{lang('hide_help_links')}:</label>
        {cms_help key2='help_myaccount_hidehelp' title=lang('hide_help_links')}
      </p>
      <p class="pageinput">
        <input class="pagenb" id="hidehelp" type="checkbox" name="hide_help_links"{if $hide_help_links} checked="checked"{/if} />
      </p>
    </div>

    <div class="pageinput pregap">
      <button type="submit" name="submit" class="adminsubmit icon check">{lang('submit')}</button>
      <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
    </div>
</form>
