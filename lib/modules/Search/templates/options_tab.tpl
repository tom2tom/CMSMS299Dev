{$formstart}
<div class="pageoverflow postgap">
  <label class="pagetext" for="stops">{$prompt_stopwords}:</label>
  <p class="pageinput" id="stops">{$input_stopwords|html_entity_decode}</p>

  <label class="pagetext" for="resets">{$prompt_resetstopwords}:</label>
  <p class="pageinput" id="resets">{$input_resetstopwords}</p>
</div>
<div class="pageoverflow postgap">
  <label class="pagetext" for="dostem">{$prompt_stemming}:</label>
  <p class="pageinput" id="dostem">{$input_stemming}</p>
</div>
<div class="pageoverflow postgap">
  <label class="pagetext" for="place">{$prompt_searchtext}:</label>
  <p class="pageinput" id="place">{$input_searchtext}</p>
</div>
<div class="pageoverflow postgap">
  <label class="pagetext" for="save">{$prompt_savephrases}:</label>
  <p class="pageinput" id="save">{$input_savephrases}</p>
</div>
<div class="pageoverflow postgap">
  <label class="pagetext" for="sort">{$prompt_alpharesults}:</label>
  <p class="pageinput" id="sort">{$input_alpharesults}</p>
</div>
<div class="pageoverflow postgap">
  <label class="pagetext" for="page">{$prompt_resultpage}:</label>
  <p class="pageinput" id="page">{page_selector name="{$actionid}resultpage" value="{$mod->GetPreference('resultpage')}"}</p>
</div>
<div class="pageinput">
  <button type="submit" name="{$actionid}submit" id="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  <button type="submit" name="{$actionid}reindex" class="adminsubmit icon do" onclick="cms_confirm_btnclick(this, '{$mod->Lang("confirm_reindex")|escape:"javascript"}');return false;">{$mod->Lang('reindexallcontent')}</button>
</div>
</form>
