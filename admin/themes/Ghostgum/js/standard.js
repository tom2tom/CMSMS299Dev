/*!
Ghostgum-theme support
(C) The CMSMS Dev Team <coreteam@cmsmadesimple.org>
License: GPL2+
*/
/**
 * @package CMS Made Simple
 * @description CMSMS theme functions - tailored for Ghostgum theme
 * @author Goran Ilic - uniqu3 <ja@ich-mach-das.at>
 * Updates since 2012 by the CMSMS Dev Team
 * NOTE includes a hardcoded url for an external cookie processor, and viewport width-threshold
 */
 /*jslint nomen: true , devel: true*/

var cookie_handler = 'themes/assets/js/js-cookie.min.js',
    small_width = 992, // viewport-width threshold
    $container, // outer container
    $menucontainer, // nav menu container
    $menu; // nav menu

$(document).ready(function() {
    $container = $('#ggp_container');
    $menucontainer = $container.find('#ggp_navwrap');
    $menu = $menucontainer.find('#ggp_nav');

    view_init();
    helper_init();
});

function view_init() {
    // handle the initial collapsed/expanded state of the sidebar
    handleSidebar($container, $menucontainer);
    // handle navigation sidebar toggling
    $(window).resize(function() {
        handleSidebar();
        updateDisplay();
    });
    // handle initial display of sub-menu
    handleSubMenu($menu);
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
            updateDisplay();
        });
        return false;
    });
    // handle notifications
    showNotifications();
    // substitute elements - buttons for inputs etc
    migrateUIElements();
    // handle updating the display.
    updateDisplay();
    // setup deprecated alert-handlers
    setupAlerts();
    // setup custom dialogs
    cms_data.alertfunc = popup_alert;
    cms_data.confirmfunc = popup_confirm;
//    cms_data.dialogfunc = popup_dialog;
}

function helper_init() {
    // open external links with rel="external" attribute in new window
    $('a[rel=external]').attr('target', '_blank');
    // focus on input with .defaultfocus class
    $('input.defaultfocus:eq(0), input[autofocus]').focus();
    // async-load a cookie handler if localStorage is not supported
    if(!isLocalStorage()) {
        loadScript(cookie_handler);
    }
}
/**
 * @description conditional load script helper function
 * @author Brad Vincent https://gist.github.com/2313262
 * @function loadScript(url, arg1, arg2)
 * @param {string} url
 * @callback requestCallback
 * @param {requestCallback|boolean} arg1
 * @param {requestCallback|boolean} arg2
 */
function loadScript(url, arg1, arg2) {
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
}
/**
 * @description saves a defined key and value to localStorage if localStorgae is supported, else falls back to cookie script
 * @requires js-cookie https://github.com/js-cookie/js-cookie
 * @function setStorageValue(key, value)
 * @param {string} key
 * @param {string} value
 * @param {number} expires (number in days)
 */
