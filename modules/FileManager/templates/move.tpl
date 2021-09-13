<h3>{$mod->Lang('prompt_move')}</h3>
<p class="pageoverflow">{$mod->Lang('info_move')}:</p>

{$formstart}
<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('itemstomove')}:</p>
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
    <label for="destdir">{$mod->Lang('move_destdir')}:</label>
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
    <button type="submit" name="{$actionid}move" class="adminsubmit icon do">{$mod->Lang('move')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </p>
</div>
</form>
