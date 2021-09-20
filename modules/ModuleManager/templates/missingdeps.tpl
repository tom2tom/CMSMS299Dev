<div class="pageoptions" style="text-align: right; float: right; margin-right: 3%;">
  <a href="{$back_url}" class="link_button icon back">{_ld($_module,'back')}</a>
</div>

<p class="pageheader">{_ld($_module,'title_missingdeps2')}:</p>
<table class="pagetable">
  <tr>
    <td>{_ld($_module,'nametext')}:</td>
    <td>{$info.name}</td>
  </tr>
  <tr>
    <td>{_ld($_module,'version')}:</td>
    <td>{$info.version}</td>
  </tr>
</table>

<table class="pagetable">
  <thead>
    <tr>
      <th>{_ld($_module,'nametext')}</th>
      <th>{_ld($_module,'minversion')}</th>
    </tr>
  </thead>
  <tbody>
  {foreach $info.missing_deps as $name => $version}
    <tr>
      <td>{$name}</td>
      <td>{$version}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
