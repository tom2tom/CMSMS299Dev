{* admin statistics tab *}

{if isset($topwords)}
{$formstart}
<div class="pageoverflow">
  <table class="pagetable">
    <thead>
      <tr>
        <th style="column-width:75%;">{$wordtext}</th>
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
<div class="bottomsubmits">
  <p class="pageinput">{$clearwordcount}&nbsp;{$exportcsv}</p>
</div>
</form>
{else}
<div class="pageinfo">{lang_by_realm('Search','nostatistics')}</div>
{/if}
