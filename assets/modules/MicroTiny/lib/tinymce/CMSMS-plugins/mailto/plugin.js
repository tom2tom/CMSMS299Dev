(function() {
  var mailto = (function() {
    'use strict';
    var edobj;

    function mailto_showDialog() {
      var selectedNode = edobj.selection.getNode();
      var isMailtoLink = selectedNode.tagName == 'A' && edobj.dom.getAttrib(selectedNode, 'href').startsWith('mailto:');
      var anchorElm, email_val, text_val;
      if (isMailtoLink) {
        email_val = edobj.dom.getAttrib(selectedNode, 'href').replace('mailto:', '');
        anchorElm = edobj.dom.getParent(selectedNode, 'a[href*="mailto:"]');
        text_val = anchorElm.innerText;
        console.log(selectedNode);
      } else {
        email_val = '';
        anchorElm = false;
        text_val = edobj.selection.getContent({
          format: 'text'
        });
      }
      edobj.windowManager.open({
        title: cmsms_tiny.prompt_insertmailto,
        body: [{
          type: 'textbox',
          name: 'email',
          size: 40,
          label: cmsms_tiny.prompt_email,
          value: email_val
        }, {
          type: 'textbox',
          name: 'text',
          size: 40,
          label: cmsms_tiny.prompt_linktext,
          value: text_val
        }],
        onsubmit: function(e) {
          var link_text;
          if (e.data.text != '') {
            link_text = e.data.text;
          } else {
            link_text = e.data.email;
          }
          // Select the tag if any
          if (anchorElm) {
            edobj.selection.select(anchorElm);
          }
          // And put the <a tag
          edobj.execCommand('mceInsertContent', false, edobj.dom.createHTML('a', {
            href: "mailto:" + e.data.email
          }, link_text));
        }
      });
    }

    tinymce.util.Tools.resolve('tinymce.PluginManager').add('mailto', function(editor, pluginUrl) {
      edobj = editor; // remember it
      // add a menu item
      editor.addMenuItem('mailto', {
        text: cmsms_tiny.mailto_text,
        title: cmsms_tiny.mailto_title,
        image: cmsms_tiny.mailto_image,
        stateSelector: 'a[href*="mailto:"]',
        context: 'insert',
        prependToContext: true,
        onclick: mailto_showDialog
//      cmd: 'mailto',
//      onPostRender: toggleActiveState(editor, enabledState),
      });
      // and a button
      editor.addButton('mailto', {
        text: '@',
        tooltip: cmsms_tiny.prompt_insertmailto,
        onclick: mailto_showDialog,
        stateSelector: 'a[href*="mailto:"]'
//      active: false,
//      cmd: 'mailto',
//      onPostRender: toggleActiveState(editor, enabledState)
      });
    });

    function Plugin() {}
    return Plugin;
  }());
})();
