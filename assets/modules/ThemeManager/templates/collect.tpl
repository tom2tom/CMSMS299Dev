<h3>
Construct Theme
</h3>
{tab_header name='main' label=$mod->Lang('tab_main') active=$tab}
{tab_header name='templates' label=$mod->Lang('tab_templates') active=$tab}
{tab_header name='stylesheets' label=$mod->Lang('tab_stylesheets') active=$tab}
{tab_header name='media' label=$mod->Lang('tab_media') active=$tab}
{tab_header name='fonts' label=$mod->Lang('tab_fonts') active=$tab}
{tab_header name='code' label=$mod->Lang('tab_code') active=$tab}
{tab_start name='main'}
<form>
  <div class="pageoverflow postgap">{$t=$mod->Lang('label_current_theme')}
    <label class="pagetext" for="dfltthm">{$t}:</label>
    {cms_help realm=$_module key='help_current_theme' title=$t}
    <p class="pageinput">
  [NAME INPUT]
    </p>
  </div>
  <div class="pageoverflow postgap">{$t=$mod->Lang('label_current_theme')}
    <label class="pagetext" for="dfltthm">{$t}:</label>
    {cms_help realm=$_module key='help_current_theme' title=$t}
    <p class="pageinput">
  [DESCRIPTION INPUT]
    </p>
  </div>
  <div class="pageoverflow postgap">{$t=$mod->Lang('label_current_theme')}
    <label class="pagetext" for="dfltthm">{$t}:</label>
    {cms_help realm=$_module key='help_current_theme' title=$t}
    <p class="pageinput">
  [PARENT SELECTOR]
    </p>
  </div>
  <div class="pageoverflow postgap">{$t=$mod->Lang('label_current_theme')}
    <label class="pagetext" for="dfltthm">{$t}:</label>
    {cms_help realm=$_module key='help_current_theme' title=$t}
    <p class="pageinput">
  [COPYRIGHT INPUT]
    </p>
  </div>
  <div class="pageoverflow postgap">{$t=$mod->Lang('label_current_theme')}
    <label class="pagetext" for="dfltthm">{$t}:</label>
    {cms_help realm=$_module key='help_current_theme' title=$t}
    <p class="pageinput">
  [LICENSE SELECTOR]
    </p>
  </div>
  <div class="pageoverflow postgap">{$t=$mod->Lang('label_current_theme')}
    <label class="pagetext" for="dfltthm">{$t}:</label>
    {cms_help realm=$_module key='help_current_theme' title=$t}
    <p class="pageinput">
  [THUMBNAIL VIEW/CREATE]
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pageinput">
      <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" class="adminsubmit icon undo">{$mod->Lang('close')}</button>
    </p>
  </div>
</form>
{tab_start name='templates'}
<div class="postgap">
  <label class="pagetext" for="x1">{$mod->Lang('label_current_theme')}:</label>
  <p id="X1" class="pageinput currentname">
  [CURRENT NAME]
  </p>
</div>
{if 1}
<table id="Xlist" class="pagetable" style="width:auto;">
  <thead>
    <tr>
    <th>Name
    </th>
    <th>Description
    </th>
    <th>Created
    </th>
    <th>Modified
    </th>
    <th>Custom
    </th>
    <th>
    </th>
    </tr>
  </thead>
  <tbody>
    <tr>
    <td>Ripper
    </td>
    <td>Woohoo
    </td>
    <td>1/1/1900 20:01
    </td>
    <td>1/1/1900 20:01
    </td>
    <td>Yes
    </td>
    <td>Icons
    </td>
    </tr>
  </tbody>
</table>
{else}
<p class="information">{$mod->Lang('no_template')}</p>
{/if}
{tab_start name='stylesheets'}
<div class="postgap">
  <label class="pagetext" for="x1">{$mod->Lang('label_current_theme')}:</label>
  <p id="X1" class="pageinput currentname">
  [CURRENT NAME]
  </p>
