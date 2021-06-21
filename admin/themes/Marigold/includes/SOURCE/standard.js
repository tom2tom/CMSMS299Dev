/** ==========================================================
 * CMSMS Marigold theme Functions
 * @package CMS Made Simple
 * @module MG
 * @author Goran Ilic - uniqu3 <ja@ich-mach-das.at>
 * ==========================================================
 */
/*!
CMSMS Marigold theme functions v.0.6
(C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL2+
*/
(function(global, $) {
    'use strict';
    /*jslint nomen: true , devel: true*/
    /**
     * @namespace MG
     */
    var MG = global.MG = {};
    $(function() {
        MG.helper.init();
        MG.view.init();
    });

    /**
     * @namespace MG.helper
     */
    MG.helper = {
        init: function() {
            var _this = this;
            // open external links with rel="external" attribute in new window
            $('a[rel="external"]').attr('target', '_blank');
            // focus on input with .defaultfocus class
            $('input.defaultfocus, input[autofocus]').eq(0).focus();
            // load js-cookie.js if localStorage is not supported
            if(!_this._isLocalStorage()) {
                _this.loadScript('themes/assets/js/js-cookie.min.js');
            }
        },
        /**
         * @description conditional load script helper function
         * @author Brad Vincent https://gist.github.com/2313262
         * @memberof MG.helper
         * @function loadScript(url, arg1, arg2)
         * @param {string} url
         * @callback requestCallback
         * @param {requestCallback|boolean} arg1
         * @param {requestCallback|boolean} arg2
         */
        loadScript: function(url, arg1, arg2) {
            var cache = true,
                callback = null,
                load = true;
            //arg1 and arg2 can be interchangable
            if(typeof arg1 === "function") {
                callback = arg1;
                cache = arg2 || cache;
            } else {
                cache = arg1 || cache;
                callback = arg2 || callback;
            }
            //check all existing script tags in the page for the url
            $('script[type="text/javascript"]').each(function() {
                var load = (url !== $(this).attr('src'));
                return load;
            });
            if(load) {
                //didn't find it in the page, so load it
                return $.ajax(url, {
                    dataType: 'script',
                    async: false,
                    cache: cache
                }).done(callback);
            } else {
                //already loaded so just call the callback
                if(typeof callback === "function") {
                    callback.call(this);
                }
            }
        },
        /**
         * @description saves a defined key and value to localStorage if localStorgae is supported, else falls back to js-cookie script
         * @memberof MG.helper
         * @function setStorageValue(key, value)
         * @param {string} key
         * @param {string} value
         * @param {number} expires (number in days)
         */
        setStorageValue: function(key, value, expires) {
            var _this = this,
                expiration = new Date().getTime() + (expires * 24 * 60 * 60 * 1000),
                obj = {};
            try {
                if(_this._isLocalStorage() === true) {
                    localStorage.removeItem(key);
                    if(expires !== null) {
                        obj = {
                            value: value,
                            timestamp: expiration
                        };
                    } else {
                        obj = {
                            value: value,
                            timestamp: ''
                        };
                    }
                    localStorage.setItem(key, JSON.stringify(obj));
                } else if(typeof Cookies !== 'undefined') {
                    if(expires !== null) {
                        Cookies.set(key, value,{
                            expires: expires
                        });
                    } else {
                        Cookies.set(key, value);
                    }
                }
            } catch(error) {
                console.log('localStorage Error: set(' + key + ', ' + value + ')');
                console.log(error);
            }
        },
        /**
         * @description gets value for defined key from localStorage if localStorgae is supported, else falls back to js-cookie script
         * @memberof MG.helper
         * @function getStorageValue(key)
         * @param {string} key
         */
        getStorageValue: function(key) {
            var _this = this,
                data = '',
                value;
            if(_this._isLocalStorage()) {
                data = JSON.parse(localStorage.getItem(key));
                if(data !== null && data.timestamp < new Date().getTime()) {
                    _this.removeStorageValue(key);
                } else if(data !== null) {
                    value = data.value;
                }
            } else if(typeof Cookies !== 'undefined') {
                value = Cookies(key);
            }
            return value;
        },
        /**
         * @description removes defined key from localStorage if localStorgae is supported, else falls back to js-cookie script
         * @memberof MG.helper
         * @function removeStorageValue(key)
         * @param {string} key
         */
        removeStorageValue: function(key) {
            var _this = this;
            if(_this._isLocalStorage()) {
                localStorage.removeItem(key);
            } else if(typeof Cookies !== 'undefined') {
                Cookies.remove(key);
            }
        },
        /**
         * @description Sets equal height on specified element group
         * @memberof MG.helper
         * @function equalHeight(obj)
         * @param {object}
         */
        equalHeight: function(obj) {
            var tallest = 0; //elHeight;
            obj.each(function() {
                var el = $(this),
                    elHeight = el.height();
                if(elHeight > tallest) {
                    tallest = elHeight;
                }
            });
            obj.height(tallest);
        },
        /**
         * @description detects if localStorage is supported by browser
         * @function _isLocalStorage()
         * @private
         */
        _isLocalStorage: function() {
            return typeof(Storage) !== 'undefined';
        },
        /**
         * @description Basic check for common mobile devices and touch capability
         * @function _isMobileDevice()
         * @private
         */
        _isMobileDevice: function() {
            var ua = navigator.userAgent.toLowerCase(),
                devices = /(Android|iPhone|iPad|iPod|Blackberry|Dolphin|IEMobile|WPhone|Windows Mobile|IEMobile9||IEMobile10||IEMobile11|Kindle|Mobile|MMP|MIDP|Pocket|PSP|Symbian|Smartphone|Sreo|Up.Browser|Up.Link|Vodafone|WAP|Opera Mini|Opera Tablet|Mobile|Fennec)/i;
            if(ua.match(devices) && (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0) || window.DocumentTouch && document instanceof DocumentTouch)) {
                return true;
            }
        }
    };

    /**
     * @namespace MG.view
     */
    MG.view = {
        init: function() {
            var _this = this,
                $sidebar_toggle = $('.toggle-button'), // object for sidebar toggle
                $container = $('#mg_container'), // page container
                $menu = $('#mg_pagemenu'); // page menu
            // handle navigation sidebar toggling
            $sidebar_toggle.on('click', function(e) {
                e.preventDefault();
                if($container.hasClass('sidebar-on')) {
                    _this._closeSidebar($container, $menu);
                } else {
                    _this._showSidebar($container, $menu);
                }
            });
            // toggle hide/reveal menu children
            _this.toggleSubMenu($menu, 50);
            // handle notifications
            _this.showNotifications();
            // apply jQueryUI buttons
            _this.setUIButtons();
            // setup alert handlers
            _this.setupAlerts();
            // handle updating the display.
            _this.updateDisplay();
            // handles the initial state of the sidebar (collapsed or expanded)
            _this.handleSidebar($container);
            $(window).resize(function() {
                _this.handleSidebar($container);
                _this.updateDisplay();
            });
        },
        /**
         * @description Checks for saved state of sidebar
         * @function handleSidebar(trigger, container)
         * @param {object} trigger
         * @param {object} container
         * @memberof MG.view
         */
        handleSidebar: function(container) {
            var viewportWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            if(MG.helper.getStorageValue('sidebar-pref') === 'sidebar-off' || viewportWidth <= 992) {
                container.addClass('sidebar-off').removeClass('sidebar-on');
            } else {
                container.addClass('sidebar-on').removeClass('sidebar-off');
            }
        },
        /**
         * @description Handles toggling of main menu child items
         * @function toggleSubMenu(obj)
         * @param {object} obj - Menu container object
         * @param {number} duration - A positive number for toggle speed control
         * @memberof MG.view
         */
        toggleSubMenu: function(obj, duration) {
            var _this = this;
            obj.find('> li > span').on('click', function(e) {
                e.preventDefault();
                var $t = $(this),
                 cur = $t.hasClass('open-sub'),
                 ul = $t.next(),
                 _p = [];
                // jQuery :visible selector is unreliable
                if (ul.length === 0 || ul.css('visibility') === 'hidden' || ul.css('display') === 'none') {
                    _p.push(obj.find('ul').slideUp(duration));
                }
                obj.find('.nav').removeClass('current').find('span').removeClass('open-sub');
                $t.parent('.nav').addClass('current');
                if(cur) {
                    _p.push(ul.slideToggle(duration));
                } else {
                    $t.addClass('open-sub');
                }
                $.when.apply($, _p).done(function() {
                    _this.updateDisplay();
                });
                return false;
            });
        },
        /**
         * @description Handles Core and Module messages
         * @function showNotification()
         */
        // TODO Rethink this in next versions, define a object based on type or something (maybe use plugin http://akquinet.github.io/jquery-toastmessage-plugin/demo/demo.html), move messages to global function in cms_admin.js so it can be reused by other themes
        showNotifications: function() {
//  back-compatibility check might be relevant in some contexts
//  if (typeof cms_notify_all === 'function') {
            cms_notify_all();
//  } else {
//    do old-style notifications
//  }

            $('.pagewarning, .message, .pageerrorcontainer, .pagemcontainer').prepend('<span class="close-warning"></span>');
            $(document).on('click', '.close-warning', function() {
                $(this).parent().hide();
                $(this).parent().remove();
            });
            // pagewarning status hidden?
            var key = $('body').attr('id') + '_notification';
            $('.pagewarning .close-warning').on('click', function() {
                MG.helper.setStorageValue(key, 'hidden', 60);
            });
            if(MG.helper.getStorageValue(key) === 'hidden') {
                $('.pagewarning').addClass('hidden');
            }
            $('.message:not(.no-slide)').on('click', function() {
                $('.message').slideUp();
            });
            $('.message:not(.no-slide), .pageerrorcontainer:not(.no-slide), .pagemcontainer:not(.no-slide)').each(function() {
                var message = $(this);
                $(message).hide().slideDown(1000, function() {
                    window.setTimeout(function() {
                        message.slideUp();
                    }, 10000);
                });
            });
            $(document).on('cms_ajax_apply', function(e) {
                $('button[name="cancel"], button[name="m1_cancel"]').fadeOut();
                $('button[name="cancel"], button[name="m1_cancel"]').button('option', 'label', e.close);
                $('button[name="cancel"], button[name="m1_cancel"]').fadeIn();
                var htmlShow = '';
                if(e.response === 'Success') {
                    htmlShow = '<aside class="message pagemcontainer" role="status"><span class="close-warning">Close</span><p class="pagemessage">' + e.details + '<\/p><\/aside>';
                } else {
                    htmlShow = '<aside class="message pageerrorcontainer" role="alert"><span class="close-warning">Close</span><ul class="pageerror">';
                    htmlShow += e.details;
                    htmlShow += '<\/ul><\/aside>';
                }
                $('body').append(htmlShow).slideDown(1000, function() {
                    window.setTimeout(function() {
                        $('.message').slideUp();
                        $('.message').remove();
                    }, 10000);
                });
                $(document).on('click', '.close-warning', function() {
                    $('.message').slideUp();
                    $('.message').remove();
                });
            });
        },
        /**
         * @description Applies jQueryUI button function to input buttons
         * @function setUIButtons()
         */
        setUIButtons: function() {
            // Standard named input buttons
            $('input[type="submit"], :button[data-ui-icon]').each(function() {
                if(!this.value.trim()) return true;
                var button = $(this),
                    icon = button.data('uiIcon') || 'ui-icon-circle-check',
                    label = button.val(),
                    $btn = $('<button />');
                if(!button.hasClass('noautobtn') || !button.hasClass('no-ui-btn')) {
                    if(button.is('[name*=apply]')) {
                        icon = button.data('uiIcon') || 'ui-icon-disk';
                    } else if(button.is('[name*=cancel]')) {
                        icon = button.data('uiIcon') || 'ui-icon-circle-close';
                    } else if(button.is('[name*=resettodefault]') || button.attr('id') === 'refresh') {
                        icon = button.data('uiIcon') || 'ui-icon-refresh';
                    }
                }
                if(button.is(':button')) {
                    label = button.text();
                }
                $(this.attributes).each(function(index, attribute) {
                    $btn.attr(attribute.name, attribute.value);
                });
                $btn.button({
                    icons: {
                        primary: icon
                    },
                    label: label
                });
                button.replaceWith($btn);
            });
            // Back links
            $('a.pageback').addClass('ui-state-default ui-corner-all')
                .prepend('<span class="ui-icon ui-icon-arrowreturnthick-1-w">')
                .hover(function() {
                    $(this).addClass('ui-state-hover');
                }, function() {
                    $(this).removeClass('ui-state-hover');
                });
        },
        /**
         * @description Placeholder function for functions that need to be triggered on window resize
         * @memberof MG.view
         * @function updateDisplay()
         */
        updateDisplay: function() {
            var $menu = $('#mg_menu');
            var $alert_box = $('#admin-alerts');
            var $header = $('header.header');
            var offset = $header.outerHeight() + $header.offset().top;
            if($alert_box.length) offset = $alert_box.outerHeight() + $alert_box.offset().top;
            console.debug('menu height = ' + $menu.outerHeight() + ' offset = ' + offset);
            console.debug('window height = ' + $(window).height());
            if($menu.outerHeight() + offset < $(window).height()) {
                console.debug('fixed');
                $menu.css({ 'position': 'fixed', 'top': offset });
            } else {
                $menu.css({ 'position': '', 'top': '' });
                console.debug('floating');
                if($menu.offset().top < $(window).scrollTop()) {
                    //if the top of the menu is not visible, scroll to it.
                    $('html, body').animate({
                        scrollTop: $("#mg_menu").offset().top
                    }, 1000);
                }
            }
        },
        /**
         * @description Handles setting for Sidebar and sets open state
         * @private
         * @function _showSidebar(obj, target)
         * @params {object} obj
         * @params {object} target
         */
        _showSidebar: function(obj, target) {
            obj.addClass('sidebar-on').removeClass('sidebar-off');
            target.find('li.current ul').show();
            MG.helper.setStorageValue('sidebar-pref', 'sidebar-on', 60);
        },
        /**
         * @description Handles setting for Sidebar and sets closed state
         * @private
         * @function _closeSidebar(obj, target)
         * @params {object} obj
         * @params {object} target
         */
        _closeSidebar: function(obj, target) {
            obj.removeClass('sidebar-on').addClass('sidebar-off');
            target.find('li ul').hide();
            MG.helper.setStorageValue('sidebar-pref', 'sidebar-off', 60);
        },
        _handleAlert: function(target) {
            var _row = $(target).closest('.alert-box');
            var _alert_name = _row.data('alert-name');
            if(!_alert_name) return;
            return $.ajax({
              method: 'POST',
              url: cms_data.ajax_alerts_url,
              data: {
                op: 'delete',
                alert: _alert_name
              }
            }).fail(function(jqXHR, textStatus, errorThrown) {
              console.debug('problem deleting an alert: ' + errorThrown);
            }).done(function() {
              _row.slideUp(1000);
              var _parent = _row.parent();
              if(_parent.children().length <= 1) {
                _row.closest('div.ui-dialog-content').dialog('close');
                $('#alert-noalerts').show();
                $('a#alerts').closest('li').remove();
              }
              _row.remove();
            });
        },
        /**
         * @description Handles popping up the notification area
         * @private
         * @function _showAlerts()
         */
        setupAlerts: function() {
            var _this = this;
            $('a#alerts').on('click', function(ev) {
                ev.preventDefault();
                cms_dialog($('#alert-dialog'));
            });
            $('.alert-msg a').on('click', function(ev) {
                ev.preventDefault();
                MG.view.handleAlert(ev.target);
            });
            $('.alert-remove').on('click', function(ev) {
                ev.preventDefault();
                _this._handleAlert(ev.target);
            });
        },
    };
})(this, jQuery);
