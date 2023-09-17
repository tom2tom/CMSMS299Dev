<ul class="list-inline d-flex flex-wrap">
  {foreach $nodes as $item}
    {if $item.show_in_menu && $item.url && $item.title}
      <li class="list-inline-item mb-2">
        <div class="card h-100 top-box" style="width:21rem">
          <div class="card-header">
            <h3 class="card-title">{strip}
              {if !empty($item.iconclass)}<i class="{$item.iconclass}"></i>
              {elseif !empty($item.img)}{$item.img}{/if}
              <span class="ml-2"><a href="{$item.url}"{if isset($item.target)} target="{$item.target}"{/if}>{$item.title}</a></span>{*TODO if rtl*}{/strip}
            </h3>
          </div>
          <div class="card-body">
            {if !empty($item.description)}<p class="card-text mt-1">{$item.description}</p>{/if}{strip}
            {if !empty($item.children)}
              <div class="container">
                <div class="row">
                  <div class="col">
                    <h4 class="card-subtitle mt-0 mb-2 text-muted">{_la('subitems')}</h4>
                    <ul class="list-unstyled">{strip}{* still testing with looks (JM)*}
                      {foreach $item.children as $one}
                        {if $one.show_in_menu}
                          <li>
                           {if !empty($one.iconclass)}<i class="{$one.iconclass} mr-1"></i>{*TODO if rtl*}
                           {elseif !empty($one.img)}{$one.img} {/if}
                            <a href="{$one.url}"
                              {if isset($one.target)} target="{$one.target}"{/if}
                              {if substr($one.url,0,6) == 'logout' && isset($is_sitedown)} onclick="return confirm('{lang("maintenance_warning")|escape:"javascript"}');"{/if}
                            >{$one.title}</a>
                          </li>
                        {/if}
                      {/foreach}{/strip}
                    </ul>
                  </div>
                </div>
              </div>
            {/if}{/strip}
          </div>
        </div>
      </li>
    {/if}
  {/foreach}
</ul>
