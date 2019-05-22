{$startform}
 {if isset($hidden)}{$hidden}{/if}
 <label for="{$search_actionid}searchinput">{$searchprompt}:</label>&nbsp;
 <input type="text" class="search-input" id="{$search_actionid}searchinput" name="{$search_actionid}searchinput" size="20" maxlength="50" placeholder="{$searchtext}" />
{*<br />
 <input type="checkbox" name="{$search_actionid}use_or" value="1" />
*}
 <input type="submit" name="{$search_actionid}submit" class="search-button" value="{$submittext}" />
</form>
