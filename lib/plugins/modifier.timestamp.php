<?php
/**
 * Smarty date_format modifier plugin
 *
 * Type:     modifier<br>
 * Name:     cms_timestamp<br>
 * Purpose:  convert a date/time string (typically from a database datetime field) to a UNIX UTC timestamp<br>
 * Input:<br>
 *          - string: input date string
 */
function smarty_modifier_timestamp($string)
{
	return cms_to_stamp($string);
}
