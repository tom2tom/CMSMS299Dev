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
//    $container = null, // cache for outer container
//    $menucontainer = null, // redundant nav menu container
    $menu = null, // nav menu
    $scroller = null, // scrolled content element(s)
    oldwide,
//  oldhigh,
    threshold;

  this.init = function () {
//    $container = $('#ggp_container');
//    $menucontainer = $container.find('#ggp_navwrap'); // redundant ATM
    $menu = $('#ggp_menu');
//    var $ob = $container.find('#page_content');
    var $ob = $('#page_content');
    if ($ob.length > 0) {
      $scroller = $ob.find('div');
//      $ob = $container.find('.pagecontainer');
      $ob = $('.pagecontainer');
      if ($ob.length > 0) {
        $ob.css('overflow', 'hidden'); // prevent the ancestor div from scrolling
      }
    } else {
//      $scroller = $container.find('.pagecontainer');
      $scroller = $('.pagecontainer');
      if ($scroller.length > 0) {
        $scroller.css('overflow', 'auto'); // ensure the ancestor div scrolls
      } else {
        $scroller = null;
      }
    }
/*  // handle the initial collapsed/expanded state of the nav sidebar
    this.handleSidebar();
*/
    // initialize menu
    this.suckerSetup($menu, {
      minWidth: 6,  // em's
      maxWidth: 25, // em's
      extraWidth: 0.01,// em's might ensure lines don't sometimes turn over
                    // due to slight differences in how browsers round values
      speed: 150,   // sub-menu popup duration (mS) or
                    // 'normal' etc recognized by jQuery for animation
      delay: 400    // interval (mS) between menu exit and hide
    });
    // handle hamburger activation
    $('#burger').on('click activate', function(ev) {
      $menu.toggleClass('hidden').find('ul').hide().css({
//       'top': '-999rem',
       'visibility': 'hidden'
      });
    });

    var fpx = parseFloat(getComputedStyle(document.documentElement).fontSize);
    threshold = (fpx * 40) | 0; // 40-rem width-threshold for toggling menu-state
    oldwide = threshold + 10; // anything > threshold
    this.updateDisplay();

    var self = this;
    var wait = false;
    $(window).on('resize', function () {
      if (!wait) {
        self.updateDisplay();
        wait = true;
        setTimeout(function () {
          wait = false;
        }, 100);
      }
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
    // logout-during-sitedown confirmation
    if (cms_data.sitedown) {
      $menu.find('a[href^="logout.php"]')
       .add($('#shortcuts').find('a[href^="logout.php"]'))
       .on('click activate', function(ev) {
         ev.preventDefault();
         var prompt = cms_lang('maintenance_warning');
         cms_confirm_linkclick(this, prompt);
         return false;
       });
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
   * @description Initialize 3-levels of suckerfish-style menu items
   * @function suckerSetup($ob)
   * Uses and manipulates nested li's classes: sub subsub subsubL hover width
   * @param {object} $ob - Menu container object
   * @param {object} opts - menu parameters
   */
  this.suckerSetup = function ($ob, opts) {
    var topTop = '';
    var timerID = 0;
    //li hover handler
    var overLi = function (ev) {
      //abort timer
      if (timerID != 0) {
        clearTimeout(timerID);
        timerID = 0;
      }
      var $LI = $(ev.target).closest('li');
      //hide unwanted menus
      $LI.siblings().find('ul').hide().css({
//        'top': '-999rem',
        'visibility': 'hidden'
      });
      if ($LI.hasClass('sub')) {
        $LI.find('ul ul').hide().css({
//          'top': '-999rem',
          'visibility': 'hidden'
        });
        //TODO revert indicator state for open submenus
      }
      //show this menu, if any
      var menu = $LI.children('ul');
      if (menu.length > 0 && menu.css('visibility') == 'hidden') {
        //animate menu display
        if ($LI.hasClass('sub')) {
          menu.css({
              'top': topTop + 20, //DEBUG
              'visibility': 'visible'
            })
            /*.stop()*/
            .animate({
              opacity: 'show',
              height: 'show'
            }, opts.speed, 'swing', function() {
              toggleIndicator($LI);
            });
        } else {
          //TODO cache this data
          var LIright = $LI[0].getBoundingClientRect().right,
            $child = $LI.find('li:first'),
            childwidth = parseInt($child.css('width'), 10),
            menuwidth = parseInt(menu.css('width'), 10),
            $container = $LI.closest('div'),
            cright = $container[0].getBoundingClientRect().right;
          if (LIright + menuwidth + childwidth <= cright) {
            if ($LI.hasClass('subsubL')) {
              $LI.removeClass('subsubL').addClass('subsub');
            }
          } else if ($LI.hasClass('subsub')) {
            $LI.removeClass('subsub').addClass('subsubL');
          }
          if ($LI.hasClass('subsub')) { // expand LTR
            menu.css({
                'top': '-30px', //DEBUG
                'visibility': 'visible'
              })
              .animate({
                opacity: 'show',
                width: 'show'
              }, opts.speed, 'swing', function() {
                toggleIndicator($LI);
              });
          } else { // $LI.hasClass('subsubL')) expand RTL
            var distance = menuwidth + 'px',
              leftPos = '-' + (menuwidth + 3) + 'px';
            menu.css({
                'left': 0,
                'width': 0,
                'top': '-1px',
                'visibility': 'visible'
              })
              .animate({
                opacity: 'show',
                left: leftPos,
                width: distance
              }, opts.speed, 'swing', function() {
                toggleIndicator($LI);
              });
          }
        }
      }
    };
    //li exit handler
    var outLi = function (ev) {
      if (timerID != 0) {
        clearTimeout(timerID);
      }
      $li = $(ev.target).closest('li');
      //start timer which when finished hides all sub-menus
      timerID = setTimeout(function () {
        toggleIndicator($li);
        $menu.find('ul').hide().css({
//          'top': '-999rem',
          'visibility': 'hidden'
        });
        timerID = 0;
      }, opts.delay);
    };
    //li click/activate handler
    var activateLi = function (ev) {
      if (timerID != 0) {
        clearTimeout(timerID);
        timerID = 0;
      }
      var $tgt = $(ev.target);
      if ($tgt.is('a')) {
        return true;
      }
      var $li = $tgt.closest('li');
      if ($li.hasClass('descend')) {
        // toggle visibility of child menu
        var $ul = $li.children('ul');
        if ($ul.css('visibility') == 'hidden') {
          overLi(ev);
        } else {
          toggleIndicator($li);
          $ul.add($ul.find('ul')).hide().css({
//            'top': '-999rem',
            'visibility': 'hidden'
          });
        }
        return false;
      }
    };
    var toggleIndicator = function ($LI) {
      var current = $LI[0].classList,
          classes = ['vshut', 'vopen', 'hshut', 'hopen'],
          i;
      for (i = 0; i < 4; i++) {
        if (current.contains(classes[i])) {
          var j = (i % 2 === 0) ? i+1 : i-1;
          current.remove(classes[i]);
          current.add(classes[j]);
          return;
        }
      }
    };
    //decimal-value rounder
    var roundNum = function (value, places) {
      return Number(Math.round(value + 'e' + places) + 'e-' + places);
    };

    $ob.css({
      'visibility': 'hidden',
      'flex-direction': 'column'
    });

    // get menu font-size
    var val = parseFloat($ob.css('font-size'));
    var fontsize = roundNum(val, 1);
    // TODO migrate non-em sizes in opts to em

    var $LIs = $ob.find('> li'); // top level
    $LIs.css({
      'visibility': 'hidden',
      'display': 'block',
      'white-space': 'nowrap',
      'width': ''
    });
    var menuWidth = $ob[0].clientWidth;
    $ob.css('flex-direction', '').data('menuwidth', menuWidth + 'px');

    //TODO hshut class on small screen
    $LIs.css('visibility', 'visible').addClass('sub vshut')/*.on('mouseover', overLi).on('mouseout', outLi)*/.on('click activate', activateLi);
    var $last = $LIs.eq(-1);
    $LIs = $LIs.find('> ul > li'); // level 2
    $LIs.addClass('sub subsub')/*.on('mouseover', overLi).on('mouseout', outLi)*/.on('click activate', activateLi);
    $last.find('> ul > li').removeClass('subsub').addClass('subsubL');
    $LIs = $LIs.find('li'); // level 3+
    $LIs.addClass('sub')/*.on('mouseover', overLi).on('mouseout', outLi)*/.on('click activate', activateLi);

    var $ULs = $ob.find('ul');
    // make everything measurable without showing
    $ULs.css({
      'visibility': 'hidden'
    });
    // loop through nested ul's
    $ULs.each(function () {
      var $ul = $(this);
      // top of level-1 menus, when displayed
      if (topTop === '') {
        val = (parseFloat($ul.css('top')) + 2) / fontsize; // TODO generalize fudge-factor :: func (ancestor-li height etc)
        topTop = roundNum(val, 2) + 'em';
      }
      // get all (li) children of this ul
      $LIs = $ul.children('li');
      // get all non-ul grand-children
      var $As = $LIs.children(':not(ul)');
      // force li content to one line
      $LIs.css({'visibility': 'hidden','white-space': 'nowrap'});
      // remove width restrictions
      var menuWidth = $ul.add($LIs).add($As).css({
          'width': '',
        })
        // this $ul will now be shrink-wrapped to longest li due to position:absolute
        // clientWidth is 2 times faster than .width() - thanks Dan Switzer
        // NOTE clientWidth issue with IE < 8 : absolute pixel-size regardless of scale-factor
        .end().end()[0].clientWidth;

      val = 1.5 + menuWidth / fontsize; // + 5/fontsize; //0.3125; //extra for padding-right, sometimes?
      if (fontsize < 10) {
        val += 0.1;
      }
      // add more width to ensure lines don't turn over at certain sizes in various browsers
      val += opts.extraWidth; //TODO handle non-em option-value
      // restrict to at least minWidth and at most maxWidth
      if (val > opts.maxWidth) {  //TODO handle non-em option-value
        val = opts.maxWidth;
      } else if (val < opts.minWidth) { //TODO handle non-em option-value
        val = opts.minWidth;
      }

      var ems = roundNum(val, 2) + 'em';
      // set li width to full width of this ul
      // revert white-space to normal
      $LIs.css({
        'width': ems,
        'white-space': '', //'normal' / default ? inherit ?
        'visibility': 'visible'
      });
      $ul.css('width', ems);
      
      // update horizontal position
      if ($ul.parent().is('li.subsub')) {
        // re-position rightwards
        val = parseFloat($ul.parent().parent().css('width')) / fontsize;
        val += 0.6; //TODO generalize this fudge: padding? ::after ?
        ems = roundNum(val, 2) + 'em';
      } else if ($ul.parent().is('li.subsubL')) {
        // re-position leftwards with gap
//        val += 0.1875;
        val -= 0.6; //TODO generalize this fudge: padding? ::after ?
        ems = '-' + roundNum(val, 2) + 'em';
      } else {
        ems = '0';
      }
      $ul/*.css('left', ems)*/.data('wideleft', ems);

    });
    // hide to support animation, off-screen until parent is hovered
    $ULs.hide(); //.css({
//      'top': '-999rem'
//    });
    $ob.css('visibility', '');
    $ob.removeClass('noflash');
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
    $(document).on('cms_ajax_apply', function (ev) {
      var type = (ev.response === 'Success') ? 'success' : 'error';
      cms_notify(type, ev.details);
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
    var wide = $(window).width(),
      hided = $menu.hasClass('hidden');
    if (wide < threshold) {
      if (!hided) {
        if (oldwide >= threshold) {
          $menu.addClass('hidden').find('#burger').show();
          // update submenu positions
          var distance = $menu.data('menuwidth');
          $menu.find('ul').each(function () {
            var $ul = $(this),
              $p = $ul.parent();
            if ($p.is('li.subsub') || $p.is('li.subsubL')) { //TODO sometimes toggle these
              $ul.data('wideleft', $ul.css('left')); // or right for rtl?
              $ul.css({'top': '1.5em','left': '0'}); //DEBUG TODO
            } else {
              $ul.css('left', distance);
            }
          });
        }
      }
    } else if (hided) {
      // update submenu positions
      $menu.find('ul').each(function () {
        var $ul = $(this),
          $p = $ul.parent();
        if ($p.is('li.subsub') || $p.is('li.subsubL')) { //TODO sometimes toggle these
          //$ul.css('left', $ul.data('wideleft')); //TODO also top
          $ul.css('top', '3em'); //TODO also top
        }
      });
      $menu.find('#burger').hide();
      $menu.removeClass('hidden');
    }
    if (wide > oldwide) {
      //do stuff
    } else if (wide < oldwide) {
      //do stuff
    }
    oldwide = wide;
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
    $('a#alerts').on('click', function (ev) {
      ev.preventDefault();
      cms_dialog($('#alert-dialog'));
      return false;
    });
    var self = this;
    $('.alert-msg a').on('click', function (ev) {
      ev.preventDefault();
      self.handleAlert(ev.target);
      return false;
    });
    $('.alert-remove').on('click', function (ev) {
      ev.preventDefault();
      self.handleAlert(ev.target);
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
    return $.fn.alertable.alert(msg, {
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
    return $.fn.alertable.confirm(msg, {
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
    return $.fn.alertable.prompt(msg, {
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
   $.fn.alertable.prompt('', {
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
