<h3>{lang_by_realm('layout','prompt_delete_stylesheet')} {$css->get_name()} ({$css->get_id()})</h3>

<form action="{$selfurl}{$urlext}" method="post">
<input type="hidden" name="css" value="{$css->get_id()}" />

<fieldset>
  <div style="width: 49%; float: left;">
    <div class="pageoverflow">
      <p class="pagetext">
      <label for="css_name">*{lang_by_realm('layout','prompt_name')}:</label>
      </p>
      <p class="pageinput">
        <input id="css_name" type="text" readonly="readonly" size="50" maxlength="50" value="{$css->get_name()}"/>
      </p>
    </div>
  </div>{* column *}

  <div style="width: 49%; float: right;">
    {if $css->get_id()}
    <div class="pageoverflow">
      <p class="pagetext">
      <label for="css_created">{lang_by_realm('layout','prompt_created')}:</label>
      </p>
      <p class="pageinput">
        <input type="text" id="css_created" value="{$css->get_created()|date_format:'%x %X'}" readonly="readonly"/>
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
      <label for="css_modified">{lang_by_realm('layout','prompt_modified')}:</label>
      </p>
      <p class="pageinput">
        <input type="text" id="css_modified" value="{$css->get_modified()|cms_date_format}" readonly="readonly"/>
      </p>
    </div>
    {/if}
  </div>{* column *}
</fieldset>
<div class="pageoverflow pregap">
  <p class="pageinput">
    <input id="check1" type="checkbox" name="check1" value="1">&nbsp;<label for="check1">{lang_by_realm('layout','confirm_delete_css_1')}</label>
    <br />
    <input id="check2" type="checkbox" name="check2" value="1">&nbsp;<label for="check2">{lang_by_realm('layout','confirm_delete_css_2')}</label>
  </p>
</div>
<div class="pageinput pregap">
  <button type="submit" name="submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
</form>
