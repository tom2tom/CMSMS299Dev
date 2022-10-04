{if !isset($is_ie)}{* IE sucks... we only do this for REAL browsers *}
<div class="drop">
  <div class="drop-inner cf">
    {if isset($dirlist)}
    <span class="folder-selection open" title="{_ld($_module,'open')}"></span>
    <div class="dialog invisible" role="dialog" title="{_ld($_module,'change_working_folder')}">
      {$chdir_formstart}
        <fieldset>
          <legend>{_ld($_module,'change_working_folder')}</legend>
          <label for="fm_newdir">{_ld($_module,'folder')}:</label>
          <select class="cms_dropdown" id="fm_newdir" name="{$actionid}newdir">
            {html_options options=$dirlist selected="/{$cwd}"}
          </select>
          <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
        </fieldset>
      </form>
    </div>
    {/if}
    <div class="zone">
      <div id="theme_dropzone">
        {$formstart}
         <input type="hidden" name="disable_buffer" value="1">
         <input type="file" id="theme_dropzone_i" name="{$actionid}files" multiple style="display:none;">
         {$prompt_dropfiles}
        </form>
      </div>
    </div>
  </div>
  {$s={_ld($_module,'open')}/{_ld($_module,'close')}}<a href="javascript:void()" title="{$s}" class="toggle-dropzone">{$s}</a>
</div>
{/if}{*!isset($is_ie)*}
