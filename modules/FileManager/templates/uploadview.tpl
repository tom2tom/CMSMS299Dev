{$formstart}
{*<input type="hidden" name="MAX_FILE_SIZE" value="{$maxfilesize}" />*}{* recommendation for browser *}
 <div class="postgap">
  {if isset($is_ie)}
  <div class="pageerror">{$ie_upload_message}</div>
  {/if}
  <div class="upload-wrapper">
    <div style="float:left;">
      <input id="fileupload" type="file" name="{$actionid}files" size="50" title="{_ld($_module,'title_filefield')}" multiple />
      <div class="pageinput pregap">
{*TODO  <button type="submit" name="{$actionid}submit" id="{$actionid}submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>*}
        <button type="submit" name="{$actionid}cancel" id="{$actionid}cancel" class="adminsubmit icon cancel" style="display:none;">{_ld($_module,'cancel')}</button>
      </div>
    </div>
    <div class="cf" style="height:4em;width:40%;float:right;display:table;">
      {if !isset($is_ie)}
      <div id="dropzone" class="vcentered hcentered" title="{_ld($_module,'title_dropzone')}">
        <p id="dropzonetext">{_ld($_module,'prompt_dropfiles')}</p>
      </div>
      {/if}
    </div>
    <div id="progressarea"></div>
  </div>
 </div>
</form>
<div id="replacedialog" title="" style="display:none;min-width:15em;">
{* TODO content for a popup overwrite-confirmation dialog *}
</div>
