/** ==========================================================
 * CMSMS Altbier theme functions
 * @package CMS Made Simple
 * @module AB
 * ==========================================================
 */
/*!
CMSMS Altbier theme functions v.2
(C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL2+
*/
(function (global, $, window, document) {
    'use strict';
    /*jslint nomen: true , devel: true*/
    /**
     * @namespace AB
     */
    var AB = global.AB = {

//      init_width: $(window).width(),
        show_submenus: false,

        init: function () {
            var _this = this,
              wait = false,
              $container = $('#ab_container'), // page container
              $menu = $('#ab_pagemenu'); // topmost ul of the nav elements
            // load js-cookie.js if localStorage is not supported
            if (!this.isLocalStorage()) {
                this.loadScript('themes/assets/js/js-cookie.min.js');
            }
            // toggle hide/reveal menu children
            this.toggleSubMenu($menu, 50);
            // substitute elements - buttons for inputs etc
//          this.migrateUIElements();
            // apply jQueryUI buttons
            this.setUIButtons();
            // handle updating the display
            this.updateDisplay();
            // handle sidebar state (collapsed | expanded)
            this.handleSidebar($container);
            // setup deprecated one-request notice handling
//          this.showNotifications();
            // setup persistent-notice handling
            this.setupAlerts();
            // setup custom dialogs N/A
//          cms_data.alertfunc = this.popup_alert;
//          cms_data.confirmfunc = this.popup_confirm;
//          cms_data.promptfunc = this.popup_prompt;
//          cms_data.dialogfunc = this.popup_dialog;
            // display pending notices
            cms_notify_all();
            // open external links with rel="external" attribute in new window
            $('a[rel="external"]').attr('target', '_blank');
            // possible logout warning
            if (cms_data.sitedown) {
                $('.outwarn').on('click activate', function(ev) {
                   ev.preventDefault();
                   var prompt = cms_lang('maintenance_warning');
                   cms_confirm_linkclick(this, prompt);
                   return false;
                });
            }
            $(window).on('resize', function() {
                if (!wait) {
                    wait = true;
                    _this.handleSidebar($container);
                    _this.updateDisplay();
                    setTimeout(function () {
                        wait = false;
                    }, 200);
                }
            });
            // handle navigation-sidebar toggling
            $('#toggle-button').on('click activate', function(ev) {
                ev.preventDefault();
                if ($container.hasClass('sidebar-on')) {
                    _this.closeSidebar($container, $menu);
                    _this.show_submenus = false;
                } else {
                    _this.show_submenus = true;
                    _this.openSidebar($container, $menu);
                }
                return false;
            });
            // focus the input with .defaultfocus class
            $('input.defaultfocus, input[autofocus]').eq(0).focus();
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
        loadScript: function (url, arg1, arg2) {
            var cache = true,
                callback = null,
                load = true;
            //arg1 and arg2 can be interchangable
            if (typeof arg1 === "function") {
                callback = arg1;
                cache = arg2 || cache;
            } else {
                cache = arg1 || cache;
                callback = arg2 || callback;
            }
            //check all existing script tags in the page for the url
            $('script[type="text/javascript"]').each(function () {
                var load = (url !== $(this).attr('src'));
                return load;
            });
            if (load) {
                //didn't find it in the page, so load it
                return $.ajax(url, {
                    dataType: 'script',
                    async: false,
                    cache: cache
                }).done(callback);
            } else {
                //already loaded so just call the callback
                if (typeof callback === "function") {
                    callback.call(this);
                }
            }
        },

        /**
         * @description saves a defined key and value to localStorage if localStorgae is supported, else falls back to js-cookie script
         * @function setStorageValue(key, value)
         * @param {string} key
         * @param {string} value
         * @param {number} expires (number in days)
         */
        setStorageValue: function (key, value, expires) {
            var expiration = new Date().getTime() + (expires * 24 * 60 * 60 * 1000),
                obj = {};
            try {
                if (this.isLocalStorage() === true) {
                    localStorage.removeItem(key);
                    if (expires !== null) {
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
                } else if (typeof Cookies !== 'undefined') {
                    if (expires !== null) {
                        Cookies.set(key, value, {
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
         * @function getStorageValue(key)
         * @param {string} key
         */
        getStorageValue: function (key) {
            var data = '',
                value;
            if (this.isLocalStorage()) {
                data = JSON.parse(localStorage.getItem(key));
                if (data !== null && data.timestamp < new Date().getTime()) {
                    this.removeStorageValue(key);
                } else if (data !== null) {
                    value = data.value;
                }
            } else if (typeof Cookies !== 'undefined') {
                value = Cookies(key);
            }
            return value;
        },

        /**
         * @description removes defined key from localStorage if localStorgae is supported, else falls back to js-cookie script
         * @function removeStorageValue(key)
         * @param {string} key
         */
        removeStorageValue: function (key) {
            if (this.isLocalStorage()) {
                localStorage.removeItem(key);
            } else if (typeof Cookies !== 'undefined') {
                Cookies.remove(key);
            }
        },

        /**
         * @description detects if localStorage is supported by browser
         * @function isLocalStorage()
         * @private
         */
        isLocalStorage: function () {
            return typeof(Storage) !== 'undefined';
        },

        /**
         * @description Basic check for common mobile devices and touch capability
         * @function isMobileDevice()
         * @private
         */
        isMobileDevice: function () {
            var ua = navigator.userAgent.toLowerCase(),
                devices = /(Android|iPhone|iPad|iPod|Blackberry|Dolphin|IEMobile|WPhone|Windows Mobile|IEMobile9||IEMobile10||IEMobile11|Kindle|Mobile|MMP|MIDP|Pocket|PSP|Symbian|Smartphone|Sreo|Up.Browser|Up.Link|Vodafone|WAP|Opera Mini|Opera Tablet|Mobile|Fennec)/i;
            if (ua.match(devices) && (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0) || window.DocumentTouch && document instanceof DocumentTouch)) {
                return true;
            }
        },

        /**
         * @description Hide/show CMSMS version info
         * @function aboutToggle()
         */
        aboutToggle: function() {
            var el = document.getElementById('aboutinfo');
            if (el.style.display === 'none') {
                el.style.display = 'inline-block';
            } else {
                el.style.display = 'none';
            }
        },

        /**
         * @description Sets equal height on specified element group
         * @function equalHeight(obj)
         * @param {object}
         */
        equalHeight: function (obj) {
            var tallest = 0;
            obj.each(function () {
                var elHeight = $(this).height();
                if (elHeight > tallest) {
                    tallest = elHeight;
                }
            });
            obj.height(tallest);
        },

        /**
         * @description Placeholder function for functions that need to be triggered on window resize
         * @function updateDisplay()
         */
        updateDisplay: function () {
            var $menu = $('#ab_menu'),
              $alert_box = $('#admin-alerts'),
              offset;
            if ($alert_box.length) {
                offset = $alert_box.outerHeight() + $alert_box.offset().top;
            } else {
                var $header = $('#header');
                offset = $header.outerHeight() + $header.offset().top;
            }
//            console.debug('menu height = ' + $menu.outerHeight() + ' offset = ' + offset);
//            console.debug('window height = ' + $(window).height());
            if ($menu.outerHeight() + offset < $(window).height()) {
//                $menu.css({ 'position': 'fixed', 'top': offset });
//                console.debug('fixed');
            } else {
//                $menu.css({ 'position': '', 'top': '' });
//                console.debug('floating');
                if ($menu.offset().top < $(window).scrollTop()) {
                    // menu top is not visible, scroll to it
                    $('html, body').animate({
                        scrollTop: $("#ab_menu").offset().top
                    }, 1000);
                }
            }
        },

        /**
         * @description Checks for saved state of sidebar
         * @function handleSidebar(container)
         * @param {object} container
         */
        handleSidebar: function (container) {
//          var viewportWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            var viewportWidth = $(window).outerWidth();
            if (this.getStorageValue('sidebar-pref') === 'sidebar-off' || viewportWidth <= 767) {
                container.addClass('sidebar-off').removeClass('sidebar-on');
                this.show_submenus = false;
            } else {
                container.addClass('sidebar-on').removeClass('sidebar-off');
                this.show_submenus = true;
            }
        },

        /**
         * @description Handles setting for sidebar and sets open state
         * @function openSidebar(container, menu)
         * @private
         * @params {object} container
         * @params {object} menu
         */
        openSidebar: function (container, menu) {
            this.show_submenus = true;
            var el = menu.find('li.current');
            if (el.length > 0) {
                el = el.parentsUntil(menu, 'ul').add(el.children('ul'));
                el.show();
            }
            container.addClass('sidebar-on').removeClass('sidebar-off');
            this.setStorageValue('sidebar-pref', 'sidebar-on', 60);
        },

        /**
         * @description Handles setting for sidebar and sets closed state
         * @function closeSidebar(container, menu)
         * @private
         * @params {object} container
         * @params {object} menu
         */
        closeSidebar: function (container, menu) {
            container.removeClass('sidebar-on').addClass('sidebar-off');
            menu.find('li ul').hide();
            this.show_submenus = false;
            this.setStorageValue('sidebar-pref', 'sidebar-off', 60);
        },

        /* *
         * @description Stop toggling of main menu child items
         * @function preventSubMenu(obj)
         * @param {object} obj - Menu container object
         */
/*        preventSubMenu: function (container) {
            //var _this = this;
            $(container).find('.nav > span').on('click activate', function() {
                return true;
            });
        },
*/
        /**
         * @description Handles toggling of main menu child items
         * @function toggleSubMenu(menu, duration)
         * @param {object} menu - Menu container object
         * @param {number} duration - A positive number for toggle speed control
         */
        toggleSubMenu: function (menu, duration) {
            var _this = this,
             $LIs = menu.find('li.sub');
            $LIs.children('a').on('click activate', function(ev) {
                var $li = $(this).parent();
                if ($li.hasClass('current')) {
                    ev.preventDefault();
                    return false;
                }
                menu.find('li').removeClass('current open').find('.nav-mark').removeClass('open');
                $li.addClass('current');
                _this.closeSidebar($('#ab_container'), menu);
                _this.updateDisplay();
                return true;
            });
            $LIs.children('span').on('click activate', function(ev) {
                ev.preventDefault();
                var $li = $(this).parent(),
                 _p = [];
                if ($li.hasClass('open')) {
                    _p.push($li.find('ul').slideUp(duration));
                    _p.push($li.add($li.find('li')).removeClass('open').find('.nav-mark').removeClass('open'));
                } else {
                    var $s = $li.siblings();
                    _p.push($s.find('ul').slideUp(duration/2));
                    _p.push($s.add($s.find('li')).removeClass('open').find('.nav-mark').removeClass('open'));
                    _p.push($li.children('ul').slideDown(duration));
                    _p.push($li.addClass('open').children('.nav-mark').addClass('open'));
                }
                $.when.apply($, _p).done(function () {
                    _this.updateDisplay();
                });
                return false;
            });
        },

        /* *
         * @description Handle 'dynamic' notifications
         * @function showNotifications()
         * @private
         */
/*      showNotifications: function () {
            if (typeof cms_notify === 'function') {
                return; // notifications handled in core
            }
            // old-style notifications
//          $('.pagewarning, .message, .pageerrorcontainer, .pagemcontainer').prepend('<span class="close-warning"></span>');
            $('.close-warning').on('click activate', function() {
                $(this).parent().hide().remove();
            });
            // pagewarning status hidden?
            var key = $('body').attr('id') + '_notification';
            $('.pagewarning .close-warning').on('click activate', function() {
                AB.setStorageValue(key, 'hidden', 60);
            });
            if (this.getStorageValue(key) === 'hidden') {
                $('.pagewarning').addClass('hidden');
            }
            $('.message:not(.no-slide)').on('click activate', function() {
                $('.message').slideUp();
            });
            $('.message:not(.no-slide), .pageerrorcontainer:not(.no-slide), .pagemcontainer:not(.no-slide)').each(function () {
                var message = $(this);
                $(message).hide().slideDown(1000, function() {
                    window.setTimeout(function () {
                        message.slideUp();
                    }, 10000);
                });
            });
            $(document).on('cms_ajax_apply', function(ev) {
                var $closer = $('button[name="cancel"], button[name="m1_cancel"]'),
                  txt = cms_lang('close'),
                  htmlShow;
                $closer.fadeOut().button('option', 'label', ev.close).fadeIn();
                if (ev.response === 'Success') {
                    htmlShow = '<aside class="message pagemcontainer" role="status"><span class="close-warning xhr">' + txt + '<\/span><p class="pagemessage">' + ev.details + '<\/p><\/aside>';
                } else {
                    htmlShow = '<aside class="message pageerrorcontainer" role="alert"><span class="close-warning">'+ txt +'<\/span><ul class="pageerror">' + ev.details + '<\/ul><\/aside>';
                }
                $('body').append(htmlShow).slideDown(1000, function() {
                    window.setTimeout(function () {
                        $('.message').slideUp().remove();
                    }, 10000);
                });
                $(document).on('click activate', '.close-warning', function() {
                    $('.message').slideUp().remove();
                });
            });
        },
*/
       /* *
        * @description Substitute styled buttons for named input-submits. And some links
        * @function migrateUIElements()
        * @private
        */
/*        migrateUIElements: function () {
        //TODO
        },
*/
        /**
         * @description Apply jQueryUI button function to input buttons
         * @function setUIButtons()
         * @private
         */
        setUIButtons: function () {
            // Standard named input buttons
            $('input[type="submit"], :button[data-ui-icon]').each(function () {
                if (!this.value.trim()) return true;
                var button = $(this),
                    icon = button.data('uiIcon') || 'ui-icon-circle-check',
                    label = button.val(),
                    $btn = $('<button />');
                if (!button.hasClass('noautobtn') || !button.hasClass('no-ui-btn')) {
                    if (button.is('[name*=apply]')) {
                        icon = button.data('uiIcon') || 'ui-icon-disk';
                    } else if (button.is('[name*=cancel]')) {
                        icon = button.data('uiIcon') || 'ui-icon-circle-close';
                    } else if (button.is('[name*=resettodefault]') || button.attr('id') === 'refresh') {
                        icon = button.data('uiIcon') || 'ui-icon-refresh';
                    }
                }
                if (button.is(':button')) {
                    label = button.text();
                }
                $(this.attributes).each(function (index, attribute) {
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
            $('.pageback').addClass('ui-state-default ui-corner-all')
                .prepend('<span class="ui-icon ui-icon-arrowreturnthick-1-w">')
                .hover(function () {
                    $(this).addClass('ui-state-hover');
                }, function() {
                    $(this).removeClass('ui-state-hover');
                });
        },

        /**
         * @description Delete persistent notice
         * @function handleAlert(target)
         * @private
         * @params {object} target
         */
        handleAlert: function (target) {
            var _row = $(target).closest('.alert-box'),
                _alert_name = _row.data('alert-name');
            if (!_alert_name) return;
            return $.ajax(cms_data.ajax_alerts_url, {
                method: 'POST',
                data: {
                  op: 'delete',
                  alert: _alert_name
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.debug('problem deleting an alert: ' + errorThrown);
            }).done(function () {
                _row.slideUp(1000);
                var _parent = _row.parent();
                if (_parent.children().length <= 1) {
                    cms_dialog($('#alert-dialog'), 'close');
                    $('#alert-noalerts').show();
                    $('#alerts').closest('li').remove();
                }
                _row.remove();
            });
        },

        /**
         * @description Handle persistent (keep-until-dismiss) notifications
         * @function setupAlerts()
         * @private
         */
        setupAlerts: function () {
            var _this = this;
            $('#alerts').on('click activate', function(ev) {
                ev.preventDefault();
                cms_dialog($('#alert-dialog')); //TODO cms_dialog full API
            });
            $('.alert-remove, .alert-msg a').on('click activate', function(ev) {
                ev.preventDefault();
                _this.handleAlert(ev.target);
            });
        }
    };

})(this, jQuery, window, document);

$(function() {
    AB.init();
});
