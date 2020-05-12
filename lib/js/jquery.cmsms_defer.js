/*
Migrate scripts of type text/cms_javascript to the end of the page
To defer their execution by the browser
Derived from: https://gist.github.com/RonnyO/2391995
*/
$(function() {
 $('script[type="text/cms_javascript"]').each(function() {
  var content = this.innerHTML;
  $(this).detach();
  $('<script type="text/javascript">\n'+content+'\n</script>').appendTo('body');
 });
});