function setStorageValue(key, value, expires) {
    try {
        if(isLocalStorage()) {
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
        } else if(isCookieScript()) {
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
}
/**
 * @description gets value for defined key from localStorage if that's supported, else falls back to js-cookie script
 * @requires js-cookie https://github.com/js-cookie/js-cookie
 * @function getStorageValue(key)
 * @param {string} key
 */
function getStorageValue(key) {
    var value;
    if(isLocalStorage()) {
        var data = JSON.parse(localStorage.getItem(key));
        if(data !== null && data.timestamp < new Date().getTime()) {
            removeStorageValue(key);
        } else if(data !== null) {
            value = data.value;
        }
    } else if(isCookieScript()) {
        value = Cookies(key);
    } else {
        value = ''; //TODO handle no cookie
    }
    return value;
}
/**
 * @description removes defined key from localStorage if that's supported, else falls back to js-cookie script
 * @requires js-cookie https://github.com/js-cookie/js-cookie
 * @function removeStorageValue(key)
 * @param {string} key
 */
function removeStorageValue(key) {
    if(isLocalStorage()) {
        localStorage.removeItem(key);
    } else if(isCookieScript()) {
        Cookies.remove(key);
    }
}
/**
 * @description Sets equal height on specified element group
 * @function equalHeight(obj)
 * @param {object}
 */
function equalHeight(obj) {
/* see jquery plugin             var tallest = 0;
    obj.each(function() {
        var elHeight = $(this).height();
        if(elHeight > tallest) {
            tallest = elHeight;
        }
    });
    obj.height(tallest);
*/
}
/**
 * @description detects if localStorage is supported by browser
 * @function isLocalStorage()
 * @private
 */
function isLocalStorage() {
    return typeof Storage !== 'undefined';
}
/**
 * @description detects if js-cookie.js is present
 * @function isCookieScript()
 * @private
 */
function isCookieScript() {
    return typeof Cookies !== 'undefined';
}
/**
 * @description Basic check for common mobile devices and touch capability
 * @function isMobileDevice()
 * @private
 */
function isMobileDevice() {
    var ua = navigator.userAgent.toLowerCase(),
        devices = /(Android|iPhone|iPad|iPod|Blackberry|Dolphin|IEMobile|WPhone|Windows Mobile|IEMobile9||IEMobile10||IEMobile11|Kindle|Mobile|MMP|MIDP|Pocket|PSP|Symbian|Smartphone|Sreo|Up.Browser|Up.Link|Vodafone|WAP|Opera Mini|Opera Tablet|Mobile|Fennec)/i;
    if(ua.match(devices) && (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0) || window.DocumentTouch && document instanceof DocumentTouch)) {
        return true;
    }
}
/**
 * @description Checks for saved state of sidebar
 * @function handleSidebar()
 */
function handleSidebar() {
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    if(getStorageValue('sidebar-pref') === 'sidebar-off' || viewportWidth <= small_width) {
        $menucontainer.addClass('sidebar-off').removeClass('sidebar-on');
        $menu.addClass('sidebar-off').removeClass('sidebar-on');
    } else {
        $menucontainer.addClass('sidebar-on').removeClass('sidebar-off');
        $menu.addClass('sidebar-on').removeClass('sidebar-off');
    }
}
/*
 * @description Toggles Sidebar open/closed state
 * @function clickSidebar()
*/
function clickSidebar() {
   if($menucontainer.hasClass('sidebar-on')) {
     closeSidebar();
   } else {
     openSidebar();
   }
}
/**
 * @description Handles setting for Sidebar and sets open state
 * @private
 * @function openSidebar()
 */
function openSidebar() {
    $menucontainer.addClass('sidebar-on').removeClass('sidebar-off');
    $menu.find('li.current ul').show();
    setStorageValue('sidebar-pref', 'sidebar-on', 60);
}
/**
 * @description Handles setting for Sidebar and sets closed state
 * @private
 * @function closeSidebar()
 */
function closeSidebar() {
    $menucontainer.removeClass('sidebar-on').addClass('sidebar-off');
    $menu.find('li ul').hide();
    setStorageValue('sidebar-pref', 'sidebar-off', 60);
}
/**
 * @description Sets intial state of main menu child items
 * @function handleSubMenu($ob)
 * @param {object} $ob - Menu container object
 */
function handleSubMenu($ob) {
    $ob.find('li.current span').addClass('open-sub');
}
/**
 * @description Handles 'dynamic' notifications
 * @function showNotifications()
 * @requires global cms_data{}, cms_notify(), cms_lang()
 */
function showNotifications() {
 //back-compatibility check might be relevant in some contexts
 //if (typeof cms_notify_all === 'function') {
    cms_notify_all();
// } else {
//   do old-style notifications
// }

    $('.pagewarning, .message, .pageerrorcontainer, .pagemcontainer').prepend('<span class="close-warning" title="' + cms_lang('gotit') + '"></span>');
    $('.close-warning').on('click', function() {
        $(this).parent().hide();
        $(this).parent().remove();
    });

   // pagewarning status hidden? TODO is this stuff still relevant ?
    var key = $('body').attr('id') + '_notification';
    $('.pagewarning .close-warning').on('click', function() {
        setStorageValue(key, 'hidden', 60);
    });
    if(getStorageValue(key) === 'hidden') {
        $('.pagewarning').addClass('hidden');
    }

    $(document).on('cms_ajax_apply', function(e) {
        var type = (e.response === 'Success') ? 'success' : 'error';
        cms_notify(type, e.details);
    });
}
/**
 * @description Substitutes styled buttons for input-submits. And some links
 * @function migrateUIElements()
 */
function migrateUIElements() {
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
            var attributes = []; //TODO;
            $(attributes).each(function(idx, attrib) {
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
}
/**
 * @description Placeholder function for functions that need to be triggered on window resize
 * @function updateDisplay()
 */
function updateDisplay() {
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
}
/**
 * @description
 * @private
 * @function handleAlert(target)
 * @requires global cms_data{}
 * @params {object} target
 * @deprecated since 2.3 use showNotifications()
 */
function handleAlert(target) {
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
            cms_dialog(_row.closest('div.ui-dialog-content'), 'close');
            $('#alert-noalerts').show();
            $('a#alerts').closest('li').remove();
        }
        _row.remove();
    }).fail(function(xhr, status, msg) {
        console.debug('problem deleting an alert: ' + msg);
    });
}
/**
 * @description Handles popping up the notification area
 * @private
 * @function setupAlerts()
 * @deprecated since 2.3 use showNotifications()
 */
function setupAlerts() {
    $('a#alerts').on('click', function(e) {
        e.preventDefault();
        cms_dialog($('#alert-dialog'));
        return false;
    });
    $('.alert-msg a').on('click', function(e) {
        e.preventDefault();
        handleAlert(e.target);
        return false;
    });
    $('.alert-icon,.alert-remove').on('click', function(e) {
        e.preventDefault();
        handleAlert(e.target);
        return false;
    });
}
/**
 * @description display a modal alert dialog
 * @function
 * @param (String) msg The message to display (text, no markup TODO)
 * @param (String) title Unused title string.
 * @return promise
 */
function popup_alert(msg, title) {
    return $.alertable.alert(msg,{
       okButton: '<button type="button" class="adminsubmit">'+ cms_lang('close') + '</button>',
    });
}
/**
 * @description display a modal confirm dialog
 * @function
 * @param (String) msg The message to display
 * @param (String) title Unused title string
 * @param (String) yestxt Text for the yes button label
 * @param (String) notxt Text for the no button label
 * @return promise
 */
function popup_confirm(msg, title, yestxt, notxt) {
    return $.alertable.confirm(msg,{
       okButton: '<button type="button" class="adminsubmit icon check">' + yestxt + '</button>',
       cancelButton: '<button type="button" class="adminsubmit icon cancel">' + notxt + '</button>'
    });
}
