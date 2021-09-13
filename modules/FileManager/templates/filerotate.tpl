<h3>{$mod->Lang('rotateimage')}</h3>

{$formstart}
<div class="pageinfo">{$mod->Lang('info_rotate')}</div>
<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('image')}: {$filename}</p>
  <p class="pageinput">
    <img id="rotimg" src="{$image}" width="{$width}" height="{$height}" />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label>{$mod->Lang('angle')}:</label>
  </p>
  <input type="text" readonly="readonly" id="angletxt" name="{$actionid}angle" value="0" />
  <p class="pageinput">{$mod->Lang('predefined')}:
    <button class="autorotate" id="neg180" title="{$mod->Lang('rotate_neg180')}">-180</button>
    <button class="autorotate" id="neg135" title="{$mod->Lang('rotate_neg135')}">-135</button>
    <button class="autorotate" id="neg90" title="{$mod->Lang('rotate_neg90')}">-90</button>
    <button class="autorotate" id="neg45" title="{$mod->Lang('rotate_neg45')}">-45</button>
    <button class="autorotate" id="neg30" title="{$mod->Lang('rotate_neg30')}">-30</button>
    <button class="autorotate" id="pos30" title="{$mod->Lang('rotate_pos30')}">+30</button>
    <button class="autorotate" id="pos45" title="{$mod->Lang('rotate_pos45')}">+45</button>
    <button class="autorotate" id="pos90" title="{$mod->Lang('rotate_pos90')}">+90</button>
    <button class="autorotate" id="pos135" title="{$mod->Lang('rotate_pos135')}">+135</button>
    <button class="autorotate" id="pos180" title="{$mod->Lang('rotate_pos180')}">+180</button>
  </p>
  <p class="pageinput" id="rotangle" title="{$mod->Lang('info_rotate_slider')}">
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="postrotate">{$mod->Lang('postrotate')}:</label>
    {cms_help realm=$_module key2='help_postrotate' title=$mod->Lang('postrotate')}
  </p>
  <p class="pageinput">
    <select id="postrotate" name="{$actionid}postrotate">
    {html_options options=$opts selected=$postrotate}
    </select>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="createthumb">{$mod->Lang('createthumbnail')}:</label>
  </p>
  <input type="hidden" name="{$actionid}createthumb" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}createthumb" id="createthumb" value="1"{if $createthumb} checked="checked"{/if} />
  </p>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}rotate" class="adminsubmit icon do">{$mod->Lang('save')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </p>
</div>
</form>
