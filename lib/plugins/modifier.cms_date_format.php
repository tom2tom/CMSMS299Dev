<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifier
 */

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
function smarty_modifier_cms_date_format($string, $format = '', $default_date = '')
{
	$fn = cms_join_path(SMARTY_PLUGINS_DIR, 'modifier.date_format.php');
	if(!is_file($fn)) die();

	if($format == '') {
		$format = cms_siteprefs::get('defaultdateformat');
		if(!CmsApp::get_instance()->is_frontend_request()) {
			if($uid = get_userid(false)) {
				$tmp = cms_userprefs::get_for_user($uid, 'date_format_string');
				if($tmp != '') $format = $tmp;
			}
		}
		if($format == '') $format = '%b %e, %Y';
	}

	require_once $fn;

	return smarty_modifier_date_format($string,$format,$default_date);
}
