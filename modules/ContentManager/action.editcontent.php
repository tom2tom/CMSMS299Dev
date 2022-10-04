<?php
/*
ContentManager module action: edit an existing or cloned page
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\ContentException;
use CMSMS\EditContentException;
use CMSMS\FormUtils;
use CMSMS\LockException;
use CMSMS\LockOperations;
use CMSMS\Lone;
use CMSMS\ScriptsMerger;
use CMSMS\UserParams;
use ContentManager\ContentBase;
use ContentManager\Utils;
use function CMSMS\add_shutdown;
use function CMSMS\log_info;

//if( some worthy test fails ) exit;

if (isset($params['cancel'])) {
	$this->SetInfo($this->Lang('msg_cancelled'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}

if (isset($params['content_id'])) {
	$content_id = (int)$params['content_id'];
	$copy = $content_id == -1 || !empty($params['clone_id']); // TODO if submitted/applied
	if ((!$copy && $content_id < 0) || ($copy && $content_id < 1)) {
		$this->SetError($this->Lang('error_invalidpageid'));
		$this->Redirect($id, 'defaultadmin', $returnid);
	}
} elseif (isset($params['clone_id'])) {
	$content_id = (int)$params['clone_id'];
	if ($content_id < 1) {
		$this->SetError($this->Lang('error_invalidpageid'));
		$this->Redirect($id, 'defaultadmin', $returnid);
	}
	$copy = true;
} else {
	// default - addition
	$content_id = 0;
	$copy = false;
}

if ($content_id == 0 || $copy) {
	// adding or cloning
	if (!$this->CheckPermission('Add Pages')) {
		// no permission to add page
		$this->SetError($this->Lang('error_editpage_permission'));
		$this->Redirect($id, 'defaultadmin', $returnid);
	}
} elseif (!$this->CanEditContent($content_id)) {
	// nope, can't edit this page anyways
	$this->SetError($this->Lang('error_editpage_permission'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}

$userid = get_userid();
$parent_id = $error = null;
$pagedefaults = Utils::get_pagedefaults();
$contentops = Lone::get('ContentOperations');
$domain = $this->GetName(); // translated-strings domain is this module

try {
	// load or create the initial content object
	if ($content_id == 0) {
		// we're creating a new content object
		if (isset($params['content_type'])) {
			$content_type = trim($params['content_type']);
		} else {
			$content_type = $pagedefaults['contenttype'];
		}

		$parent_id = (isset($params['parent_id'])) ? (int)$params['parent_id'] : 0;
		if ($parent_id < 1) {
			$dflt_parent = (int) UserParams::get('default_parent');
			if ($dflt_parent < 1) {
				$dflt_parent = -1;
			}
			if (!($this->CheckPermission('Modify Any Page') || $this->CheckPermission('Manage All Content'))) {
				// we get the list of pages that this user has access to.
				// if he is not an editor of the default page, then we use the first page the user has access to, or -1
				$list = $contentops->GetPageAccessForUser($userid);
				if ($list && !in_array($dflt_parent, $list)) {
					$dflt_parent = $list[0];
				}
			}
			// double check if this parent is valid... if it is not, we use -1
			if ($dflt_parent > 0) {
				$ptops = $gCms->GetHierarchyManager();
				$node = $ptops->get_node_by_id($dflt_parent);
				if (!$node) {
					$dflt_parent = -1;
				}
			}
			$parent_id = $dflt_parent;
		}

		//TODO support themed/named templates
		$params = [
			'parent_id' => $parent_id,
			'owner_id' => $userid,
			'last_modified_by' => $userid,
			'show_in_menu' => $pagedefaults['showinmenu'],
			'active' => $pagedefaults['active'],
			'cachable' => $pagedefaults['cachable'],
			'template_id' => $pagedefaults['template_id'],
			'metadata' => $pagedefaults['metadata'],
			'styles' => $pagedefaults['styles'],
		];

		$content_obj = $contentops->CreateNewContent($content_type, $params, true);
		if (!$content_obj) {
			throw new Exception('Failed to create content object - type ', $content_type);
		}

		$content_obj->SetPropertyValue('searchable', $pagedefaults['searchable']);
		$content_obj->SetPropertyValue('content_en', $pagedefaults['content']);
		$content_obj->SetPropertyValue('extra1', $pagedefaults['extra1']);
		$content_obj->SetPropertyValue('extra2', $pagedefaults['extra2']);
		$content_obj->SetPropertyValue('extra3', $pagedefaults['extra3']);
		$content_obj->SetAdditionalEditors($pagedefaults['addteditors']);
	} elseif ($copy) {
		// we're cloning an existing content object
		$from_obj = $contentops->LoadEditableContentFromId($content_id, true);
		if (!$from_obj) {
			$this->SetError($this->Lang('error_invalidpageid'));
			$this->Redirect($id, 'defaultadmin', $returnid);
		}
		$from_obj->GetAdditionalEditors();

		$content_obj = clone $from_obj; // includes id = -1 etc
		$content_obj->SetName('Copy of '.$from_obj->Name());
		$content_obj->SetMenuText('Copy of '.$from_obj->MenuText());
		$content_obj->SetAlias('copyof-' . $from_obj->Alias());
		$content_obj->SetDefaultContent(false);
		$content_obj->SetOwner($userid);
		$content_obj->SetLastModifiedBy($userid);
		$content_type = $content_obj->Type();
		$content_id = -1;
	} else {
		// we're editing an existing content object
		$content_obj = $contentops->LoadEditableContentFromId($content_id);
		if (!$content_obj) {
			throw new Exception('Failed to load the content object to be edited');
		}
		if (isset($params['content_type'])) {
			// maybe the user wants to change type ...
			$content_type = trim($params['content_type']);
		} else {
			$content_type = $content_obj->Type();
		}
	}

	// validate the content type
	$existingtypes = Lone::get('ContentTypeOperations')->ListContentTypes(false, true, false, $domain);
	if (!$existingtypes || !in_array($content_type, array_keys($existingtypes))) {
		$this->SetError($this->Lang('error_editpage_contenttype'));
		$this->Redirect($id, 'defaultadmin', $returnid);
	}
	//TODO for the default page, disable|omit errorpage, sectionheader, separator, maybe also link, pagelink
} catch (Throwable $t) {
	// An error here means we can't display anything
	$this->SetError($t->getMessage());
	$this->Redirect($id, 'defaultadmin', $returnid);
}

// handle changing content types
// or a POST

try {
	if (!$copy && $content_type != $content_obj->Type()) {
		// content type changed - create a new content object with the same id etc.
		$props = $content_obj->ToData();
		unset($props['create_date'], $props['last_modified_by'], $props['modified_date']);
		$tmpobj = $contentops->CreateNewContent($content_type, $props, true);
		//TODO $tmpobj AdditionalEditors
		$tmpobj->Properties(); // TODO deal with now-irrelevant props
		$content_obj = $tmpobj;
	}

	$was_defaultcontent = $content_obj->DefaultContent();
	if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
		// if we're in a POST action, another item may have changed that requires reloading the page
		// filling the properties from supplied $params will ensure that no edited content gets lost.
		$content_obj->FillParams($params, ($content_id > 0));
	}

	if (isset($params['submit']) || isset($params['apply']) || isset($params['preview'])) {
		$error = $content_obj->ValidateData();
		if ($error) {
			if (isset($params['ajax'])) {
				$tmp = ['response' => 'Error', 'details' => $error];
				echo json_encode($tmp);
				exit;
			}
			// error, but no ajax... fall through
		} elseif (isset($params['submit']) || isset($params['apply'])) {
			$content_obj->SetLastModifiedBy(get_userid());
			$content_obj->Save();
			if (!$was_defaultcontent && $content_obj->DefaultContent()) {
				$contentops->SetDefaultContent($content_obj->Id());
			}
			unset($_SESSION['__cms_copy_obj__']);
			log_info($content_obj->Id(), 'ContentManager', 'Edited content item '.$content_obj->Name());
			if (isset($params['submit'])) {
				$this->SetMessage($this->Lang('msg_editpage_success'));
				$this->Redirect($id, 'defaultadmin', $returnid);
			}
			if (isset($params['ajax'])) {
				$tmp = ['response' => 'Success', 'details' => $this->Lang('msg_editpage_success'), 'url' => $content_obj->GetURL()];
				echo json_encode($tmp);
				exit;
			}
			$this->ShowMessage($this->Lang('msg_editpage_success'));
		} elseif (isset($params['preview']) && $content_obj->HasPreview()) {
			$_SESSION[CMS_PREVIEW] = serialize($content_obj);
			$_SESSION[CMS_PREVIEW_TYPE] = $content_type;
			$tmp = ['response' => 'Success'];
			echo json_encode($tmp);
			exit;
		}
	}
} catch (EditContentException $e) {
/*
	if( isset($params['submit']) ) {
		$this->SetError($e->getMessage());
		$this->Redirect($id,'defaultadmin',$returnid);
	};
*/
	$error = $e->getMessage();
	if (isset($params['ajax'])) {
		$tmp = ['response' => 'Error', 'details' => $error];
		echo json_encode($tmp);
		exit;
	}
} catch (ContentException $e) {
	$error = $e->getMessage();
	if (isset($params['ajax'])) {
		$tmp = ['response' => 'Error', 'details' => $error];
		echo json_encode($tmp);
		exit;
	}
}

