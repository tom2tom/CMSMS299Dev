<?php

use CMSMS\LogicException;
use CMSMS\SimplePluginOperations;
use function __installer\get_app;

// 1. Convert UDT's to simple plugins, widen users-table columns
$app = get_app();
$destdir = $app->get_destdir();
$config = $app->get_config();
$assetsdir = ( !empty( $config['assets_path'] ) ) ? $config['assets_path'] : $destdir . DIRECTORY_SEPARATOR . 'assets';

$udt_list = $db->GetArray('SELECT * FROM '.CMS_DB_PREFIX.'userplugins');
if( $udt_list ) {
    if( !$destdir || !is_dir($destdir) ) {
        throw new LogicException('Destination directory does not exist');
    }
    $to = $assetsdir . DIRECTORY_SEPARATOR . 'simple_plugins';
    if( !is_dir( $to ) ) @mkdir( $to, 0775, true );
    if( !is_dir( $to ) ) throw new LogicException("Could not create $to directory");

    $create_simple_plugin = function( array $row, SimplePluginOperations $ops ) {
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

		if( $ops->save($meta, $code) ) {
	        verbose_msg('Converted UDT '.$row['userplugin_name'].' to a plugin file');
		} else {
            verbose_msg('Error saving UDT named '.$row['userplugin_name']);
		}
    };

	$ops = SimplePluginOperations::get_instance();
    foreach( $udt_list as $udt ) {
        $create_simple_plugin( $udt, $ops );
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

// 2. Move ex-core modules to /assets/modules
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

// 3. Tweak callbacks for page and generic layout template types
$page_type = \CMSLayoutTemplateType::load('__CORE__::page');
$page_type_type->set_lang_callback('\\CMSMS\internal\\std_layout_template_callbacks::page_type_lang_callback');
$page_type_type->set_content_callback('\\CMSMS\internal\\std_layout_template_callbacks::reset_page_type_defaults');
$page_type_type->set_help_callback('\\CMSMS\internal\\std_layout_template_callbacks::template_help_callback');
$page_type->save();

$generic_type = \CMSLayoutTemplateType::load('__CORE__::generic');
$generic_type_type->set_lang_callback('\\CMSMS\internal\\std_layout_template_callbacks::generic_type_lang_callback');
$generic_type_type->set_help_callback('\\CMSMS\internal\\std_layout_template_callbacks::template_help_callback');
$page_type->save();
