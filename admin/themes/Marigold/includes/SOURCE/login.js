jQuery(function($) {
	$('#info-wrapper').removeClass('open');
	// transition messages
	$('.message').hide().fadeIn(2600);
	// shake on error
	$('#login.error').effect('shake', {
		times: 6,
		distance: 3
	}, 15);
	// focus input with class focus
	$('input:first.focus').trigger('focus');
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
