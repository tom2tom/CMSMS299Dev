{* warning template *}

{extends file='index.tpl'}

{block name='logic'}
    {$title = 'title_warning'|tr}
{/block}

{block name='contents'}{$message}{/block}
