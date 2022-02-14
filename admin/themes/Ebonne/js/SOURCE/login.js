jQuery(function($) {
  $('#info-wrapper').removeClass('open');
  // shake upon error
  $('.pageerror').effect('shake', {
    distance: 6,
    times: 4
  }, 800);
  // ease messages
  $('.message').hide().fadeIn(2000);
  // focus input with class focus
  $('input:first.focus').trigger('focus');
  // toggle info window
  $('#toggle-info').on('click activate', function () {
    var $p = $('#info-wrapper');
    if ($p.hasClass('open')) {
      $p.fadeOut(300, function () {
        $p.off('click activate').removeClass('open');
      });
    } else {
      $p.fadeIn(400, function () {
        $p.addClass('open');
      }).on('click activate', function () {
        $p.fadeOut(300, function () {
          $p.off('click activate').removeClass('open');
        });
      });
    }
    return false;
  });
});