// BUILD THE DISPLAY

if ($content_id > 0 && Utils::locking_enabled()) {
	try {
		$lock_id = null;
		// check if this thing is locked
		for ($i = 0; $i < 3; ++$i) {
			$lock_id = LockOperations::is_locked('content', $content_id);
			if ($lock_id == 0) {
				break;
			}
			usleep(500);
		}
		if ($lock_id > 0) {
			// it's locked.. by somebody. If lock's expired, remove it
			$lock = LockOperations::load('content', $content_id);
			if (!$lock->expired()) {
				throw new LockException('CMSEX_L010');
			}
			LockOperations::unlock($lock_id, 'content', $content_id);
		}
	} catch (Throwable $t) {
		$this->SetError($t->getMessage());
		$this->Redirect($id, 'defaultadmin', $returnid);
	}
}

$tab_contents_array = [];
$tab_message_array = [];
$maintab = ContentBase::TAB_MAIN;

try {
	$tab_names = $content_obj->GetTabNames(); //admin realm cuz wierd lang-keys
	// the content object might not have a main tab, but we require one
	if (!in_array($maintab, $tab_names)) {
		$tab_names = [$maintab => $this->Lang($maintab)] + $tab_names; //another wierd lang-key
	}
	$props = $content_obj->GetSortedEditableProperties();
	$adding = $content_obj->Id() == 0; // indicate a new page

	foreach ($tab_names as $currenttab => $label) {
		$tmp = $content_obj->GetTabMessage($currenttab);
		if ($tmp) {
			$tab_message_array[$currenttab] = $tmp;
		}

		/* Each 'bundle' of content provided to Smarty is an array, with
		 * 3-4 members:
		 * [0] = property-label content
		 * [1] = popup-help content or falsy
		 * [2] = property-input element(s) or text
		 * [3] = optional supplmentary content (advice of some sort)
		 * Any related js is pushed directly into the page bottom
		 */
		$bundles = [];
		if ($currenttab == $maintab) {
			// main tab... prepend a content-type selector
			// unless the user is only an additional editor for this page
			if ($this->CheckPermission('Manage All Content')
			 || $this->CheckPermission('Modify Any Page')
			 || $content_obj->Owner() == $userid) {
				natcasesort($existingtypes);
				//TODO a selector if $adding, or else just text ?
				$input = FormUtils::create_select([
					'type' => 'drop',
					'name' => 'content_type',
					'htmlid' => 'content_type',
					'getid' => $id,
					'multiple' => false,
					'options' => array_flip($existingtypes),
					'selectedvalue' => $content_type,
				]);
				$bundles[] = [
				'for="content_type">* '.$this->Lang('prompt_editpage_contenttype'),
				AdminUtils::get_help_tag($domain, 'help_content_type', $this->Lang('help_title_content_type')),
				$input
				];
				//TODO js to handle selector-change
			}
		}

		foreach ($props as &$one) {
			if ($one['name'] == 'design_id') {
				continue; // deprecated property since 3.0, ignored
			}
			if (!isset($one['tab']) || $one['tab'] === '') {
				$one['tab'] = $maintab;
			}
			if ($one['tab'] == $currenttab) {
				$bundles[] = $content_obj->ShowElement($one['name'], $adding);
			}
		}
		unset($one);

		$tab_contents_array[$currenttab] = $bundles;
	}
} catch (Throwable $t) {
	$tab_names = null;
	$error = $t->getMessage();
}

