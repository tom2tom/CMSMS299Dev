<h3>{_ld($_module,'prompt_move')}</h3>
<p class="pageoverflow">{_ld($_module,'info_move')}:</p>

{$formstart}
<div class="pageoverflow">
  <p class="pagetext">{_ld($_module,'itemstomove')}:</p>
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
    <label for="destdir">{_ld($_module,'move_destdir')}:</label>
  </p>
  <p class="pageinput">
    <select id="destdir" name="{$actionid}destdir">
    {html_options options=$dirlist selected=$cwd}
    </select>
  </p>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{_ld($_module,'move')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </p>
</div>
</form>
