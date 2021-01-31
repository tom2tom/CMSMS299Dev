jQuery(function($) {
  // nicely display message, if any
  $('#message').hide().fadeIn(2000);
  // focus input with class focus
  $('input:first.focus').focus();
  // toggle info window
  $('#show-info').on('click', function() {
    $('#maininfo').fadeIn(400);
    return false;
  });
  $('#hide-info').on('click', function() {
    $('#maininfo').fadeOut(400);
    return false;
  });
  // shake upon error
  $('#login-box.error').effect('shake', {
    times: 3,
    distance: 10
  });
});
