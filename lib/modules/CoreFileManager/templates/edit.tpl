{$start_form}
<textarea id="uploader" name="{$actionid}content" style="display:none;"></textarea>
{if !empty($acts)}
<div class="actions">
{foreach $acts as $one}{$act}{/foreach}
</div>
{/if}
{if !empty($about)}
<div class="vbox">
{foreach $about as $one}<div class="hbox"><div class="boxchild">{$one@key}:</div><div class="boxchild">{$one}</div></div>{/foreach}
</div>
{/if}
<div id="Editor" class="editor">
{$content}
</div>
<div class="pregap">
<button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
<button type="submit" name="{$actionid}cancel" class="adminsubmit icon close">{$mod->Lang('cancel')}</button>
<button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
</div>
</form>
