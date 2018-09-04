<?php

use CMSMS\LogicException;
use CMSMS\SimplePluginOperations;
use function __installer\get_app;

$app = get_app();
$destdir = $app->get_destdir();
if( !$destdir || !is_dir($destdir) ) {
    throw new LogicException('Destination directory does not exist');
}
$config = $app->get_config();
$s = ( !empty( $config['admin_dir'] ) ) ? $config['admin_dir'] : 'admin';
$admindir = $destdir . DIRECTORY_SEPARATOR . $s;
$assetsdir = ( !empty( $config['assets_path'] ) ) ? $config['assets_path'] : $destdir . DIRECTORY_SEPARATOR . 'assets';

// 1. Create new folders, if necessary
$dirs = [
['admin','configs'],
['assets','admin_custom'],
['assets','configs'],
['assets','css'],
['assets','images'],
['assets','module_custom'],
['assets','modules'],
['assets','plugins'],
['assets','simple_plugins'],
['assets','templates'],
];

foreach ($dirs as $segs) {
    switch($segs[0]) {
        case 'admin':
            $to = $admindir . DIRECTORY_SEPARATOR . $segs[1];
            break;
        case 'assets':
            $to = $assetsdir . DIRECTORY_SEPARATOR . $segs[1];
            break;
        default:
            break 2;
    }
    if( !is_dir( $to ) ) @mkdir( $to, 0771, true );
    if( !is_dir( $to ) ) throw new LogicException("Could not create $to directory");
    touch($to . DIRECTORY_SEPARATOR . 'index.html');
}
touch($assetsdir . DIRECTORY_SEPARATOR . 'index.html');

// 2. Convert UDT's to simple plugins, widen users-table columns
$udt_list = $db->GetArray('SELECT * FROM '.CMS_DB_PREFIX.'userplugins');
if( $udt_list ) {

    $create_simple_plugin = function( array $row, SimplePluginOperations $ops, $smarty ) {
        $fp = $ops->file_path($row['userplugin_name']);
        if( is_file( $fp ) ) {
            verbose_msg('simple plugin named '.$row['userplugin_name'].' already exists');
            return;
        }

        $code = preg_replace(
                ['/^[\s\r\n]*<\\?php\s*[\r\n]*/i', '/[\s\r\n]*\\?>[\s\r\n]*$/'],
                ['', ''], $row['code']);
        if ( !$code ) {
            verbose_msg('UDT named '.$row['userplugin_name'].' is empty, and will be discarded');
            return;
        }

        $meta = ['name'=>$row['userplugin_name']];
        if( $row['description'] ) {
            $desc = trim($row['description'], " \t\n\r");
            if( $desc ) {
                $meta['description'] = $desc;
            }
        }

        if( $ops->save($row['userplugin_name'], $meta, $code, $smarty) ) {
            verbose_msg('Converted UDT '.$row['userplugin_name'].' to a plugin file');
        } else {
            verbose_msg('Error saving UDT named '.$row['userplugin_name']);
        }
    };

    $ops = SimplePluginOperations::get_instance();
	//$smarty defined upstream, used downstream
    foreach( $udt_list as $udt ) {
        $create_simple_plugin( $udt, $ops, $smarty );
    }

    $dict = GetDataDictionary($db);
    $sqlarr = $dict->DropTableSQL(CMS_DB_PREFIX.'userplugins_seq');
    $dict->ExecuteSQLArray($sqlarr);
    $sqlarr = $dict->DropTableSQL(CMS_DB_PREFIX.'userplugins');
    $dict->ExecuteSQLArray($sqlarr);
    status_msg('Converted User Defined Tags to simple-plugin files');

    $db->Execute( 'ALTER TABLE '.CMS_DB_PREFIX.'users MODIFY username VARCHAR(80)' );
    $db->Execute( 'ALTER TABLE '.CMS_DB_PREFIX.'users MODIFY password VARCHAR(128)' );

    verbose_msg(ilang('upgrading_schema',204));
    $query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 204';
    $db->Execute($query);
}

// 3. Move ex-core modules to /assets/modules
foreach( ['MenuManager', 'CMSMailer'] as $modname ) {
    $fp = $destdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
    if( is_dir( $fp ) ) {
        $to = $assetsdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
        if( !is_dir( $to ) ) {
            rename( $fp, $to );
        } else {
            unlink( $fp );
        }
    }
}

// 4. Tweak callbacks for page and generic layout template types
$page_type = CmsLayoutTemplateType::load('__CORE__::page');
if( $page_type ) {
    $page_type->set_lang_callback('\\CMSMS\\internal\\std_layout_template_callbacks::page_type_lang_callback');
    $page_type->set_content_callback('\\CMSMS\\internal\\std_layout_template_callbacks::reset_page_type_defaults');
    $page_type->set_help_callback('\\CMSMS\\internal\\std_layout_template_callbacks::template_help_callback');
    $page_type->save();
} else {
    error_msg('__CORE__::page template update '.ilang('failed'));
}

$generic_type = CmsLayoutTemplateType::load('__CORE__::generic');
if( $generic_type ) {
    $generic_type->set_lang_callback('\\CMSMS\\internal\\std_layout_template_callbacks::generic_type_lang_callback');
    $generic_type->set_help_callback('\\CMSMS\\internal\\std_layout_template_callbacks::template_help_callback');
    $generic_type->save();
} else {
    error_msg('__CORE__::generic template update '.ilang('failed'));
}