if ($error) {
	$this->ShowErrors($error);
}

$active_tab = $params['active_tab'] ?? null;

$tpl = $smarty->createTemplate($this->GetTemplateResource('editcontent.tpl')); //,null,null,$smarty);

if ($content_obj->HasPreview()) {
	$tpl->assign('has_preview', 1);
	$preview_url = CMS_ROOT_URL.'/index.php?'.$config['query_var'].'='.CMS_PREVIEW_PAGEID;
	$validate_url = $this->create_action_url($id, 'editcontent', ['preview' => 1, CMS_JOB_KEY => 1]);
} else {
	$preview_url = '';
	$validate_url = '';
}
/*
if( $this->GetPreference('template_list_mode','allpage') != 'all')  {
	$designchanged_ajax_url = $this->create_action_url($id,'ajax_gettemplates',['forjs'=>1,CMS_JOB_KEY=>1]);
}
else {
	$designchanged_ajax_url = '';
}
*/

$tpl->assign('content_id', $content_id)
	->assign('content_obj', $content_obj)
	->assign('tab_names', $tab_names)
	->assign('active_tab', trim($active_tab))
	->assign('tab_contents_array', $tab_contents_array)
	->assign('tab_message_array', $tab_message_array);
/*$factory = new ContentAssistantFactory($content_obj);
  $assistant = $factory->getEditContentAssistant(); */
