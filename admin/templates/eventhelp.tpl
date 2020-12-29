<h3>{$event}</h3>
{if $desctext}
<p><strong>{lang('description')}</strong>: {$desctext}</p>
{/if}
{if !empty($text)}{$text}{else}No helptext available...{/if}

<h4>{lang('eventhandlers')}</h4>
{if $hlist}
<ul>{foreach $hlist as $one}
  <li>{$one.handler_order}. {strip}
      {if $one.type=='U'}
        {lang('user_tag')}: {$one.func}
      {elseif $one.type=='M'}
        {lang('module')}: {$one.class}
      {elseif $one.type=='C'}
        {lang('callable')}: {$one.class}::{$one.func}
      {elseif $one.type=='D'}
          {lang('plugin')}: {$one.class}
      {else}
        {lang('error')}!
      {/if}
  {/strip}</li>
{/foreach}</ul>
{else}
<p>{lang('none')}</p>
{/if}
