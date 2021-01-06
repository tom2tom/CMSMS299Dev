<?php
/**
 * Smarty date_format modifier plugin
 *
 * Type:     modifier<br>
 * Name:     cms_date_format<br>
 * Purpose:  format datestamps via strftime<br>
 * Input:<br>
 *          - string: input date string
 *          - format: strftime format for output
 *          - default_date: default date if $string is empty
 *
 * @link http://www.smarty.net/manual/en/language.modifier.date.format.php date_format (Smarty online manual)
 * @author Monte Ohrt <monte at ohrt dot com>
 * @param string $string       input date/time, a UNIX timestamp or other format supported by PHP strtotime()
 * @param string $format       strftime format for output
 * @param string $default_date default date if $string is empty
 * @return string | void
 *
 * Modified by Tapio Löytty <stikki@cmsmadesimple.org>
 */

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\UserParams;

function smarty_modifier_cms_date_format($string, $format = '', $default_date = '')
{
	$fn = cms_join_path(SMARTY_PLUGINS_DIR, 'modifier.date_format.php');
	if (!is_file($fn)) exit;

	if ($format == '') {
		$format = AppParams::get('defaultdateformat');
		if (!AppSingle::App()->is_frontend_request()) {
			$uid = get_userid(false);
			if ($uid) {
				$tmp = UserParams::get_for_user($uid, 'date_format_string');
				if ($tmp) $format = $tmp;
			}
		}
		if ($format == '') $format = '%b %e, %Y';
	}

	require_once $fn;

	return smarty_modifier_date_format($string, $format, $default_date);
}
/*
function smarty_cms_about_modifier_cms_date_format()
{
	echo lang_by_realm('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
function smarty_cms_help_modifier_cms_date_format()
{
	echo lang_by_realm('tags', 'help_generic2',
	'This plugin converts a date-time value (timestamp or string) to the format used for displaying such values on this website',
	'$datevar|cms_date_format',
	<<<'EOS'
<li>first, optional strftime() format to use instead of the CMSMS format-setting<li>
<li>second, optional default date to use if the $datevar is empty<li>
EOS
	);
}
