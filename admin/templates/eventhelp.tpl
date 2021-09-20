<h3>{$event}</h3>
{if $desctext}
<p><strong>{_ld('admin','description')}</strong>: {$desctext}</p>
{/if}
{if !empty($text)}{$text}{else}No helptext available...{/if}

<h4>{_ld('admin','eventhandlers')}</h4>
{if $hlist}
<ul>{foreach $hlist as $one}
  <li>{$one.handler_order}. {strip}
      {if $one.type=='U'}
        {_ld('admin','user_tag')}: {$one.func}
      {elseif $one.type=='M'}
        {_ld('admin','module')}: {$one.class}
      {elseif $one.type=='C'}
        {_ld('admin','callable')}: {$one.class}::{$one.func}
      {elseif $one.type=='D'}
          {_ld('admin','plugin')}: {$one.class}
      {else}
        {_ld('admin','error')}!
      {/if}
  {/strip}</li>
{/foreach}</ul>
{else}
<p>{_ld('admin','none')}</p>
{/if}
