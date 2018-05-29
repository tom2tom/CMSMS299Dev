{if !empty($acts)}
<div class="actions" style="float:right;">
{foreach $acts as $one}{$one}{/foreach}
</div>
<div class="clearb"></div>
{/if}
{if !empty($about)}
<div class="vbox">
{foreach $about as $one}<div class="hbox"><div class="boxchild">{$one@key}:&nbsp;</div><div class="boxchild">{$one}</div></div>{/foreach}
</div>
<br />
{/if}
{if $ftype == 'archive'}
 {if $filenames}
  <code class="maxheight">
  {foreach $filenames as $fn}
   {strip}{if $fn.folder}
    <strong>{$fn.name}</strong>
   {else}
    {$fn.name} ({$fn.filesize})
   {/if}<br />{/strip}
  {/foreach}
  </code>
 {else}
  <p>Error while fetching archive info</p>
 {/if}
{elseif $ftype == 'image'}
  <p><img src="{$file_url}" alt=""{if !empty($setsize)} width="100"{/if} class="preview-img"></p>
{elseif $ftype == 'video'}
  <div class="preview-video"><video src="{$file_url}" width="640" height="360" controls preload="metadata"></video></div>
{elseif $ftype == 'audio'}
  <p><audio src="{$file_url}" controls preload="metadata"></audio></p>
{elseif $ftype == 'text'}
{if isset($edit)}
{$start_form}{$reporter}
{/if}
<div id="Editor" class="editor">{$content}</div>
{if isset($edit)}
<div class="pregap">
<button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
<button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
<button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
</div>
</form>
{/if}
{/if}
