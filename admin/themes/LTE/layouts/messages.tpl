{*
<pre>{$messages|adjust:'print_r':1}</pre>
<pre>{$errors|adjust:'print_r':1}</pre>
*}
<script>
$(function()
{
  {if isset($errors) && $errors[0] != ''}
    {foreach $errors as $error}
      {if $error}
  $(document).Toasts('create', {
    class: 'bg-danger',
    body: '{$error}',
    icon: 'fas fa-exclamation-triangle fa-lg',
    autohide: false,
    position: 'bottomRight',
  })
      {/if}
    {/foreach}
  {/if}
  {if isset($messages) && $messages[0] != ''}
    {foreach $messages as $message}
      {if $message}
  $(document).Toasts('create', {
    class: 'bg-info',
    body: '{$message}',
    icon: 'fas fa-check fa-lg',
    autohide: true,
    delay: 5000,
    position: 'bottomRight',
  })
      {/if}
    {/foreach}
  {/if}

});
</script>
