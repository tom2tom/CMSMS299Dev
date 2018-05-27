<h3>{$mod->Lang('prompt_copy')}</h3>

{$formstart}
<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('itemstocopy')}:</p>
  <p class="pageinput">
    <ul>
    {foreach $sel as $one}
      <li>{$one}</li>
    {/foreach}
    </ul>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="destdir">{$mod->Lang('copy_destdir')}:</label>
  </p>
  <p class="pageinput">
    <select id="destdir" name="{$actionid}destdir">
    {html_options options=$dirlist selected=$cwd}
    </select>
  </p>
</div>
{if count($sel) == 1}
<div class="pageoverflow">
  <p class="pagetext">
    <label for="destname">{$mod->Lang('copy_destname')}:</label>
  </p>
  <p class="pageinput">
    <input type="text" id="destname" name="{$actionid}destname" size="50" maxlength="255" />
  </p>
</div>
{/if}
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}copy" class="adminsubmit icon do">{$mod->Lang('copy')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </p>
</div>
</form>
