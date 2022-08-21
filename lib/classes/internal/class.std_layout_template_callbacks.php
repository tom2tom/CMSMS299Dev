<?php
namespace CMSMS\internal;

use CMSMS\TemplateType;
use const CMS_ADMIN_PATH;
use function cms_join_path;
use function lang;

final class std_layout_template_callbacks
{
//	private function __construct() {}
	#[\ReturnTypeWillChange]
	private function __clone() {}// : void {}

	public static function tpltype_lang_callback($key)
	{
		if( $key == TemplateType::CORE ) return 'Core';
		return lang($key);
	}

	public static function generic_type_lang_callback($key)
	{
		if( $key == TemplateType::CORE ) return 'Core';
		return lang($key);
	}

	public static function reset_tpltype_default()
	{
		$file = cms_join_path(CMS_ADMIN_PATH,'layouts','orig_page_template.tpl');
		if( is_file($file) ) {
			return @file_get_contents($file);
		}
		return '';
	}

	public static function tpltype_help_callback($typename)
	{
		$typename = trim($typename);
		if( $typename == 'generic' ) {
			return '';
		}
		$key = 'tplhelp_'.$typename;
		return lang($key);
	}
} // class
