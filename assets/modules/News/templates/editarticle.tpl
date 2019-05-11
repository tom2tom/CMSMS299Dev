{if $articleid >= 0}{$edit=true}{else}{$edit=false}{/if}
<h3>{if $edit}{$mod->Lang('editarticle')}{else}{$mod->Lang('addarticle')}{/if}</h3>
<div id="edit_news">
  {$startform}
  <div class="postgap">
    <p class="pageinput">
      <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" id="{$actionid}cancel" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>
      <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{lang('apply')}</button>
    </p>
  </div>

  {if isset($start_tab_headers)}
  {$start_tab_headers}
  {$tabheader_article}
  {$tabheader_preview}
  {$end_tab_headers}

  {$start_tab_content}
  {$start_tab_article}
  {/if}

  {if $edit}
   <p class="pagetext">{$mod->Lang('title_history')}:</p>
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
      <p class="pagetext">
        {$t=$mod->Lang('title')}<label for="fld1">*{$t}:</label> {cms_help realm=$_module key='help_article_title' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" name="{$actionid}title" id="fld1" value="{$title}" size="80" maxlength="255" required="required" />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        {$t=$mod->Lang('category')}<label for="fld2">*{$t}:</label> {cms_help realm=$_module key='help_article_category' title=$t}
      </p>
      <p class="pageinput">
        <select name="{$actionid}category" id="fld2">
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
       {$t=$mod->Lang('content')}*{$t}: {cms_help realm=$_module key='help_article_content' title=$t}
      </p>
      <p class="pageinput">
        {$inputcontent}
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        {$t=$mod->Lang('searchable')}<label for="fld6">{$t}:</label>
        {cms_help realm=$_module key='help_article_searchable' title=$t}
      </p>
      <input type="hidden" name="{$actionid}searchable" value="0" />
      <p class="pageinput">
        <input type="checkbox" name="{$actionid}searchable" id="fld6" value="1"{if $searchable} checked="checked"{/if} />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        {$t=$mod->Lang('url')}<label for="fld7">{$t}:</label> {cms_help realm=$_module key='help_article_url' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" name="{$actionid}news_url" id="fld7" value="{$news_url}" size="50" maxlength="255" />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        {$t=$mod->Lang('extra')}<label for="fld8">{$t}:</label> {cms_help realm=$_module key='help_article_extra' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" name="{$actionid}extra" id="fld8" value="{$extra}" size="50" maxlength="255" />
      </p>
    </div>
  </div>

  {if isset($statuses)}
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('status')}<label for="fld9">*{$t}:</label> {cms_help realm=$_module key='help_article_status' title=$t}
      </p>
      <p class="pageinput">
        {$statuses}
      </p>
    </div>
  {else}
    <input type="hidden" name="{$actionid}status" value="{$status}" />
  {/if}

  <div id="pickers" class="pageoverflow pagetext">
    <p class="pageinput">
    {$t=$mod->Lang('title_publish')}{$t}:
    <input type="text" name="{$actionid}fromdate" data-select="datepicker" value="{$fromdate}" size="12" />
    {if $withtime}{$mod->Lang('at')} <input type="text" name="{$actionid}fromtime" class="time" value="{$fromtime}" size="10" />{/if}
    {cms_help realm=$_module key='help_article_publish' title=$t}
    </p>
    <p class="pageinput">
    {$t=$mod->Lang('title_expire')}{$t}:
    <input type="text" name="{$actionid}todate" data-select="datepicker" value="{$todate}" size="12" />
    {if $withtime}{$mod->Lang('at')} <input type="text" name="{$actionid}totime" class="time" value="{$totime}" size="10" />{/if}
    {cms_help realm=$_module key='help_article_expire' title=$t}
    </p>
  </div>
  {$end_tab}{*article*}

  {if isset($start_tab_preview)} {$start_tab_preview}
  <div class="pagewarn">{$mod->Lang('warning_preview')}</div>
  <fieldset>
    <label for="preview_template">{$mod->Lang('detail_template')}:</label>
    <select name="{$actionid}preview_template" id="preview_template">
      {html_options options=$detail_templates selected=$cur_detail_template}
    </select>&nbsp;
    <label>{$mod->Lang('detail_page')}: {$preview_returnid}</label>
  </fieldset>
  <br />
  <iframe id="previewframe" style="height: 800px; width: 100%; border: 1px solid black; overflow: auto;"></iframe>
  {$end_tab}{*preview*}
  {/if}
  {if isset($start_tab_headers)}
  {$end_tab_content}
  {/if}

  <div class="pregap">
    <p class="pageinput">
      <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">&nbsp;{lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" id="{$actionid}cancel" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>
      <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{lang('apply')}</button>
    </p>
  </div>
 </form>
</div>
