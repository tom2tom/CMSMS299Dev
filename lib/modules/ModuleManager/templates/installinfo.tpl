{if $is_upgrade}
  <h3>{$mod->Lang('upgrade_module')} {$module_name} <em>({$mod->Lang('version')} {$module_version})</em></h3>
{else}
  <h3>{$mod->Lang('install_module')} {$module_name} <em>({$mod->Lang('version')} {$module_version})</em></h3>
{/if}

<div class="pagewarn">
  <h3>{$mod->Lang('notice')}:</h3>
  {$mod->Lang('time_warning')}
</div>
<div class="clearb"></div>

{if isset($dependencies)}
  {$has_custom=0}
  {foreach $dependencies as $name => $rec}
     {if $rec.has_custom}{$has_custom=1}{/if}
  {/foreach}
  {if $has_custom}
    <div class="pagewarn">
      <h3>{$mod->Lang('warning')}</h3>
      {$mod->Lang('warn_modulecustom')}
      <ul>
        {foreach $dependencies as $name => $rec}
          {if $rec.has_custom}<li>{$name}</li>{/if}
	{/foreach}
      </ul>
    </div>
    <div class="clearb"></div>
  {/if}

  {if count($dependencies) > 1}
    <div class="pagewarn">
      <h3>{$mod->Lang('warning')}</h3>
      {$mod->Lang('warn_dependencies')}
    </div>

    <ul>
    {foreach $dependencies as $name => $rec}
      <li>
        {if $rec.action == 'i'}{$mod->Lang('depend_install',$rec.name,$rec.version)}
        {elseif $rec.action == 'u'}{$mod->Lang('depend_upgrade',$rec.name,$rec.version)}
        {elseif $rec.action == 'a'}{$mod->Lang('depend_activate',$rec.name)}{/if}
      </li>
    {/foreach}
    </ul>
  {/if}
{/if}

{if isset($form_start)}
{$form_start}
<div class="pageinput pregap">
 {if count($dependencies) > 1}
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{$mod->Lang('install_procede')}</button>
{else}
 <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('install_submit')}</button>
{/if}
 <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
</div>
</form>
{/if}
