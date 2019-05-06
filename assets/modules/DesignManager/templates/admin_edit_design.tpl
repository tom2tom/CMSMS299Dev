{form_start id="admin_edit_design"}
<input type="hidden" name="{$actionid}design" value="{$design->get_id()}" />
<input type="hidden" name="{$actionid}ajax" id="ajax" />

<fieldset>
 <div class="rowbox expand">
  <div class="boxchild">
    <div class="pageinput postgap">
      <button type="submit" name="{$actionid}submit" id="submitme" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
      <button type="submit" name="{$actionid}apply" id="applyme" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="design_name">{$mod->Lang('prompt_name')}</label>:
        {cms_help realm=$_module key2='help_design_name' title=$mod->Lang('prompt_name')}
      </p>
      <p class="pageinput">
        <input type="text" id="design_name" name="{$actionid}name" value="{$design->get_name()}" size="40" maxlength="64"/>
      </p>
    </div>
  </div>{*boxchild*}
  <div class="boxchild">
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
  </div>{*boxchild*}
 </div>{*rowbox*}

 <div class="rowbox expand">
   <div class="boxchild">
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="description">{$mod->Lang('prompt_description')}:</label>
        {cms_help realm=$_module key2=help_design_description title=$mod->Lang('prompt_description')}
      </p>
      <p class="pageinput">
        <textarea id="description" name="{$actionid}description" style="width:40em;min-height:2em;">{$design->get_description()}</textarea>
      </p>
    </div>
  </div>{*boxchild*}
 </div>{*rowbox*}
</fieldset>

{tab_header name='templates' label=$mod->Lang('prompt_templates')}
{tab_header name='stylesheets' label=$mod->Lang('prompt_stylesheets')}

{tab_start name='templates'}
{if $all_templates}
<p class="pageinfo">{$mod->Lang('info_edittemplate_templates_tab')}</p>
<fieldset>
  <legend>{$mod->Lang('attached_templates')}</legend>
  <table class="selected draggable">
  <tbody class="selected rsortable">
    <tr class="placeholder"><td>{$placeholder}</td></tr>
    {foreach $all_templates as $id => $name}{if $design_templates && in_array($id,$design_templates)}
    <tr><td>{$name}<input type="hidden" name="{$actionid}designtpl[{$id}]" value="1" /></td></tr>
    {/if}{/foreach}
  </tbody>
  </table>
</fieldset>
<fieldset>
  <legend>{$mod->Lang('available_templates')}</legend>
  <table class="unselected draggable">
  <tbody class="unselected rsortable">
    <tr class="placeholder"><td>{$placeholder}</td></tr>
    {foreach $all_templates as $id => $name}{if !$design_templates || !in_array($id,$design_templates)}
    <tr><td>{$name}<input type="hidden" name="{$actionid}designtpl[{$id}]" value="0" /></td></tr>
    {/if}{/foreach}
  </tbody>
  </table>
</fieldset>
{else}
<p class="pageinfo">{$mod->Lang('info_no_templates')}</p>
{/if}

{tab_start name='stylesheets'}
{if $all_stylesheets}
<p class="pageinfo">{$mod->Lang('info_edittemplate_stylesheets_tab')}</p>
<fieldset>
  <legend>{$mod->Lang('attached_stylesheets')}</legend>
  <table class="selected draggable">
  <tbody class="selected rsortable">
    <tr class="placeholder"><td>{$placeholder}</td></tr>
    {foreach $all_stylesheets as $id => $name}{if $design_stylesheets && in_array($id,$design_stylesheets)}
    <tr><td>{$name}<input type="hidden" name="{$actionid}designcss[{$id}]" value="1" /></td></tr>
    {/if}{/foreach}
  </tbody>
  </table>
</fieldset>
<fieldset>
  <legend>{$mod->Lang('available_stylesheets')}</legend>
  <table class="unselected draggable">
  <tbody class="unselected rsortable">
    <tr class="placeholder"><td>{$placeholder}</td></tr>
    {foreach $all_stylesheets as $id => $name}{if !$design_stylesheets || !in_array($id,$design_stylesheets)}
    <tr><td>{$name}<input type="hidden" name="{$actionid}designcss[{$id}]" value="0" /></td></tr>
    {/if}{/foreach}
  </tbody>
  </table>
</fieldset>
{else}
<p class="pageinfo">{$mod->Lang('info_no_stylesheets')}</p>
{/if}

{tab_end}
</form>
