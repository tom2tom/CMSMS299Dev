<?php
namespace CMSMS\internal;

use CMSMS\TemplateType;
use const CMS_ADMIN_PATH;
use function cms_join_path;
use function lang;

final class std_layout_template_callbacks
{
	private function __construct() {}
	private function __clone() {}

	public static function page_type_lang_callback($key)
	{
		if( $key == TemplateType::CORE ) return 'Core';
		return lang($key);
	}

	public static function generic_type_lang_callback($key)
	{
		if( $key == TemplateType::CORE ) return 'Core';
		return lang($key);
	}

	public static function reset_page_type_defaults()
	{
		$file = cms_join_path(CMS_ADMIN_PATH,'templates','orig_page_template.tpl');
		$contents = '';
		if( is_file($file) ) $contents = @file_get_contents($file);
		return $contents;
	}

	public static function template_help_callback($typename)
	{
		$typename = trim($typename);
		if( $typename == 'generic' ) return;
		$key = 'tplhelp_'.$typename;
		return lang($key);
	}
} // class
