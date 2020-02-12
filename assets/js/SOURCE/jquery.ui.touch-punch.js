/*
 * jQuery UI Touch Punch 0.3
 *
 * Copyright 2011–2020, Dave Furfero http://furf.com
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Depends:
 *  jquery.ui.widget.js
 *  jquery.ui.mouse.js
 */
/*!
jQuery UI Touch Punch v0.3
(C) 2011–2020 Dave Furfero http://furf.com
License: MIT or GPL2
*/
(function($, navigator, window, document, undefined) {

  if (typeof $.fn.touchpunch === 'undefined') {
    $.fn.touchpunch = {};
  }

  if (typeof $.fn.touchpunch.touchy === 'undefined') {
    // Detect touch support, if we can
    // references:
    // https://peterscene.com/blog/detecting-touch-devices-2018-update
    // https://medium.com/@ferie/detect-a-touch-device-with-only-css-9f8e30fa1134
    // https://codepen.io/Ferie/pen/vQOMmO
    var is_touch_device = function() {
      if (
        'ontouchstart' in window ||
        'ontouchstart' in document.documentElement ||
        (window.DocumentTouch && document instanceof DocumentTouch) ||
        navigator.msMaxTouchPoints > 0) {
        return true;
      }
      if (window.matchMedia) {
        var prefixes = ' -webkit- -moz- -o- -ms- '.split(' ');
        var mq = function (query) {
          return window.matchMedia(query).matches;
        };
        // include the 'Z' to help terminate the join
        // https://git.io/vznFH
        var query = ['(', prefixes.join('touch-enabled),('), 'Z', ')'].join('').replace(',(Z)', '');
        return mq(query);
        // TODO consider also checking media hover and pointer props, per ref above
      }
      return false;
    };

    $.fn.touchpunch.touchy = is_touch_device();
  }

  // Ignore browsers without touch support
  if (!$.fn.touchpunch.touchy) {
    return;
  }

  var mouseProto = $.ui.mouse.prototype,
    mouseInit = mouseProto._mouseInit,
    mouseDestroy = mouseProto._mouseDestroy,
    touchHandled;

  /**
   * Simulate a mouse event based on a corresponding touch event
   * @param {Object} event A touch event
   * @param {String} simulatedType The corresponding mouse event
   */
  function simulateMouseEvent(event, simulatedType) {

    // Ignore multi-touch events
    if (event.originalEvent.touches.length > 1) {
      return;
    }

    event.preventDefault();

    var touch = event.originalEvent.changedTouches[0],
      simulatedEvent = document.createEvent('MouseEvents');

    // Initialize the simulated mouse event using the touch event's coordinates
    simulatedEvent.initMouseEvent(
      simulatedType, // type
      true, // bubbles
      true, // cancelable
      window, // view
      1, // detail
      touch.screenX, // screenX
      touch.screenY, // screenY
      touch.clientX, // clientX
      touch.clientY, // clientY
      false, // ctrlKey
      false, // altKey
      false, // shiftKey
      false, // metaKey
      0, // button
      null // relatedTarget
    );

    // Dispatch the simulated event to the target element
    event.target.dispatchEvent(simulatedEvent);
  }

  /**
   * Handle the jQuery UI widget's touchstart events
   * @param {Object} event The widget element's touchstart event
   */
  mouseProto._touchStart = function(event) {

    var self = this;

    // Ignore the event if another widget is already being handled
    if (touchHandled || !self._mouseCapture(event.originalEvent.changedTouches[0])) {
      return;
    }

    // Set the flag to prevent other widgets from inheriting the touch event
    touchHandled = true;

    // Track movement to determine if interaction was a click
    self._touchMoved = false;

    // Simulate the mouseover event
    simulateMouseEvent(event, 'mouseover');

    // Simulate the mousemove event
    simulateMouseEvent(event, 'mousemove');

    // Simulate the mousedown event
    simulateMouseEvent(event, 'mousedown');
  };

  /**
   * Handle the jQuery UI widget's touchmove events
   * @param {Object} event The document's touchmove event
   */
  mouseProto._touchMove = function(event) {

    // Ignore event if not handled
    if (!touchHandled) {
      return;
    }

    // Interaction was not a click
    this._touchMoved = true;

    // Simulate the mousemove event
    simulateMouseEvent(event, 'mousemove');
  };

  /**
   * Handle the jQuery UI widget's touchend events
   * @param {Object} event The document's touchend event
   */
  mouseProto._touchEnd = function(event) {

    // Ignore event if not handled
    if (!touchHandled) {
      return;
    }

    // Simulate the mouseup event
    simulateMouseEvent(event, 'mouseup');

    // Simulate the mouseout event
    simulateMouseEvent(event, 'mouseout');

    // If the touch interaction did not move, it should trigger a click
    if (!this._touchMoved) {

      // Simulate the click event
      simulateMouseEvent(event, 'click');
    }

    // Unset the flag to allow other widgets to inherit the touch event
    touchHandled = false;
  };

  /**
   * A duck punch of the $.ui.mouse _mouseInit method to support touch events.
   * This method extends the widget with bound touch event handlers that
   * translate touch events to mouse events and pass them to the widget's
   * original mouse event handling methods.
   */
  mouseProto._mouseInit = function() {

    var self = this;

    // Delegate the touch handlers to the widget's element
    self.element.bind({
      touchstart: $.proxy(self, '_touchStart'),
      touchmove: $.proxy(self, '_touchMove'),
      touchend: $.proxy(self, '_touchEnd')
    });

    // Call the original $.ui.mouse init method
    _mouseInit.call(self);
  };

  /**
   * Remove the touch event handlers
   */
  mouseProto._mouseDestroy = function() {

    var self = this;

    // Delegate the touch handlers to the widget's element
    self.element.unbind({
      touchstart: $.proxy(self, '_touchStart'),
      touchmove: $.proxy(self, '_touchMove'),
      touchend: $.proxy(self, '_touchEnd')
    });

    // Call the original $.ui.mouse destroy method
    _mouseDestroy.call(self);
  };

})(jQuery, navigator, window, document);