</div>
{if 1}
<table id="Xlist" class="pagetable" style="width:auto;">
  <thead>
    <tr>
    <th>Name
    </th>
    <th>Description
    </th>
    <th>Created
    </th>
    <th>Modified
    </th>
    <th>Custom
    </th>
    <th>
    </th>
    </tr>
  </thead>
  <tbody>
    <tr>
    <td>Ripper
    </td>
    <td>Woohoo
    </td>
    <td>1/1/1900 20:01
    </td>
    <td>1/1/1900 20:01
    </td>
    <td>Yes
    </td>
    <td>Icons
    </td>
    </tr>
  </tbody>
</table>
{else}
<p class="information">{$mod->Lang('no_style')}</p>
{/if}
{tab_start name='media'}
<div class="postgap">
  <label class="pagetext" for="x1">{$mod->Lang('label_current_theme')}:</label>
  <p id="X1" class="pageinput currentname">
  [CURRENT NAME]
  </p>
</div>
{if 1}
<table id="Xlist" class="pagetable" style="width:auto;">
  <thead>
    <tr>
    <th>Name
    </th>
    <th>Description
    </th>
    <th>Created
    </th>
    <th>Modified
    </th>
    <th>Custom
    </th>
    <th>
    </th>
    </tr>
  </thead>
  <tbody>
    <tr>
    <td>Ripper
    </td>
    <td>Woohoo
    </td>
    <td>1/1/1900 20:01
    </td>
    <td>1/1/1900 20:01
    </td>
    <td>Yes
    </td>
    <td>Icons
    </td>
    </tr>
  </tbody>
</table>
{else}
<p class="information">{$mod->Lang('no_media')}</p>
{/if}
{tab_start name='fonts'}
<div class="postgap">
  <label class="pagetext" for="x1">{$mod->Lang('label_current_theme')}:</label>
  <p id="X1" class="pageinput currentname">
  [CURRENT NAME]
  </p>
</div>
{if 1}
<table id="Xlist" class="pagetable" style="width:auto;">
  <thead>
    <tr>
    <th>Name
    </th>
    <th>Description
    </th>
    <th>Created
    </th>
    <th>Modified
    </th>
    <th>Custom
    </th>
    <th>
    </th>
    </tr>
  </thead>
  <tbody>
    <tr>
    <td>Ripper
    </td>
    <td>Woohoo
    </td>
    <td>1/1/1900 20:01
    </td>
    <td>1/1/1900 20:01
    </td>
    <td>Yes
    </td>
    <td>Icons
    </td>
    </tr>
  </tbody>
</table>
{else}
<p class="information">{$mod->Lang('no_font')}</p>
{/if}
{tab_start name='code'}
<div class="postgap">
  <label class="pagetext" for="x1">{$mod->Lang('label_current_theme')}:</label>
  <p id="X1" class="pageinput currentname">
  [CURRENT NAME]
  </p>
</div>
{if 1}
<table id="Xlist" class="pagetable" style="width:auto;">
  <thead>
    <tr>
    <th>Name
    </th>
    <th>Description
    </th>
    <th>Created
    </th>
    <th>Modified
    </th>
    <th>Custom
    </th>
    <th>
    </th>
    </tr>
  </thead>
  <tbody>
    <tr>
    <td>Ripper
    </td>
    <td>Woohoo
    </td>
    <td>1/1/1900 20:01
    </td>
    <td>1/1/1900 20:01
    </td>
    <td>Yes
    </td>
    <td>Icons
    </td>
    </tr>
  </tbody>
</table>
{else}
<p class="information">{$mod->Lang('no_code')}</p>
{/if}
{tab_end}
{*
{if $themes && $contextmenus}
<div id="contextmenus">
{foreach $contextmenus as $menu}{$menu}
{/foreach}
</div>
{/if}
*}
{*
{if $themes && $pmod}{ * popup dialog * }
<div id="clone_dlg" title="{$mod->Lang('title_clonename')}" style="display:none;">
 <form id="clonedialog_form" enctype="multipart/form-data" method="post">
 <div class="dlg-options">
  <label for="fld_name">{$mod->Lang('name')}:</label> <input type="text" id="fld_name" name="{$actionid}name" />
 </div>
 </form> 
</div>
{/if}
*}
