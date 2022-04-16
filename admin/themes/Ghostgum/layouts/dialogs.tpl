{block name=hiddendialogs append}
{if isset($marks)}
<!-- start bookmarks -->
<div class="dialog invisible" role="dialog" title="{_la('bookmarks')}">
  {if is_array($marks) && count($marks)}
  <h3>{_la('user_created')}</h3>
  <ul>
    {foreach $marks as $mark}
    <li>
      <a href="{$mark->url}"{if $mark->bookmark_id > 0} class="bookmark"{/if} title="{$mark->title}">{$mark->title}</a>
    </li>
    {/foreach}
  </ul>
  {/if}
  <h3>{_la('help')}</h3>
  <ul>
    <li><a rel="external" class="external" href="https://docs.cmsmadesimple.org" title="{_la('documentation')}">{_la('documentation')}</a></li>
    <li><a rel="external" class="external" href="https://forum.cmsmadesimple.org" title="{_la('forums')}">{_la('forums')}</a></li>
    <li><a rel="external" class="external" href="http://cmsmadesimple.org/main/support/IRC">{_la('irc')}</a></li>
  </ul>
</div>
<!-- end bookmarks -->
{/if}
{$my_alerts=$theme->get_my_alerts()}{if $my_alerts}
<!-- start alerts -->
<div id="alert-dialog" class="alert-dialog" role="dialog" title="{_la('alerts')}" style="padding:0;display:none;">
    {foreach $my_alerts as $one}
    <div class="alert-box jqtoast {if $one->priority == '_high'}error{elseif $one->priority != '_low'}warn{else}info{/if}" data-alert-name="{$one->get_prefname()}">
        <div class="jqt-heading">{$one->get_title()|default:_la('alert')}
        <span class="jqt-close alert-remove" title="{_la('remove_alert')}"></span>
        </div>
        <span>{$one->get_message()}</span>
    </div>
    {/foreach}
</div>
<!-- end alerts -->
{/if}
{/block}
