<h3 class="{if !empty($titleclass)}{$titleclass}{else}error{/if}">{$title}</h3>
{if $message}<p{if !empty($messageclass)} class="{$messageclass}"{/if}>{$message}</p>{/if}
{if isset($backlink)}<br />
<p>{$backlink}</p>
{/if}
