{if $author}
  <div class="card mt-3">
    <div class="card-body">
      <address>
        <strong>{lang('author')}: {$author}</strong><br>
        <a href="mailto:first.last@example.com">{$author_email}</a><br>
        {lang('version')}: {$version}
      </address>
    </div>
  </div>
{/if}
{if $changelog}
  <div class="card mt-3">
    <div class="card-header">
      {lang('changehistory')}
    </div>
    <div class="card-body">
      {$changelog}
    </div>
  </div>
{/if}