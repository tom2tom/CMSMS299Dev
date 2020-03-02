$(function () {
  // shake upon error
  $('.pageerror').effect('shake', {
    distance: 6,
    times: 4
  }, 800);
  // nicely display message
  $('.message').hide().fadeIn(2000);
  $('#info-wrapper').removeClass('open');
  // toggle info window
  $('#toggle-info').on('click', function () {
    var $p = $('#info-wrapper');
    if ($p.hasClass('open')) {
      $p.fadeOut(300, function () {
        $p.removeClass('open');
      });
    } else {
      $p.fadeIn(400, function () {
        $p.addClass('open');
      });
    }
    return false;
  });
  // focus input with class focus
  $('input:first.focus').focus();
});
