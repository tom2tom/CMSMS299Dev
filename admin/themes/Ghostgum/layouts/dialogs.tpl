{block name=hiddendialogs append}
{if isset($marksmenu)}{$marksmenu}{/if}{*not a dialog*}
{$my_alerts=$theme->get_my_alerts()}{if $my_alerts}
<!-- start alerts -->
<div id="alert-dialog" class="alert-dialog" role="dialog" title="{_la('alerts')}" style="padding:0;display:none">
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
