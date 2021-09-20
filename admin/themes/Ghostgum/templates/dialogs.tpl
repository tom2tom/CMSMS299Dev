{block name=hiddendialogs append}
{if isset($marks)}
<!-- start bookmarks -->
<div class="dialog invisible" role="dialog" title="{_ld('admin','bookmarks')}">
  {if is_array($marks) && count($marks)}
  <h3>{_ld('admin','user_created')}</h3>
  <ul>
    {foreach $marks as $mark}
    <li>
      <a href="{$mark->url}"{if $mark->bookmark_id > 0} class="bookmark"{/if} title="{$mark->title}">{$mark->title}</a>
    </li>
    {/foreach}
  </ul>
  {/if}
  <h3>{_ld('admin','help')}</h3>
  <ul>
    <li><a rel="external" class="external" href="https://docs.cmsmadesimple.org" title="{_ld('admin','documentation')}">{_ld('admin','documentation')}</a></li>
    <li><a rel="external" class="external" href="https://forum.cmsmadesimple.org" title="{_ld('admin','forums')}">{_ld('admin','forums')}</a></li>
    <li><a rel="external" class="external" href="http://cmsmadesimple.org/main/support/IRC">{_ld('admin','irc')}</a></li>
  </ul>
</div>
<!-- end bookmarks -->
{/if}
{$my_alerts=$theme->get_my_alerts()}{if $my_alerts}
<!-- start alerts -->
<div id="alert-dialog" class="alert-dialog" role="dialog" title="{_ld('admin','alerts')}" style="padding:0;display:none;">
    {foreach $my_alerts as $one}
    <div class="alert-box jqtoast {if $one->priority == '_high'}error{elseif $one->priority != '_low'}warn{else}info{/if}" data-alert-name="{$one->get_prefname()}">
        <div class="jqt-heading">{$one->get_title()|default:_ld('admin','alert')}
        <span class="jqt-close alert-remove" title="{_ld('admin','remove_alert')}"></span>
        </div>
        <span>{$one->get_message()}</span>
    </div>
    {/foreach}
</div>
<!-- end alerts -->
{/if}
{/block}
