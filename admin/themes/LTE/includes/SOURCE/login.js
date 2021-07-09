jQuery(function($) {
    // hide message
    $('.message').hide().fadeIn(2600);
    // toggle info window
    $('.info-wrapper').removeClass('open');
    // focus input with class focus
    $('input:first.focus').triggger('focus');
    // shake on error
    $('#error').effect('shake', {
        times: 6,
        distance: 3
    }, 15);
    $('.toggle-info').on('click', function() {
        $('.info').toggle();
        $('.info-wrapper').toggleClass('open');
        return false;
    });
});
