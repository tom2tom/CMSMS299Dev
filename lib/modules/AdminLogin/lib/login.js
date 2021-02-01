jQuery(function($) {
  // nicely display message, if any
  $('#message').hide().fadeIn(2000);
  // focus input with class focus
  $('input:first.focus').focus();
  // toggle info window
  $('#show-info').on('click', function() {
    $('#maininfo').fadeIn(400);
    var b = $('#login-box');
    b.find('input,button').prop('disabled', 1);
    b.find('a').addClass('disabled').on('click.lb', function(ev) {
      ev.preventDefault();
      return false;
    });
    return false;
  });
  $('#hide-info').on('click', function() {
    $('#maininfo').fadeOut(400);
    var b = $('#login-box');
    b.find('input,button').prop('disabled', 0);
    b.find('a').removeClass('disabled').off('click.lb');
    return false;
  });
  // shake upon error
  $('#login-box.error').effect('shake', {
    times: 3,
    distance: 10
  });
});
