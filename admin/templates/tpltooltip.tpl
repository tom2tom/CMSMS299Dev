{strip}{if $template->locked()}
  {$lock=$template->get_lock()}
  {if $template->lock_expired()}<strong style="color: red;">{lang_by_realm('layout','msg_steal_lock')}</strong><br />{/if}
  <strong>{lang_by_realm('layout','prompt_lockedby')}:</strong> {cms_admin_user uid=$lock.uid}<br/>
  <strong>{lang_by_realm('layout','prompt_lockedsince')}:</strong> {$lock.created|date_format:'%x %H:%M'}<br/>
  {if $lock.expires < $smarty.now}
    <strong>{lang_by_realm('layout','prompt_lockexpired')}:</strong> <span style="color: red;">{$lock.expires|relative_time}</span>
  {else}
    <strong>{lang_by_realm('layout','prompt_lockexpires')}:</strong> {$lock.expires|relative_time}
  {/if}
{else}
  <strong>{lang_by_realm('layout','prompt_name')}:</strong> {$template->get_name()} <em>({$template->get_id()})</em><br/>
  <strong>{lang_by_realm('layout','prompt_owner')}:</strong> {cms_admin_user uid=$template->get_owner_id()}<br/>
  <strong>{lang_by_realm('layout','prompt_created')}:</strong> {$template->get_created()|cms_date_format|cms_escape}<br/>
  <strong>{lang_by_realm('layout','prompt_modified')}:</strong> {$template->get_modified()|relative_time}
  {$tmp=$template->get_description()}
  {if $tmp != ''}<br /><strong>{lang_by_realm('layout','prompt_description')}:</strong> {$tmp|strip_tags|cms_escape|summarize}{/if}
{/if}{/strip}