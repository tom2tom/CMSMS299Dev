{* default CC-advice template *}
{* <h4>{_ld($_module,'TODO title')}</h4> *}
{$formstart}
  {$t=_ld($_module,'TODO elemtitle')}<label class="pagetext" for="cctext">{$t}:</label>
  {cms_help 0=$_module key='info_enabled' title=$t}
  <div class="pageinput">
    <textarea id="cctext" name="{$actionid}cctext" rows="4" cols="40">{$cctext}</textarea>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon submit">{_ld($_module,'submit')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </div>
</form>
