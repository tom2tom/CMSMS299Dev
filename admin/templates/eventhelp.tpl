<h3>{$event}</h3>
{if $desctext}
<p><strong>{lang('description')}</strong>: {$desctext}</p>
{/if}
{if !empty($text)}{$text}{else}No helptext available...{/if}

<h4>{lang('eventhandlers')}</h4>
{if $hlist}
<ul>{foreach $hlist as $one}
  <li>{$one.handler_order}. {if !empty($one.tag_name)}{lang('user_tag')}: {$one.tag_name}{elseif !empty($one.module_name)}{lang('module')}: {$one.module_name}{else}Error!{/if}
  </li>
{/foreach}</ul>
{else}
<p>{lang('none')}</p>
{/if}
