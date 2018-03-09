<script type="text/javascript">
{literal}//<![CDATA[
function handle_change() {
  var val = $('#fld_type').val();
  if(val === 'dropdown') {
    $('#area_maxlen').hide('slow');
    $('#area_options').show('slow');
  } else if(val === 'checkbox' || val === 'file' || val === 'linkedfile') {
    $('#area_maxlen').hide('slow');
    $('#area_options').hide('slow');
  } else {
    $('#area_maxlen').show('slow');
    $('#area_options').hide('slow');
  }
}
$(document).ready(function() {
  handle_change();
  $('#fld_type').change(handle_change);
  $('#{$actionid}cancel').click(function() {
    $(this).closest('form').attr('novalidate','novalidate');
  });
});
{/literal}//]]>
</script>

<h3>{$title}</h3>
{$startform}{$hidden|default:''}
<div class="pageoverflow">
  <p class="pagetext">
    <label for="fld_name">*{$nametext}:</label>
    {cms_help realm=$_module key='help_fielddef_name' title=$nametext}
  </p>
  <p class="pageinput">
    <input type="text" name="{$actionid}name" id="fld_name" value="{$name}" size="30" maxlength="255" required />
  </p>
</div>
{if $showinputtype eq true}
<div class="pageoverflow">
  <p class="pagetext">
    <label for="fld_type">*{$typetext}:</label>
    {cms_help realm=$_module key='help_fielddef_type' title=$typetext}
  </p>
  <p class="pageinput">
    <select name="{$actionid}type" id="fld_type">
     {html_options options=$fieldtypes selected=$type}
    </select>
  </p>
</div>
{else}
<input type="hidden" name="{$actionid}type" id="fld_type" value="{$type}" />
{/if}
<div class="pageoverflow" id="area_options">
  <p class="pagetext">
    <label for="fld_options">{$mod->Lang('options')}:</label>
    {cms_help realm=$_module key='help_fielddef_options' title=$mod->Lang('options')}
  </p>
  <p class="pageinput">
    <textarea name="{$actionid}options" id="fld_options" rows="5" cols="80">{$options}</textarea>
  </p>
</div>
<div class="pageoverflow" id="area_maxlen">
  <p class="pagetext">
    <label for="fld_maxlen">{$maxlengthtext}:</label>
    {cms_help realm=$_module key='help_fielddef_maxlen' title=$maxlengthtext}
  </p>
  <p class="pageinput">
    <input type="text" name="{$actionid}max_length" id="fld_maxlen" value="{$max_length}" size="5" maxlength="5" /><br />{$info_maxlength}
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="fld_public">{$userviewtext}:</label>
    {cms_help realm=$_module key='help_fielddef_public' title=$userviewtext}
  </p>
  <input type="hidden" name="{$actionid}public" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}public" id="fld_public" value="1"{if $public==1} checked="checked"{/if} />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">&nbsp;</p>
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
    <button type="submit" name="{$actionid}cancel" id="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </p>
</div>
{$endform}
