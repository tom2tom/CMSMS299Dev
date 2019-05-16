{if $articleid >= 0}{$edit=true}{else}{$edit=false}{/if}
<h3>{if $edit}{$mod->Lang('prompt_editarticle')}{else}{$mod->Lang('prompt_addarticle')}{/if}</h3>
{form_start action=$formaction id='edit_news' extraparms=$formparms}
  <div class="pageoverflow postgap">
    <div class="pageinput">
      <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" id="cancel" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>
      <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{lang('apply')}</button>
    </div>
  </div>

{if !empty($preview)}
  {tabs_restart}
  {tab_header name='article' label=$mod->Lang('article')}
  {tab_header name='preview' label=$mod->Lang('preview')}
  {tab_start name='article'}
{/if}

{if $edit}
   <p class="pagetext">{$mod->Lang('prompt_history')}:</p>
   <p class="pageinput">
   {$mod->Lang('created')}: {$createat}
{if isset($modat)}<br /> {$mod->Lang('modified')}: {$modat}{/if}
{if isset($pubat)}<br /> {$mod->Lang('published')}: {$pubat}{/if}
{if isset($archat)}<br /> {$mod->Lang('archived')}: {$archat}{/if}
   </p>
{/if}

  <div id="edit_article">
    {if $inputauthor}
    <div class="pageoverflow">
      <p class="pagetext">{$mod->Lang('author')}:</p>
      <p class="pageinput">
        {$inputauthor}
      </p>
    </div>
    {/if}

    <div class="pageoverflow">
      {$t=$mod->Lang('title')}<label class="pagetext" for="itemtitle">* {$t}:</label>
      {cms_help realm=$_module key='help_article_title' title=$t}
      <p class="pageinput">
        <input type="text" name="{$actionid}title" id="itemtitle" value="{$title}" size="32" maxlength="48" required="required" />
      </p>
    </div>

    <div class="pageoverflow">
      {$t=$mod->Lang('category')}<label class="pagetext" for="itemcat">* {$t}:</label>
      {cms_help realm=$_module key='help_article_category' title=$t}
      <p class="pageinput">
        <select name="{$actionid}category" id="itemcat">
         {html_options options=$categorylist selected=$category}
        </select>
      </p>
    </div>
    {if empty($hide_summary_field)}
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=$mod->Lang('summary')}{$t}: {cms_help realm=$_module key='help_article_summary' title=$t}
      </p>
      <p class="pageinput">
        {$inputsummary}
      </p>
    </div>
    {/if}

    <div class="pageoverflow">
      <p class="pagetext">
      {$t=$mod->Lang('content')}* {$t}: {cms_help realm=$_module key='help_article_content' title=$t}
      </p>
      <p class="pageinput">
        {$inputcontent}
      </p>
    </div>

    <div class="pageoverflow">
      {$t=$mod->Lang('searchable')}<label class="pagetext" for="cansearch">{$t}:</label>
      {cms_help realm=$_module key='help_article_searchable' title=$t}
      <input type="hidden" name="{$actionid}searchable" value="0" />
      <p class="pageinput">
        <input type="checkbox" name="{$actionid}searchable" id="cansearch" value="1"{if $searchable} checked="checked"{/if} />
      </p>
    </div>

    <div class="pageoverflow">
      {$t=$mod->Lang('url')}<label class="pagetext" for="urlslug">{$t}:</label>
      {cms_help realm=$_module key='help_article_url' title=$t}
      <p class="pageinput">
        <input type="text" name="{$actionid}news_url" id="urlslug" value="{$news_url}" size="50" maxlength="255" />
      </p>
    </div>

    <div class="pageoverflow">
      {$t=$mod->Lang('extra')}<label class="pagetext" for="extradata">{$t}:</label>
      {cms_help realm=$_module key='help_article_extra' title=$t}
      <p class="pageinput">
        <input type="text" name="{$actionid}extra" id="extradata" value="{$extra}" size="50" maxlength="255" />
      </p>
    </div>
  </div>

  {if isset($statuses)}
    <div class="pageoverflow">
     {$t=lang('status')}<label class="pagetext" for="TODOstatus">* {$t}:</label>
     {cms_help realm=$_module key='help_article_status' title=$t}
      <div class="pageinput">
        {$statuses}{* radio group *}
      </div>
    </div>
  {else}
    <input type="hidden" name="{$actionid}status" value="{$status}" />
  {/if}

  <div id="pickers" class="pageoverflow pagetext">
    <p class="pageinput">
    {$t=$mod->Lang('prompt_publish')}{$t}:
    <input type="text" name="{$actionid}fromdate" data-select="datepicker" value="{$fromdate}" size="12" />
    {if $withtime}{$mod->Lang('at')} <input type="text" name="{$actionid}fromtime" class="time" value="{$fromtime}" size="10" />{/if}
    {cms_help realm=$_module key='help_article_publish' title=$t}
    </p>
    <p class="pageinput">
    {$t=$mod->Lang('prompt_expire')}{$t}:
    <input type="text" name="{$actionid}todate" data-select="datepicker" value="{$todate}" size="12" />
    {if $withtime}{$mod->Lang('at')} <input type="text" name="{$actionid}totime" class="time" value="{$totime}" size="10" />{/if}
    {cms_help realm=$_module key='help_article_expire' title=$t}
    </p>
  </div>

{if !empty($preview)}
  {tab_start name='preview'}
  <div class="pagewarn">{$mod->Lang('warning_preview')}</div>
  <fieldset>
    <label for="preview_template">{$mod->Lang('detail_template')}:</label>
    <select name="{$actionid}preview_template" id="preview_template">
      {html_options options=$detail_templates selected=$cur_detail_template}
    </select>&nbsp;
    <label for="cms_hierdropdown1">{$mod->Lang('detail_page')}:</label>
    {$preview_returnid}
  </fieldset>
  <br />
  <iframe id="previewframe" style="height:800px;width:100%;border:1px solid black;overflow:auto;"></iframe>

  {tab_end}
{/if}

  <div class="pageoverflow pregap">
    <div class="pageinput">
      <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">&nbsp;{lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" id="cancel" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>
      <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{lang('apply')}</button>
    </div>
  </div>
</form>
