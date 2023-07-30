$(function() {
  $('#info-wrapper').removeClass('open');
  var mel = $('.message');
  if (mel.length > 0) { mel.hide(); }
  // shake it all on error
  $('#error').effect('shake', {
    times: 6,
    distance: 3
  }, 15);
  // reveal any message
  if (mel.length > 0) { mel.fadeIn(2600); }
  // focus 1st input with class 'focus'
  $('input.focus').first().trigger('focus');
  // toggle info window
  $('#toggle-info').on('click', function(ev) {
    ev.preventDefault();
    $('#info-wrapper').toggleClass('open');
    return false;
  });
});
