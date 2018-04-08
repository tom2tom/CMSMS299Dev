<?php
// 1. Convert UDT's to simple plugins, widen users-table columns
$app = \__appbase\get_app();
$destdir = $app->get_destdir();
$config = $app->get_config();
$assetsdir = ( !empty( $config['assets_path'] ) ) ? $config['assets_path'] : $destdir . DIRECTORY_SEPARATOR . 'assets';

$udt_list = $db->GetArray('SELECT * FROM '.CMS_DB_PREFIX.'userplugins');
if( $udt_list ) {
    if( !$destdir || !is_dir($destdir) ) {
        throw new \LogicException('Destination directory does not exist');
    }
    $to = $assetsdir . DIRECTORY_SEPARATOR . 'simple_plugins';
    if( !is_dir( $to ) ) @mkdir( $to, 0775, true );
    if( !is_dir( $to ) ) throw new \LogicException("Could not create $to directory");

    $create_simple_plugin = function( array $row, string $destdir ) {
        $fn = $destdir . DIRECTORY_SEPARATOR . $row['userplugin_name'];
        if( is_file($fn) ) {
            verbose_msg('simple plugin with name '.$row['userplugin_name'].' already exists');
            return;
        }

		$code = preg_replace(
				['/^[\s\r\n]*<\\?php\s*[\r\n]*/i', '/[\s\r\n]*\\?>[\s\r\n]*$/'],
				['', ''], $row['code']);
		if ( !$code ) {
            verbose_msg('UDT named '.$row['userplugin_name'].' is empty, and will be discarded');
			return;
		}

		$out = "<?php\n";
        if( $row['description'] ) $out .= "/*\n" . trim($row['description']) . "\n*/\n";
		$out .= $code . "\n";

        file_put_contents($fn, $out);
        verbose_msg('Converted UDT '.$row['userplugin_name'].' to a simple plugin');
    };
    foreach( $udt_list as $udt ) {
        $create_simple_plugin( $udt, $to );
    }

    $dict = NewDataDictionary($db);
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
    $fr = $destdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
    if( is_dir( $fr ) ) {
        $to = $assetsdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
        if( !is_dir( $to ) ) {
            rename( $fr, $to );
        } else {
            unlink( $fr );
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
