/*!
jQuery plugin ContextMenu v.1.2 <https://github.com/GGaritaJ/ggContextMenu>
(C) 2018 Gerardo Garita <info@ggaritaj.com>
License: GPL3
*/
///////////////////////////////////////////////
//  ContextMenu   JS/CSS PlugIn V1.1         //
//  Developed by: Ing.Gerardo Garita J.      //
//                FullStack Developer        //
//  email:      info@ggaritaj.com            //
//  date:       friday, 2018-09-10           //
//  last date:  friday, 2018-09-10           //
///////////////////////////////////////////////
;(function($, window, document, undefined) {
    $.fn.ContextMenu = function(options) {
        try {
            $(this).each(function() {
                var that = this;
                this.cfg = $.extend({}, $.fn.ContextMenu.defaults, options || {});
                $(this).on("click", function(event) {
                    event.preventDefault();
                    var menuSel = "#" + $(event.currentTarget).attr("context-menu"),
                      holder = getScrollParent(event.currentTarget/*, false*/);
                    $("." + this.cfg.mainclass).removeClass("active").removeAttr("style");
                    $(menuSel).find(".active").removeClass("active");
                    if (typeof this.cfg.beforeShow === 'function') {
                        this.cfg.beforeShow(this, menuSel);
                    }
                    PlaceMenu(menuSel, event.clientX, event.clientY);
                    $(menuSel).addClass("active");
                    $("html").on("click.ctxmenu", function(event) {
                        $(menuSel).removeClass("active").removeAttr("style")
                        .find("ul.sub-level, li.sub-level").removeClass("active").removeAttr("style");
                        if (typeof that.cfg.afterHidden === 'function') {
                            that.cfg.afterHidden(that, menuSel);
                        }
                        $("html").off("click.ctxmenu");
//                        if (holder) {
                            $(holder).off("scroll.ctxmenu");
//                        }
                    });
//                    if (holder) {
                        $(holder).on("scroll.ctxmenu", function() { // and [throttled] 'resize' ?
                            // close menu without callback
                            var cn = "." + that.cfg.mainclass,
                               sel = cn + ", " + cn + " ul.sub-level, " + cn + " li.sub-level";
                            $(sel).removeClass("active").removeAttr("style");
                            $("html").off("click.ctxmenu");
                            $(holder).off("scroll.ctxmenu");
                        });
//                    }
                    return false;
                }).find("li.sub-level").on("click", function(event) {
                    var opts = $(this).find("ul.sub-level")[0];
                    if(!$(opts).hasClass("active")) {
                        $(opts).addClass("active");
                        $(this).addClass("active");
                        var menuSel = ("#" + $(event.currentTarget).closest("." + that.cfg.mainclass).attr("id"));
                        var pos = $(menuSel).position();
                        PlaceMenu(menuSel, pos.left, pos.top);
                    } else if($(this).find("ul").find(event.target).length !== 0) {
                        $(opts).addClass("active");
                        $(this).addClass("active");
                    } else {
                        $(opts).removeClass("active");
                        $(this).removeClass("active");
                    }
                });
            });
        } catch(err) {
            console.log("Error: " + err + ".");
        } finally {
            setTimeout(function() {
                window.dispatchEvent(new Event("resize"));
            }, 1000);
        }
    };

    $.fn.ContextMenu.defaults = $.extend({}, {
        // Default parameters
        beforeShow: false,
        afterHidden: false,
        mainclass: "ContextMenu"
    }, $.fn.ContextMenu.defaults || {});

    // adapted from https://stackoverflow.com/questions/35939886/find-first-scrollable-parent
    function getScrollParent(element/*, includeHidden*/) {
        var style = getComputedStyle(element);
        if (style.position === "fixed") {
            return window; //document.body;
        }
        var excludeStaticParent = style.position === "absolute";
        var overflowRegex = /*includeHidden ? /(auto|scroll|hidden)/ : *//(auto|scroll)/;

        for (var parent = element; (parent = parent.parentElement);) {
            style = getComputedStyle(parent);
            if (excludeStaticParent && style.position === "static") {
                continue;
            }
            if (overflowRegex.test(style.overflow + style.overflowY + style.overflowX)) {
                return parent;
            }
        }
        return window; //document.body;
    }

    function PlaceMenu(menuSel, x, y) {
        var $w = $(window),
         screenW = $w.width(),
         screenH = $w.height(),
         $m = $(menuSel),
         menuW = $m.width(),
         menuH = $m.height(),
         pos1, pos2, props;
        if((y + menuH) > screenH) {
            y -= (menuH + 18);
            pos1 = "bottom";
        } else {
            pos1 = "top";
        }
        if((x + menuW) > screenW) {
            x -= (menuW + 18);
            pos2 = "right";
        } else {
            pos2 = "left";
        }

        props = {
            "top": y + "px",
            "left": x + "px"
        };
        props["border-" + pos1 + "-" + pos2 + "-radius"] = 0;
        $m.removeAttr("style").css(props);
    }
})(jQuery, window, document);
