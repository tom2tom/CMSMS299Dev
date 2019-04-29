<h3>{lang_by_realm('layout','prompt_copy_stylesheet')}</h3>

<form action="{$selfurl}{$urlext}" method="post">
<input type="hidden" name="css" value="{$actionparams.css}" />
<fieldset>
  <legend>{lang_by_realm('layout','prompt_source_css')}:</legend>
  <div style="width: 49%; float: left;">
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="css_name">{lang_by_realm('layout','prompt_name')}:</label>
      </p>
      <p class="pageinput">
        <input id="css_name" type="text" size="50" maxlength="50" value="{$css->get_name()}" readonly/>
      </p>
    </div>
{*    <div class="pageoverflow">
      <p class="pagetext">
        <label for="css_name">{lang_by_realm('layout','prompt_designs')}:</label>
      </p>
      <p class="pageinput" style="max-height: 5em; overflow: auto;">
      {foreach $css->get_designs() as $design_id} DISABLED
        {$design_names[$design_id]}<br/>
      {/foreach}
      </p>
    </div>
*}
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="css_name">{lang_by_realm('layout','prompt_description')}:</label>
      </p>
      <p class="pageinput" style="max-height: 5em; overflow: auto;">{$css->get_description()|summarize}</p>
    </div>
  </div>{* column *}

  <div style="width: 49%; float: right;">
  {if $css->get_id()}
    <div class="pageoverflow">
      <p class="pagetext">{lang_by_realm('layout','prompt_created')}:</p>
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

<div class="pageinfo">{lang_by_realm('layout','info_copy_css')}</div>

<fieldset>
  <legend>{lang_by_realm('layout','prompt_dest_css')}:</legend>
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="css_destname">*{lang_by_realm('layout','prompt_name')}:</label>
    </p>
    <p class="pageinput">
      <input type="text" id="css_destname" name="new_name" value="{$new_name|default:''}" size="50" maxlength="50"/>
    </p>
  </div>
</fieldset>
<div class="pageinput pregap">
  <button type="submit" name="submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  <button type="submit" name="apply" class="adminsubmit icon apply">{lang('apply')}</button>
</div>
</form>
