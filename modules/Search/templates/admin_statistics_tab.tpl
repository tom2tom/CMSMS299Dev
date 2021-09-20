{* admin statistics tab *}
{if !empty($topwords)}
<div class="pageoverflow">
  <table class="pagetable">
    <thead>
      <tr>
        <th style="width:75%;">{_ld($_module,'word')}</th>
        <th>{_ld($_module,'count')}</th>
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
 {$formstart1}
  <button type="submit" name="{$actionid}clearwordcount" class="adminsubmit icon undo">{_ld($_module,'clear')}</button>
  <button type="submit" name="{$actionid}exportcsv" class="adminsubmit icon do">{_ld($_module,'export_to_csv')}</button>
 </form>
</div>
{else}
<div class="pageinfo">{_ld($_module,'nostatistics')}</div>
{/if}
