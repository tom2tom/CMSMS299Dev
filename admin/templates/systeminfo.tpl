<div class="pagecontainer">
  {if !empty($message)}<div class="messagebox {/strip}
{if $error}error
{elseif $warning}warning
{elseif $info}info
{elseif $success}success{/if}{/strip}">{$message}</div>{/if}

  {if empty($smarty.get.cleanreport)}
  <p class="pageshowrows">
    <a href="{$sysinfurl}{$urlext}&amp;cleanreport=1" class="link_button icondo">
     {si_lang a=copy_paste_forum}
    </a>
  </p>
  {/if}
  <div class="pageinfo">{si_lang a=help_systeminformation}</div>
  <div class="pageoverflow">
    <table class="pagetable" summary="{si_lang a=cms_install_information}">
      <thead>
        <tr>
          <th colspan="3">{si_lang a=cms_install_information}</th>
        </tr>
      </thead>
      <tbody>
        <tr class="{cycle name='c1' values='row1,row2'}">
          <td style="width:45%;">{si_lang a=cms_version}</td>
          <td style="width:5%;"></td>
          <td style="width:50%;">{$cms_version}</td>
        </tr>
      </tbody>
    </table>
    <br /><br />

    <table class="pagetable" summary="{si_lang a=installed_modules}">
      <thead>
        <tr>
          <th colspan="3">{si_lang a=installed_modules}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $installed_modules as $module}
        <tr class="{cycle name='c2' values='row1,row2'}">
          <td style="width:45%;">{$module.module_name}</td>
          <td style="width:5%;"></td>
          <td style="width:50%;">{$module.version}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>
    <br /><br />

    <table class="pagetable" summary="{si_lang a=config_information}">
      <thead>
        <tr>
          <th colspan="3">{si_lang a=config_information}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $config_info as $view => $tmp} {foreach $tmp as $key => $test}
        <tr class="{cycle name='c3' values='row1,row2'}">
          <td style="width:45%;">{$test->title}</td>
          <td style="width:5%;">{if isset($test->res)}<img class="systemicon" src="themes/{$themename}/images/icons/extra/{$test->res}.gif" title="{$test->res_text|default:''}" alt="{$test->res_text|default:''}" />{/if}</td>
          <td style="width:50%;">
            {if isset($test->value)}{$test->value|default:"&nbsp;"}{/if}
            {if isset($test->secondvalue)}({$test->secondvalue|default:"&nbsp;"}){/if}
            {if isset($test->error_fragment)}<a href="{$cms_install_help_url}#{$test->error_fragment}" class="external" rel="external"><img src="themes/{$themename}/images/icons/system/info-external.gif" title="?" alt="?" /></a>{/if}
            {if isset($test->message)}<br />{$test->message}{/if}
          </td>
        </tr>
        {/foreach} {/foreach}
      </tbody>
    </table>
    <br /><br />

    <table class="pagetable" summary="{lang('performance_information')}">
      <thead>
        <tr>
          <th colspan="3">{lang('performance_information')}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $performance_info as $view => $tmp} {foreach $tmp as $key => $test}
        <tr class="{cycle name='c4' values='row1,row2'}">
          <td style="width:45%;">{$test->title}</td>
          <td style="width:5%;">{if isset($test->res)}<img class="systemicon" src="themes/{$themename}/images/icons/extra/{$test->res}.gif" title="{$test->res_text|default:''}" alt="{$test->res_text|default:''}" />{/if}</td>
          <td style="width:50%;">
            {if isset($test->value)}{$test->value|default:"&nbsp;"}{/if} {if isset($test->secondvalue)}({$test->secondvalue|default:"&nbsp;"}){/if} {if isset($test->error_fragment)}<a href="{$cms_install_help_url}#{$test->error_fragment}" class="external" rel="external"><img src="themes/{$themename}/images/icons/system/info-external.gif" title="?" alt="?" /></a>{/if}
            {if isset($test->message)}<br />{$test->message}{/if}
          </td>
        </tr>
        {/foreach} {/foreach}
      </tbody>
    </table>
    <br /><br />

    <table class="pagetable" summary="{si_lang a=php_information}">
      <thead>
        <tr>
          <th colspan="3">{si_lang a=php_information}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $php_information as $view => $tmp} {foreach $tmp as $key => $test}
        <tr class="{cycle name='c5' values='row1,row2'}">
          <td style="width:45%;">{si_lang a=$key} ({$key})</td>
          <td style="width:5%;">{if isset($test->res)}<img class="systemicon" src="themes/{$themename}/images/icons/extra/{$test->res}.gif" title="{$test->res_text|default:''}" alt="{$test->res_text|default:''}" />{/if}</td>
          <td style="width:50%;">
            {if isset($test->value) && $test->display_value != 0}{$test->value}{/if} {if isset($test->secondvalue)}({$test->secondvalue}){/if} {if isset($test->error_fragment)}<a href="{$cms_install_help_url}#{$test->error_fragment}" class="external" rel="external"><img src="themes/{$themename}/images/icons/system/info-external.gif" title="?" alt="?" /></a>{/if} {if isset($test->message)}{$test->message}{/if}
            {if isset($test->opt)} {foreach $test->opt as $key => $opt}
            <br />{$key}: {$opt.message} <img class="systemicon" src="themes/{$themename}/images/icons/extra/{$opt.res}.gif" alt="{$opt.res_text|default:''}" title="{$opt.res_text|default:''}" />
            {/foreach} {/if}
          </td>
        </tr>
        {/foreach} {/foreach}
      </tbody>
    </table>
    <br /><br />

    <table class="pagetable" summary="{si_lang a=server_information}">
      <thead>
        <tr>
          <th colspan="3">{si_lang a=server_information}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $server_info as $view => $tmp} {foreach $tmp as $key => $test}
        <tr class="{cycle name='c6' values='row1,row2'}">
          <td style="width:45%;">{si_lang a=$key} ({$key})</td>
          <td style="width:5%;">{if isset($test->res)}<img class="systemicon" src="themes/{$themename}/images/icons/extra/{$test->res|default:" space "}.gif" title="{$test->res_text|default:''}" alt="{$test->res_text|default:''}" />{/if}</td>
          <td style="width:50%;">
            {if isset($test->value)}{$test->value|lower}{/if} {if isset($test->secondvalue)}({$test->secondvalue}){/if} {if isset($test->message)}<br />{$test->message}{/if}
          </td>
        </tr>
        {/foreach} {/foreach}
      </tbody>
    </table>
    <br /><br />

    <table class="pagetable" summary="{si_lang a=permission_information}">
      <thead>
        <tr>
          <th colspan="3">{si_lang a=permission_information}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $permission_info as $view => $tmp} {foreach $tmp as $key => $test}
        <tr class="{cycle name='c7' values='row1,row2'}">
          <td style="width:45%;">{$key}</td>
          <td style="width:5%;">{if isset($test->res)}<img class="systemicon" src="themes/{$themename}/images/icons/extra/{$test->res}.gif" title="{$test->res_text|default:''}" alt="{$test->res_text|default:''}" />{/if}</td>
          <td style="width:50%;">
            {if isset($test->value)}{$test->value}{/if} {if isset($test->secondvalue)}({$test->secondvalue}){/if} {if isset($test->message)}<br />{$test->message}{/if}
          </td>
        </tr>
        {/foreach} {/foreach}
      </tbody>
    </table>

  </div>
</div>
