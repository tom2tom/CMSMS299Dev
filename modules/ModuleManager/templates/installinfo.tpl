{if $is_upgrade}
  <h3>{_ld($_module,'upgrade_module')} {$module_name} <em>({_ld($_module,'version')} {$module_version})</em></h3>
{else}
  <h3>{_ld($_module,'install_module')} {$module_name} <em>({_ld($_module,'version')} {$module_version})</em></h3>
{/if}

<div class="pagewarn cf">
  <h3>{_ld($_module,'notice')}:</h3>
  {_ld($_module,'time_warning')}
</div>

{if isset($dependencies)}
  {$has_custom=0}
  {foreach $dependencies as $name => $rec}
     {if $rec.has_custom}{$has_custom=1}{/if}
  {/foreach}
  {if $has_custom}
    <div class="pagewarn cf">
      <h3>{_ld($_module,'warning')}</h3>
      {_ld($_module,'warn_modulecustom')}
      <ul>
        {foreach $dependencies as $name => $rec}
          {if $rec.has_custom}<li>{$name}</li>{/if}
	{/foreach}
      </ul>
    </div>
  {/if}

  {if count($dependencies) > 1}
    <div class="pagewarn">
      <h3>{_ld($_module,'warning')}</h3>
      {_ld($_module,'warn_dependencies')}
    </div>

    <ul>
    {foreach $dependencies as $name => $rec}
      <li>
        {if $rec.action == 'i'}{_ld($_module,'depend_install',$rec.name,$rec.version)}
        {elseif $rec.action == 'u'}{_ld($_module,'depend_upgrade',$rec.name,$rec.version)}
        {elseif $rec.action == 'a'}{_ld($_module,'depend_activate',$rec.name)}{/if}
      </li>
    {/foreach}
    </ul>
  {/if}
{/if}

{if isset($form_start)}
{$form_start}
<div class="pageinput pregap">
 {if count($dependencies) > 1}
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{_ld($_module,'install_procede')}</button>
{else}
 <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_ld($_module,'install_submit')}</button>
{/if}
 <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
</div>
</form>
{/if}
