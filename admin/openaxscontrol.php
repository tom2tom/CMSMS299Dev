<?php
/*
Script to display/edit the content of a folder control-set
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\DataException;
use CMSMS\FileType;
use CMSMS\FolderControls;
use CMSMS\FolderControlOperations;
use CMSMS\FormUtils;
use CMSMS\Lone;
use CMSMS\Utils;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid(false);
$pmod = check_permission($userid, 'Modify Site Preferences');

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listaxscontrols.php'.$urlext);
}

// TODO sanitize $_REQUEST[]

try {
    $id = $_REQUEST['edit'] ?? 0;
    if ($id !== 0) {
        $id = (int)$id;
    }
    if ($id > 0) {
        $cset = FolderControlOperations::load_by_id($id);
        if (!$cset) {
            throw new LogicException('Invalid control-set id');
        }
    } else {
        $cset = new FolderControls();
    }

    if (isset($_POST['submit'])) {
        if ($_POST['exclude_patterns']) {
            $arr = explode(';', $_POST['exclude_patterns']);
            $arr = array_map('trim', $arr);
            $_POST['exclude_patterns'] = $arr;
        } else {
            $_POST['exclude_patterns'] = [];
        }
        if ($_POST['match_patterns']) {
            $arr = explode(';', $_POST['match_patterns']);
            $arr = array_map('trim', $arr);
            $_POST['match_patterns'] = $arr;
        } else {
            $_POST['match_patterns'] = [];
        }
        try {
            $cset = $cset->overrideWith($_POST);
            FolderControlOperations::save($cset);
            redirect('listaxscontrols.php'.$urlext);
        } catch (Exception $e) {
            $themeObject = Utils::get_theme_object();
            $themeObject->RecordNotice('error', $e->GetMessage());
        }
    }

    if ($pmod) {
        $arr = [];
        foreach (FileType::getAll() as $key => $val) {
            switch ($key) {
                case 'NONE':
                case 'ANY':
                    break;
                default:
                    if (strncmp($key, 'TYPE_', 5) !== 0) {
                        $arr[$key] = $val;
                    }
                    break;
            }
        }
        ksort($arr);
        $sel = ($cset->file_types) ? $cset->file_types : [$arr['ALL']];
        $typesel = FormUtils::create_select([
         'type' => 'list',
         'name' => 'file_types',
         'getid' => '',
         'htmlid' => 'filetypes',
         'multiple' => true,
         'options' => $arr,
         'selectedvalue' => $sel,
        ]);
    } else {
        if ($cset->file_types) {
            $arr = array_intersect(FileType::getAll(), $cset->file_types);
            $arr = array_unique($arr, SORT_NUMERIC);
            $typesel = implode(',', array_keys($arr));
        } else {
            $typesel = 'ALL';
        }
    }

    if ($pmod) {
        // sort on: 'name','size','date' modified?? created ??
        $arr = [
         'name' => _la('name'),
         'size' => _ld('controlsets', 'size'),
         'date' => _ld('controlsets', 'modified'),
        ];
        $sel = ($cset->sort_by) ? $cset->sort_by : 'name';
        $sortsel = FormUtils::create_select([
         'type' => 'drop',
         'name' => 'sort_by',
         'getid' => '',
         'htmlid' => 'sortby',
         'options' => array_flip($arr),
         'selectedvalue' => $sel,
        ]);
    } else {
        $sortsel = ($cset->sort_by) ? $cset->sort_by : 'name';
        if ($cset->sort_asc) {
            $sortsel .= ' ('._ld('controlsets', 'ascorder').')';
        } else {
            $sortsel .= ' ('._ld('controlsets', 'descorder').')';
        }
    }

    // don't care about users' active-state
    // or admin_access=1 if that still exists
    $sql = 'SELECT user_id,first_name,last_name FROM '.CMS_DB_PREFIX.'users WHERE user_id > 1 ORDER BY last_name, first_name';
    $rows = $db->getArray($sql);
    if ($rows) {
        $users = [-1 => _ld('controlsets', 'all_users')];
        foreach ($rows as &$one) {
            $nm = trim($one['first_name'].' '.$one['last_name']);
            if (!$nm) {
                $nm = _ld('controlsets', 'nousername', $one['user_id']);
            }
            $users[$one['user_id']] = $nm;
        }
        unset($one);
        $sel = ($cset->match_users) ? $cset->match_users : [-1];
        if ($pmod) {
            $inusersel = FormUtils::create_select([
             'type' => 'list',
             'name' => 'match_users',
             'getid' => '',
             'htmlid' => 'incusers',
             'multiple' => true,
             'options' => array_flip($users),
             'selectedvalue' => $sel,
            ]);
        } else {
            $arr = array_intersect_key($users, $sel);
            if ($arr) {
                $inusersel = implode(',', $arr);
            } else {
                $inusersel = $users[-1]; // i.e. all
            }
        }
        unset($users[-1]);
        $sel = ($cset->exclude_users) ? $cset->exclude_users : [''];
        if ($pmod) {
            $outusersel = FormUtils::create_select([
             'type' => 'list',
             'name' => 'exclude_users',
             'getid' => '',
             'htmlid' => 'excusers',
             'multiple' => true,
             'options' => array_flip($users),
             'selectedvalue' => $sel,
            ]);
        } else {
            $arr = array_intersect_key($users, $sel);
            $outusersel = implode(',', $arr);
            if (!$outusersel) { $outusersel = _la('none'); }
        }
    } else {
        $inusersel = _ld('controlsets', 'nouser');
        $outusersel = $inusersel;
    }

    // don't care about groups' active-state
    $sql = 'SELECT group_id,group_name FROM `'.CMS_DB_PREFIX.'groups` WHERE group_id > 1 ORDER BY group_name';
    $rows = $db->getAssoc($sql);
    if ($rows) {
        $grps = [-1 => _ld('controlsets', 'all_groups')] + $rows;
        $sel = ($cset->match_groups) ? $cset->match_groups : [-1];
        if ($pmod) {
            $ingrpsel = FormUtils::create_select([
             'type' => 'list',
             'name' => 'match_groups',
             'getid' => '',
             'htmlid' => 'incgrps',
             'multiple' => true,
             'options' => array_flip($grps),
             'selectedvalue' => $sel,
            ]);
        } else {
            $arr = array_intersect_key($grps, $sel);
            if ($arr) {
                $ingrpsel = implode(',', $arr);
            } else {
                $ingrpsel = $grps[-1]; // i.e. all
            }
        }
        unset($grps[-1]);
        $sel = ($cset->exclude_groups) ? $cset->exclude_groups : [''];
        if ($pmod) {
            $outgrpsel = FormUtils::create_select([
             'type' => 'list',
             'name' => 'exclude_groups',
             'getid' => '',
             'htmlid' => 'excgrps',
             'multiple' => true,
             'options' => array_flip($grps),
             'selectedvalue' => $sel,
            ]);
        } else {
            $arr = array_intersect_key($grps, $sel);
            $outgrpsel = implode(',', $arr);
            if (!$outgrpsel) { $outgrpsel = _la('none'); }
        }
    } else {
        $ingrpsel = _ld('controlsets', 'nogroup');
        $outgrpsel = $ingrpsel;
    }

    $inpats = ($cset->match_patterns) ? implode(';', $cset->match_patterns) : '';
    $outpats = ($cset->exclude_patterns) ? implode(';', $cset->exclude_patterns) : '';
    if (!$pmod) {
        if (!$inpats) { $inpats = _la('none'); }
        if (!$outpats) { $outpats = _la('none'); }
    }

    // TODO support more-sophisticated filtering
    // folder-paths to be omitted
    if (0) { // TODO tailor this
        $excludes = [
         CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'tmp',
         CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'phar_installer',
        ];
    } else {
        $ups = Lone::get('Config')['uploads_path'];
        $excludes = array_diff(
            glob(CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'*', GLOB_NOSORT|GLOB_NOESCAPE|GLOB_ONLYDIR),
            [$ups]);
    }

    /* Recursively scan directory $path and any descendants, to generate
     *  ul, li elements representing a folders-tree
     *
     * @param string $path absolute filesystem-path of a folder
     * @param bool $hide optional flag whether to omit 'hidden' folders
     *  (name begins with '.' or '_'). default true
     * @param int $max optional maximum recursion-depth. 0 = unlimited.
     *  default 0
     * @return string
     */
    function rdir_tree(string $path, bool $hide = true, int $max = 0, int $depth = 0) : string
    {
        if (!is_readable($path)) {
            return '';
        }
        $alldirs = glob($path.DIRECTORY_SEPARATOR.'*', GLOB_NOSORT|GLOB_NOESCAPE|GLOB_ONLYDIR);
        if (!$alldirs) {
            return '';
        }
        if ($depth == 0) {
            global $excludes;
            $alldirs = array_diff($alldirs, $excludes);
        }
        // ignore OS-entries
        $n = array_search($path.DIRECTORY_SEPARATOR.'.', $alldirs);
        if ($n !== false) {
            unset($alldirs[$n]);
        }
        $n = array_search($path.DIRECTORY_SEPARATOR.'..', $alldirs);
        if ($n !== false) {
            unset($alldirs[$n]);
        }
        if (!$alldirs) {
            return '';
        }
        natcasesort($alldirs); //TODO mb_ sorting $col = new Collator(TODO) $col->sort($alldirs)

        $tree_content = '';
        foreach ($alldirs as $onedir) {
            $name = basename($onedir);
            //TODO c.f. https://stackoverflow.com/questions/284115/cross-platform-hidden-file-detection
            if ($hide && ($name[0] === '.' || $name[0] === '_')) { //naive !
                continue;
            }
            $tree_content .= '<li>' . $name;
            if ($max == 0 || $depth < $max) {
                $tree_content .= rdir_tree($onedir, $hide, $max, $depth + 1);
            }
            $tree_content .= '</li>';
        }
        if ($tree_content) {
            return '<ul>' . $tree_content . '</ul>';
        }
        return '';
    }

    $tree = rdir_tree(CMS_ROOT_PATH, 3); // TODO tailor this place/depth per context

