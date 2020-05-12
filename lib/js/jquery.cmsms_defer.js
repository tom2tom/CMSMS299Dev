// jQuery to detect scripts of type text/cms_javascript,
// and migrate them to the end of the page.
// source: https://gist.github.com/RonnyO/2391995
$(function() {
 $('script[type="text/cms_javascript"]').each(function() {
  var content = this.innerHTML;
  $(this).detach();
  $('<script type="text/javascript">\n'+content+'\n</script>').appendTo('body');
 });
});
