{if !empty($acts)}
<div class="actions" style="float:right;">
{foreach $acts as $one}{$one}{/foreach}
</div>
<div class="clearb postgap"></div>
{/if}
{if !empty($about)}
<div class="colbox postgap">
{foreach $about as $one}<div class="rowbox"><div class="boxchild">{$one@key}:&nbsp;</div><div class="boxchild">{$one}</div></div>{/foreach}
</div>
{/if}
{$start_form}
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
  <p>{$mod->Lang('err_arch')}</p>
 {/if}
{elseif $ftype == 'image'}
  <p><img src="{$file_url}" alt=""{if !empty($setsize)} width="100"{/if} class="preview-img"></p>
{elseif $ftype == 'video'}
  <div class="preview-video"><video src="{$file_url}" width="640" height="360" controls preload="metadata"></video></div>
{elseif $ftype == 'audio'}
  <p><audio src="{$file_url}" controls preload="metadata"></audio></p>
{elseif $ftype == 'text'}
  <textarea{if isset($edit)} name="{$actionid}content"{/if} id="content">{$content}</textarea>
{/if}
<div class="pregap">
{if isset($edit)}
<button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
<button type="submit" name="{$actionid}close" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
<button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
{else}
<button type="submit" name="{$actionid}close" class="adminsubmit icon cancel">{$mod->Lang('close')}</button>
{/if}
</div>
</form>
