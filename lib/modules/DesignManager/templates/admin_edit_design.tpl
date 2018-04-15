{form_start id="admin_edit_design"}
<input type="hidden" name="{$actionid}design" value="{$design->get_id()}" />
<input type="hidden" name="{$actionid}ajax" id="ajax" />

<fieldset>
  <div style="width: 49%; float: left;">
    <br />
    <div class="pageoverflow">
      <p class="pageinput">
        <button type="submit" name="{$actionid}submit" id="submitme" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
        <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
        <button type="submit" name="{$actionid}apply" id="applyme" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="design_name">{$mod->Lang('prompt_name')}</label>:
        {cms_help realm=$_module key2='help_design_name' title=$mod->Lang('prompt_name')}
      </p>
      <p class="pageinput">
        <input type="text" id="design_name" name="{$actionid}name" value="{$design->get_name()}" size="50" maxlength="90"/>
      </p>
    </div>
  </div>
  <div style="width: 49%; float: right;">
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="created">{$mod->Lang('prompt_created')}:</label>
       {cms_help realm=$_module key2='help_design_created' title=$mod->Lang('prompt_created')}
      </p>
      <p class="pageinput">{$design->get_created()|date_format:'%x %X'}</p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="modified">{$mod->Lang('prompt_modified')}:</label>
        {cms_help realm=$_module key2='help_design_modified' title=$mod->Lang('prompt_modified')}
      </p>
      <p class="pageinput">{$design->get_modified()|date_format:'%x %X'}</p>
    </div>
  </div>
</fieldset>

{tab_header name='templates' label=$mod->Lang('prompt_templates')}
{tab_header name='stylesheets' label=$mod->Lang('prompt_stylesheets')}
{tab_header name='tab_description' label=$mod->Lang('prompt_description')}
{tab_start name='templates'}
{include file='module_file_tpl:DesignManager;admin_edit_design_templates.tpl' scope='root'}
{tab_start name='stylesheets'}
{include file='module_file_tpl:DesignManager;admin_edit_design_stylesheets.tpl' scope='root'}
{tab_start name='tab_description'}
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="description">{$mod->Lang('prompt_description')}:</label>
      {cms_help realm=$_module key2=help_design_description title=$mod->Lang('prompt_description')}
    </p>
    <p class="pageinput">
      <textarea id="description" name="{$actionid}description" rows="5">{$design->get_description()}</textarea>
    </p>
  </div>
{tab_end}
{form_end}
<div style="display: none;">{strip}
  <div id="help_design_name" title="{$mod->Lang('help_design_name')}">{$mod->Lang('help_design_name')}</div>
{/strip}</div>

<script type="text/javascript">
{literal}//<![CDATA[
var __changed=0;
function set_changed() {
  __changed=1;
  console.debug('design is changed');
}
/*
function save_design() {
  var form = $('#admin_edit_design');
  var action = form.attr('action');

  $('#ajax').val(1);
  return $.ajax({
    url: action,
    data: form.serialize()
  });
}
*/
$(document).ready(function() {
  $('.sortable-list input[type="checkbox"]').hide();
  $(':input').on('change', function() {
    set_changed();
  });
  $('ul.available-items').on('click', 'li', function () {
    $(this).toggleClass('selected ui-state-hover');
  });
  $('#submitme,#applyme').on('click', function() {
    $('select.selall').attr('multiple','multiple');
    $('select.selall option').attr('selected','selected');
  });
});
{/literal}//]]>
</script>