// if( is_object($assistant) ) $tpl->assign('extra_content',$assistant->getExtraCode());

$parms = [CMS_JOB_KEY => 1];
if ($content_id > 0) {
	$parms['content_id'] = $content_id;
}
$apply_ajax_url = $this->create_action_url($id, 'editcontent', $parms);
$lock_timeout = AppParams::get('lock_timeout', 60);
$do_locking = ($content_id > 0 && $lock_timeout > 0) ? 1 : 0;
if ($do_locking) {
	add_shutdown(10, 'LockOperations::delete_for_nameduser', $userid);
}
$lock_refresh = AppParams::get('lock_refresh', 120);
$options_tab_name = ContentBase::TAB_OPTIONS;
$s1 = addcslashes($this->Lang('msg_lostlock'), "'");
$s2 = addcslashes($this->Lang('error_editpage_contenttype'), "'");
$close = $this->Lang('close');

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
if ($do_locking) {
	$jsm->queue_matchedfile('jquery.cmsms_lock.js', 2);
}
$js = $jsm->page_content();
if ($js) {
	add_page_foottext($js);
}

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  var do_locking = $do_locking;
  // initialize the dirtyform stuff
  $('#Edit_Content').dirtyForm({
    beforeUnload: function(is_dirty) {
      if (do_locking) $('#Edit_Content').lockManager('unlock').done(function() {
        console.log('after dirtyform unlock');
      });
    },
    unloadCancel: function() {
      if (do_locking) $('#Edit_Content').lockManager('relock');
    }
  });
  // initialize lock manager
  if (do_locking) {
    $('#Edit_Content').lockManager({
      type: 'content',
      oid: $content_id,
      uid: $userid,
      lock_timeout: $lock_timeout,
      lock_refresh: $lock_refresh,
      error_handler: function(err) {
        cms_alert('{$this->Lang('lockerror')}: ' + err.type + ' -- ' + err.msg);
      },
      lostlock_handler: function(err) {
      // we lost the lock on this content... make sure we can't save anything.
      // and display a nice message.
        $('[name={$id}cancel]').fadeOut().attr('value', '$close').fadeIn();
        $('#Edit_Content').dirtyForm('option', 'dirty', false);
        cms_alert('$s1');
      }
    });
  }

