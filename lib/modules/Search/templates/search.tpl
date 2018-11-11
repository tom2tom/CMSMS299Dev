{$startform}
{if isset($hidden)}{$hidden}{/if}
<label for="{$search_actionid}searchinput">{$searchprompt}:&nbsp;</label>
<input type="text" class="search-input" id="{$search_actionid}searchinput" name="{$search_actionid}searchinput" size="20" maxlength="50" placeholder="{$searchtext}" />
{*
<br/>
<input type="checkbox" name="{$search_actionid}use_or" value="1"/>
*}
<button type="submit" name="submit" class="adminsubmit icon do search-button">{$submittext}</button>
{$endform}
