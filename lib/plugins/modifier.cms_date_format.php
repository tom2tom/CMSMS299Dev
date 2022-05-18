<?php
/**
 * Smarty plugin
 * Type:    modifier
 * Name:    cms_date_format
 * Purpose: format a supplied date-time string using PHP date() or strftime()-replacment
 * Input:
 *          - mixed:  input date
 *          - format: strftime()- or date()-compatible format for output
 *          - default_date: default date if input is empty
 *
 * @link http://www.smarty.net/manual/en/language.modifier.date.format.php date_format (Smarty online manual)
 * @author Monte Ohrt <monte at ohrt dot com>
 * @param mixed $datevar      date/time to be processed, a UNIX timestamp or other format supported by PHP strtotime()
 * @param string $format      optional strftime()- or date()-compatible output-format, to override system/user preference
 * @param mixed $default_date optional default date/time if $datevar is empty
 * @return string
 */

use CMSMS\AppParams;
use CMSMS\UserParams;
use CMSMS\Utils;
use function CMSMS\is_frontend_request;
use function CMSMS\specialize;

function smarty_modifier_cms_date_format($datevar, $format = '', $default_date = '')
{
	if (strpos($format, 'timed') !== false) {
		$xt = true;
		$format = str_replace(['timed', '  '], ['', ' '], $format);
	} else {
		$xt = false;
	}

	if (!$format) {
		if (!is_frontend_request()) {
			$userid = get_userid(false);
			if ($userid) {
				$format = UserParams::get_for_user($userid, 'date_format');
			}
		}
		if (!$format) {
			$format = AppParams::get('date_format', 'j F, Y');
		}
	}
	if ($xt) {
		//ensure time is displayed
		if (strpos($format, '%') !== false) {
			if (!preg_match('/%[HIklMpPrRSTXzZ]/', $format)) {
				if (strpos($format, '-') !== false || strpos($format, '/') !== false) {
					$format .= ' %k:%M';
				} else {
					$format .= ' %l:%M %P';
				}
			}
		} elseif (!preg_match('/(?<!\\\\)[aABgGhHisuv]/', $format)) {
			if (strpos($format, '-') !== false || strpos($format, '/') !== false) {
				$format .= ' H:i';
			} else {
				$format .= ' g:i a';
			}
		}
	}

	if (strpos($format, '%') !== false) {
		$out = Utils::dt_format($datevar, $format, $default_date);
	} else {
		$fn = cms_join_path(SMARTY_PLUGINS_DIR, 'modifier.date_format.php');
		if (!is_file($fn)) exit;
		include_once $fn;
		$out = smarty_modifier_date_format($datevar, $format, $default_date);
	}
	return specialize($out);
}

function smarty_cms_about_modifier_cms_date_format()
{
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004',
	'<li>Mar 2022<ul>
 <li>Use site setting \'date_format\' if no \'format\' parameter is supplied</li>
 <li>Support \'timed\' in the format parameter</li>
 <li>If appropriate, generate output using replacement for deprecated strftime()</li>
</ul></li>'
	);
}

function smarty_cms_help_modifier_cms_date_format()
{
	echo _ld('tags', 'help_generic2',
	'This plugin converts a date-time value (timestamp or string or DateTime) to the format used for displaying such values on this website',
	'$datevar|cms_date_format',
	'<li>first, optional strftime()- and/or date()-compatible format to use instead of the system default format. It may be, or include, the special-case \'timed\'.<li>
<li>second, optional default (timestamp|string|DateTime object) to use if the $datevar is empty<li>'
	);
}
