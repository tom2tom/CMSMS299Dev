{if $articleid >= 0}{$edit=true}{else}{$edit=false}{/if}
<h3>{if $edit}{_ld($_module,'prompt_editarticle')}{else}{_ld($_module,'prompt_addarticle')}{/if}</h3>
{form_start action=$formaction id='edit_news' extraparms=$formparms}
  <div class="pageoverflow postgap">
    <div class="pageinput">
      <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_la('submit')}</button>
      <button type="submit" name="{$actionid}cancel" id="cancel" class="adminsubmit icon cancel" formnovalidate>{_la('cancel')}</button>
      <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{_la('apply')}</button>
    </div>
  </div>

{if !empty($preview)}
  {tabs_restart}
  {tab_header name='article' label=_ld($_module,'article')}
  {tab_header name='preview' label=_ld($_module,'preview')}
  {tab_start name='article'}
{/if}

{if $edit}
   <p class="pagetext">{_ld($_module,'prompt_history')}:</p>
   <div class="pageinput">
   {_ld($_module,'created')}: {$createat}
{if isset($modat)}<br>{_ld($_module,'modified')}: {$modat}{/if}
{if isset($pubat)}<br>{_ld($_module,'published')}: {$pubat}{/if}
{if isset($archat)}<br>{_ld($_module,'archived')}: {$archat}{/if}
   </div>
{/if}

  <div id="edit_article">
    {if $inputauthor}
    <div class="pageoverflow">
      <p class="pagetext">{_ld($_module,'author')}:</p>
      <div class="pageinput">
        {$inputauthor}
      </div>
    </div>
    {/if}

    <div class="pageoverflow">
      {$t=_ld($_module,'title')}<label class="pagetext" for="itemtitle">* {$t}:</label>
      {cms_help realm=$_module key='help_article_title' title=$t}
      <div class="pageinput">
        <input type="text" name="{$actionid}title" id="itemtitle" value="{$title}" size="32" maxlength="48" required>
      </div>
    </div>

    {if !empty($categorylist)}
    <div class="pageoverflow">
      {$t=_ld($_module,'category')}<label class="pagetext" for="itemcat">* {$t}:</label>
      {cms_help realm=$_module key='help_article_category' title=$t}
      <div class="pageinput">
        <select name="{$actionid}category" id="itemcat">
          {html_options options=$categorylist selected=$category}        </select>
      </div>
    </div>
    {/if}

    <div class="pageoverflow pagetext" style="max-height:12em">
      {$t=_ld($_module,'summary')}{$t}:
      {cms_help realm=$_module key='help_article_summary' title=$t}
      <p class="pageinput">
        {$inputsummary}
      </p>
    </div>

    <div class="pageoverflow pagetext">
      {$t=_ld($_module,'content')}* {$t}:
      {cms_help realm=$_module key='help_article_content' title=$t}
      <p class="pageinput">
        {$inputcontent}
      </p>
    </div>

    <div class="pageoverflow">
      {$t=_ld($_module,'searchable')}<label class="pagetext" for="cansearch">{$t}:</label>
      {cms_help realm=$_module key='help_article_searchable' title=$t}
      <input type="hidden" name="{$actionid}searchable" value="0">
      <div class="pageinput">
        <input type="checkbox" name="{$actionid}searchable" id="cansearch" value="1"{if $searchable} checked{/if}>
      </div>
    </div>

    <div class="pageoverflow">
      {$t=_ld($_module,'prettyurl')}<label class="pagetext" for="itemurl">{$t}:</label>
      {cms_help realm=$_module key='help_article_url' title=$t}
      <div class="pageinput">
        <input type="text" name="{$actionid}news_url" id="itemurl" value="{$news_url}" size="32" maxlength="64"><br>
        <input type="checkbox" id="genurl" name="{$actionid}generate_url" value="1">
        <label for="genurl">{_ld($_module,'generateurl')}</label>
      </div>
    </div>

    <div class="pageoverflow">
      {$t=_ld($_module,'extra')}<label class="pagetext" for="extradata">{$t}:</label>
      {cms_help realm=$_module key='help_article_extra' title=$t}
      <div class="pageinput">
        <input type="text" name="{$actionid}extra" id="extradata" value="{$extra}" size="50" maxlength="255">
      </div>
    </div>

    <div class="pageoverflow">
      <label class="pagetext" for="itemimage">{_ld($_module,'item_image')}:</label>
      <div class="pageinput">
        <img id="itemimage" class="yesimage" src="{$image_url}" alt="{$image_url}">
        <br class="yesimage">
        {$filepicker}
      </div>
    </div>

  {if isset($statuses)}
    <div class="pageoverflow">
      {$t=_la('status')}<label class="pagetext">* {$t}:</label>
      {cms_help realm=$_module key='help_article_status' title=$t}
      <div class="pageinput">
        {$statuses}{* radio group *}
      </div>
    </div>
  {else}
    <input type="hidden" name="{$actionid}status" value="{$status}">
  {/if}

    <div id="pickers" class="pageoverflow pagetext">
      {$t=_ld($_module,'prompt_publish')}{$t}:
      {cms_help realm=$_module key='help_article_publish' title=$t}
      <div class="pageinput">
        <input type="text" name="{$actionid}fromdate" data-select="datepicker" value="{$fromdate}" size="12">
        {if $withtime}{_ld($_module,'at')} <input type="text" name="{$actionid}fromtime" class="time" value="{$fromtime}" size="10">{/if}
      </div>
      {$t=_ld($_module,'prompt_expire')}{$t}:
      {cms_help realm=$_module key='help_article_expire' title=$t}
      <div class="pageinput">
        <input type="text" name="{$actionid}todate" data-select="datepicker" value="{$todate}" size="12">
        {if $withtime}{_ld($_module,'at')} <input type="text" name="{$actionid}totime" class="time" value="{$totime}" size="10">{/if}
      </div>
    </div>{*pickers*}
  </div>{*edit_article*}

{if !empty($preview)}
  {tab_start name='preview'}
  <div class="pagewarn">{_ld($_module,'warning_preview')}</div>
  <fieldset>
    <label for="preview_template">{_ld($_module,'detail_template')}:</label>
    <select name="{$actionid}preview_template" id="preview_template">
      {html_options options=$detail_templates selected=$cur_detail_template}
    </select>&nbsp;
    <label for="cms_hierdropdown1">{_ld($_module,'detail_page')}:</label>
    {$preview_returnid}
  </fieldset>
  <br>
  <iframe id="previewframe" style="height:800px;width:100%;border:1px solid black;overflow:auto"></iframe>

  {tab_end}
{/if}

  <div class="pregap pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_la('submit')}</button>
    <button type="submit" name="{$actionid}cancel" id="cancel" class="adminsubmit icon cancel" formnovalidate>{_la('cancel')}</button>
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{_la('apply')}</button>
  </div>

  <div id="post_notice" title="" style="display:none">
    <div class="TODO">
      <p>{_ld($_module,'info_notified')}}</p>
    </div>
  </div>
</form>
