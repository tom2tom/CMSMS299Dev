/*!
jquery alertable plugin V.1.2 <github.com/claviska/jquery-alertable>
(C) Cory LaViska <https://twitter.com/claviska>
License: MIT
*/
/* jquery-alertable 1.2
 Minimal alert, confirmation, and prompt alternatives
 Derived from V.1.0.2 by Cory LaViska https://twitter.com/claviska
 Licensed under the MIT license
*/
//requires jQuery 1.7+
(function ($, document, undefined) {
  'use strict';
  // Shared parameters
  var modal,
    overlay,
    okButton,
    cancelButton,
    promptElement,
    activeElement;

  function show(type, message, useroptions) {

    // Remove focus from the background
    activeElement = document.activeElement;
    activeElement.blur();

    // Remove other instances
    if (modal) $(modal).add(overlay).remove();

    // Merge options
    var options = $.extend({}, $.fn.alertable.defaults, useroptions);
    // Create elements
    modal = $(options.modal).hide();
    overlay = $(options.overlay).hide();

    // Add message
    if (options.html) {
      modal.find('#alertable-message').html(message);
    } else {
      modal.find('#alertable-message').text(message);
    }

    // Add prompt
    if (type === 'prompt') {
      modal.find('#alertable-prompt').html(options.prompt);
      promptElement = $('#alertable-prompt input', modal);
    } else {
      modal.find('#alertable-prompt').remove();
      promptElement = null;
    }

    // Add button(s)
    var ob = modal.find('#alertable-buttons');
    if (ob.length === 1) {
      var btext;
      if (options.okName) {
        btext = '<button id="alertable-ok" type="button">' + options.okName + '</button>'; //not a 'submit'
      } else {
        btext = options.okButton || '';
      }
      okButton = btext ? $(btext) : '';

      if (type === 'alert') {
        cancelButton = '';
      } else {
        if (options.cancelName) {
          btext = '<button id="alertable-cancel" type="button">' + options.cancelName + '</button>'; //not a 'submit'
        } else {
          btext = options.cancelButton || '';
        }
        cancelButton = btext ? $(btext) : '';
      }
      if (options.ltr) {
        ob.append(okButton).append(cancelButton);
      } else {
        ob.append(cancelButton).append(okButton);
      }
    } else {
      okButton = null;
      cancelButton = null;
    }

    // Add to container
    $(options.container).append(overlay).append(modal);

    // Show it
    options.show.call({
      modal: modal,
      overlay: overlay
    });

    // Set focus
    if (type === 'prompt') {
      // First input in the prompt
      promptElement.eq(0).focus();
    } else {
      // OK button
      modal.find('button').eq(0).focus();
    }

    var defer = $.Deferred();

    // Watch for submit
    modal.on('submit.alertable', function (event) {
      var i,
        formData,
        values = [];
      event.preventDefault();

      if (type === 'prompt') {
        formData = modal.serializeArray();
        for (i = 0; i < formData.length; i++) {
          values[formData[i].name] = formData[i].value;
        }
      } else {
        values = null;
      }

      hide(options);
      defer.resolve(values);
    });

    // Watch for OK
    if (okButton) {
      okButton.on('click.alertable', function () {
        if (type === 'prompt') {
          var val = promptElement.val();
          hide(options);
          defer.resolve(val);
        } else {
          hide(options);
          defer.resolve();
        }
      });
    }

    // Accept on enter-key when a prompt-input is focused
    if (promptElement) {
      promptElement.on('keydown.alertable', function (event) {
        if (event.keyCode === 13) {
          event.preventDefault();
          return false;
        }
      }).on('keyup.alertable', function (event) {
        if (event.keyCode === 13) {
          if (okButton) {
            okButton.trigger('click.alertable');
          }
        }
      });
    }

    // Watch for cancel
    if (cancelButton) {
      cancelButton.on('click.alertable', function () {
        hide(options);
        defer.reject();
      });
    }

    // Cancel on escape
    $(document).on('keyup.alertable', function (event) {
      if (event.keyCode === 27) {
        event.preventDefault();
        hide(options);
        defer.reject();
      }
    });

    // Prevent focus from leaving the modal
    $(document).on('focus.alertable', '*', function (event) {
      if (!$(event.target).parents().is('.alertable')) {
        event.stopPropagation();
        event.target.blur();
        $(modal).find(':input:first').focus();
      }
    });

    return defer.promise();
  }

  function hide(options) {
    // Hide it
    options.hide.call({
      modal: modal,
      overlay: overlay
    });

    // Remove bindings
    $(document).off('.alertable');
    modal.off('.alertable');
    if (cancelButton) {
      cancelButton.off('.alertable');
    }

    // Restore focus
    activeElement.focus();
  }

  $.fn.alertable = {
    // Show an alert
    alert: function (message, options) {
      return show('alert', message, options);
    },

    // Show a confirmation
    confirm: function (message, options) {
      return show('confirm', message, options);
    },

    // Show a prompt
    prompt: function (message, options) {
      return show('prompt', message, options);
    }
  };

  // Default parameters
  $.fn.alertable.defaults = $.extend({}, {
    // Preferences
    container: 'body',
    html: false,
    ltr: true,
    // Labels
    cancelName: 'Cancel',
    okName: 'OK',
    // Templates
    cancelButton: '<button id="alertable-cancel" type="button">Cancel</button>', //not a 'submit'
    okButton: '<button id="alertable-ok" type="button">OK</button>', //not a 'submit'
    overlay: '<div id="alertable-overlay"></div>',
    prompt: '<input id="alertable-input" type="text" name="value">',
    modal: '<form class="alertable">' +
      '<div id="alertable-message"></div>' +
      '<div id="alertable-prompt"></div>' +
      '<div id="alertable-buttons"></div>' +
      '</form>',
    // Hooks
    hide: function () {
      $(this.modal).add(this.overlay).fadeOut(100);
    },
    show: function () {
      $(this.modal).add(this.overlay).fadeIn(100);
    }
  }, $.fn.alertable.defaults || {});

})(jQuery, document);