EOS;

if ($preview_url) {
//TODO generic migration of all editor(s)-content to form-element(s) to be saved
	$js .= <<<EOS
  $('#_preview_').on('click', function() {
//    if (typeof tinyMCE !== 'undefined') {
//      tinyMCE.triggerSave();
//    } else {
      var v = geteditorcontent();
      setpagecontent(v);
//    }
    var params = [{
      name: '{$id}ajax',
      value: 1
    },{
      name: '{$id}preview',
      value: 1
    }].concat($('#Edit_Content').find('input:not([type=submit]), select, textarea').serializeArray());
    $.ajax('$validate_url', {
      method: 'POST',
      data: params,
      dataType: 'json'
    }).fail(function(jqXHR, textStatus, errorThrown) {
      cms_notify('error', errorThrown);
    }).done(function(data) {
      if (data !== null && data.response == 'Error') {
        $('#previewframe').attr('src', '').hide();
        $('#preview_errors').html('<ul></ul>');
        for (var i = 0; i < data.details.length; i++) {
          $('#preview_errors').append('<li>' + data.details[i] + '</li>');
        }
        $('#previewerror').show();
      } else {
        var x = new Date().getTime();
        var url = '{$preview_url}&junk=' + x;
        $('#previewerror').hide();
        $('#previewframe').attr('src', url).show();
      }
    });
  });

EOS;
}
	$js .= <<<EOS
  $('#template_id').data('lastValue', $('#template_id').val());
  // limit the content-type for the default page.
  $('#content_type')
   .data('lastValue', $('#content_type').val())
   .on('change', function(ev) {
     if ($('#defaultcontent').val()) {
       var ts = $(this),
        v = ts.val();
       if (['errorpage','sectionheader','separator','pagelink','link'].indexOf(v) !== false) {
         //invalid type
         v = ts.data('lastValue'); // revert value
         ts.val(v);
         cms_notify('error', '$s2');
         ev.stopImmediatePropagation();
         ev.preventDefault();
         return false;
       }
     }
   });
  // submit the form if disable wysiwyg, template id, or content-type field is changed
  $('#id_disablewysiwyg, #template_id, #content_type').on('change', function() {
    // disable the dirty form stuff, and unlock pending relock after reload.
    var self = this;
    var this_id = $(this).attr('id');
    $('#Edit_Content').dirtyForm('disable');
    if (this_id != 'content_type') $('#active_tab').val('{$options_tab_name}');
    if (do_locking) {
      $('#Edit_Content').lockManager('unlock', 1).done(function() {
        $(self).closest('form').submit();
      });
    } else {
      $(self).closest('form').submit();
    }
  });

  // handle cancel/close ... and unlock
  $('[name={$id}cancel]').on('click', function(ev) {
    // turn off all required elements, we're cancelling
    $('#Edit_Content :hidden').removeAttr('required');
    // do not touch the dirty flag, so that theunload handler stuff can warn us.
    if (do_locking) {
      // unlock the item, and submit the form.
      var self = this;
      var form = $(this).closest('form');
      ev.preventDefault();
      $('#Edit_Content').lockManager('unlock', 1).done(function() {
        var el = $('<input type="hidden"QQ>');
        el.attr('name', $(self).attr('name')).val($(self).val()).appendTo(form);
        form.submit();
      });
    }
  });

  $('[name={$id}submit]').on('click', function(ev) {
//    if (typeof tinyMCE !== 'undefined') {
//      tinyMCE.triggerSave();
//    } else {
//TODO generic migration of all editor(s)-content to form element(s) to be saved
      var v = geteditorcontent();
      setpagecontent(v);
//    }
    // set the form to not dirty.
    $('#Edit_Content').dirtyForm('option', 'dirty', false);
    if (do_locking) {
      // unlock the item, and submit the form
      var self = this;
      ev.preventDefault();
      var form = $(this).closest('form');
      $('#Edit_Content').lockManager('unlock', 1).done(function() {
        var el = $('<input type="hidden"QQ>');
        el.attr('name', $(self).attr('name')).val($(self).val()).appendTo(form);
        form.submit();
      });
    }
  });

  // handle apply (via ajax)
  $('[name={$id}apply]').on('click', function() {
//    if (typeof tinyMCE !== 'undefined') {
//      tinyMCE.triggerSave();
//    } else {
//TODO generic migration of all editor(s)-content to form-element(s) to be saved
      var v = geteditorcontent();
      setpagecontent(v);
//    }
    // apply does not do an unlock
    var params = [{
      name: '{$id}ajax',
      value: 1
    },{
      name: '{$id}apply',
      value: 1
    }].concat($('#Edit_Content').find('input:not([type=submit]), select, textarea').serializeArray());
    $.ajax('$apply_ajax_url', {
      method: 'POST',
      data: params,
      dataType: 'json'
    }).fail(function(jqXHR, textStatus, errorThrown) {
      cms_notify('error', errorThrown);
    }).done(function(data, text) {
      var event = $.Event('cms_ajax_apply');
      event.response = data.response;
      event.details = data.details;
      event.close = '$close';
      if (typeof data.url !== 'undefined' && data.url !== '') event.url = data.url;
      $('body').trigger(event);
    });
    return false;
  });

  $(document).on('cms_ajax_apply', function(e) {
    $('#Edit_Content').dirtyForm('option', 'dirty', false);
    if (typeof e.url !== 'undefined' && e.url !== '') {
      $('a#viewpage').attr('href', e.url);
    }
  });

