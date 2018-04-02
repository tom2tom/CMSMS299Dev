/*!
Ghostgum-theme support
(C) CMSMS <coreteam@cmsmadesimple.org>
License: GPL2+
*/
/**
 * @package CMS Made Simple
 * @description CMSMS theme functions - tailored for Ghostgum theme
 * @author Goran Ilic - uniqu3 <ja@ich-mach-das.at>
 * Updates since 2012 by the CMSMS Dev Team
 * NOTE includes a hardcoded url for an external cookie processor, and viewport width-threshold
 */
;(function(global, $) {
    "$:nomunge"; //compressor directive
    'use strict';
    /*jslint nomen: true , devel: true*/

    $(document).ready(function() {
        ThemeJS.view_init();
        ThemeJS.helper_init();
    });

    var ThemeJS = {
        cookie_handler: 'themes/Ghostgum/includes/js-cookie.min.js',  //TODO more suitable place
        small_width: 992, // viewport-width threshold

        view_init: function() {
            var _this = this,
           $container = $('#ggp_container'), // outer container
       $menucontainer = $container.find('#ggp_navwrap'), // nav menu container
             $toggler = $menucontainer.find('#toggle-button'), // span for sidebar toggle
                $menu = $menucontainer.find('#ggp_nav'); // nav menu
            // handle the initial collapsed/expanded state of the sidebar
            this.handleSidebar($container, $menucontainer);
            // handle navigation sidebar toggling
            $toggler.on('click', function(e) {
                e.preventDefault();
                if($container.hasClass('sidebar-on')) {
                    _this.closeSidebar($container, $menucontainer);
                } else {
                    _this.openSidebar($container, $menucontainer);
                }
                return false;
            });
            $(window).resize(function() {
                _this.handleSidebar($container, $menucontainer);
                _this.updateDisplay();
            });
            // handle initial display of sub-menu
            this.handleSubMenu($menu);
            // handle sub-menu display toggling
            $menu.find('.open-nav').on('click', function(e) {
                //clicked span in a menu item title
                e.preventDefault();
                var $ob = $(this),
                    $ul = $ob.next(), //sub-menu container for this item
                     _p = [];
                if(!$ul.is(':visible')) {
                    //close any other open submenu
                    var $open = $menu.find('.open-sub');
                    if($open.length) {
                        $open.removeClass('open-sub');
                        _p.push($open.next().slideUp(50));
                    }
                    $ob.addClass('open-sub');
                } else {
                    $ob.removeClass('open-sub');
                }
                _p.push($ul.slideToggle(50));
                $.when.apply($, _p).done(function() {
                    _this.updateDisplay();
                });
                return false;
            });
            // handle notifications
            this.showNotifications();
            // substitute elements - buttons for inputs etc
            this.migrateUIElements();
            // handle updating the display.
            this.updateDisplay();
            // setup deprecated alert-handlers
            this.setupAlerts();
        },

        helper_init: function() {
            // open external links with rel="external" attribute in new window
            $('a[rel=external]').attr('target', '_blank');
            // focus on input with .defaultfocus class
            $('input.defaultfocus:eq(0), input[autofocus]').focus();
            // async-load a cookie handler if localStorage is not supported
            if(!this._isLocalStorage()) {
                this.loadScript(this.cookie_handler);
            }
        },
        /**
         * @description conditional load script helper function
         * @author Brad Vincent https://gist.github.com/2313262
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
            if($.isFunction(arg1)) {
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
                $.ajax({
                    type: 'GET',
                    url: url,
                    async: false,
                    success: callback,
                    dataType: 'script',
                    cache: cache
                });
            } else {
                //already loaded so just call the callback
                if($.isFunction(callback)) {
                    callback.call(this);
                }
            }
        },
        /**
         * @description saves a defined key and value to localStorage if localStorgae is supported, else falls back to cookie script
         * @requires js-cookie https://github.com/js-cookie/js-cookie
         * @function setStorageValue(key, value)
         * @param {string} key
         * @param {string} value
         * @param {number} expires (number in days)
         */
        setStorageValue: function(key, value, expires) {
            try {
                if(this._isLocalStorage()) {
                    localStorage.removeItem(key);
                    var obj;
                    if(expires !== null) {
                        var expiration = new Date().getTime() + (expires * 24 * 60 * 60 * 1000);
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
                } else if(this._isCookieScript()) {
                    if(expires !== null) {
                        Cookies.set(key, value, {
                            expires: expires
                        });
                    } else {
                        Cookies.set(key, value);
                    }
                } else {
                    throw "No cookie storage!";
                }
            } catch(error) {
                console.log('localStorage Error: set(' + key + ', ' + value + ')');
                console.log(error);
            }
        },
        /**
         * @description gets value for defined key from localStorage if localStorgae is supported, else falls back to js-cookie script
         * @requires js-cookie https://github.com/js-cookie/js-cookie
         * @function getStorageValue(key)
         * @param {string} key
         */
        getStorageValue: function(key) {
            var value;
            if(this._isLocalStorage()) {
                var data = JSON.parse(localStorage.getItem(key));
                if(data !== null && data.timestamp < new Date().getTime()) {
                    this.removeStorageValue(key);
                } else if(data !== null) {
                    value = data.value;
                }
            } else if(this._isCookieScript()) {
                value = Cookies(key);
            } else {
                value = ''; //TODO handle no cookie
            }
            return value;
        },
        /**
         * @description removes defined key from localStorage if localStorage is supported, else falls back to js-cookie script
         * @requires js-cookie https://github.com/js-cookie/js-cookie
         * @function removeStorageValue(key)
         * @param {string} key
         */
        removeStorageValue: function(key) {
            if(this._isLocalStorage()) {
                localStorage.removeItem(key);
            } else if(this._isCookieScript()) {
                Cookies.remove(key);
            }
        },
        /**
         * @description Sets equal height on specified element group
         * @function equalHeight(obj)
         * @param {object}
         */
        equalHeight: function(obj) {
/* see jquery plugin             var tallest = 0;
            obj.each(function() {
                var elHeight = $(this).height();
                if(elHeight > tallest) {
                    tallest = elHeight;
                }
            });
            obj.height(tallest);
*/
        },
        /**
         * @description detects if localStorage is supported by browser
         * @function _isLocalStorage()
         * @private
         */
        _isLocalStorage: function() {
            return typeof Storage !== 'undefined';
        },
        /**
         * @description detects if js-cookie.js is present
         * @function _isCookieScript()
         * @private
         */
        _isCookieScript: function() {
            return typeof Cookies !== 'undefined';
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
        },
        /**
         * @description Checks for saved state of sidebar
         * @function handleSidebar(trigger, container)
         * @param {object} trigger
         * @param {object} container
         */
        handleSidebar: function(container, menu) {
            var viewportWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            if(this.getStorageValue('sidebar-pref') === 'sidebar-off' || viewportWidth <= this.small_width) {
                container.addClass('sidebar-off').removeClass('sidebar-on');
                menu.addClass('sidebar-off').removeClass('sidebar-on');
            } else {
                container.addClass('sidebar-on').removeClass('sidebar-off');
                menu.addClass('sidebar-on').removeClass('sidebar-off');
            }
        },
        /**
         * @description Handles setting for Sidebar and sets open state
         * @private
         * @function _openSidebar(obj, target)
         * @params {object} obj
         * @params {object} target
         */
        openSidebar: function(container, menu) {
            container.addClass('sidebar-on').removeClass('sidebar-off');
            menu.addClass('sidebar-on').removeClass('sidebar-off');
            menu.find('li.current ul').show();
            this.setStorageValue('sidebar-pref', 'sidebar-on', 60);
        },
        /**
         * @description Handles setting for Sidebar and sets closed state
         * @private
         * @function _closeSidebar(obj, target)
         * @params {object} obj
         * @params {object} target
         */
        closeSidebar: function(container, menu) {
            container.removeClass('sidebar-on').addClass('sidebar-off');
            menu.removeClass('sidebar-on').addClass('sidebar-off');
            menu.find('li ul').hide();
            this.setStorageValue('sidebar-pref', 'sidebar-off', 60);
        },
        /**
         * @description Sets intial state of main menu child items
         * @function handleSubMenu($ob)
         * @param {object} $ob - Menu container object
         */
        handleSubMenu: function($ob) {
            $ob.find('li.current span').addClass('open-sub');
        },
        /**
         * @description Handles 'dynamic' notifications
         * @function showNotifications()
         * @requires global cms_data{}, cms_notify(), cms_lang()
         */
        showNotifications: function() {
            //back-compatibility check might be relevant in some contexts
//            if (typeof cms_notify_all === 'function') {
                cms_notify_all();
//            } else {
//                do old-style notifications
//            }

            $('.pagewarning, .message, .pageerrorcontainer, .pagemcontainer').prepend('<span class="close-warning" title="' + cms_lang('gotit') + '"></span>');
            $(document).on('click', '.close-warning', function() {
                $(this).parent().hide();
                $(this).parent().remove();
            });

           // pagewarning status hidden? TODO is this stuff still relevant ?
            var _this = this,
                key = $('body').attr('id') + '_notification';
            $('.pagewarning .close-warning').click(function() {
                _this.setStorageValue(key, 'hidden', 60);
            });
            if(this.getStorageValue(key) === 'hidden') {
                $('.pagewarning').addClass('hidden');
            }

            $(document).on('cms_ajax_apply', function(e) {
                var type = (e.response === 'Success') ? 'success' : 'error';
                cms_notify(type, e.details);
            });
        },
        /**
         * @description Substitutes styled buttons for input-submits. And some links
         * @function migrateUIElements()
         */
        migrateUIElements: function() {
            // Standard input buttons
            $('input[type="submit"], :button[data-ui-icon]').each(function() {
                var button = $(this);
                if(!(button.hasClass('noautobtn') || button.hasClass('no-ui-btn'))) {
                    var xclass, label, $btn;
                    if(button.is('[name*=submit]')) {
                        xclass = 'iconcheck';
                    } else if(button.is('[name*=apply]')) {
                        xclass = 'iconapply';
                    } else if(button.is('[name*=cancel]') || button.is('[name*=close]')) {
                        xclass = 'iconclose';
                    } else if(button.is('[name*=reset]') || button.attr('id') === 'refresh') {
                        xclass = 'iconundo';
                    } else {
                        xclass = '';
                    }
                    //ETC
                    if(button.is('input')) {
                        label = button.val();
                    } else {
                        label = button.text();
                    }
                    $btn = $('<button type="submit" class="adminsubmit ' + xclass + '">' + label + '</button>');
                    $(this.attributes).each(function(idx, attrib) {
                        switch (attrib.name) {
                          case 'type':
                            break;
                          case 'class':
                            var oc = attrib.value.replace(/(^|\s*)ui-\S+/g,'');
                            if (oc !== '') {
                                $btn.attr('class', 'adminsubmit ' + xclass + ' ' + oc);
                            }
                            break;
                          default:
                            $btn.attr(attrib.name, attrib.value);
                            break;
                        }
                    });
                    button.replaceWith($btn);
                }
            });
            // Back links
            $('a.pageback').addClass('link_button icon back');
        },
        /**
         * @description Placeholder function for functions that need to be triggered on window resize
         * @function updateDisplay()
         */
        updateDisplay: function() {
/*
            var $menu = $('#pg_menu');
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
                        scrollTop: $("#pg_menu").offset().top
                    }, 1000);
                }
            }
*/
        },
        /**
         * @description
         * @private
         * @function _handleAlert(target)
         * @requires global cms_data{}
         * @params {object} target
         * @deprecated since 2.3 use showNotifications()
         */
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
            }).done(function() {
                _row.slideUp(1000);
                var _parent = _row.parent();
                if(_parent.children().length <= 1) {
                    _row.closest('div.ui-dialog-content').dialog('close');
                    $('#alert-noalerts').show();
                    $('a#alerts').closest('li').remove();
                }
                _row.remove();
            }).fail(function(xhr, status, msg) {
                console.debug('problem deleting an alert: ' + msg);
            });
        },
        /**
         * @description Handles popping up the notification area
         * @private
         * @function setupAlerts()
         * @deprecated since 2.3 use showNotifications()
         */
        setupAlerts: function() {
            var _this = this;
            $('a#alerts').click(function(e) {
                e.preventDefault();
                $('#alert-dialog').dialog();
            });
            $('.alert-msg a').click(function(e) {
                e.preventDefault();
                _this.handleAlert(e.target);
            });
            $('.alert-icon,.alert-remove').click(function(e) {
                e.preventDefault();
                _this._handleAlert(e.target);
            });
        }
    };
})(this, jQuery);
