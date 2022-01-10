<h3>{_ld($_module,'import_design_step2')}</h3>

{form_start step=2 tmpfile=$tmpfile}
<div class="pageinfo">{_ld($_module,'info_import_xml_step2')}</div>

<fieldset>
 <div class="rowbox expand">
  <div class="boxchild">
    <div class="pageoverflow">
      {$lbltxt=_ld($_module,'prompt_name')}<label class="pagetext" for="import_newname">{$lbltxt}:</label>
      {cms_help 0=$_module key='help_import_newname' title=$lbltxt}
      <div class="pageinput">
        <input id="import_newname" type="text" name="{$actionid}newname" value="{$new_name}" size="50" maxlength="50" />
        <br/>
        {_ld($_module,'prompt_orig_name')}: {$design_info.name}
      </div>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
      {$lbltxt=_ld($_module,'prompt_created')}{$lbltext}:
      {cms_help 0=$_module key='help_import_created' title=$lbltext}
      </p>
      <p class="pageinput">
        {$tmp=$design_info.generated|cms_date_format:'timed'}{if $tmp == ''}{$tmp=_ld($_module,'unknown')}{/if}
        <span class="red">{$tmp}</span>
      </p>
    </div>
  </div>{*boxchild*}

  <div class="boxchild">
    <div class="pageoverflow">
      <p class="pagetext">
      {$lbltxt=_ld($_module,'prompt_cmsversion')}{$lbltext}:
      {cms_help 0=$_module key='help_import_cmsversion' title=$lbltext}
      </p>
      <p class="pageinput">
        {if version_compare($design_info.cmsversion,$cms_version) < 0}
          <span class="red">{$design_info.cmsversion}</span>
        {else}
          {$design_info.cmsversion}
        {/if}
      </p>
    </div>
  </div>{*boxchild*}
 </div>{*rowbox*}
</fieldset>

{tab_header name='description' label=_ld($_module,'prompt_description')}
{* tab_header name='copyright' label=_ld($_module,'prompt_copyrightlicense') *}
{tab_header name='templates' label=_ld($_module,'prompt_templates')}
{tab_header name='stylesheets' label=_ld($_module,'prompt_stylesheets')}

{tab_start name='description'}

<textarea name={$actionid}newdescription rows="5" cols="80">{$design_info.description}</textarea>

{* tab_start name='copyright' *}

{tab_start name='templates'}
<table class="pagetable">
  <thead>
    <tr>
      <th>{_ld($_module,'name')}</th>
      <th>{_ld($_module,'newname')}</th>
      <th>{_ld($_module,'type')}</th>
      <th>{_ld($_module,'prompt_description')}</th>
      <th class="pageicon"></th>
    </tr>
  </thead>
  <tbody>
  {foreach $templates as $one}
   {$typename=$one.type_originator|cat:'::'|cat:$one.type_name}
   {$type_obj=CmsLayoutTemplateType::load($typename)}
   <tr class="{cycle values='row1,row2'}">
    <td>
      <span data-idx="{$one@index}" class="template_view pointer">{$one.name}</span>
    </td>
    <td><h3>{$one.newname}</h3></td>
    <td>{$type_obj->get_langified_display_value()}</td>
    <td>{$one.desc|default:_ld($_module,'info_nodescription')|summarize:80}
      <div id="tpl_{$one@index}" class="template_content" title="{$one.name}" style="display:none;"><textarea rows="10" cols="80">{$one.data}</textarea></div>
    </td>
    <td>
      {admin_icon class="template_view pointer" icon='view.gif' alt=_la('view')}
    </td>
  </tr>
  {/foreach}
  </tbody>
</table>

{tab_start name='stylesheets'}
<div id="stylesheet_list">
  <table class="pagetable">
    <thead>
      <tr>
        <th>{_ld($_module,'name')}</th>
        <th>{_ld($_module,'newname')}</th>
        <th>{_ld($_module,'prompt_media_type')}</th>
        <th>{_ld($_module,'prompt_description')}</th>
        <th class="pageicon"></th>
      </tr>
    </thead>
    <tbody>
      {foreach $stylesheets as $one}
      <tr>
        <td>{$one.name}</td>
        <td><h3>{$one.newname}</h3></td>
        <td>{$one.mediatype}</td>
        <td>{$one.desc|default:_ld($_module,'info_nodescription')}
           <div class="stylesheet_content" title="{$one.name}" style="display: none;">
	       <textarea rows="10" cols="80">{$one.data}</textarea>
	      </div>
	    </td>
        <td>
        {admin_icon class="stylesheet_view pointer" icon='view.gif' alt=_la('view')}
        </td>
      </tr>
      {/foreach}
    </tbody>
  </table>
</div>
{tab_end}

<div class="pageoverflow">
  <p class="pagetext">* {_ld($_module,'confirm_import')}:</p>
  <div class="pageinput">
    <input type="checkbox" name="{$actionid}check1" value="1" id="check1">&nbsp;<label class="pagetext" for="check1">{_ld($_module,'confirm_import_1')}</label>
  </div>
</div>
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}next2" class="adminsubmit icon go">{_ld($_module,'next')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
</div>
</form>
