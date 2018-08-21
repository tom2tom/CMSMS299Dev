<?php
/*
TreeFiler module action: defaultadmin
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

if (!function_exists('cmsms')) {
    exit;
}

require_once __DIR__.DIRECTORY_SEPARATOR.'action.getlist.php';

//$xp = (!empty($params['astfiles'])) ? ['astfiles' => 1] : null; //in assets-edit mode

$tpl = $smarty->createTemplate($this->GetTemplateResource('defaultadmin.tpl'),null,null,$smarty);

// breadcrumbs

if ($CFM_RELPATH) {
    $urlparms = ['p'=>''];
//    if ($xp) $urlparms += $xp;
    $u = $this->create_url($id, 'defaultadmin', $returnid, $urlparms);
    //root
    $oneset = new stdClass();
    $oneset->name = $this->Lang('top');
    $oneset->url = $u;
    $items = [$oneset];
    //rest
    $t = '';
    $segs = explode(DIRECTORY_SEPARATOR, $CFM_RELPATH);
    $c = count($segs);
    for ($i=0; $i<$c; ++$i) {
        $oneset = new stdClass();
        $oneset->name = cfm_enc(cfm_convert_win($segs[$i]));
        if ($i > 0) $t .= DIRECTORY_SEPARATOR;
        $t .= $segs[$i];
        $oneset->url = $u.rawurlencode($t);
        $items[] = $oneset;
    }
    $tpl->assign('crumbs', $items);
    $t = dirname($CFM_RELPATH);
    if ($t == '.') {$t = '';} else {$t = rawurlencode($t);}
    $tpl->assign('parent_url', $u.$t);
}

$tpl->assign('crumbjoiner', 'if-angle-double-right'); //or 'if-angle-double-left' for 'rtl'

// permitted operations
//$profile set in 'action.getlist.php'

if ($profile['can_mkfile']) {
    $tpl->assign('pupload', 1);
}
if ($profile['can_mkdir']) {
    $tpl->assign('pmkdir', 1);
}
if ($profile['can_delete']) {
    $tpl->assign('pdel', 1);
}

// folders tree

$tpl->assign('browse', $this->Lang('browse'));
$t = cfm_dir_tree($CFM_ROOTPATH, (($CFM_RELPATH) ? $pathnow : ''));
$tpl->assign('treeview', $t);

// tailor the compression UI

$items = cfm_get_arch_picker($this);
$tpl->assign('archtypes', $items);
if (count($items) > 1) {
    $t = $this->Lang('compress_sel');
}else {
    $t = $this->Lang('compress_typed', reset($items)['label']);
}
$tpl->assign('title_compress', $t);

//$tpl->assign('form_start', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', ['p'=> rawurlencode($CFM_RELPATH)]));
$baseurl = $this->GetModuleURLPath();

// page infrastructure

$urlparms = ['p'=>$CFM_RELPATH];
//if ($xp) $urlparms += $xp;
$u = $this->create_url($id, 'fileaction', $returnid, $urlparms);
$action_url = rawurldecode(str_replace('&amp;', '&', $u).'&cmsjobtype=1');
$urlparms = ['p'=>$CFM_RELPATH, 'ajax'=>1];
//if ($xp) $urlparms += $xp;
$u = $this->create_url($id, 'getlist', $returnid, $urlparms);
$relist_url = rawurldecode(str_replace('&amp;', '&', $u).'&cmsjobtype=1');
$urlparms = ['ajax'=>1];
//if ($xp) $urlparms += $xp;
$u = $this->create_url($id, 'gettree', $returnid, $urlparms);
$retree_url = rawurldecode(str_replace('&amp;', '&', $u).'&cmsjobtype=1');

//TODO $CFM_ROOTPATH
$here = $CFM_RELPATH;

//<link rel="stylesheet" href="{$baseurl}/lib/css/jquery.dm-uploader.css">
$css = <<<EOS
<link rel="stylesheet" href="{$baseurl}/lib/css/module.css">

EOS;
$this->AdminHeaderContent($css);

$p = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR;
$sm = new \CMSMS\ScriptManager();
$sm->queue_file($p.'jquery.metadata.min.js');
$sm->queue_file($p.'jquery.SSsort.min.js');
$sm->queue_file($p.'jquery.treemenu.js'); //OR .min for production
$sm->queue_file($p.'jquery.treefilter.js'); //OR .min for production
$sm->queue_file($p.'jquery.easysearch.js'); //OR .min for production
$sm->queue_file($p.'jquery.dm-uploader.js'); //OR .min for production
$sm->queue_file($p.'jquery.filedrag.js'); //OR .min for production

$fn = $sm->render_scripts();
$u =  \CMSMS\AdminUtils::path_to_url(TMP_CACHE_LOCATION).'/'.$fn;
$js = <<<EOS
<script type="text/javascript" src="{$u}"></script>
<script>
//<![CDATA[

EOS;
$t = file_get_contents($p.'defaultadmin.inc.js');
/* separate files for development/debug
$js = <<<EOS
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.SSsort+metadata.min.js"></script>
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.treemenu.min.js"></script>
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.easysearch.js"></script>
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.dm-uploader.js"></script>
<script>
//<![CDATA[

EOS;
$t = file_get_contents(cms_join_path(__DIR__, 'lib', 'js', 'defaultadmin.inc.js'));
*/
// included js may include variables enclosed in markers '~%' and '%~'.
// like $varname or lang|key or lang|key,param[,param2 ...] Such $varname's must all be 'used' here
$js .= preg_replace_callback('/~%(.+?)%~/', function ($match)
 use ($id, $action_url, $relist_url, $retree_url, $here)
{
 $name = $match[1];
 if ($name[0] == '$') {
    $name = substr($name, 1);
    return $$name;
 } elseif (strncmp($name,'lang|',5) == 0) {
    $name = substr($name, 5);
    if (strpos($name,',') === false) {
       return $this->Lang($name);
    } else {
       $parts = explode(',',$name);
       return $this->Lang(...$parts);
    }
 } else {
    return '';
 }
}, $t);

$js .= <<<EOS
//]]>
</script>

EOS;
$this->AdminBottomContent($js);

$tpl->display();
