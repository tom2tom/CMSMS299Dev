<h3 class="{if !empty($titleclass)}{$titleclass}{else}error{/if}">{$title_error}</h3>
{if $message}<p{if !empty($messgeclass)} class="{$messgeclass}"{/if}>{$message</p>{/if}
{if isset($link_back)}<br />
<p>{$link_back}</p>
{/if}
