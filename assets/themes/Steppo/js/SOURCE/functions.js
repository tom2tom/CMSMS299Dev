document.addEventListener('DOMContentLoaded', function() {
	'use strict';
	var steppofuncs = {
		init: function() {
			document.documentElement.classList.add('jsenabled');
			steppofuncs.searchFieldFocus();
			steppofuncs.nav.init();
		},
		searchFieldFocus: function() {
			//! add and remove classes depending on form input for styling with CSS
			var searchform = document.querySelector('body > header form');
			var searchField = searchform.querySelector('input[type=search]');
			var searchWrapper = searchField.parentNode;
			if(searchField.value) {
				searchWrapper.classList.add('val');
			}
			searchField.addEventListener('focus', function() {
				searchWrapper.classList.add('focus');
			});
			searchField.addEventListener('blur', function() {
				searchWrapper.classList.remove('focus');
				if(searchField.value) {
					searchWrapper.classList.add('val');
				}
				else {
					searchWrapper.classList.remove('val');
				}
			});
		},
		nav: {
			init: function() {
				var doc = document,
					html = doc.documentElement,
					nav = doc.getElementById('nav'),
					toggle = doc.getElementById('nav_toggle');
				//! small viewport nav functionality (toggle submenus open etc.)
				if(toggle.checked) {
					html.classList.add('nav_active')
				}
				toggle.addEventListener('change', function() {
					if(toggle.checked) {
						html.classList.add('nav_active');
					}
					else {
						html.classList.remove('nav_active');
						nav.scrollTop = 0;
					}
						console.log(nav.scrollTop);
				});
				nav.addEventListener('scroll', function() {
					if(nav.scrollTop > 0) {
						if(!nav.classList.contains('scrolled')) {
							nav.classList.add('scrolled');
						}
					}
					else {
						nav.classList.remove('scrolled');
					}
				});
				//! large viewport nav functionality
				var parentItems = nav.querySelectorAll('.parent');
					parentItems.forEach(function(item,index,listObj) {
						var childList = item.querySelector('ul');
						item.addEventListener('mouseenter', function() {
							var viewportWidth = html.clientWidth,
								rightEdge = childList.getBoundingClientRect().left+childList.offsetWidth;
							if(rightEdge > viewportWidth) {
								childList.classList.add('outside');
							}
						});
						item.addEventListener('mouseleave', function() {
								childList.classList.remove('outside');
						});
				});
			}
		}
	}
	steppofuncs.init();
});
