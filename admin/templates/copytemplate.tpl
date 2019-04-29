<h3>{lang_by_realm('layout','prompt_copy_template')}</h3>

<form action="{$selfurl}{$urlext}" method="post">
<input type="hidden" name="tpl" value="{$actionparams.tpl}" />
<fieldset>
  <legend>{lang_by_realm('layout','prompt_source_template')}:</legend>
  <div style="width: 49%; float: left;">
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="tpl_name">*{lang_by_realm('layout','prompt_name')}:</label>
  </p>
    <p class="pageinput">
      <input id="tpl_name" type="text" size="50" maxlength="50" value="{$tpl->get_name()}" readonly="readonly"/>
    </p>
  </div>

  {if isset($type_list)}
  <div class="pageoverflow">
    <p class="pagetext">{lang_by_realm('layout','prompt_type')}:</p>
    <p class="pageinput">
      {$type_list[$tpl->get_type_id()]}
    </p>
  </div>
  {/if}

  {if isset($user_list)}
  <div class="pageoverflow">
    <p class="pagetext">{lang_by_realm('layout','prompt_owner')}:</p>
    <p class="pageinput">
      {$user_list[$tpl->get_owner_id()]}
    </p>
  </div>
  {/if}
{* multi-groups allowed
  {if isset($category_list)}
  <div class="pageoverflow">
    <p class="pagetext">{lang_by_realm('layout','prompt_group')}:</p>
    <p class="pageinput">
      {$category_list[$tpl->get_category_id()|default:0]}
    </p>
  </div>
  {/if}
*}

  </div>{* column *}

  <div style="width: 49%; float: right;">
  {if $tpl->get_id()}
    <div class="pageoverflow">
      <p class="pagetext">
      <label for="tpl_created">{lang_by_realm('layout','prompt_created')}:</label>
      </p>
      <p class="pageinput">
        <input type="text" id="tpl_created" value="{$tpl->get_created()|date_format:'%x %X'}" readonly="readonly"/>
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
      <label for="tpl_modified">{lang_by_realm('layout','prompt_modified')}:</label>
      </p>
      <p class="pageinput">
        <input type="text" id="tpl_modified" value="{$tpl->get_modified()|cms_date_format}" readonly="readonly"/>
      </p>
    </div>
  {/if}
  </div>{* column *}
</fieldset>

<fieldset>
  <legend>{lang_by_realm('layout','prompt_dest_template')}</legend>
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="tpl_destname">{lang_by_realm('layout','prompt_name')}:</label>
    </p>
    <p class="pageinput">
      <input type="text" id="tpl_destname" name="new_name" value="{$new_name|default:''}" size="50" maxlength="50"/>
    </p>
  </div>
</fieldset>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="submit" class="adminsubmit icon check">{lang('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
    <button type="submit" name="apply" class="adminsubmit icon apply">{lang('apply')}</button>
  </p>
</div>
</form>
