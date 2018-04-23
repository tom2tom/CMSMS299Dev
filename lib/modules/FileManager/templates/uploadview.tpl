{$formstart}
 <input type="hidden" name="disable_buffer" value="1" />
{*<input type="hidden" name="MAX_FILE_SIZE" value="{$maxfilesize}" />*}{* recommendation for browser *}
 <fieldset>
  {if isset($is_ie)}
  <div class="pageerror">{$ie_upload_message}</div>
  {/if}
  <div class="upload-wrapper">
    <div style="width:60%;float:left;">
      <input id="fileupload" type="file" name="{$actionid}files[]" size="50" title="{$mod->Lang('title_filefield')}" multiple />
      <div class="pageinput pregap">
{*TODO  <button type="submit" name="{$actionid}submit" id="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>*}
        <button type="submit" name="{$actionid}cancel" id="{$actionid}cancel" class="adminsubmit icon cancel" style="display:none;">{$mod->Lang('cancel')}</button>
      </div>
    </div>
    <div id="leftcol" style="height:4em;width:40%;float:left;display:table;">
      {if !isset($is_ie)}
      <div id="dropzone" class="vcentered hcentered" title="{$mod->Lang('title_dropzone')}">
        <p id="dropzonetext">{$mod->Lang('prompt_dropfiles')}</p>
      </div>
      {/if}
    </div>
    <div class="clearb"></div>
    <div id="progressarea"></div>
  </div>
 </fieldset>
</form>
