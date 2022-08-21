<h3>{$event}</h3>
{if $desctext}
<p><strong>{_la('description')}</strong>: {$desctext}</p>
{/if}
{if !empty($text)}{$text}{else}_la('nohelp'){/if}

<h4>{_la('eventhandlers')}</h4>
{if $hlist}
<ul>{foreach $hlist as $one}
  <li>{$one.handler_order}. {strip}
      {if $one.type=='U'}
        {_la('user_tag')}: {$one.func}
      {elseif $one.type=='M'}
        {_la('module')}: {$one.class}
      {elseif $one.type=='C'}
        {_la('callable')}: {$one.class}::{$one.func}
      {elseif $one.type=='D'}
          {_la('plugin')}: {$one.class}
      {else}
        {_la('error')}!
      {/if}
  {/strip}</li>
{/foreach}</ul>
{else}
<p>{_la('none')}</p>
{/if}
