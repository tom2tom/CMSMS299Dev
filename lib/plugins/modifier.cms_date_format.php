<?php
/**
 * Smarty date_format modifier plugin
 *
 * Type:     modifier<br>
 * Name:     cms_date_format<br>
 * Purpose:  format a supplied date-time string using PHP date()<br>
 * Input:<br>
 *          - string: input date string
 *          - format: strftime()-compatible or date()-compatible format for output
 *          - default_date: default date if $string is empty
 *
 * @link http://www.smarty.net/manual/en/language.modifier.date.format.php date_format (Smarty online manual)
 * @author Monte Ohrt <monte at ohrt dot com>
 * @param string $string       input date/time, a UNIX timestamp or other format supported by PHP strtotime()
 * @param string $format       strftime- or date-format for output
 * @param string $default_date default date if $string is empty
 * @return string | void
 *
 * Modified by Tapio Löytty <stikki@cmsmadesimple.org>
 */

use CMSMS\AppParams;
use CMSMS\SingleItem;
use CMSMS\UserParams;
use CMSMS\Utils;
use function CMSMS\specialize;

function smarty_modifier_cms_date_format($string, $format = '', $default_date = '')
{
	$fn = cms_join_path(SMARTY_PLUGINS_DIR, 'modifier.date_format.php');
	if (!is_file($fn)) exit;

	if (strpos($format, 'timed') !== false) {
		$withtime = true;
		$format = trim(str_replace(['timed', '  '], ['', ' '], $format));
	} else {
		$withtime = false;
	}
	if (!$format) {
		if (!SingleItem::App()->is_frontend_request()) {
			$userid = get_userid(false);
			if ($userid) {
				$format = UserParams::get_for_user($userid, 'date_format');
			}
		}
		if (!$format) {
			$format = AppParams::get('date_format', 'j F, Y');
		}
	} elseif (strpos($format, '%') !== false) {
		$format = Utils::convert_dt_format($format);
	}
	if ($withtime) {
		$format = preg_replace('/[aABgGhHisuv]/', '', $format);
		if (strpos($format, '-') !== false || strpos($format, '/') !== false) {
			$format .= ' H:i';
		} else {
			$format .= ' g:i a';
		}
	}

	require_once $fn;

	$out = smarty_modifier_date_format($string, $format, $default_date);
	return specialize($out);
}
/*
function smarty_cms_about_modifier_cms_date_format()
{
	echo _ld('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
function smarty_cms_help_modifier_cms_date_format()
{
	echo _ld('tags', 'help_generic2',
	'This plugin converts a date-time value (timestamp or string) to the format used for displaying such values on this website',
	'$datevar|cms_date_format',
	<<<'EOS'
<li>first, optional date()-compatible format to use instead of the system default format. It may be, or include, the special-case 'timed'.<li>
<li>second, optional default date to use if the $datevar is empty<li>
EOS
	);
}
