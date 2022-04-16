/*!
jQuery Basictable v.2.0.3 <https://www.github.com/jerrylow/basictable>
(C) Jerry Low 2014-2022 <lowjer@gmail.com>
License: MIT
*/
//Backported to ES5, and with resize-event throttling, and without deprecated jQuery 3.6 content
(function($) {
  $.fn.basictable = function(options) {
    var setup = function(table, data) {
      var headings = [];
      if (data.tableWrap) {
        table.wrap('<div class="bt-wrapper"></div>');
      }
      if (data.header) {
        var $cells;
        if (table.find("thead tr th").length) {
          $cells = table.find("thead th");
        } else if (table.find("tbody tr th").length) {
          $cells = table.find("tbody tr th");
        } else if (table.find("th").length) {
          $cells = table.find("tr").eq(0).find("th");
        } else {
          $cells = table.find("tr").eq(0).find("td");
        }
        $.each($cells, function() {
          var $heading = $(this);
          var colspan = parseInt($heading.attr("colspan"), 10) || 1;
          var row = $heading.closest("tr").index();
          if (!headings[row]) {
            headings[row] = [];
          }
          for (var i = 0; i < colspan; i++) {
            headings[row].push($heading);
          }
        });
      }
      $.each(table.find("tbody tr"), function() {
        setupRow($(this), headings, data);
      });
      $.each(table.find("tfoot tr"), function() {
        setupRow($(this), headings, data);
      });
    };
    var setupRow = function($row, headings, data) {
      $row.children().each(function() {
        var $cell = $(this),
          cntnt = $cell.html();
        if (!data.showEmptyCells && (cntnt === "" || cntnt === "&nbsp;")) {
          $cell.addClass("bt-hide");
        } else {
          var cellIndex = $cell.index();
          var headingText = "";
          for (var j = 0, k = headings.length; j < k; j++) {
            var head = headings[j][cellIndex].text(); // what if is html?
            if (head) {
              if (headingText) {
                headingText += ": ";
              }
              headingText += head;
            }
          }
          $cell.attr("data-th", headingText);
          if (data.contentWrap && !$cell.children().hasClass("bt-content")) {
            $cell.wrapInner('<div class="bt-content"></div>');
          }
        }
      });
    };
    var unwrap = function(table) {
      $.each(table.find("td"), function() {
        var $cell = $(this);
        var content = $cell.children(".bt-content").html();
        $cell.html(content);
      });
    };
    var check = function(table, data) {
      if (!data.forceResponsive) {
        if (table.removeClass("bt").outerWidth() > table.parent().width()) {
          start(table, data);
        } else {
          end(table, data);
        }
      } else {
        if (data.breakpoint !== null && $(window).width() <= data.breakpoint || data.containerBreakpoint !== null && table.parent().width() <= data.containerBreakpoint) {
          start(table, data);
        } else {
          end(table, data);
        }
      }
    };
    var start = function(table, data) {
      table.addClass("bt");
      if (!data.header) {
        table.addClass("bt--no-header");
      }
      if (data.tableWrap) {
        table.parent(".bt-wrapper").addClass("active");
      }
    };
    var end = function(table, data) {
      table.removeClass("bt bt--no-header");
      if (data.tableWrap) {
        table.parent(".bt-wrapper").removeClass("active");
      }
    };
    var destroy = function(table, data) {
      table.removeClass("bt bt--no-header");
      table.find("td").removeAttr("data-th");
      if (data.tableWrap) {
        table.unwrap();
      }
      if (data.contentWrap) {
        unwrap(table);
      }
      table.removeData("basictable");
    };
    var resize = function(table) {
      if (table.data("basictable")) {
        check(table, table.data("basictable"));
      }
    };
    this.each(function() {
      var table = $(this);
      if (table.length === 0) {
        return false;
      }
      if (table.data("basictable")) {
        var data = table.data("basictable");
        if (options === "destroy") {
          destroy(table, data);
        } else if (options === "restart") {
          destroy(table, data);
          table.data("basictable", data);
          setup(table, data);
          check(table, data);
        } else if (options === "start") {
          start(table, data);
        } else if (options === "stop") {
          end(table, data);
        } else {
          check(table, data);
        }
        return false;
      }
      var settings = $.extend({}, $.fn.basictable.defaults, options);
      var vars = {
        breakpoint: settings.breakpoint,
        containerBreakpoint: settings.containerBreakpoint,
        contentWrap: settings.contentWrap,
        forceResponsive: settings.forceResponsive,
        noResize: settings.noResize,
        tableWrap: settings.tableWrap,
        showEmptyCells: settings.showEmptyCells,
        header: settings.header
      };
      if (vars.breakpoint === null && vars.containerBreakpoint === null) {
        vars.breakpoint = 568;
      }
      table.data("basictable", vars);
      setup(table, vars);
      if (!vars.noResize) {
        var tid = 0;
        check(table, vars);
        $(window).on("resize.basictable", function() {
          if (tid === 0) {
            tid = setTimeout(function () {
              tid = 0;
            }, 250);
            resize(table);
          }
        });
      }
    });
  };
  $.fn.basictable.defaults = {
    breakpoint: null,
    containerBreakpoint: null,
    contentWrap: true,
    forceResponsive: true,
    noResize: false,
    tableWrap: false,
    showEmptyCells: false,
    header: true
  };
})(jQuery);
