<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark elevation-4 h-100">
  <!-- Control sidebar content goes here -->
    <div class="pt-3 pb-2 pl-3 pr-3">
      {if !empty($marks_cntrls)}
        <div class="btn-toolbar mb-3" role="toolbar">
          <a class="btn btn-default btn-sm" role="button" href="{$marks_cntrls[0]->url}" title="{$marks_cntrls[0]->title}"><i class="fa fa-plus"></i></a>
          <a class="btn btn-default btn-sm" role="button" href="{$marks_cntrls[1]->url}" title="{$marks_cntrls[1]->title}"><i class="fa fa-star"></i></a>
          <a class="btn btn-default btn-sm text-red" role="button" href="javascript:void()" data-widget="control-sidebar" title="{lang('close')}"><i class="fa fa-times"></i></a>
        </div>
      {/if}
      <h3>{lang('user_created')}</h3>
  </div>
  <div id="shorcuts-crol-sidebar" class="pt-1 pb-1 pl-3 pr-3 mr-3 d-block h-50">
    {if !empty($marks)}
      {foreach $marks as $mark}
        <a class="btn btn-outline-secondary btn-sm btn-block text-white" role="button" href="{$mark->url}" title="{$mark->title}">{$mark->title}</a>
      {/foreach}
    {/if}
  </div>

  <div class="p-3 mr-3">
    <h3>{lang('help')}</h3>
    <a class="btn btn-outline-secondary btn-sm btn-block text-white" rel="external" targuet="_blank" role="button" href="" title="{lang('documentation')}">{lang('documentation')}</a>
  </div>
</aside>
<!-- /.control-sidebar -->
