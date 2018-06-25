{* admin statistics tab *}

{if isset($topwords)}
{$formstart}
<div class="pageoverflow">
  <table class="pagetable">
    <thead>
      <tr>
        <th style="width:75%;">{$wordtext}</th>
        <th>{$counttext}</th>
      </tr>
    </thead>
    <tbody>
    {foreach $topwords as $entry}
      <tr class="{cycle values='row1,row2'}">
        <td>{$entry.word}</td>
        <td>{$entry.count}</td>
      </tr>
    {/foreach}
    </tbody>
  </table>
</div>
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}clearwordcount" id="{$actionid}clearwordcount" class="adminsubmit icon undo" onclick="cms_confirm_btnclick(this,'{$mod->Lang("confirm_clearstats")}');return false;">{$mod->Lang('clear')}</button>
  <button type="submit" name="{$actionid}exportcsv" id="{$actionid}exportcsv" class="adminsubmit icon do">{$mod->Lang('export_to_csv')}</button>
</div>
</form>
{else}
<div class="pageinfo">{lang_by_realm('Search','nostatistics')}</div>
{/if}
