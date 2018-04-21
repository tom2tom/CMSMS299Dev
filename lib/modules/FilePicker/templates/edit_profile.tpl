{if $profile->id}
<h3>{$mod->Lang('hdr_edit_profile')} <em>({$profile->id})</em></h3>
{else}
<h3>{$mod->Lang('hdr_add_profile')}</h3>
{/if}

{form_start pid=$profile->id}
<input type="hidden" name="{$actionid}show_thumbs" value="0" />
<input type="hidden" name="{$actionid}can_upload" value="0" />
<input type="hidden" name="{$actionid}can_delete" value="0" />
<input type="hidden" name="{$actionid}can_mkdir" value="0" />
<div class="vbox">
 <div class="hbox flow">
  <div class="boxchild"><label for="profile_name" class="grid_2 required">* {$mod->Lang('name')}:</label>
  {cms_help realm=$_module key2='HelpPopup_ProfileName' title=$mod->Lang('HelpPopupTitle_ProfileName')}</div>
  <div class="boxchild fill"><input type="text" size="40" id="profile_name" name="{$actionid}name" value="{$profile->name}" required /></div>
 </div>
 <div class="hbox flow">
  <div class="boxchild"><label for="profile_top" class="grid_2">{$mod->Lang('topdir')}:</label>
  {cms_help realm=$_module key2='HelpPopup_ProfileDir' title=$mod->Lang('HelpPopupTitle_ProfileDir')}</div>
  <div class="boxchild fill"><input type="text" id="profile_top" name="{$actionid}top" value="{$profile->reltop}" size="80" /></div>
 </div>
 <div class="hbox flow">
  <div class="boxchild"><label for="profile_thumbs" class="grid_2">{$mod->Lang('show_thumbs')}:</label>
  {cms_help realm=$_module key2='HelpPopup_ProfileShowthumbs' title=$mod->Lang('HelpPopupTitle_ProfileShowthumbs')}</div>
  <div class="boxchild fill"><input type="checkbox" name="{$actionid}show_thumbs" id="profile_thumbs" value="1"{if $profile->show_thumbs} checked="checked"{/if} /></div>
 </div>
 <div class="hbox flow">
  <div class="boxchild"><label for="profile_canupload" class="grid_2">{$mod->Lang('can_upload')}:</label>
  {cms_help realm=$_module key2='HelpPopup_ProfileCan_Upload' title=$mod->Lang('HelpPopupTitle_ProfileCan_Upload')}</div>
  <div class="boxchild fill"><input type="checkbox" name="{$actionid}can_upload" id="profile_canupload" value="1"{if $profile->can_upload} checked="checked"{/if} /></div>
 </div>
 <div class="hbox flow">
  <div class="boxchild"><label for="profile_candelete" class="grid_2">{$mod->Lang('can_delete')}:</label>
  {cms_help realm=$_module key2='HelpPopup_ProfileCan_Delete' title=$mod->Lang('HelpPopupTitle_ProfileCan_Delete')}</div>
  <div class="boxchild fill"><input type="checkbox" name="{$actionid}can_delete" id="profile_candelete" value="1"{if $profile->can_delete} checked="checked"{/if} /></div>
 </div>
 <div class="hbox flow">
  <div class="boxchild"><label for="profile_canmkdir" class="grid_2">{$mod->Lang('can_mkdir')}:</label>
  {cms_help realm=$_module key2='HelpPopup_ProfileCan_Mkdir' title=$mod->Lang('HelpPopupTitle_ProfileCan_Mkdir')}</div>
  <div class="boxchild fill"><input type="checkbox" name="{$actionid}can_mkdir" id="profile_canmkdir" value="1"{if $profile->can_mkdir} checked="checked"{/if} /></div>
 </div>
</div>{*vbox*}
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" id="submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" id="cancel" class="adminsubmit icon cancel" formnovalidate>{$mod->Lang('cancel')}</button>
</div>
</form>
