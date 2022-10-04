<?php
/**
 * Smarty plugin
 * Type:     modifier
 * Name:     localedate_format
 * Purpose:  format date/time values
 *
 * @param mixed $datevar      input date-time string | timestamp | DateTime object
 * @param string $format      optional strftime()- and/or date()-compatible format for output. Default '%b %e, %Y'
 * @param mixed $default_date optional date-time to use if $datevar is empty. Default ''
 *
 * @return string
 */
use CMSMS\Utils;

function smarty_modifier_localedate_format($datevar, $format = '%b %e, %Y', $default_date = '')
{
    return Utils::dt_format($datevar, $format, $default_date);
}

function smarty_cms_about_modifier_localedate_format()
{
    $n = _la('none');
    echo _ld('tags', 'about_generic', 'Feb 2022', "<li>$n</li>");
}

function smarty_cms_help_modifier_localedate_format()
{
    echo _ld('tags', 'help_generic2',
    'This is an alternate to Smarty\'s date_format modifier. Same API and functionality but without using deprecated PHP strftime().<br>It converts a date-time value (timestamp or string or DateTime object) to the format specified',
    '$datevar|localedate_format',
    '<li>first, optional strftime()- and/or date()-compatible format to use instead of the default (which is \'%e %b, %Y\')</li>
<li>second, optional default (timestamp | string | DateTime object) to use if $datevar is empty</li>'
    );
    echo 'See also: <a href="https://www.smarty.net/docs/en/language.modifier.date.format" target="_blank">Smarty date_format</a>';
}
