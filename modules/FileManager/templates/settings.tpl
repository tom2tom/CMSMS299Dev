{form_start action='savesettings'}
<div class="pageoverflow">
  <p class="pagetext">
    <label for="advancedmode">{$mod->Lang('enableadvanced')}:</label>
    {cms_help realm=$_module key2='help_advancedmode' title=$mod->Lang('enableadvanced')}
  </p>
  <input type="hidden" name="{$actionid}advancedmode" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}advancedmode" id="advancedmode" value="1"{if $advancedmode} checked="checked"{/if} />
  </p>
</div>

<div class="pageoverflow">
  <p class="pagetext">
    <label for="showhidden">{$mod->Lang('showhiddenfiles')}:</label>
    {cms_help realm=$_module key2='help_showhiddenfiles' title=$mod->Lang('showhiddenfiles')}
  </p>
  <input type="hidden" name="{$actionid}showhiddenfiles" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}showhiddenfiles" id="showhidden" value="1"{if $showhiddenfiles} checked="checked"{/if} />
  </p>
</div>

<div class="pageoverflow">
  <p class="pagetext">
    <label for="showthumbnails">{$mod->Lang('showthumbnails')}:</label>
    {cms_help realm=$_module key2='help_showthumbnails' title=$mod->Lang('showthumbnails')}
  </p>
  <input type="hidden" name="{$actionid}showthumbnails" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}showthumbnails" id="showthumbnails" value="1"{if $showthumbnails} checked="checked"{/if} />
  </p>
</div>

<div class="pageoverflow">
  <p class="pagetext">
    <label for="createthumbs">{$mod->Lang('create_thumbnails')}:</label>
    {cms_help realm=$_module key2='help_create_thumbnails' title=$mod->Lang('create_thumbnails')}
  </p>
  <input type="hidden" name="{$actionid}create_thumbnails" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}create_thumbnails" id="createthumbs" value="1"{if $create_thumbnails} checked="checked"{/if} />
  </p>
</div>

<div class="pageoverflow">
  <p class="pagetext">
    <label for="iconsize">{$mod->Lang('iconsize')}:</label>
    {cms_help realm=$_module key2='help_iconsize' title=$mod->Lang('iconsize')}
  </p>
  <p class="pageinput">
    <select id="iconsize" name="{$actionid}iconsize">
      {html_options options=$iconsizes selected=$iconsize}
    </select>
  </p>
</div>

<div class="pageoverflow">
  <p class="pagetext">
    <label for="permstyle">{$mod->Lang('permissionstyle')}:</label>
    {cms_help realm=$_module key2='help_permissionstyle' title=$mod->Lang('permissionstyle')}
  </p>
  <p class="pageinput">
    <select id="permstyle" name="{$actionid}permissionstyle">
      {html_options options=$permstyles selected=$permissionstyle}
    </select>
  </p>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  </p>
</div>
</form>
