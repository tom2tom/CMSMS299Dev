<div class="user-panel mt-1 pb-2 mb-1 d-flex">
  {if !isset($myaccount)}
    <div class="image">
      <a class="welcome-user" href="useraccount.php?{$secureparam}" title="{'myaccount'|lang}"><i class="fa fa-user" style="margin-top:0.4rem;font-size:1.2rem"></i></a>
    </div>
    <div class="info">
      <span class="d-block bg-gray-dark">{'welcome_user'|lang}: <a href="useraccount.php?{$secureparam}">{$user->username}</a></span>
    </div>
  {else}
    <div class="image">
      <i class="fa fa-user bg-gray-dark" style="margin-top:0.4rem;font-size:1.2rem"></i>
    </div>
    <div class="info">
      <span class="d-block bg-gray-dark">{'welcome_user'|lang}: {$user->firstname|default:$user->username}</span>
    </div>
  {/if}
</div>
