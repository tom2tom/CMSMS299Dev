<?php
/**
 * Smarty date_format modifier plugin
 *
 * Type:     modifier
 * Name:     timestamp
 * Purpose:  convert a date/time string (typically from a database datetime field) to a UNIX UTC timestamp
 * Input:
 *          - string: input date string
 */
function smarty_modifier_timestamp($string)
{
	return cms_to_stamp($string);
}
/*
function smarty_cms_about_modifier_timestamp()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', '2021', "<li>$n</li>");
}
*/
function smarty_cms_help_modifier_timestamp()
{
	$n = _la('none');
	echo _ld('tags', 'help_generic2',
	'This plugin converts a date-time value (string) to a corresponding *NIX UTC timestamp',
	'$datevalue|timestamp',
	"<li>$n</li>"
	);
}