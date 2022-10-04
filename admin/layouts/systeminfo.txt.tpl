<h3>{si_lang a=systeminfo}</h3>
<div class="pageoverflow">
    <p><strong>CMSMS version</strong>: {$cms_version}</p>
    <br>
    <p><strong>Installed Modules</strong>:</p>
    <ul>
      {foreach $installed_modules as $module}
      <li>{$module.module_name}: {$module.version}</li>
      {/foreach}
    </ul>
    {if $count_config_info > 1}
    <br>
    <p><strong>Config information</strong>:</p>
    <ul>
      {foreach $config_info as $view => $tmp}
        {if $view < 1} {foreach $tmp as $key => $test}
          <li>{$key|ucfirst|replace:'_':' '}:{if isset($test->value)} {$test->value}{/if}</li>
        {/foreach} {/if}
      {/foreach}
    </ul>
    {/if}
    {if $count_php_information > 1}
    <br>
    <p><strong>PHP information</strong>:</p>
    <ul>
      {foreach $php_information as $view => $tmp}
       {if $view < 1} {foreach $tmp as $key => $test}
        <li>{$key|ucfirst|replace:'_':' '}: {if isset($test->secondvalue)}{$test->value} ({$test->secondvalue}){elseif isset($test->value)}{$test->value}{/if}
         {if isset($test->opt)}<ul>{foreach $test->opt as $key => $opt}
           <li>{$key} {$opt.message|default:''}</li>  
         {/foreach}</ul>{/if}
        </li>
       {/foreach} {/if}
      {/foreach}
    </ul>
    {/if}
    {if count($performance_info)}
    <br>
    <p><strong>Performance information</strong>:</p>
    <ul>
      {$list=$performance_info[0]}
      {foreach $list as $key => $test}
      <li>{$key|ucfirst|replace:'_':' '}: {if isset($test->secondvalue)}{$test->value} ({$test->secondvalue}){elseif isset($test->value)}{$test->value}{/if}</li>
      {/foreach}
    </ul>
    {/if}
    {if $count_server_info > 1}
    <br>
    <p><strong>Server information</strong>:</p>
    <ul>
      {foreach $server_info as $view => $tmp}
        {if $view < 1} {foreach $tmp as $key => $test}
         <li>{$key|ucfirst|replace:'_':' '}: {if isset($test->value)}{$test->value}{/if}</li>
        {/foreach} {/if}
      {/foreach}
    </ul>
    {/if}
    {if $count_permission_info > 1}
    <br>
    <p><strong>Permission information</strong>:</p>
    <ul>
      {foreach $permission_info as $view => $tmp}
       {if $view < 1} {foreach $tmp as $key => $test}
        <li>{$key}: {if isset($test->secondvalue)}{$test->value} ({$test->secondvalue}){elseif isset($test->value)}{$test->value}{/if}
          {if isset($test->opt)}<ul>{foreach $test->opt as $key => $opt}
           <li>{$key} {$opt.message|default:''}</li>  
          {/foreach}</ul>{/if}
        </li>
       {/foreach} {/if}
      {/foreach}
    </ul>
    {/if}
</div>
