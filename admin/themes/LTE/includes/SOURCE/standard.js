/** ==========================================================
 * CMSMS LTE theme functions
 * @package CMS Made Simple
 * @module LT
 * ==========================================================
*/
/*!
CMSMS LTE theme functions v.2
(C) 2020-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License GPL2+
*/
(function(global, $, window, document) {
    'use strict';
    /*jslint nomen: true , devel: true*/
    /**
     * @namespace LT
     */
    var LT = global.LT = {

        init: function () {
            // load js-cookie.js if localStorage is not supported
            if (!this.isLocalStorage()) {
                this.loadScript('themes/assets/js/js-cookie.min.js');
            }
            // substitute elements - buttons for inputs etc
//          this.migrateUIElements();
            // setup deprecated one-request notice handling
//            this.showNotifications();
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
            $('script[type="text/javascript"]').each(function() {
                var load = ( url !== $(this).attr('src') );
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
            var expiration = new Date().getTime() + ( expires * 24 * 60 * 60 * 1000 ),
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
            return typeof (Storage) !== 'undefined';
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
        aboutToggle: function () {
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
            obj.each(function() {
                var elHeight = $(this).height();
                if (elHeight > tallest) {
                    tallest = elHeight;
                }
            });
            obj.height(tallest);
        },

        /* *
         * @description Handle 'dynamic' notifications
         * @function showNotifications()
         * @private
         */
/*        showNotifications: function () {
            if (typeof cms_notify === 'function') {
                return; // notifications handled in core
            }
            // old-style notifications
            // $('.pagewarning, .message, .pageerrorcontainer, .pagemcontainer').prepend('<span class="close-warning"></span>');
            $('.close-warning').on('click activate', function() {
                $(this).parent().hide().remove();
            });
            // pagewarning status hidden?
            var key = $('body').attr('id') + '_notification';
            $('.pagewarning .close-warning').on('click activate', function() {
                LT.setStorageValue(key, 'hidden', 60);
            });
            if (this.getStorageValue(key) === 'hidden') {
                $('.pagewarning').addClass('hidden');
            }
            $('.message:not(.no-slide)').on('click activate', function() {
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
            $(document).on('cms_ajax_apply', function(ev) {
                var $closer = $('button[name="cancel"], button[name="m1_cancel"]'),
                  txt = cms_lang('close'),
                  htmlShow;
                $closer.fadeOut().button('option', 'label', ev.close).fadeIn();
                if (ev.response === 'Success') {
                    htmlShow = '<aside class="message pagemcontainer" role="status"><span class="close-warning">' + txt + '<\/span><p class="pagemessage">' + ev.details + '<\/p><\/aside>';
                } else {
                    htmlShow = '<aside class="message pageerrorcontainer" role="alert"><span class="close-warning">'+ txt +'<\/span><ul class="pageerror">' + ev.details + '<\/ul><\/aside>';
                }
                $('body').append(htmlShow).slideDown(1000, function() {
                    window.setTimeout(function() {
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
/*      migrateUIElements: function () {
        //TODO
        },
*/
        /**
         * @description Delete persistent notice
         * @function handleAlert(target)
         * @private
         * @params {object} target
         */
        handleAlert: function (target) {
            var _row = $(target).closest('.alert-box'),
                _alert_name = _row.data('alert-name');
            if ( ! _alert_name ) return;
            return $.ajax(cms_data.ajax_alerts_url, {
                method: 'POST',
                data: {
                    op: 'delete',
                    alert: _alert_name
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.debug('problem deleting an alert: ' + errorThrown);
            }).done(function(){
                _row.slideUp(1000);
                var _parent = _row.parent();
                if ( _parent.children().length <= 1 ) {
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
            $('#alerts').on('click activate', function(ev){
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
    LT.init();
});
