<h3>{lang_by_realm('layout','prompt_edit_type')}</h3>

<form id="form_edittype" action="{$selfurl}" enctype="multipart/form-data" method="post">
{foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />{/foreach}
<fieldset>
  <div style="width: 49%; float: left;">{* left container *}
    <div class="pageoverflow">
      <p class="pagetext">
      <label for="originator">{lang_by_realm('layout','prompt_originator')}:</label>
      {cms_help realm='layout' key2='help_type_originator' title=lang_by_realm('layout','prompt_originator')}
      </p>
      <p class="pageinput">{$type->get_originator(TRUE)}</p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
      <label for="name">{lang_by_realm('layout','prompt_name')}:</label>
      {cms_help realm='layout' key2='help_type_name' title=lang_by_realm('layout','prompt_name')}
      </p>
      <p class="pageinput">{$type->get_name()}</p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
      <label for="descname">{lang_by_realm('layout','prompt_descriptive_name')}:</label>
      {cms_help realm='layout' key2='help_type_descriptive_name' title=lang_by_realm('layout','prompt_descriptive_name')}
      </p>
      <p class="pageinput">{$type->get_langified_display_value()}</p>
    </div>
  </div>{* end left container *}

  <div style="width: 45%; float: right;">{* right container *}
    <div class="pageoverflow">
      <p class="pagetext">
      <label for="hasdflt">{lang_by_realm('layout','prompt_has_dflt')}:</label>
      {cms_help realm='layout' key2=help_has_dflt title=lang_by_realm('layout','prompt_has_dflt')}
      </p>
      <p class="pageinput">{if $type && $type->get_dflt_flag()}{lang('yes')}{else}{lang('no')}{/if}</p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
      <label for="rcb">{lang_by_realm('layout','prompt_requires_content_blocks')}:</label>
      {cms_help realm='layout' key2='help_type_reqcontentblocks' title=lang_by_realm('layout','prompt_requires_content_blocks')}
      </p>
      <p class="pageinput">{if $type->get_content_block_flag()}{lang('yes')}{else}{lang('no')}{/if}</p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
      <label for="created">{lang_by_realm('layout','prompt_created')}:</label>
      {cms_help realm='layout' key2='help_type_createdate' title=lang_by_realm('layout','prompt_created')}
      </p>
      <p class="pageinput">{$type->get_created()|cms_date_format|cms_escape}</p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
      <label for="modified">{lang_by_realm('layout','prompt_modified')}:</label>
      {cms_help realm='layout' key2='help_type_modifieddate' title=lang_by_realm('layout','prompt_modified')}
      </p>
      <p class="pageinput">{$type->get_modified()|cms_date_format|cms_escape}</p>
    </div>
  </div>{* end right container *}
  <div style="clear: both;"></div>
</fieldset>{* container *}

<input type="hidden" name="type" value="{$type->get_id()}" />

{if $type->get_content_callback()}
{tab_header name='description' label=lang_by_realm('layout','prompt_description')}
{tab_header name='template' label=lang_by_realm('layout','prompt_proto_template')}

{tab_start name='description'}
{/if}
<div class="pageoverflow">
  <p class="pagetext">
      <label for="type_description">{lang_by_realm('layout','prompt_description')}:</label>
    {cms_help realm='layout' key2='help_type_description' title=lang_by_realm('layout','prompt_description')}
  <p class="pageinput">
    <textarea id="type_description" name="description" rows="3" cols="40" style="width:40em;min-height:2em;">{$type->get_description()}</textarea>
  </p>
</div>

{if $type->get_content_callback()}
{tab_start name='template'}
<div class="pageoverflow">
  <p class="pagetext">
    <label for="type_dfltcont">{lang_by_realm('layout','prompt_proto_template')}:</label>
    {cms_help realm='layout' key2='help_proto_template' title=lang_by_realm('layout','prompt_proto_template')}
  </p>
  <p class="pageinput">
    <textarea id="type_dfltcont" name="content" data-cms-lang="smarty" rows="20" cols="80" style="width:40em;min-height:2em;">{$type->get_dflt_contents()}</textarea>
  </p>
  <div class="pageinput pregap">
    <button type="button" name="reset" class="adminsubmit icon undo">{lang_by_realm('layout','reset_factory')}</button>
  </div>
</div>

{tab_end}
{/if}
<div class="pageinput pregap">
  <button type="submit" name="dosubmit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
</form>