/*
TODO inline css might be bad for content security policy
in which case
$csm = new CMSMS\StylesMerger();
$csm->queue_string($styles);
$out = $csm->page_content();
*/
    $styles = <<<EOS
<style>
 #treecontainer {
  max-height:15em;
  overflow:auto;
  margin:0;
  padding:0
 }
 #treecontainer ul {
  list-style-type:none;
  margin:0 1em;
  padding:0
 }
 #treecontainer li {
  margin:0;
  cursor:pointer
 }
</style>
EOS;
    add_page_headtext($styles, false);

    //TODO ensure flexbox css for multi-row .colbox, .rowbox.flow, .boxchild
    //TODO fix crappy interactions between single- and double-clicks
    $close = _la('close');
    $sep = DIRECTORY_SEPARATOR;
    $js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 cms_equalWidth($('.boxchild label'));
 $('#selectbtn').on('click', function(e) {
  e.preventDefault();
  var tid = 0,
   dirs = $('#popup').find('li');
  dirs.on('click', function(e) {
   e.stopPropagation();
   var subs = $(e.target).children('ul');
   if (subs.length > 0) {
    tid = setTimeout(function() {
     if (subs[0].offsetWidth > 0 && subs[0].offsetHeight > 0) {
      subs.hide();
     } else {
      subs.show();
     }
    }, 400);
   }
  })
  dirs.on('dblclick', function(e) {
   if (tid !== 0) {
    clearTimeout(tid);
    tid = 0;
   }
   e.stopPropagation();
   e.preventDefault();
   var path,
    nm = e.target.firstChild.nodeValue.trim(),
    segs = [nm];
   $(e.target).parents('li').each(function() {
    nm = this.firstChild.nodeValue.trim();
    segs.unshift(nm);
   });
   segs.shift();
   path = segs.join('$sep');
   $('#reldir').val(path);
   return false;
  });
  $('#popup').find('ul').hide();
  cms_dialog($('#popup'), {
   modal: true,
   buttons: {
    '$close': function() {
     $(this).dialog('close');
     dirs.off('dblclick').off('click');
    }
   },
   width: 'auto'
  });
  return false;
 });
});
//]]>
</script>
EOS;
    add_page_foottext($js);

    $extras = get_secure_param_array();
    $selfurl = basename(__FILE__);
    $props = $cset->getRawData();
    if ($pmod) {
        $extras += [
         'edit' => $props['id'],
        // backup for un-selected checkboxes
         'can_delete' => 0,
         'can_mkdir' => 0,
         'can_mkfile' => 0,
         'can_upload' => 0,
         'show_hidden' => 0,
         'show_thumbs' => 0,
         'sort_asc' => 0,
        ];
    }

    $smarty = Lone::get('Smarty');
    $smarty->assign([
     'selfurl' => $selfurl,
     'extras' => $extras,
     'pmod' => $pmod,
     'cset' => $props,
     'types' => $typesel,
     'sorts' => $sortsel,
     'incusers' => $inusersel,
     'excusers' => $outusersel,
     'incgroups' => $ingrpsel,
     'excgroups' => $outgrpsel,
     'incpatns' => $inpats,
     'excpatns' => $outpats,
     'folders' => $tree,
     'yes' => _la('yes'),
     'no' => _la('no'),
    ]);

    $content = $smarty->fetch('openaxscontrol.tpl');
    require ".{$dsep}header.php";
    echo $content;
    require ".{$dsep}footer.php";
} catch (DataException $e) {
    $themeObject = Utils::get_theme_object();
    $themeObject->ParkNotice('error', $e->GetMessage());
    redirect('listaxscontrols.php'.$urlext);
} catch (Throwable $t) {
    $themeObject = Utils::get_theme_object();
    $themeObject->ParkNotice('error', $t->GetMessage());
    redirect('listaxscontrols.php'.$urlext);
}
