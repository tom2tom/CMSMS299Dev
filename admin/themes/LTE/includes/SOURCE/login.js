jQuery(function($) {
    // hide info window
    $('#info-wrapper').removeClass('open');
    // transition messages
    $('.message').hide().fadeIn(2600);
    // shake on error
    $('#error').effect('shake', {
        times: 6,
        distance: 3
    }, 15);
    // focus 1st input with class 'focus'
    $('input.focus').eq(0).trigger('focus');
    // toggle info window
    $('#toggle-info').on('click activate', function() {
        $('#info-wrapper').toggleClass('open')
        .on('click activate', function() {
            $('#info-wrapper').removeClass('open');
            return false;
        });
        //TODO also toggle visibility of extraneous elements
        return false;
    });
});
