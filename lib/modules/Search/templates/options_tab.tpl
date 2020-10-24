{$formstart}
<div class="pageoverflow postgap">
  {$t=$prompt_stopwords}<label class="pagetext" for="stops">{$t}:</label>
  {cms_help realm=$_module key2='help_stopwords' title=$t}
  <p class="pageinput" id="stops">{$input_stopwords|html_entity_decode}</p>
</div>
<div class="pageoverflow postgap">
  {$t=$prompt_resetstopwords}<label class="pagetext" for="resets">{$t}:</label>
  {cms_help realm=$_module key2='help_resetstopwords' title=$t}
  <p class="pageinput">
  <button type="submit" name="{$actionid}resettodefault" id="resets" class="adminsubmit icon undo">{$mod->Lang('input_resetstopwords')}</button>
  </p>
</div>
<input type="hidden" name="{$actionid}usestemming" value="0" />
<div class="pageoverflow postgap">
  {$t=$prompt_stemming}<label class="pagetext" for="dostem">{$t}:</label>
  {cms_help realm=$_module key2='help_stemming' title=$t}<br />
  <input type="checkbox" name="{$actionid}usestemming" id="dostem" class="pageinput pagecheckbox" value="1"{if $stemming} checked="checked"{/if} />
</div>
<div class="pageoverflow postgap">
  {$t=$prompt_searchtext}<label class="pagetext" for="prompt">{$t}:</label>
  {cms_help realm=$_module key2='help_searchtext' title=$t}<br />
  <input type="text" name="{$actionid}searchtext" id="prompt" class="pageinput" value="{$searchtext}" size="15" maxlength="100" />
</div>
<input type="hidden" name="{$actionid}savephrases" value="0" />
<div class="pageoverflow postgap">
  {$t=$prompt_savephrases}<label class="pagetext" for="phrases">{$t}:</label>
  {cms_help realm=$_module key2='help_savephrases' title=$t}<br />
  <input type="checkbox" name="{$actionid}savephrases" id="phrases" class="pageinput pagecheckbox" value="1"{if $savephrases} checked="checked"{/if} />
</div>
<input type="hidden" name="{$actionid}alpharesults" value="0" />
<div class="pageoverflow postgap">
  {$t=$prompt_alpharesults}<label class="pagetext" for="alpha">{$t}:</label>
  {*cms_help realm=$_module key2='help_alpharesults' title=$t*}<br />
  <input type="checkbox" name="{$actionid}alpharesults" id="alpha" class="pageinput pagecheckbox" value="1"{if $alpharesults} checked="checked"{/if} />
</div>
<div class="pageoverflow postgap">
  {$t=$prompt_resultpage}<label class="pagetext" for="page">{$t}:</label>
  {cms_help realm=$_module key2='help_resultpage' title=$t}
  <p class="pageinput" id="page">{$input_resultpage}</p>
</div>
<div class="pageinput">
  <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  &nbsp;
  <button type="submit" name="{$actionid}reindex" class="adminsubmit icon do">{$mod->Lang('reindexallcontent')}</button>
</div>
</form>
