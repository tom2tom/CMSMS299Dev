{block name=messages}
{strip}
{if isset($errors) && $errors[0] != ''}
<aside class="message pageerrorcontainer" role="alert">
{foreach from=$errors item='error'}
	{if $error}
	<p>{$error}</p>
	{/if}
{/foreach}
</aside>
{/if}
{if isset($messages) && $messages[0] != ''}
<aside class="message pagemcontainer" role="status">
{foreach $messages as $message}
	{if $message}
	<p>{$message}</p>
	{/if}
{/foreach}
</aside>
{/if}
{/strip}
{/block}
