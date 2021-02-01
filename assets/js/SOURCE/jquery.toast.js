/*!
jQuery toast plugin v1.4.0
(C) 2015-2018 Kamran Ahmed <http://kamranahmed.info>
License: MIT
*/
/*
jQuery toast plugin v.1.4.0
Copyright (C) 2015-2018 Kamran Ahmed <http://kamranahmed.info>
License: MIT
*/
/*
options and their default values:
 text: '', string or string(s) array, text or markup
 heading: '', string, text or markup
 showHideTransition: 'fade', type of display transition: 'fade', 'slide' or anything else
 allowToastClose: true, whether to enable click-to-close
 hideAfter: 5000,  dialog lifetime (mS) or false for sticky
 loader: true, whether to display lifetime progress bar
 loaderBg: false, CSS property, or false progress bar colour
 stack: 5, maximum no. of contemporary toasts, or 0
 position: 'bottom-left', defines toast position relative to the window, one of
         'bottom-left','bottom-right','top-right','top-left','bottom-center','top-center','mid-center'
         or an object with at least one of the properties top, bottom, left, right
 tempposition: false, or an object as described above, used once then discarded
 bgColor: false, CSS property, or false
 textColor: false, CSS property, or false
 textAlign: 'left', CSS property, or false
 icon: false, status-indicator, one of 'success', 'error', 'info', 'warning'
         or any markup which represents an icon e.g. <img attrs ... />,
         or <span attrs ...><img attrs ... /></span>
 closeicon: '&times;', text or markup which displays something clickable
         e.g. 'CLOSE', '&times;', <span attrs ...><img attrs ...  /></span>, or empty
 myclass: false, toast-specific class to be applied
 beforeShow: false, callable, called before dialog is shown, argument is the dialog's container div element
 afterShown: false, callable, called after dialog is shown, argument is the dialog's container div element
 beforeHide: false, callable, called before dialog is hidden, argument is the dialog's container div element
 afterHidden: false, callable, called after dialog is hidden, argument is the dialog's container div element
 onClick: false callable, called after any click anywhere in the dialog (i.e. not just close)

classes:
'jqtoast' applied to each created div containing toast Elements
settings.position applied to each toast-div
settings.myclass applied to the toast-div to which these settings apply
'jqt-has-icon' applied to each toast-div which has a type-indicator icon
'jqt-icon-' + 'success', 'error', 'info', 'warning' applied to the type-indicator icon
'jqt-loader' loader-container span identifier
'jqt-loaded' applied to loader-container if loader is true
'jqt-heading' applied to a div containing markup and closer (if any)
'jqt-close' applied to span including settings.closeicon (whatever and wherever that is)

ids:
'jqt-wrap' applied to div containing all created toasts
'jqt-item-' + i the li's
*/
if ( typeof Object.create !== 'function' ) {
    Object.create = function( obj ) {
        function F() {}
        F.prototype = obj;
        return new F();
    };
}

