<h3>{$mod->Lang('resizecrop')}</h3>

{$formstart}
<div>
  <div id="test1" style="width: 74%; float: left;">
    <img id="img" src="{$image}" alt=""/>
  </div>
  <div style="width: 24%; float: left;">
    <div style="pageoverflow">
      <p class="pagetext">{$mod->Lang('image')}:&nbsp;{$filename}</p>
      <p class="pagetext">{$mod->Lang('pie_image_natural_size')}: <span id="natsize"></span></p>
    </div>
    <table id="coords" class="coords">
      <tr><td>
      <label for="cx">{$mod->Lang("pie_crop_x")}:</label></td><td><input type="text" id='cx' size="6" name='{$actionid}cx' /></td></tr>
      <tr><td>
      <label for="cy">{$mod->Lang("pie_crop_y")}:</label></td><td><input type="text" id='cy' size="6" name='{$actionid}cy' /></td></tr>
      <tr><td>
      <label for="cw">{$mod->Lang("pie_crop_w")}:</label></td><td><input type="text" id='cw' size="6" name='{$actionid}cw' /></td></tr>
      <tr><td>
      <label for="ch">{$mod->Lang("pie_crop_h")}:</label></td><td><input type="text" id='ch' size="6" name='{$actionid}ch' /></td></tr>
      <tr><td>
      <label for="iw">{$mod->Lang("pie_image_w")}:</label></td><td><input type="text" id='iw' size="6" name='{$actionid}iw' /></td></tr>
      <tr><td>
      <label for="ih">{$mod->Lang("pie_image_h")}:</label></td><td><input type="text" id='ih' size="6" name='{$actionid}ih' /></td></tr>
      <tr><td>
      <label for="lp">{$mod->Lang("pie_lock_proportion")}:</label></td><td><input type="checkbox" id="lp" checked="checked" /></td></tr>
    </table>
    <div style="pageoverflow">
      <button id="submit" name="{$actionid}save">{$mod->Lang('save')}</p>
      <button name="{$actionid}cancel">{$mod->Lang('cancel')}</p>
    </div>
  </div>
  <div style="clear: both;"></div>
</div>
</form>
