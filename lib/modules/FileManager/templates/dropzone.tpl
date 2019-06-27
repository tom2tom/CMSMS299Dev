{if !isset($is_ie)}{* IE sucks... we only do this for REAL browsers *}
<div class="drop">
  <div class="drop-inner cf">
    {if isset($dirlist)}
    <span class="folder-selection open" title="{$mod->Lang('open')}"></span>
    <div class="dialog invisible" role="dialog" title="{$mod->Lang('change_working_folder')}">
      {$chdir_formstart}
        <fieldset>
          <legend>{$mod->Lang('change_working_folder')}</legend>
          <label for="fm_newdir">{$mod->Lang('folder')}:</label>
          <select class="cms_dropdown" id="fm_newdir" name="{$actionid}newdir">
            {html_options options=$dirlist selected="/`$cwd`"}
          </select>
          <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
        </fieldset>
      </form>
    </div>
    {/if}
    <div class="zone">
      <div id="theme_dropzone">
        {$formstart}
         <input type="hidden" name="disable_buffer" value="1" />
         <input type="file" id="theme_dropzone_i" name="{$actionid}files" multiple style="display:none;" />
         {$prompt_dropfiles}
        </form>
      </div>
    </div>
  </div>
  {$s={$mod->Lang('open')}/{$mod->Lang('close')}}<a href="#" title="{$s}" class="toggle-dropzone">{$s}</a>
</div>
{/if}{*!isset($is_ie)*}
