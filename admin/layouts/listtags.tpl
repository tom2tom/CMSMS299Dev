{if isset($subheader)}
  <div class="pageheader">{$subheader}
   {if isset($wiki_url) && isset($image_help_external)}
    <span class="helptext">
      <a href="{$wiki_url}" target="_blank" class="helpicon">{$image_help_external}</a>
      <a href="{$wiki_url}" target="_blank">{_la('help')}</a> ({_la('new_window')})
    </span>
   {/if}
  </div>
 {else}
  <div class="information">
   <p>{_ld('tags','tag_info')}<br />{_ld('tags','tag_info3')}</p>
  </div>
{/if}
{if !empty($pdev)}
<div class="pageoverflow pregap">
  <p class="pagetext">{_la('upload_plugin_file')}</p>
  <form action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <p class="pageinput"><input type="file" name="pluginfile" size="30" maxlength="255" accept="application/x-php" /></p>
  <div class="pageinput pregap">
   <button type="submit" name="upload" class="adminsubmit icon do">{_la('submit')}</button>
  </div>
  </form>
</div>
<br />
{/if}
{if isset($content)}
  {$content}
{elseif isset($plugins)}
  <table class="pagetable">
    <thead>
     <tr>
       <th title="{_ld('tags','tag_name')}">{_la('name')}</th>
       <th title="{_ld('tags','tag_type')}">{_la('type')}</th>
{*       <th title="{_ld('tags','tag_cachable')}" style="text-align:center">{_la('cachable')}</th> *}
       <th title="{_ld('tags','tag_adminplugin')}" style="text-align:center">{_la('adminplugin')}</th>
       <th title="{_ld('tags','tag_help')}" style="text-align:center">{_la('help')}</th>
       <th title="{_ld('tags','tag_about')}" style="text-align:center">{_la('about')}</th>
     </tr>
    </thead>
    <tbody>
      {foreach $plugins as $one}
      <tr class="{cycle values='row1,row2'}">
       {strip}
       <td>
        {$one.name}
       </td>
       <td>
          <span title="{_ld('tags',$one.type)}">{$one.type}</span>
       </td>
{*       <td style="text-align:center;">
         {if empty($one.cachable)}{$iconcno}{else}{$iconcyes}{/if}
       </td> *}
       <td style="text-align:center;">
         {if empty($one.admin)}{$iconno}{else}{$iconyes}{/if}
       </td>
       <td style="text-align:center;">
         {if isset($one.help_url)}
           <a href="{$one.help_url}" title="{_ld('tags','viewhelp')}">{$iconhelp}</a>
         {/if}
       </td>
       <td style="text-align:center;">
         {if isset($one.about_url)}
           <a href="{$one.about_url}" title="{_ld('tags','viewabout')}">{$iconabout}</a>
         {/if}
       </td>
{/strip}
     </tr>
    {/foreach}
    </tbody>
  </table>
{/if}
