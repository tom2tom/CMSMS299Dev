{$formstart2}
<div class="pageoverflow postgap">
  {$t=$prompt_stopwords}<label class="pagetext" for="stops">{$t}:</label>
  {cms_help realm=$_module key='help_stopwords' title=$t}
  <p class="pageinput" id="stops">{html_entity_decode($input_stopwords)}</p>{*TODO why decode for display?*}
</div>
<div class="pageoverflow postgap">
  {$t=$prompt_resetstopwords}<label class="pagetext" for="resets">{$t}:</label>
  {cms_help realm=$_module key='help_resetstopwords' title=$t}
  <p class="pageinput">
  <button type="submit" name="{$actionid}resettodefault" id="resets" class="adminsubmit icon undo">{_ld($_module,'input_resetstopwords')}</button>
  </p>
</div>
<input type="hidden" name="{$actionid}usestemming" value="0">
<div class="pageoverflow postgap">
  {$t=$prompt_stemming}<label class="pagetext" for="dostem">{$t}:</label>
  {cms_help realm=$_module key='help_stemming' title=$t}<br>
  <input type="checkbox" name="{$actionid}usestemming" id="dostem" class="pageinput pagecheckbox" value="1"{if $stemming} checked{/if}>
</div>
<div class="pageoverflow postgap">
  {$t=$prompt_searchtext}<label class="pagetext" for="prompt">{$t}:</label>
  {cms_help realm=$_module key='help_searchtext' title=$t}<br>
  <input type="text" name="{$actionid}searchtext" id="prompt" class="pageinput" value="{$searchtext}" size="15" maxlength="100">
</div>
<input type="hidden" name="{$actionid}savephrases" value="0">
<div class="pageoverflow postgap">
  {$t=$prompt_savephrases}<label class="pagetext" for="phrases">{$t}:</label>
  {cms_help realm=$_module key='help_savephrases' title=$t}<br>
  <input type="checkbox" name="{$actionid}savephrases" id="phrases" class="pageinput pagecheckbox" value="1"{if $savephrases} checked{/if}>
</div>
<input type="hidden" name="{$actionid}alpharesults" value="0">
<div class="pageoverflow postgap">
  {$t=$prompt_alpharesults}<label class="pagetext" for="alpha">{$t}:</label>
  {*cms_help 0=$_module key='help_alpharesults' title=$t*}<br>
  <input type="checkbox" name="{$actionid}alpharesults" id="alpha" class="pageinput pagecheckbox" value="1"{if $alpharesults} checked{/if}>
</div>
<div class="pageoverflow postgap">
  {$t=$prompt_resultpage}<label class="pagetext" for="page">{$t}:</label>
  {cms_help realm=$_module key='help_resultpage' title=$t}
  <p class="pageinput" id="page">{$input_resultpage}</p>
</div>
<div class="pageinput">
  <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{_ld($_module,'apply')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  &nbsp;
  <button type="submit" name="{$actionid}reindex" class="adminsubmit icon do">{_ld($_module,'reindexallcontent')}</button>
</div>
</form>
