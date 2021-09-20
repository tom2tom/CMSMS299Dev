<ul class="list-inline d-flex flex-wrap">
	{foreach $nodes as $node}
  
    {if $node.show_in_menu && $node.url && $node.title}

      {$module = "../modules/`$node.name`/images/icon"}

      {if file_exists($module|cat:'.png')}
        {$image_src = "{$module}.png"}
      {elseif file_exists($module|cat:'.gif')}
        {$image_src = "{$module}.gif"}
      {elseif file_exists($module|cat:'.png')}
        {$image_src = "{$module}.png"}
      {elseif file_exists($module|cat:'.gif')}
        {$image_src = "{$module}.gif"}
      {else}
        {*$image_src = "{$theme_url}/images/icons/topfiles/modules.png"*}
        {$image_src = ''}
      {/if}

      <li class="list-inline-item mb-2">
				<div class="card h-100" style="width: 21rem;">
					<div class="card-header">
						<h3 class="card-title">

							{if $section_name == 'extensions'}
                {if $image_src}
                  <img src="{$image_src}" alt="{$node.title}"{if $node.description} title="{$node.description|strip_tags}"{/if} style="width: 1.05rem;">
                {else}
                  <i class="fas fa-03-{$section_name}"></i>
                {/if}

							{elseif $section_name && $section_name != 'extensions'}
                <i class="fas fa-03-{$section_name}"></i>
							{else}
								<i class="fas fa-03-{$node.name|lower}"></i>
              {/if}
              <span class="ml-2"><a href="{$node.url}"{if isset($node.target)} target="{$node.target}"{/if}>{$node.title}</a></span></h3>
					</div>
					<div class="card-body">


						{if $node.description}<p class="card-text mt-2">{$node.description}</p>{/if}

            {if isset($node.children)}
              <div class="container">
								<div class="row">
									<div class="col">
										<h4 class="card-subtitle mt-2 mb-2 text-muted">{_ld('admin','subitems')}</h4>
                    <ul class="list-unstyled"> {* still testing with looks (JM)*}
											{foreach from=$node.children item='one'}
                        {if $one.show_in_menu == 1}
                          <li>
                            <i class="fas fa-03-{$node.name|lower} mr-1"> </i>
                            {* <i class="fa fa-link mr-1"> </i> *}
                            <a href="{$one.url}"
                                    {if isset($one.target)} target="{$one.target}"{/if}
                                    {if substr($one.url,0,6) == 'logout' and isset($is_sitedown)}onclick="return confirm('{'maintenance_warning'|lang|escape:'javascript'}')"{/if}
                            >{$one.title}</a>
                          </li>
                        {/if}
                      {/foreach}
										</ul>
									</div>
								</div>
							</div>
            {/if}
					</div>
				</div>

			</li>
    {/if}

  {/foreach}
</ul>