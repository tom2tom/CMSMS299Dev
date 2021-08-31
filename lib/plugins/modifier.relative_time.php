<?php
/**
 * Smarty relative date / time plugin
 *
 * Type:     modifier<br>
 * Name:     relative_time<br>
 * Date:     March 18, 2009
 * Purpose:  converts the specified date/time to a date/time relative to now (i.e.PHP time())
 * Input:    a UNIX timestamp or other format supported by PHP strtotime()
 * Example:  {$datetime|relative_datetime}
 * @author   Eric Lamb <eric@ericlamb.net>
 * @version 1.0
 * @param string
 * @return string
 */

/*
 * This modifier modified by Robert Campbell to be compatible with CMSMS.
 */
function smarty_modifier_relative_time($timestamp)
{
    if(!$timestamp) return '';

    if(!is_int($timestamp)) {
        $timestamp = (int)strtotime($timestamp);
        if($timestamp === false) return '';
    }

    $difference = time() - $timestamp;

    if($difference > 2678400 || $difference < -2678400) { //1-month threshold for relative report
        $fn = __DIR__.DIRECTORY_SEPARATOR.'modifier.cms_date_format.php';
        if(is_file($fn)) {
            require_once $fn;
            return smarty_modifier_cms_date_format($timestamp);
        }
    }

    $periods = ['sec', 'min', 'hour', 'day', 'week','month', 'year', 'decade'];
    $lengths = ['60','60','24','7','4.35','12','10'];
    $total_lengths = count($lengths);

    if ($difference > 0) { // this was in the past
        $ending = lang('period_ago');
    } else { // this was in the future
        $difference = -$difference;
        $ending = lang('period_fromnow');
    }

    for( $j = 0; $j < $total_lengths && $difference > $lengths[$j]; $j++ ) {
         $difference /= $lengths[$j];
    }

    $period = $periods[$j];
    $difference = (int)round($difference);
    if($difference != 1) {
        $period .= 's';
    }

    $period = lang('period_'.$period);
    $text = lang('period_fmt',$difference,$period,$ending);

    return $text;
}
/*
function smarty_cms_about_modifier_relative_time()
{
	echo lang_by_realm('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
function smarty_cms_help_modifier_relative_time()
{
	$n = lang('none');
	echo lang_by_realm('tags', 'help_generic2',
	'This plugin converts a date-time value to a corresponding low-resolution description relative to the current time',
	'$datevar|relative_time',
	"<li>$n</li>"
	);
}
