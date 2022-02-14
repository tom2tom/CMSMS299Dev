{block name=messages}
{strip}
{if isset($errors) && $errors[0] != ''}
	<aside class="pageerrorcontainer container message" role="alert">
		<div class="row pt-2">
			<div class="cell col-10"><span class="fas fa-lg fa-exclamation-circle" aria-hidden="true"></span> <strong class="h3 text-uppercase">Error</strong></div>
			<div class="cell col-2"><span aria-title="close dialog" class="close-warning"><span class="fas fa-times" aria-hidden="true"></span></span></div>
		</div>
		<div class="row pt-3">
		{foreach from=$errors item='error'}
			{if $error}
			<div class="cell col-12">
			<ul>{$error}</ul>
			</div>
			{/if}
		{/foreach}
		</div>
	</aside>
{/if}
{if isset($messages) && $messages[0] != ''}
	<aside class="pagemcontainer container message" role="status">
		<div class="row pt-2">
			<div class="cell col-10"><span class="fas fa-lg fa-check-circle" aria-hidden="true"></span> <strong class="h3 text-uppercase">Success</strong></div>
			<div class="cell col-2"><span aria-title="close dialog" class="close-warning"><span class="fas fa-times" aria-hidden="true"></span></span></div>
		</div>
		<div class="row pt-3">
		{foreach from=$messages item='message'}
			{if $message}
			<div class="cell col-12">
			<p>{$message}</p>
			</div>
			{/if}
		{/foreach}
		</div>
	</aside>
{/if}
{/strip}
{/block}
