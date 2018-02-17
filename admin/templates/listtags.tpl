<div class="pagecontainer">
  {$maintitle}
  {if isset($subheader)}
    <div class="pageheader">{$subheader}
     {if isset($wiki_url) && isset($image_help_external)}
      <span class="helptext">
        <a href="{$wiki_url}" target="_blank" class="helpicon">{$image_help_external}</a>
        <a href="{$wiki_url}" target="_blank">{lang('help')}</a> ({lang('new_window')})
      </span>
     {/if}
    </div>
  {/if}

  {if isset($content)}
    <br />{$content}
  {elseif isset($error)}
    <div class="pageerrorcontainer">
     <div class="pageoverflow">
       <ul class="pageerror"><li>{$error}</li></ul>
     </div>
    </div>
  {elseif isset($plugins)}
    <table class="pagetable">
      <thead>
       <tr>
         <th title="{lang_by_realm('tags','tag_name')}">{lang('name')}</th>
         <th title="{lang_by_realm('tags','tag_type')}">{lang('type')}</th>
         <th class="pagew10" title="{lang_by_realm('tags','tag_adminplugin')}">{lang('adminplugin')}</th>
         <th class="pagew10" title="{lang_by_realm('tags','tag_help')}">{lang('help')}</th>
         <th class="pagew10" title="{lang_by_realm('tags','tag_about')}">{lang('about')}</th>
       </tr>
      </thead>
      <tbody>
        {foreach $plugins as $one}
        <tr class="{cycle values='row1,row2'}">
         {strip}
         <td>
           {if isset($one.help_url)}
             <a href="{$one.help_url}" title="{lang_by_realm('tags','viewhelp')}">{$one.name}</a>
           {else}
             {$one.name}
           {/if}
         </td>
         <td>
            <span title="{lang_by_realm('tags',$one.type)}">{$one.type}</span>
         </td>
         <td>
            {if isset($one.admin) && $one.admin}
              <span title="{lang_by_realm('tags','title_admin')}">{lang('yes')}</span>
            {else}
              <span title="{lang_by_realm('tags','title_notadmin')}">{lang('no')}</span>
            {/if}
         </td>
         <td>
           {if isset($one.help_url)}
             <a href="{$one.help_url}" title="{lang_by_realm('tags','viewhelp')}">{lang('help')}</a>
           {/if}
         </td>
         <td>
           {if isset($one.about_url)}
             <a href="{$one.about_url}" title="{lang_by_realm('tags','viewabout')}">{lang('about')}</a>
           {/if}
         </td>
{/strip}
       </tr>
      {/foreach}
      </tbody>
    </table>
  {/if}
</div>
