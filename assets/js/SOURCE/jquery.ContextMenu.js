/*!
jQuery plugin ContextMenu v.1.2 <https://github.com/GGaritaJ/ggContextMenu>
(C) 2018 Gerardo Garita <info@ggaritaj.com>
License: GPL3
*/
﻿///////////////////////////////////////////////
//  ContextMenu   JS/CSS PlugIn V1.1         //
//  Developed by: Ing.Gerardo Garita J.      //
//                FullStack Developer        //
//  email:  info@ggaritaj.com                //
//  date:       friday, 2018-09-10           //
//  last date:  friday, 2018-09-10           //
///////////////////////////////////////////////
;(function($, window, document, undefined) {
    $.fn.ContextMenu = function(options) {
        var defaults = {
            beforeShow: false,
            afterHidden: false,
        };
        try {
            $(this).each(function() {
                this.cfg = $.extend(defaults, options || {});
                $(this).on("click", function(event) {
                    event.preventDefault();
                    var that = this,
                      menuSel = "#" + $(event.currentTarget).attr("context-menu");
                    $("div.ContextMenu").removeClass("active").removeAttr("style");
                    $(menuSel).find(".active").removeClass("active");
                    if(typeof this.cfg.beforeShow === 'function') {
                        this.cfg.beforeShow(this, menuSel);
                    }
                    PlaceMenu(menuSel, event.clientX, event.clientY);
                    $(menuSel).addClass("active");
                    $("html").on("click.ggmenu", function(event) {
                        $(menuSel).removeClass("active").removeAttr("style")
                        .find("ul.sub-level, li.sub-level").removeClass("active").removeAttr("style");
                        if (typeof that.cfg.afterHidden === 'function') {
                            that.cfg.afterHidden(that, menuSel);
                        }
                        $("html").off("click.ggmenu");
                    });
                    return false;
                }).find("li.sub-level").on("click", function(event) {
                    var opts = $(this).find("ul.sub-level")[0];
                    if(!$(opts).hasClass("active")) {
                        $(opts).addClass("active");
                        $(this).addClass("active");
                        var menuSel = ("#" + $(event.currentTarget).closest(".ContextMenu").attr("id"));
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
            $(document).on("scroll", function() {
                //TODO find any popup, process it to closed e.g. afterHidden();
                $("div.ContextMenu, div.ContextMenu ul.sub-level, div.ContextMenu li.sub-level").removeClass("active").removeAttr("style");
                $("html").off("click.ggmenu");
            });
        } catch(err) {
            console.log("Error: " + err + ".");
        } finally {
            setTimeout(function() {
                window.dispatchEvent(new Event("resize"));
            }, 1000);
        }
    };

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
