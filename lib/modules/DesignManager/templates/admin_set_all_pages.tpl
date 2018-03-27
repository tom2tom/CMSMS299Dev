<h3>{$mod->Lang('set_all_pages')}</h3>
<div class="pagewarn">{$mod->Lang('warning_set_all_pages')}</div>

{form_start extraparms=$extraparms}
<fieldset>
  <div style="width: 49%; float: left;">
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="tpl_name">{$mod->Lang('prompt_name')}:</label>
      </p>
      <p class="pageinput">
        <input id="tpl_name" type="text" size="50" maxlength="50" value="{$template->get_name()}" value="{$template->get_name()}" readonly="readonly"/>
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">{$mod->Lang('prompt_type')}:</p>
      <p class="pageinput">
        {$template_type->get_langified_display_value()}
      </p>
    </div>

    {if isset($user_list)}
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="tpl_name">{$mod->Lang('prompt_owner')}:</label>
      </p>
      <p class="pageinput">
        {$user_list[$template->get_owner_id()]}
      </p>
    </div>
    {/if}

    {if isset($category_list)}
    <div class="pageoverflow">
      <p class="pagetext">{$mod->Lang('prompt_category')}:</p>
      <p class="pageinput">
        {$category_list[$template->get_category_id()|default:0]}
      </p>
    </div>
    {/if}
  </div>

  <div style="width: 49%; float: left;">
  {if $template->get_id()}
    <div class="pageoverflow">
      <p class="pagetext">{$mod->Lang('prompt_created')}:</p>
      <p class="pageinput">
        <input type="text" value="{$template->get_created()|date_format:'%x %X'}" readonly="readonly"/>
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="template_modified">{$mod->Lang('prompt_modified')}:</label>
      </p>
      <p class="pageinput">
        <input type="text" value="{$template->get_modified()|date_format:'%x %X'}" readonly="readonly"/>
      </p>
    </div>
  {/if}
  </div>

  <div style="width: 49%; float: right;">
  </div>
</fieldset>

{if isset($noblocks)}
<div class="pagewarn">{$mod->Lang('warn_setall_nocontentblocks')}</div>
{elseif isset($template_error)}
<div class="pagewarn">{$template_error}</div>
{/if}

<div class="pageoverflow pregap">
  <p class="pagetext">{$mod->Lang('confirm_setall_1')}:</p>
  <div class="pageinput">
    <input type="checkbox" name="{$actionid}check1" value="1" id="check1" />&nbsp;<label for="check1">{$mod->Lang('confirm_setall_2')}</label><br/>
    <input type="checkbox" name="{$actionid}check2" value="1" id="check2" />&nbsp;<label for="check2">{$mod->Lang('confirm_setall_3')}</label>
  </div>
</div>
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
</div>
</form>
