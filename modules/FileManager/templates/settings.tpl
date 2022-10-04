{form_start action='savesettings'}
<div class="pageoverflow">
  {$t=_ld($_module,'enableadvanced')}<label class="pagetext" for="advancedmode">{$t}:</label>
  {cms_help 0=$_module key='help_advancedmode' title=$t}
  <input type="hidden" name="{$actionid}advancedmode" value="0">
  <div class="pageinput">
    <input type="checkbox" name="{$actionid}advancedmode" id="advancedmode" value="1"{if $advancedmode} checked{/if}>
  </div>
</div>

<div class="pageoverflow">
  {$t=_ld($_module,'showhiddenfiles')}<label class="pagetext" for="showhidden">{$t}:</label>
  {cms_help 0=$_module key='help_showhiddenfiles' title=$t}
  <input type="hidden" name="{$actionid}showhiddenfiles" value="0">
  <div class="pageinput">
    <input type="checkbox" name="{$actionid}showhiddenfiles" id="showhidden" value="1"{if $showhiddenfiles} checked{/if}>
  </div>
</div>

<div class="pageoverflow">
  {$t=_ld($_module,'showthumbnails')}<label class="pagetext" for="showthumbnails">{$t}:</label>
  {cms_help 0=$_module key='help_showthumbnails' title=$t}
  <input type="hidden" name="{$actionid}showthumbnails" value="0">
  <div class="pageinput">
    <input type="checkbox" name="{$actionid}showthumbnails" id="showthumbnails" value="1"{if $showthumbnails} checked{/if}>
  </div>
</div>

<div class="pageoverflow">
  {$t=_ld($_module,'create_thumbnails')}<label class="pagetext" for="createthumbs">{$t}:</label>
  {cms_help 0=$_module key='help_create_thumbnails' title=$t}
  <input type="hidden" name="{$actionid}create_thumbnails" value="0">
  <div class="pageinput">
    <input type="checkbox" name="{$actionid}create_thumbnails" id="createthumbs" value="1"{if $create_thumbnails} checked{/if}>
  </div>
</div>

<div class="pageoverflow">
  {$t=_ld($_module,'iconsize')}<label class="pagetext" for="iconsize">{$t}:</label>
  {cms_help 0=$_module key='help_iconsize' title=$t}
  <div class="pageinput">
  <select id="iconsize" name="{$actionid}iconsize">
    {html_options options=$iconsizes selected=$iconsize}  </select>
  </div>
</div>

<div class="pageoverflow">
  {$t=_ld($_module,'permissionstyle')}<label class="pagetext" for="permstyle">{$t}:</label>
  {cms_help 0=$_module key='help_permissionstyle' title=$t}
  <div class="pageinput">
  <select id="permstyle" name="{$actionid}permissionstyle">
    {html_options options=$permstyles selected=$permissionstyle}  </select>
  </div>
</div>

<div class="pregap pageinput">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
</div>
</form>
