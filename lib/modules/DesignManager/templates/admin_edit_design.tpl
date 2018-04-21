{form_start id="admin_edit_design"}
<input type="hidden" name="{$actionid}design" value="{$design->get_id()}" />
<input type="hidden" name="{$actionid}ajax" id="ajax" />

<fieldset>
 <div class="hbox expand">
  <div class="boxchild">
    <div class="pageinput posgap">
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
        <input type="text" id="design_name" name="{$actionid}name" value="{$design->get_name()}" size="50" maxlength="90"/>
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
 </div>{*hbox*}
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
</form>