EOS;
/*
if ($designchanged_ajax_url) {
	$s1 = addcslashes($this->Lang('warn_notemplates_for_design'), "'");
	$js .= <<<EOS
  $('#design_id').on('change', function(e, edata) {
    var v = $(this).val();
    var lastValue = $(this).data('lastValue');
    var data = {'{$id}design_id': v};
    $.get('$designchanged_ajax_url', data, function(data, text) {
      if (typeof data == 'object') {
        var sel = $('#template_id').val();
        var fnd = false;
        var first = null;
        for (var key in data) {
          if (!data.hasOwnProperty(key)) continue;
          if (first === null) first = key;
          if (key == sel) fnd = true;
        }
        if (!first) {
          $('#design_id').val(lastValue);
          cms_alert('$s1');
        }
        else {
          $('#template_id').val('');
          $('#template_id').empty();
          for (key in data) {
            if (!data.hasOwnProperty(key)) continue;
            $('#template_id').append('<option value="' + key + '">' + data[key] + '</option>');
          }
          if (fnd) {
            $('#template_id').val(sel);
          }
          elseif (first) {
            $('#template_id').val(first);
          }
          if (typeof edata === 'undefined' || typeof edata.skip_fallthru === 'undefined') {
            $('#template_id').trigger('change');
          }
        }
      }
    }, 'json');
  });

  $('#design_id').trigger('change', [{ skip_fallthru: 1 }]);
  $('#design_id').data('lastValue', $('#design_id').val());
  $('#template_id').data('lastValue', $('#template_id').val());
  $('#Edit_Content').dirtyForm('option', 'dirty', false);

EOS;
}
*/
	$js .= <<<'EOS'
});
//]]>
</script>

EOS;
add_page_foottext($js);

$tpl->display();