;(function( $, window, document, undefined ) {
    "use strict";

    var Toast = {
        _positionClasses : ['bottom-left', 'bottom-right', 'top-right', 'top-left', 'bottom-center', 'top-center', 'mid-center'],
        _defaultIcons : ['success', 'error', 'info', 'warning'],

        init: function (options, elem) {
            this.prepareOptions(options, $.toast.options);
            this.process();
        },

        prepareOptions: function(options, options_to_extend) {
            var _options = {};
            if ( ( typeof options === 'string' ) || ( options instanceof Array ) ) {
                _options.text = options;
            } else {
                _options = options;
            }
            this.options = $.extend( {}, options_to_extend, _options );
        },

        process: function () {
            this.setup();
            this.addToDom();
            this.bindToast();
            this.position();
            this.animate();
        },

        setup: function () {

            this._toastEl = this._toastEl || $('<div/>', {
                'class' : 'jqtoast',
                 role : 'alert',
                'aria-live' : 'polite'
            });
            this._toastEl.data('Toastob', this);

            var _toastContent = '';

            // For the loader on top
            if ( this.options.loader && this.options.hideAfter ) {
                _toastContent += '<span class="jqt-loader"></span>';
            }

            if ( this.options.heading ) {
                _toastContent +='<div class="jqt-heading">' + this.options.heading;
            }

            if ( this.options.allowToastClose ) {
                _toastContent += '<span class="jqt-close">' + this.options.closeicon + '</span>';
            }

            if ( this.options.heading ) {
                _toastContent += '</div>';
            }

            if ( this.options.text instanceof Array ) {
                _toastContent += '<ul>';
                for (var i = 0; i < this.options.text.length; i++) {
                    _toastContent += '<li id="jqt-item-' + i + '">' + this.options.text[i] + '</li>';
                }
                _toastContent += '</ul>';

            } else {
                _toastContent += this.options.text;
            }

            this._toastEl.html( _toastContent );

            if ( this.options.bgColor ) {
                this._toastEl.css('background-color', this.options.bgColor);
            }

            if ( this.options.textColor ) {
                this._toastEl.css('color', this.options.textColor);
            }

            if ( this.options.textAlign ) {
                this._toastEl.css('text-align', this.options.textAlign);
            }

            if ( this.options.icon ) {
                this._toastEl.addClass('jqt-has-icon');

                if ( this._defaultIcons.indexOf(this.options.icon) !== -1 ) {
                    this._toastEl.addClass('jqt-icon-' + this.options.icon);
                }
            }

            if ( this.options.myclass ) {
                this._toastEl.addClass(this.options.myclass);
            }

            this._toastEl.hide();
        },

        position: function () {

            if ( ( typeof this.options.position === 'string' ) && ( this._positionClasses.indexOf(this.options.position) !== -1 ) ) {

                this._toastEl.addClass( this.options.position );

                if ( this.options.position === 'bottom-center' ) {
                    this._toastEl.css({
                        left: ( $(window).outerWidth() - this._toastEl.outerWidth() ) / 2,
                    });
                } else if ( this.options.position === 'top-center' ) {
                    this._toastEl.css({
                        left: ( $(window).outerWidth() - this._toastEl.outerWidth() ) / 2,
                    });
                } else if ( this.options.position === 'mid-center' ) {
                    this._toastEl.css({
                        left: ( $(window).outerWidth() - this._toastEl.outerWidth() ) / 2,
                        top: ( $(window).outerHeight() - this._toastEl.outerHeight() ) /2
                    });
                }
            } else if ( typeof this.options.position === 'object' ) {
                this._toastEl.css({
                    top : this.options.position.top ? this.options.position.top : 'auto',
                    bottom : this.options.position.bottom ? this.options.position.bottom : 'auto',
                    left : this.options.position.left ? this.options.position.left : 'auto',
                    right : this.options.position.right ? this.options.position.right : 'auto'
                });
            } else {
                this._toastEl.addClass( 'bottom-left' );
            }
        },

        postHide: function () {

            var that = $(this).data('Toastob');

            if (typeof that.options.afterHidden === 'function') {
                that.options.afterHidden(this);
            }
        },

        bindToast: function () {

            var that = this;

            this._toastEl.find('.jqt-close').on('click', function ( e ) {

                e.preventDefault();

                if ( typeof that.options.beforeHide === 'function' ) {
                    that.options.beforeHide(that._toastEl[0]);
                }

                if( that.options.showHideTransition === 'fade') {
                    that._toastEl.fadeOut(that.postHide);
                } else if ( that.options.showHideTransition === 'slide' ) {
                    that._toastEl.slideUp(that.postHide);
                } else {
                    that._toastEl.hide(that.postHide);
                }
            });

            if ( typeof this.options.onClick === 'function' ) {
                this._toastEl.on('click', function () {
                    that.options.onClick(that._toastEl);
                });
            }
        },

        addToDom: function () {

             var _container = $('#jqt-wrap');

             if ( _container.length === 0 ) {

                _container = $('<div/>',{
                    id : 'jqt-wrap'
                });

                $('body').append( _container );

             } else if ( !this.options.stack || isNaN( parseInt(this.options.stack, 10) ) ) {
                _container.empty();
             } else {
                 _container.find('.toast-single:hidden').remove();
             }

             _container.append( this._toastEl );

            if ( this.options.stack && !isNaN( parseInt( this.options.stack ), 10 ) ) {

                var _prevToastCount = _container.find('.jqtoast').length,
                    _extToastCount = _prevToastCount - this.options.stack;

                if ( _extToastCount > 0 ) {
                    $('#jqt-wrap').find('.jqtoast').slice(0, _extToastCount).remove();
                }

            }
        },

        canAutoHide: function () {
            return ( this.options.hideAfter ) && !isNaN( parseInt( this.options.hideAfter, 10 ) );
        },

        processLoader: function () {
            // Show the loader only if auto-hide is on and loader is demanded
            if (!this.canAutoHide() || !this.options.loader) {
                return false;
            }

            var loader = this._toastEl.find('.jqt-loader'),
            // 400 mS is jquery's default duration for fade/slide
            // Divide by 1000 for mS to S conversion
                transitionTime = (this.options.hideAfter - 400) / 1000 + 's',
                loaderBg = this.options.loaderBg,
                style = loader.attr('style') || '';

            style = style.substring(0, style.indexOf('-webkit-transition')); // Remove the last transition definition

            style += '-webkit-transition:width ' + transitionTime + ' ease-in;' +
                     '-o-transition:width ' + transitionTime + ' ease-in;' +
                     'transition:width ' + transitionTime + ' ease-in;' +
                     'background-color:' + loaderBg + ';';

            loader.attr('style', style).addClass('jqt-loaded');
        },

        postShow: function () {

            var that = $(this).data('Toastob');

            if (that.options.loader && that.options.hideAfter ) {
                that.processLoader();
            }
            if (typeof that.options.afterShown === 'function') {
               that.options.afterShown(this);
            }
        },

        animate: function () {

            if ( typeof this.options.beforeShow === 'function' ) {
               this.options.beforeShow(this._toastEl[0]);
            }

            var trans = this.options.showHideTransition.toLowerCase();

            switch ( trans ) {
                case 'fade':
                    this._toastEl.fadeIn(this.postShow);
                    break;
                case 'slide':
                    this._toastEl.slideDown(this.postShow);
                    break;
                default:
                    this._toastEl.show(this.postShow);
            }

            if (this.canAutoHide()) {

                var that = this;

                window.setTimeout(function() {

                    if ( typeof that.options.beforeHide === 'function' ) {
                       that.options.beforeHide(that._toastEl[0]);
                    }

                    switch ( trans ) {
                        case 'fade':
                            that._toastEl.fadeOut(that.postHide);
                            break;
                        case 'slide':
                            that._toastEl.slideUp(that.postHide);
                            break;
                        default:
                            that._toastEl.hide(that.postHide);
                    }

                }, this.options.hideAfter);
            }
        },

        /* public methods */

        reset: function ( resetWhat ) {

            if ( resetWhat === 'all' ) {
                $('#jqt-wrap').remove();
            } else {
                this._toastEl.remove();
            }

        },

        update: function(options) {
            this.prepareOptions(options, this.options);
            this.setup();
            this.bindToast();
        },

        close: function() {
            this._toastEl.find('.jqt-close').click();
        }
    };

    $.toast = function(options) {
        var toast = Object.create(Toast);
        toast.init(options, this);

        return {

            reset: function ( what ) {
                toast.reset( what );
            },

            update: function( options ) {
                toast.update( options );
            },

            close: function( ) {
                toast.close( );
            }
        };
    };

    $.toast.options = {
        text: '',
        heading: '',
        showHideTransition: 'fade',
        allowToastClose: true,
        hideAfter: 5000,
        loader: true,
        loaderBg: '#9EC600',
        stack: 5,
        position: 'bottom-left',
        tempposition: false,
        bgColor: false,
        textColor: false,
        textAlign: 'left',
        icon: false,
        closeicon: '&times;',
        myclass: false,
        beforeShow: false,
        afterShown: false,
        beforeHide: false,
        afterHidden: false,
        onClick: false
    };

    $.toast.clear = function( ) {
       for(var i in $.toast.options) $.toast.options[i] = false;
    };

})( jQuery, window, document );
