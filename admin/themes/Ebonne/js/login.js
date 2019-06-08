$(function() {
    // shake error
    $('#error').effect('shake', {
        times: '6',
        distance: '3'
    }, 15);
    // nicely display message
    $('.message').hide().fadeIn(2000);
    $('#info-wrapper').removeClass('open');
    // toggle info window
    $('#toggle-info').on('click', function() {
        var $p = $('#info-wrapper');
        if($p.hasClass('open')) {
            $p.fadeOut(300, function() {
               $p.removeClass('open');
            });
        } else {
            $p.fadeIn(400, function() {
               $p.addClass('open');
            });
        }
        return false;
    });
    // focus input with class focus
    $('input:first.focus').focus();
});
