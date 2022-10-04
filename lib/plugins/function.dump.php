<?php
/*
Plugin to display an object in a friendly fashion
Copyright (C) 2012-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

namespace {

use function dump_plugin\dump_array;
use function dump_plugin\dump_object;

function smarty_function_dump($params, $template)
{
	$ignore = ['cms','smarty','db','config','params','param_map','langhash','xml_exclude_files','xmldtd'];

	// get the item name (without any $)
	if( !isset($params['item']) ) return;

	$item = trim($params['item']);
	if( startswith($item,'$') ) $item = substr($item,1);

	// get the base object name.
	$pos1 = strpos($item,'->');
	$pos2 = strpos($item,'-');
	$pos = $pos1;
	$len = 2;

	if( $pos2 < $pos1 && $pos2 !== FALSE ) {
		$pos = $pos2;
		$len = 1;
	}

	$str = substr($item,0,$pos);
	$work = substr($item,$pos+$len);

	// get the base object from smarty.
	$baseobj = $template->getTemplateVars($str);
	$obj = $baseobj;

	$str = '$baseobj';
	$done = false;
//	$tmpobj =& $baseobj->modules['Album'];
	$count = 0;
	while( $done == FALSE ) {
		$count++;
		$pos1 = strpos($work,'->');
		$pos2 = strpos($work,'.');
		if( $pos1 === FALSE ) $pos1 = 1000000;
		if( $pos2 === FALSE ) $pos2 = 1000000;
		$pos = $pos1;
		$len = 2;
		if( $pos2 < $pos1 )	{
			$pos = $pos2;
			$len = 1;
		}
		$tmp = '';
		if( $pos1 == $pos2 && $pos1 == 1000000 ) {
			$tmp = $work;
		}
		elseif( $pos !== FALSE && $pos < 100000 ) {
			$tmp = substr($work,0,$pos);
		}

		if( !empty($tmp) ) {
			if( is_object($obj) ) {
				$str .= '->'.$tmp;
			}
			elseif( is_array($obj) ) {
				$str .= '[\''.$tmp.'\']';
			}
			$work = substr($work,$pos+$len);
			$tmp2 = '$obj =& '.$str.';';
			eval($tmp2);
//			$type = gettype($obj);
			if( $count > 4 ) { $str .= print_r( $obj,TRUE )."\n".'<hr>'; }
		}
		else {
			$done = true;
		}
	}

	$parenttype = gettype($obj);
	$str .= PHP_EOL.'<pre><strong>Dump of: $'.$item;
	$str .= '</strong> ('.ucwords($parenttype).')<br>'.PHP_EOL;

	if( is_object($obj) ) {
		$str .= dump_object($params, $obj, 0, $ignore, $item);
	}
	elseif( is_array($obj) ) {
		$str .= dump_array($params, $obj, 0, $ignore, $item);
	}
	else {
		$str .= $obj.'<br>';
	}
	$str .= '</pre>';
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $str);
		return '';
	}
	return $str;
}

function smarty_cms_about_function_dump()
{
	//TODO announce presence of protected, private object-properties
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Robert Campbell 2012',"<li>$n</li>");
}

function smarty_cms_help_function_dump()
{
	//TODO parameter descriptions
	echo _ld('tags', 'help_generic',
	'This plugin displays an object in a friendly fashion',
	'dump ...',
	'<li>item: </li>
<li>nomethods: </li>
<li>novars: </li>
<li>recurse: </li>
<li>maxlevel: </li>'
	);
//	echo 'See dump_object(), dump_array()';
}


} //namespace

namespace dump_plugin {

use ReflectionObject;
use function CMSMS\specialize;

function build_accessor($parent_str, $parent_type, $childname)
{
	$str = $parent_str;
	if( $parent_type == 'object' ) {
		$str .= '-&gt;';
	}
	elseif( $parent_type == 'array' ) {
		$str .= '.';
	}
	$str .= $childname;
	return $str;
}

function dump_object($params, &$obj, $level = 1, $ignore = [], $accessor)
{
	$maxlevel = 3;
	if( isset($params['maxlevel']) ) {
		$maxlevel = min(max(1, (int)$params['maxlevel']), 10);
	}

	if( $level > $maxlevel ) return '';

	$objname = get_class($obj);
	$str = str_repeat('  ',$level).'Object Name: '.$objname.'<br>';
	$str .= str_repeat('  ',$level).'Parent: '.get_parent_class($obj).'<br>';

	if( !isset($params['nomethods']) ) {
		$methods = get_class_methods($objname);
		if( $methods ) {
			$str .= str_repeat('  ',$level).'Methods: <br>';
			foreach( $methods as $one )	{
				$str .= str_repeat('  ',$level).'- '.$one.'<br>';
			}
		}
	}

	if( !isset($params['novars']) )	{
		$ref = new ReflectionObject($obj);
		$vars = $ref->getProperties();
		if( $vars ) {
			$str .= str_repeat('  ',$level).'Properties: <br>';
			foreach( $vars as $prop ) {
				$name = $prop->getName();
				if( in_array($name,$ignore) ) continue;
				$acc = build_accessor($accessor,'object',$name);

				$value = $prop->getValue($obj);
				$type = gettype($value);
				if( $type == 'object' )	{
					$str .= str_repeat('  ',$level).'- '.'<u>'.$name.': Object</u> <em>{$'.$acc.'}</em><br>';
					if( isset($params['recurse']) )	$str .= dump_object($params,$value,$level+1,$ignore,$acc);
				}
				elseif( $type == 'array' ) {
					$str .= str_repeat('  ',$level).'- '.'<u>'.$name.': Array ('.count($value).')</u> <em>{$'.$acc.'}</em><br>';
					if( isset($params['recurse']) )	$str .= dump_array($params,$value,$level+1,$ignore,$acc);
				}
				elseif( $type == 'NULL' ) {
					$str .= str_repeat('  ',$level).'- '.$name.': NULL <em>{$'.$acc.'}</em><br>';
				}
				else {
					$str .= str_repeat('  ',$level).'- '.$name.' = '. specialize($value).' <em>{$'.$acc.'}</em><br>';
				}
			}
		}
	}
	return $str;
}

function dump_array($params, &$data, $level = 1, $ignore = [], $accessor)
{
	$maxlevel = 3;
	if( isset($params['maxlevel']) ) {
		$maxlevel = min(max(1, (int)$params['maxlevel']), 10);
	}

	if( $level > $maxlevel ) return '';
	$str = '';

	foreach( $data as $key => $value ) {
		$acc = build_accessor($accessor,'array',$key);
		$type = gettype($value);
		if( is_object($value) )	{
			$str .= str_repeat('  ',$level).'- <u>'.$key.' = Object</u> <em>{$'.$acc.'}</em><br>';
			if( isset($params['recurse']) )	$str .= dump_object($params,$value,$level+1,$ignore,$acc);
		}
		elseif( is_array($value) )	{
			$str .= str_repeat('  ',$level)."- <u>$key = Array (".count($value).')</u> <em>{$'.$acc.'}</em><br>';
			if( isset($params['recurse']) )	$str .= dump_array($params,$value,$level+1,$ignore,$acc);
		}
		elseif( $type == 'NULL' ) {
			$str .= str_repeat('  ',$level).'- '.$name.': NULL <em>{$'.$acc.'\}</em><br>';
		}
		else {
			$str .= str_repeat('  ',$level)."- $key = ". specialize($value).' {$'.$acc.'}<br>';
		}
	}
	return $str;
}

} // namespace
