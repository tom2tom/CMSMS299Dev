jQuery(function($) {
	$('#info-wrapper').removeClass('open');
	// transition messages
	$('.message').hide().fadeIn(2600);
	// shake on error
	$('#error').effect('shake', {
		times: 3,
		distance: 10
	});
	// focus input with class focus
	$('input.focus').eq(0).trigger('focus');
	// toggle info window
	$('#toggle-info').on('click activate', function() {
		$('#info-wrapper').toggleClass('open')
		.on('click activate', function() {
			$('#info-wrapper').removeClass('open');
			return false;
		});
		return false;
	});
});
