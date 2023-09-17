{tab_header name='settings' label=_ld($_module,'settings')}
{tab_header name='example' label=_ld($_module,'example')}
{tab_start name ='settings'}
{if isset($info)}
<div class="pageinfo">{$info}</div><br>
{/if}
{if isset($warning)}
<div class="pagewarn">{$warning}</div><br>
{/if}
{if isset($form_start)}
{$form_start}
 <fieldset>
  <legend>{_ld($_module,'prompt_source')}</legend>
  {$t=_ld($_module,'label_sourceurl')}<label class="pagetext" for="fromurl">* {$t}:</label>
  {cms_help realm=$_module key2='help_sourceurl' title=$t}
  <div class="pageinput">
   <input type="text" id="fromurl" name="{$actionid}source_url" value="{$source_url}" size="70" required>
  </div>
  {$t=_ld($_module,'label_sourcesri')}<label class="pagetext" for="srihash">{$t}:</label>
  {cms_help realm=$_module key2='help_sourcesri' title=$t}
  <div class="pageinput">
   <input type="text" id="srihash" name="{$actionid}source_sri" value="{$source_sri}" size="98">
  </div>
  {$t=_ld($_module,'label_skin')}<label class="pagetext" for="skinurl">{$t}:</label>
  {cms_help realm=$_module key2='help_skin' title=$t}
  <div class="pageinput">
   <input type="text" id="skinurl" name="{$actionid}skin_url" value="{$skin_url}" size="70">
  </div>
  <div class="pageinput pregap">
   <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{_la('apply')}</button>
   <button  type="submit" name="{$actionid}cancel" class="adminsubmit icon undo" formnovalidate>{_la('cancel')}</button>
  </div>
 </fieldset>
</form>
{/if}
 <fieldset>
  <legend>{_ld($_module,'prompt_profiles')}</legend>
{if $profiles}
  <table class="pagetable">
    <thead>
      <tr>
        <th>{_ld($_module,'prompt_name')}</th>
        <th class="pageicon">{*edit*}</th>
      </tr>
    </thead>
    <tbody>
      {foreach $profiles as $profile}
        {cms_action_url action='editprofile' profile=$profile.name assign='edit_url'}
      <tr>
        <td><a href="{$edit_url}" title="{_ld($_module,'title_edit_profile')}">{$profile.label}</a></td>
        <td><a href="{$edit_url}">{admin_icon icon='edit.gif' alt=_ld($_module,'title_edit_profile')}</a></td>
      </tr>
      {/foreach}
    </tbody>
  </table>
{else}
 {_ld($_module,'none')}
{/if}
 </fieldset>
{tab_start name='example'}
{capture assign='value'}<p><img src="{uploads_url}/images/uTiny-demo.png" style="float:right;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris et ipsum id ante dignissim cursus sollicitudin eget erat. Quisque sit amet arcu urna. Nulla ultricies lacinia sapien, sed aliquam quam feugiat in. Donec consectetur pretium congue. Integer aliquam facilisis lacus, ut facilisis erat pharetra eget. Duis dapibus posuere nunc, id gravida massa pellentesque ac.</p>{/capture}
{cms_textarea forcemodule='MicroTiny' name='mt_example' id='mt_example' enablewysiwyg=1 rows=10 columns=80 value=$value}
{tab_end}
