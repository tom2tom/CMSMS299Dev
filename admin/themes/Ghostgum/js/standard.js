/**
 * @package CMS Made Simple
 * @description CMSMS theme functions - tailored for Ghostgum theme
 */
/*!
javascript for CMSMS Ghostgum-theme v.0.9
(C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License: Affero GPL 3+
*/
/* jslint nomen: true, devel: true */
/* global $, jQuery, cms_data, cms_lang, cms_dialog, cms_notify, cms_notify_all, Cookies, DocumentTouch */

// NOTE this class includes default property-values for:
//  URL for a fallback external cookie processor script, and
//  viewport width-threshold for layout changes
// Those may be altered using setParms()

var themejs = {};
(function ($, window, document, classname) {
  var
    cookieHandler = 'themes/assets/js/js-cookie.min.js', // assistant script
    smallWidth = 600, // viewport-width threshold related to sidebar display
    $container = null, // cache for outer container
    $menucontainer = null, // nav menu container
    $menu = null; // nav menu

  this.init = function () {
    $container = $('#ggp_container');
    $menucontainer = $container.find('#ggp_navwrap');
    $menu = $menucontainer.find('#ggp_nav');
    // prevent scrolling of some page content
    var $ob = $container.find('#page_tabs');
    if ($ob.length > 0) {
      this.stickIt($ob);
      //TODO on tab-change, scroll to top
    }
    // handle the initial collapsed/expanded state of the nav sidebar
    this.handleSidebar();
    var self = this;
    // handle navigation sidebar toggling etc
    $menucontainer.find('#ggp_headlink').prop('href', 'javascript:' + classname + '.clickSidebar()');
    $menucontainer.find('#ggp_headzone').on('click', function (e) {
      e.preventDefault();
      self.clickSidebar();
    });
    $(window).on('resize', function () {
      self.updateDisplay();
    });
    // handle initial display of sub-menu
    this.handleSubMenu($menu);
    // handle sub-menu display toggling
    $menu.find('.open-nav').on('click', function (e) {
      //clicked span in a menu item title
      e.preventDefault();
      var $ob = $(this),
        $ul = $ob.next(), //sub-menu container for this item
        _p = [];
      // jQuery :visible selector is unreliable
      if ($ul.length === 0 || $ul.css('visibility') === 'hidden' || $ul.css('display') === 'none') {
        // close any other open submenu
        var $open = $menu.find('.open-sub');
        if ($open.length) {
          $open.removeClass('open-sub');
          var $ulo = $open.next();
          _p.push($ulo.slideUp(50), function () {
            $ulo.find('li,ul').hide();
          });
        }
        $ob.addClass('open-sub');
      } else {
        $ob.removeClass('open-sub');
      }
      $ul.find('li,ul').show();
      _p.push($ul.slideToggle(50));
      $.when.apply($, _p).done(function () {
        self.updateDisplay();
      });
      return false;
    });
    // handle notifications
    this.showNotifications();
    // substitute elements - buttons for inputs etc
    this.migrateUIElements();
    // handle updating the display
    this.updateDisplay();
    // setup deprecated alert-handlers
    this.setupAlerts();
    // setup custom dialogs
    cms_data.alertfunc = this.popup_alert;
    cms_data.confirmfunc = this.popup_confirm;
    cms_data.promptfunc = this.popup_prompt;
    cms_data.dialogfunc = this.popup_dialog;
    // open external links with rel="external" attribute in new window
    $('a[rel="external"]').attr('target', '_blank');
    // focus on input with .defaultfocus class
    $('input.defaultfocus, input[autofocus]').eq(0).focus();
    // async-load a cookie handler if localStorage is not supported
    if (!this.isLocalStorage()) {
      this.loadScript(cookieHandler);
    }
  };
  /**
   * @description conditional load script helper function
   * @author Brad Vincent https://gist.github.com/2313262
   * @function loadScript(url, arg1, arg2)
   * @param {string} url
   * @callback requestCallback
   * @param {requestCallback|boolean} arg1
   * @param {requestCallback|boolean} arg2
   */
  this.loadScript = function (url, arg1, arg2) {
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
  };
  /**
   * @description Change the current value for parameter(s) cookie-handler script URL and/or viewport-width threshold
   * @function setParms(options)
   * @param {object} options with member(s)
   *  cookiescript (string) absolute or <site>/admin-relative URL and/or
   *  viewwidth (int pixels)
   */
  this.setParms = function (options) {
    if (typeof options.cookiescript !== 'undefined') {
      cookieHandler = options.cookiescript; // no sanitization
      if (!this.isLocalStorage()) {
        this.loadScript(cookieHandler);
      }
    }
    if (typeof options.viewwidth !== 'undefined') {
      smallWidth = parseInt(options.viewwidth, 10) || 600;
    }
  };
  /**
   * @description Save a defined key and value to localStorage if localStorgae is supported, else fall back to cookie script
   * @requires js-cookie https://github.com/js-cookie/js-cookie
   * @function setStorageValue(key, value, expires)
   * @param {string} key
   * @param {string} value
   * @param {number} expires (number in days)
   */
  this.setStorageValue = function (key, value, expires) {
    try {
      if (this.isLocalStorage()) {
        localStorage.removeItem(key);
        var obj;
        if (expires !== null) {
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
      } else if (this.isCookieScript()) {
        if (expires !== null) {
          Cookies.set(key, value, {
            expires: expires
          });
        } else {
          Cookies.set(key, value);
        }
      } else {
        throw "No cookie storage!";
      }
    } catch (error) {
      console.log('localStorage Error: set(' + key + ', ' + value + ')');
      console.log(error);
    }
  };
  /**
   * @description Get value for defined key from localStorage if that's supported, else falls back to js-cookie script
   * @requires js-cookie https://github.com/js-cookie/js-cookie
   * @function getStorageValue(key)
   * @param {string} key
   */
  this.getStorageValue = function (key) {
    var value;
    if (this.isLocalStorage()) {
      var data = JSON.parse(localStorage.getItem(key));
      if (data !== null && data.timestamp < new Date().getTime()) {
        this.removeStorageValue(key);
      } else if (data !== null) {
        value = data.value;
      }
    } else if (this.isCookieScript()) {
      value = Cookies(key);
    } else {
      value = ''; //TODO handle no cookie
    }
    return value;
  };
  /**
   * @description Remove defined key from localStorage if that's supported, else falls back to js-cookie script
   * @requires js-cookie https://github.com/js-cookie/js-cookie
   * @function removeStorageValue(key)
   * @param {string} key
   */
  this.removeStorageValue = function (key) {
    if (this.isLocalStorage()) {
      localStorage.removeItem(key);
    } else if (this.isCookieScript()) {
      Cookies.remove(key);
    }
  };
  /**
   * @description Detect whether localStorage is supported by browser
   * @function isLocalStorage()
   * @private
   */
  this.isLocalStorage = function () {
    return typeof Storage !== 'undefined';
  };
  /**
   * @description Detect whether js-cookie.js is present
   * @function isCookieScript()
   * @private
   */
  this.isCookieScript = function () {
    return typeof Cookies !== 'undefined';
  };
  /**
   * @description Check whether running on a touch-capable device (regardless of device form-factor)
   * @function _isMobileDevice()
   * @return boolean
   */
  this._isMobileDevice = function () {
    // references:
    // https://peterscene.com/blog/detecting-touch-devices-2018-update
    // https://medium.com/@ferie/detect-a-touch-device-with-only-css-9f8e30fa1134
    // https://codepen.io/Ferie/pen/vQOMmO
    if (
      'ontouchstart' in window ||
      'ontouchstart' in document.documentElement ||
      (window.DocumentTouch && document instanceof DocumentTouch) ||
      navigator.msMaxTouchPoints > 0) {
      return true;
    }
    if (window.matchMedia) {
      var prefixes = ' -webkit- -moz- -o- -ms- '.split(' ');
      // include the 'Z' to help terminate the join (https://git.io/vznFH)
      var query = ['(', prefixes.join('touch-enabled),('), 'Z', ')'].join('').replace(',(Z)', '');
      return window.matchMedia(query).matches;
      // TODO consider also checking media hover and pointer props, per ref above
    }
    return false;
  };
  /**
   * @description Fix the screen-position of the given element, a workaround for css overflow-scroll limitations
   * @function stickIt($ob)
   * @param {jquery selection} $ob the element to be processed
   * @private
   */
  this.stickIt = function ($ob) {
    var h = $ob.outerHeight(true),
      //      w = $ob.outerWidth(true),
      c = $ob.css('zIndex'),
      z = (c === 'auto') ? 500 : c + 10;
    // insert replacement for to-be-fixed $ob, so that layout is not blarked
    $ob.after($('<div/>', {
      style: 'height:' + h + 'px;'
    }));
    $ob.css({
      position: 'fixed',
      width: '100%', // w + 'px',
      'z-index': z
    });
  };
  /**
   * @description Check for saved state of sidebar
   * @function handleSidebar()
   */
  this.handleSidebar = function () {
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    if (this.getStorageValue('sidebar-pref') === 'sidebar-off' || viewportWidth <= smallWidth) {
      $menucontainer.addClass('sidebar-off').removeClass('sidebar-on');
      $menu.addClass('sidebar-off').removeClass('sidebar-on');
    } else {
      $menucontainer.addClass('sidebar-on').removeClass('sidebar-off');
      $menu.addClass('sidebar-on').removeClass('sidebar-off');
    }
  };
  /*
   * @description Toggles Sidebar open/closed state
   * @function clickSidebar()
   */
  this.clickSidebar = function () {
    if ($menucontainer.hasClass('sidebar-on')) {
      this.closeSidebar();
    } else {
      this.openSidebar();
    }
  };
  /**
   * @description Handle setting for sidebar and sets open state
   * @function openSidebar()
   * @private
   */
  this.openSidebar = function () {
    $menucontainer.addClass('sidebar-on').removeClass('sidebar-off');
    $menu.find('li.current ul').show();
    this.setStorageValue('sidebar-pref', 'sidebar-on', 60);
  };
  /**
   * @description Handle setting for Sidebar and sets closed state
   * @function closeSidebar()
   * @private
   */
  this.closeSidebar = function () {
    $menucontainer.removeClass('sidebar-on').addClass('sidebar-off');
    $menu.find('li ul').hide();
    this.setStorageValue('sidebar-pref', 'sidebar-off', 60);
  };
  /**
   * @description Set intial state of main menu child items
   * @function handleSubMenu($ob)
   * @param {object} $ob - Menu container object
   */
  this.handleSubMenu = function ($ob) {
    $ob.find('li.current span').addClass('open-sub');
  };
  /**
   * @description Handle 'dynamic' notifications
   * @function showNotifications()
   * @requires global cms_data{}, cms_notify(), cms_lang()
   */
  this.showNotifications = function () {
    //  back-compatibility check might be relevant in some contexts
    //  if (typeof cms_notify_all === 'function') {
    cms_notify_all();
    //  } else {
    //    do old-style notifications
    //  }
    $('.pagewarning, .message, .pageerrorcontainer, .pagemcontainer').prepend('<span class="close-warning" title="' + cms_lang('gotit') + '"></span>');
    $('.close-warning').on('click', function () {
      var $ob = $(this).parent();
      $ob.hide().remove();
    });
    // pagewarning status hidden? TODO is this stuff still relevant ?
    var key = $('body').attr('id') + '_notification';
    $('.pagewarning .close-warning').on('click', function () {
      this.setStorageValue(key, 'hidden', 60);
    });
    if (this.getStorageValue(key) === 'hidden') {
      $('.pagewarning').addClass('hidden');
    }
    $(document).on('cms_ajax_apply', function (e) {
      var type = (e.response === 'Success') ? 'success' : 'error';
      cms_notify(type, e.details);
    });
  };
  /**
   * @description Substitute styled buttons for named input-submits. And some links
   * @function migrateUIElements()
   */
  this.migrateUIElements = function () {
    // Standard input buttons
    $('input[type="submit"], :button[data-ui-icon]').each(function () {
      var button = $(this);
      if (!(button.hasClass('noautobtn') || button.hasClass('no-ui-btn')) && button.val().trim()) {
        var xclass, label, $btn;
        if (button.is('[name*=submit]')) {
          xclass = 'iconcheck';
        } else if (button.is('[name*=apply]')) {
          xclass = 'iconapply';
        } else if (button.is('[name*=cancel]') || button.is('[name*=close]')) {
          xclass = 'iconclose';
        } else if (button.is('[name*=reset]') || button.attr('id') === 'refresh') {
          xclass = 'iconundo';
        } else {
          xclass = '';
        }
        //ETC
        if (button.is('input')) {
          label = button.val();
        } else {
          label = button.text();
        }
        $btn = $('<button type="submit" class="adminsubmit ' + xclass + '">' + label + '</button>');
        $(this.attributes).each(function (idx, attrib) {
          switch (attrib.name) {
          case 'type':
            break;
          case 'class':
            var oc = attrib.value.replace(/(^|\s*)ui-\S+/g, '');
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
  };
  /**
   * @description Do things upon window resize
   * @function updateDisplay()
   */
  this.updateDisplay = function () {
    this.handleSidebar();
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
  };
  /**
   * @description
   * @function handleAlert(target)
   * @private
   * @requires global cms_data{}
   * @params {object} target
   * @deprecated since 2.3 use showNotifications()
   */
  this.handleAlert = function (target) {
    var _row = $(target).closest('.alert-box');
    var _alert_name = _row.data('alert-name');
    if (!_alert_name) return;
    return $.ajax({
      method: 'POST',
      url: cms_data.ajax_alerts_url,
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
        cms_dialog(_row.closest('div.ui-dialog-content'), 'close');
        $('#alert-noalerts').show();
        $('a#alerts').closest('li').remove();
      }
      _row.remove();
    });
  };
  /**
   * @description Handle popping up the notification area
   * @function setupAlerts()
   * @private
   * @deprecated since 2.3 use showNotifications() ?
   */
  this.setupAlerts = function () {
    $('a#alerts').on('click', function (e) {
      e.preventDefault();
      cms_dialog($('#alert-dialog'));
      return false;
    });
    var self = this;
    $('.alert-msg a').on('click', function (e) {
      e.preventDefault();
      self.handleAlert(e.target);
      return false;
    });
    $('.alert-remove').on('click', function (e) {
      e.preventDefault();
      self.handleAlert(e.target);
      return false;
    });
  };
  /**
   * @description Display a modal alert dialog
   * @function popup_alert()
   * @param (String) msg The message to display (text, no markup TODO)
   * @param (String) title Unused title string.
   * @return promise
   */
  this.popup_alert = function (msg, title) {
    return $.alertable.alert(msg, {
      okButton: '<button type="button" class="adminsubmit">' + cms_lang('close') + '</button>',
    });
  };
  /**
   * @description Display a modal confirm dialog
   * @function popup_confirm()
   * @param (String) msg The message to display
   * @param (String) title Unused title string
   * @param (String) yestxt Text for the yes button label
   * @param (String) notxt Text for the no button label
   * @return promise
   */
  this.popup_confirm = function (msg, title, yestxt, notxt) {
    return $.alertable.confirm(msg, {
      okButton: '<button type="button" class="adminsubmit icon check">' + yestxt + '</button>',
      cancelButton: '<button type="button" class="adminsubmit icon cancel">' + notxt + '</button>'
    });
  };
  /**
   * @description Display a modal prompt dialog
   * @function popup_prompt()
   * @param (String) msg The prompt to display
   * @param (String) suggest Optional initial value
   * @param (String) title Unused title string
   * @return promise
   */
  this.popup_prompt = function (msg, suggest, title) {
    return $.alertable.prompt(msg, {
      prompt: '<input type="text" class="alertable-input" value="' + suggest + '" />',
      okButton: '<button type="button" class="adminsubmit icon check">' + cms_lang('ok') + '</button>',
      cancelButton: '<button type="button" class="adminsubmit icon cancel">' + cms_lang('cancel') + '</button>'
    });
  };
  /**
   * @description Display a modal dialog with caller-defined content, and related settings in opts
   * @function popup_dialog()
   * @param (String) content The entire dialog markup, often wrapped in a div styled 'hidden'
   * @param (object) opts Optional parameters
   * @return the dialog's outer div
   */
  this.popup_dialog = function (content, opts) {
    opts = opts || {};
    opts.classes = $.extend(opts.classes || {}, {
      'ui-dialog': 'alertable',
      'ui-dialog-title': 'alertable-message',
      'ui-dialog-buttonpane': 'alertable-buttons',
      'ui-dialog-buttonset': 'alertable-buttons'
    });
    return content.dialog(opts); //JQueryUI dialog
    //JQueryUI >> $('div[class~="ui-draggable"]').removeClass('ui-draggable'); TOO LATE
/* TODO alertable only :: process opts, if any
   $.alertable.prompt('', {
     modal: content
   });/*.then(function(data) {
     console.log('Dialog promise data', data);
   }, function() {
     console.log('Dialog cancelled');
   });
   return TODO;
*/
  };
}).call(themejs, jQuery, window, document, 'themejs');

$(function () {
  themejs.init();
});
