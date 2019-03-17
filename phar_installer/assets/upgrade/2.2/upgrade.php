<?php

use function cms_installer\get_app;
use function cms_installer\joinpath;
use function cms_installer\lang;

function create_private_dir(string $relative_dir)
{
    $relative_dir = trim($relative_dir);
    if( !$relative_dir ) return;

    $destdir = get_app()->get_destdir();
    $dir = joinpath($destdir,$relative_dir);
    if( !is_dir($dir) ) {
        @mkdir($dir,0771,true);
    }
    @touch($dir.DIRECTORY_SEPARATOR.'index.html');
}

function move_directory_files(string $srcdir,string $destdir)
{
    $srcdir = trim($srcdir);
    if( !is_dir($srcdir) ) return;

    $files = glob($srcdir.DIRECTORY_SEPARATOR.'*');
    if( !$files ) return;

    $destdir = trim($destdir);
    foreach( $files as $src ) {
        $bn = basename($src);
        $dest = $destdir.DIRECTORY_SEPARATOR.$bn;
        rename($src,$dest);
    }
    @touch($destdir.DIRECTORY_SEPARATOR.'index.html');
}

status_msg('Performing structure changes for CMSMS 2.2');

//$gCms = cmsms();
$dbdict = GetDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME,'help_content_cb C(255), one_only I1');
$dbdict->ExecuteSQLArray($sqlarray);

verbose_msg(lang('upgrading_schema',202));
$query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 202';
$db->Execute($query);

$type = CmsLayoutTemplateType::load('__CORE__::page');
$type->set_help_callback('CmsTemplateResource::template_help_callback');
$type->save();

$type = CmsLayoutTemplateType::load('__CORE__::generic');
$type->set_help_callback('CmsTemplateResource::template_help_callback');
$type->save();

// create the assets directory structure
verbose_msg('Creating assets structure');
create_private_dir('assets/templates');
create_private_dir('assets/configs');
create_private_dir('assets/module_custom');
create_private_dir('assets/admin_custom');
create_private_dir('assets/plugins');
create_private_dir('assets/images');
create_private_dir('assets/css');
$destdir = get_app()->get_destdir();
$srcdir = $destdir.'/module_custom';
if( is_dir($srcdir) ) {
    move_directory_files($srcdir,$destdir.'/assets/module_custom');
}
$srcdir = $destdir.'/admin/custom';
if( is_dir($srcdir) ) {
    move_directory_files($srcdir,$destdir.'/assets/admin_custom');
}
$srcdir = $destdir.'/tmp/configs';
if( is_dir($srcdir) ) {
    move_directory_files($srcdir,$destdir.'/assets/configs');
}
$srcdir = $destdir.'/tmp/templates';
if( is_dir($srcdir) ) {
    move_directory_files($srcdir,$destdir.'/assets/templates');
}
