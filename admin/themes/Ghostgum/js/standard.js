/**
 * @package CMS Made Simple
 * @description CMSMS theme functions - tailored for Ghostgum theme
 * NOTE includes a hardcoded url for an external cookie processor, and viewport width-threshold
 */
/*!
javascript for CMSMS Ghostgum-theme v.0.8
(C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
License: Affero GPL 3+
*/
/*jslint nomen: true , devel: true*/

var ggjs = {
  cookie_handler: 'themes/assets/js/js-cookie.min.js',
  small_width: 600, // viewport-width threshold related to sidebar display
  $container: null, // outer container
  $menucontainer: null, // nav menu container
  $menu: null, // nav menu

  init: function() {
    this.$container = $('#ggp_container');
    this.$menucontainer = this.$container.find('#ggp_navwrap');
    this.$menu = this.$menucontainer.find('#ggp_nav');
    // handle the initial collapsed/expanded state of the nav sidebar
    this.handleSidebar();
    // handle navigation sidebar toggling
    $(window).resize(function() {
      ggjs.updateDisplay();
    });
    // handle initial display of sub-menu
    this.handleSubMenu(this.$menu);
    // handle sub-menu display toggling
    this.$menu.find('.open-nav').on('click', function(e) {
      //clicked span in a menu item title
      e.preventDefault();
      var $ob = $(this),
        $ul = $ob.next(), //sub-menu container for this item
        _p = [];
      if(!$ul.is(':visible')) {
        //close any other open submenu
        var $open = ggjs.$menu.find('.open-sub');
        if($open.length) {
          $open.removeClass('open-sub');
          var $ulo = $open.next();
          _p.push($ulo.slideUp(50), function() {
           $ulo.find('li,ul').hide();
          });
        }
        $ob.addClass('open-sub');
      } else {
        $ob.removeClass('open-sub');
      }
      $ul.find('li,ul').show();
      _p.push($ul.slideToggle(50));
      $.when.apply($, _p).done(function() {
        ggjs.updateDisplay();
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
    // setup custom dialogs
    cms_data.alertfunc = this.popup_alert;
    cms_data.confirmfunc = this.popup_confirm;
    cms_data.promptfunc = this.popup_prompt;
    cms_data.dialogfunc = this.popup_dialog;
    // open external links with rel="external" attribute in new window
    $('a[rel="external"]').attr('target', '_blank');
    // focus on input with .defaultfocus class
    $('input.defaultfocus:eq(0), input[autofocus]').focus();
    // async-load a cookie handler if localStorage is not supported
    if(!this.isLocalStorage()) {
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
      return $.ajax(url, {
        dataType: 'script',
        async: false,
        cache: cache
      }).done(callback);
    } else {
      //already loaded so just call the callback
      if($.isFunction(callback)) {
        callback.call(this);
      }
    }
  },
  /**
   * @description Save a defined key and value to localStorage if localStorgae is supported, else fall back to cookie script
   * @requires js-cookie https://github.com/js-cookie/js-cookie
   * @function setStorageValue(key, value)
   * @param {string} key
   * @param {string} value
   * @param {number} expires (number in days)
   */
  setStorageValue: function(key, value, expires) {
    try {
      if(this.isLocalStorage()) {
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
      } else if(this.isCookieScript()) {
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
   * @description Get value for defined key from localStorage if that's supported, else falls back to js-cookie script
   * @requires js-cookie https://github.com/js-cookie/js-cookie
   * @function getStorageValue(key)
   * @param {string} key
   */
  getStorageValue: function(key) {
    var value;
    if(this.isLocalStorage()) {
      var data = JSON.parse(localStorage.getItem(key));
      if(data !== null && data.timestamp < new Date().getTime()) {
        this.removeStorageValue(key);
      } else if(data !== null) {
        value = data.value;
      }
    } else if(this.isCookieScript()) {
      value = Cookies(key);
    } else {
      value = ''; //TODO handle no cookie
    }
    return value;
  },
  /**
   * @description Remove defined key from localStorage if that's supported, else falls back to js-cookie script
   * @requires js-cookie https://github.com/js-cookie/js-cookie
   * @function removeStorageValue(key)
   * @param {string} key
   */
  removeStorageValue: function(key) {
    if(this.isLocalStorage()) {
      localStorage.removeItem(key);
    } else if(this.isCookieScript()) {
      Cookies.remove(key);
    }
  },
  /**
   * @description Detect whether localStorage is supported by browser
   * @function isLocalStorage()
   * @private
   */
  isLocalStorage: function() {
    return typeof Storage !== 'undefined';
  },
  /**
   * @description Detect whether js-cookie.js is present
   * @function isCookieScript()
   * @private
   */
  isCookieScript: function() {
    return typeof Cookies !== 'undefined';
  },
  /**
   * @description Basic check for common mobile devices and touch capability
   * @function isMobileDevice()
   * @private
   */
  isMobileDevice: function() {
    var ua = navigator.userAgent.toLowerCase(),
      devices = /(Android|iPhone|iPad|iPod|Blackberry|Dolphin|IEMobile|WPhone|Windows Mobile|IEMobile9||IEMobile10||IEMobile11|Kindle|Mobile|MMP|MIDP|Pocket|PSP|Symbian|Smartphone|Sreo|Up.Browser|Up.Link|Vodafone|WAP|Opera Mini|Opera Tablet|Mobile|Fennec)/i;
    if(ua.match(devices) && (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0) || window.DocumentTouch && document instanceof DocumentTouch)) {
      return true;
    }
  },
  /**
   * @description Check for saved state of sidebar
   * @function handleSidebar()
   */
  handleSidebar: function() {
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    if(this.getStorageValue('sidebar-pref') === 'sidebar-off' || viewportWidth <= this.small_width) {
      this.$menucontainer.addClass('sidebar-off').removeClass('sidebar-on');
      this.$menu.addClass('sidebar-off').removeClass('sidebar-on');
    } else {
      this.$menucontainer.addClass('sidebar-on').removeClass('sidebar-off');
      this.$menu.addClass('sidebar-on').removeClass('sidebar-off');
    }
  },
  /*
   * @description Toggles Sidebar open/closed state
   * @function clickSidebar()
   */
  clickSidebar: function() {
    if(this.$menucontainer.hasClass('sidebar-on')) {
      this.closeSidebar();
    } else {
      this.openSidebar();
    }
  },
  /**
   * @description Handle setting for sidebar and sets open state
   * @private
   * @function openSidebar()
   */
  openSidebar: function() {
    this.$menucontainer.addClass('sidebar-on').removeClass('sidebar-off');
    this.$menu.find('li.current ul').show();
    this.setStorageValue('sidebar-pref', 'sidebar-on', 60);
  },
  /**
   * @description Handle setting for Sidebar and sets closed state
   * @private
   * @function closeSidebar()
   */
  closeSidebar: function() {
    this.$menucontainer.removeClass('sidebar-on').addClass('sidebar-off');
    this.$menu.find('li ul').hide();
    this.setStorageValue('sidebar-pref', 'sidebar-off', 60);
  },
  /**
   * @description Set intial state of main menu child items
   * @function handleSubMenu($ob)
   * @param {object} $ob - Menu container object
   */
  handleSubMenu: function($ob) {
    $ob.find('li.current span').addClass('open-sub');
  },
  /**
   * @description Handle 'dynamic' notifications
   * @function showNotifications()
   * @requires global cms_data{}, cms_notify(), cms_lang()
   */
  showNotifications: function() {
//  back-compatibility check might be relevant in some contexts
//  if (typeof cms_notify_all === 'function') {
    cms_notify_all();
//  } else {
//    do old-style notifications
//  }
    $('.pagewarning, .message, .pageerrorcontainer, .pagemcontainer').prepend('<span class="close-warning" title="' + cms_lang('gotit') + '"></span>');
    $('.close-warning').on('click', function() {
      var $ob = $(this).parent();
      $ob.hide().remove();
    });
    // pagewarning status hidden? TODO is this stuff still relevant ?
    var key = $('body').attr('id') + '_notification';
    $('.pagewarning .close-warning').on('click', function() {
      this.setStorageValue(key, 'hidden', 60);
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
   * @description Substitute styled buttons for named input-submits. And some links
   * @function migrateUIElements()
   */
  migrateUIElements: function() {
    // Standard input buttons
    $('input[type="submit"], :button[data-ui-icon]').each(function() {
      var button = $(this);
      if(!(button.hasClass('noautobtn') || button.hasClass('no-ui-btn')) && button.val().trim()) {
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
          switch(attrib.name) {
            case 'type':
              break;
            case 'class':
              var oc = attrib.value.replace(/(^|\s*)ui-\S+/g, '');
              if(oc !== '') {
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
   * @description Do things upon window resize
   * @function updateDisplay()
   */
  updateDisplay: function() {
    this.handleSidebar();
/*
    var this.$menu = $('#pg_menu');
    var $alert_box = $('#admin-alerts');
    var $header = $('header.header');
    var offset = $header.outerHeight() + $header.offset().top;
    if($alert_box.length) offset = $alert_box.outerHeight() + $alert_box.offset().top;
    console.debug('menu height = ' + this.$menu.outerHeight() + ' offset = ' + offset);
    console.debug('window height = ' + $(window).height());
    if($menu.outerHeight() + offset < $(window).height()) {
        console.debug('fixed');
        this.$menu.css({ 'position': 'fixed', 'top': offset });
    } else {
        this.$menu.css({ 'position': '', 'top': '' });
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
   * @function handleAlert(target)
   * @requires global cms_data{}
   * @params {object} target
   * @deprecated since 2.3 use showNotifications()
   */
  handleAlert: function(target) {
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
        cms_dialog(_row.closest('div.ui-dialog-content'), 'close');
        $('#alert-noalerts').show();
        $('a#alerts').closest('li').remove();
      }
      _row.remove();
    });
  },
  /**
   * @description Handle popping up the notification area
   * @private
   * @function setupAlerts()
   * @deprecated since 2.3 use showNotifications() ?
   */
  setupAlerts: function() {
    $('a#alerts').on('click', function(e) {
      e.preventDefault();
      cms_dialog($('#alert-dialog'));
      return false;
    });
    $('.alert-msg a').on('click', function(e) {
      e.preventDefault();
      ggjs.handleAlert(e.target);
      return false;
    });
    $('.alert-remove').on('click', function(e) {
      e.preventDefault();
      ggjs.handleAlert(e.target);
      return false;
    });
  },
  /**
   * @description Display a modal alert dialog
   * @function
   * @param (String) msg The message to display (text, no markup TODO)
   * @param (String) title Unused title string.
   * @return promise
   */
  popup_alert: function(msg, title) {
    return $.alertable.alert(msg, {
      okButton: '<button type="button" class="adminsubmit">' + cms_lang('close') + '</button>',
    });
  },
  /**
   * @description Display a modal confirm dialog
   * @function
   * @param (String) msg The message to display
   * @param (String) title Unused title string
   * @param (String) yestxt Text for the yes button label
   * @param (String) notxt Text for the no button label
   * @return promise
   */
  popup_confirm: function(msg, title, yestxt, notxt) {
    return $.alertable.confirm(msg, {
      okButton: '<button type="button" class="adminsubmit icon check">' + yestxt + '</button>',
      cancelButton: '<button type="button" class="adminsubmit icon cancel">' + notxt + '</button>'
    });
  },
  /**
   * @description Display a modal prompt dialog
   * @function
   * @param (String) msg The prompt to display
   * @param (String) suggest Optional initial value
   * @param (String) title Unused title string
   * @return promise
   */
  popup_prompt: function(msg, suggest, title) {
    return $.alertable.prompt(msg, {
      prompt: '<input type="text" class="alertable-input" value="'+ suggest +'" />',
      okButton: '<button type="button" class="adminsubmit icon check">' + cms_lang('ok') + '</button>',
      cancelButton: '<button type="button" class="adminsubmit icon cancel">' + cms_lang('cancel') + '</button>'
    });
  },
  /**
   * @description Display a modal dialog with caller-defined content, and related settings in opts
   * @function
   * @param (String) content The entire dialog markup, often wrapped in a div styled 'hidden'
   * @param (object) opts Optional parameters
   * @return the dialog's outer div
   */
  popup_dialog: function(content, opts) {
   opts = opts || {};
   opts.classes = $.extend(opts.classes || {}, {
    'ui-dialog': 'alertable',
    'ui-dialog-title': 'alertable-message',
    'ui-dialog-buttonpane': 'alertable-buttons',
    'ui-dialog-buttonset': 'alertable-buttons'
   });
   return content.dialog(opts); //JQueryUI dialog
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
  }
};

$(function() {
  ggjs.init();
});
