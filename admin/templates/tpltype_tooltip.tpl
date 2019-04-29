{strip}{$tpltype=$list_all_types.$type_id}
<strong>{lang_by_realm('layout','prompt_id')}:</strong>&nbsp;{$type_id}<br/>
<strong>{lang_by_realm('layout','prompt_name')}:</strong>&nbsp;{$tpltype->get_name()}<br/>
{$org=$tpltype->get_originator()}{if $org == $coretypename}{$org='Core'}{/if}
<strong>{lang_by_realm('layout','prompt_originator')}:</strong>&nbsp;{$org}<br/>
{$tmp=$tpltype->get_description()}{if $tmp}
 <strong>{lang_by_realm('layout','prompt_description')}:</strong>&nbsp;{$tmp|summarize}
{/if}
{strip}
