<h3>{_ld($_module,'rotateimage')}</h3>

{$formstart}
<div class="pageinfo">{_ld($_module,'info_rotate')}</div>
<div class="pageoverflow">
  <p class="pagetext">{_ld($_module,'image')}: {$filename}</p>
  <div class="pageinput">
    <img id="rotimg" src="{$image}" width="{$width}" height="{$height}" />
  </div>
</div>
<div class="pageoverflow">
  <label class="pagetext">{_ld($_module,'angle')}:</label>
  <input type="text" readonly="readonly" id="angletxt" name="{$actionid}angle" value="0" />{* WHAT ?? *}
  <div class="pageinput">{_ld($_module,'predefined')}:
    <button class="autorotate" id="neg180" title="{_ld($_module,'rotate_neg180')}">-180</button>
    <button class="autorotate" id="neg135" title="{_ld($_module,'rotate_neg135')}">-135</button>
    <button class="autorotate" id="neg90" title="{_ld($_module,'rotate_neg90')}">-90</button>
    <button class="autorotate" id="neg45" title="{_ld($_module,'rotate_neg45')}">-45</button>
    <button class="autorotate" id="neg30" title="{_ld($_module,'rotate_neg30')}">-30</button>
    <button class="autorotate" id="pos30" title="{_ld($_module,'rotate_pos30')}">+30</button>
    <button class="autorotate" id="pos45" title="{_ld($_module,'rotate_pos45')}">+45</button>
    <button class="autorotate" id="pos90" title="{_ld($_module,'rotate_pos90')}">+90</button>
    <button class="autorotate" id="pos135" title="{_ld($_module,'rotate_pos135')}">+135</button>
    <button class="autorotate" id="pos180" title="{_ld($_module,'rotate_pos180')}">+180</button>
  </div>
  <p class="pageinput" id="rotangle" title="{_ld($_module,'info_rotate_slider')}"></p>{* WHAT ?? *}
</div>
<div class="pageoverflow">
  <label class="pagetext" for="postrotate">{_ld($_module,'postrotate')}:</label>
  {cms_help 0=$_module key='help_postrotate' title=_ld($_module,'postrotate')}
  <div class="pageinput">
    <select id="postrotate" name="{$actionid}postrotate">
      {html_options options=$opts selected=$postrotate}    </select>
  </div>
</div>
<div class="pageoverflow">
  <label class="pagetext" for="createthumb">{_ld($_module,'createthumbnail')}:</label>
  <input type="hidden" name="{$actionid}createthumb" value="0" />
  <div class="pageinput">
    <input type="checkbox" name="{$actionid}createthumb" id="createthumb" value="1"{if $createthumb} checked="checked"{/if} />
  </div>
</div>
<div class="pregap pageinput">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{_ld($_module,'save')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
</div>
</form>
