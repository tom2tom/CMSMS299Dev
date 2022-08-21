<h3>{_ld($_module,'prompt_copy')}</h3>

{$formstart}
<div class="pageoverflow">
  <p class="pagetext">{_ld($_module,'itemstocopy')}:</p>
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
    <label for="destdir">{_ld($_module,'copy_destdir')}:</label>
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
    <label for="destname">{_ld($_module,'copy_destname')}:</label>
  </p>
  <p class="pageinput">
    <input type="text" id="destname" name="{$actionid}destname" size="50" maxlength="255" />
  </p>
</div>
{/if}
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{_ld($_module,'copy')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </p>
</div>
</form>
