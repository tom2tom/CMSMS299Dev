<div class="user-panel mt-3 pb-3 mb-3 d-flex">
  {if !isset($myaccount)}
    <div class="image">
      <a class="welcome-user" href="myaccount.php?{$secureparam}" title="{'myaccount'|lang}"><i class="fa fa-user fa-2x elevation-2"></i></a>
    </div>
    <div class="info">
      <span class="d-block bg-gray-dark">{'welcome_user'|lang}: <a href="myaccount.php?{$secureparam}">{$user->username}</a></span>
    </div>
  {else}
    <div class="image">
      <i class="fa fa-user fa-2x elevation-2 bg-gray-dark"></i>
    </div>
    <div class="info">
      <span class="d-block bg-gray-dark">{'welcome_user'|lang}: {$user->firstname|default:$user->username}</span>
    </div>
  {/if}
</div>