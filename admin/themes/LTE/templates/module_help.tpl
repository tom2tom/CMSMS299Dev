<div class="card mt-3">
  <div class="card-body">
    {$help}
  </div>
  {if !empty($dependencies)}
    <div class="card-footer">
      <p>
        <strong>{lang('dependencies')}:</strong>
        <ul class="list-inline">
          {foreach $dependencies as $dependency => $version}
            <li class="list-inline-item">{$dependency} =&gt; {$version}</li>
          {/foreach}
        </ul>
      </p>
  </div>
  {/if}
</div>
{if !empty($parammeters)}
  <div class="card">
  <div class="card-header">
    <h3 class="card-title">{lang('parameters')}</h3>
    <div class="card-tools">
      <button type="button" class="btn btn-tool" data-widget="maximize"><i class="fas fa-expand"></i></button>
    </div>
  </div>
  <div class="card-body">
    {foreach $parammeters as $one}
      <ul class="list-group">
        <li class="list-group-item">
          <i class="fas fa-code"></i> <strong>{$one.name} = "{$one.default}"</strong> {if $one.optional}{/if}{lang('optional')}
          {if !empty($one.help|default:'')}<p>{$one.help}</p>{/if}
        </li>
      </ul>
    {/foreach}
  </div>
</div>
{/if}