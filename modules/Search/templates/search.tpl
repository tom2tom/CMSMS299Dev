{$startform}{if !empty($extraparms)}
 <div class="hidden">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
 </div>
 {elseif !empty($hidden)}{$hidden}{/if}
 <label for="{$search_actionid}searchinput">{$searchprompt}:</label>&nbsp;
 <input type="text" class="search-input" id="{$search_actionid}searchinput" name="{$search_actionid}searchinput" size="20" maxlength="50" placeholder="{$searchtext}">
{*<br>
 <input type="checkbox" name="{$search_actionid}use_or" value="1">
*}
 <button type="submit" name="{$search_actionid}submit" class="search-button">{$submittext}</button>
</form>
