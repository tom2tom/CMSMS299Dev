<h3>{_ld('layout','prompt_edit_type')}</h3>

<form id="form_edittype" action="{$selfurl}" enctype="multipart/form-data" method="post">
{foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
<fieldset>
  <div style="width:49%;float:left">{* left container *}
    <div class="pageoverflow">
      {$t=_ld('layout','prompt_originator')}<label class="pagetext" for="originator">{$t}:</label>
      {cms_help realm='layout' key='help_type_originator' title=$t}
      <p id="originator" class="pageinput">{$type->get_originator(TRUE)}</p>
    </div>

    <div class="pageoverflow">
      {$t=_ld('layout','prompt_name')}<label class="pagetext" for="typename">{$t}:</label>
      {cms_help realm='layout' key='help_type_name' title=$t}
      <p id="typename" class="pageinput">{$type->get_name()}</p>
    </div>

    <div class="pageoverflow">
      {$t=_ld('layout','prompt_descriptive_name')}<label class="pagetext" for="descname">{$t}:</label>
      {cms_help realm='layout' key='help_type_descriptive_name' title=$t}
      <p id="descname" class="pageinput">{$type->get_langified_display_value()}</p>
    </div>
  </div>{* end left container *}

  <div style="width:45%;float:right">{* right container *}
    <div class="pageoverflow">
      {$t=_ld('layout','prompt_has_dflt')}<label class="pagetext" for="hasdflt">{$t}:</label>
      {cms_help realm='layout' key=help_has_dflt title=$t}
      <p id="hasdflt" class="pageinput">{if $type && $type->get_dflt_flag()}{_la('yes')}{else}{_la('no')}{/if}</p>
    </div>

    <div class="pageoverflow">
      {$t=_ld('layout','prompt_requires_content_blocks')}<label class="pagetext" for="rcb">{$t}:</label>
      {cms_help realm='layout' key='help_type_reqcontentblocks' title=$t}
      <p id="rcb" class="pageinput">{if $type->get_content_block_flag()}{_la('yes')}{else}{_la('no')}{/if}</p>
    </div>

    <div class="pageoverflow">
      {$t=_ld('layout','prompt_created')}<label class="pagetext" for="created">{$t}:</label>
      {cms_help realm='layout' key='help_type_createdate' title=$t}
      <p id="created" class="pageinput">{$type->get_created()|cms_date_format:'timed'}</p>
    </div>
    <div class="pageoverflow">
      {$t=_ld('layout','prompt_modified')}<label class="pagetext" for="modified">{$t}:</label>
      {cms_help realm='layout' key='help_type_modifieddate' title=$t}
      <p id="modified" class="pageinput">{$type->get_modified()|cms_date_format:'timed'}</p>
    </div>
  </div>{* end right container *}
  <div style="clear:both"></div>
</fieldset>{* container *}

<input type="hidden" name="type" value="{$type->get_id()}">

{if $type->get_content_callback()}
{tab_header name='description' label=_ld('layout','prompt_description')}
{tab_header name='template' label=_ld('layout','prompt_proto_template')}
{tab_start name='description'}
{/if}
<div class="pageoverflow">
  {$t=_ld('layout','prompt_description')}<label class="pagetext" for="typedesc">{$t}:</label>
  {cms_help realm='layout' key='help_type_description' title=$t}
  <div class="pageinput">
    <textarea id="typedesc" name="description" rows="3" cols="40" style="width:40em;min-height:2em">{$type->get_description()}</textarea>
  </div>
</div>

{if $type->get_content_callback()}
{tab_start name='template'}
<div class="pageoverflow">
  {$t=_ld('layout','prompt_proto_template')}<label class="pagetext" for="dfltcont">{$t}:</label>
  {cms_help realm='layout' key='help_proto_template' title=$t}
  <div class="pageinput">
    <textarea id="dfltcont" name="content" data-cms-lang="smarty" rows="20" cols="80" style="width:40em;min-height:2em">{$type->get_dflt_contents()}</textarea>
  </div>
  <div class="pageinput pregap">
    <button type="button" name="reset" class="adminsubmit icon undo">{_ld('layout','reset_factory')}</button>
  </div>
</div>
{tab_end}
{/if}
<div class="pageinput pregap">
  <button type="submit" name="dosubmit" class="adminsubmit icon check">{_la('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
</div>
</form>
