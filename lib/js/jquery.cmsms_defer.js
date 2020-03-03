// some jQuery to detect scripts of type text/cms_javascript,
// clone them and insert after the page html element.
// source: https://gist.github.com/RonnyO/2391995
$(function() {
 $('script[type="text/cms_javascript"]').each(function() {
  var content = $(this).html();
  $('<script/>').html( content ).insertAfter( this );
 });
});
