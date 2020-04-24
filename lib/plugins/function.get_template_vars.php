<?php
#Plugin to display all template variables in a pretty format
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace get_template_vars {

use LogicException;

	/**
	 * @param mixed $ptype the parent type
	 * @param mixed $key sting|number the current key we are trying to output
	 * @param int $depth recursion depth, internal use only
	 * @return string
	 * @throws LogicException
	 */
	function _cms_output_accessor($ptype,$key,$depth)
	{
		if( $depth == 0 ) return "\${$key}";
		switch( strtolower($ptype) ) {
		case 'object':
			return "-&gt;{$key}";

		case 'array':
			if( is_numeric($key) ) return "[{$key}]";
			if( strpos($key,' ') !== FALSE ) return "['{$key}']";
			return ".{$key}";

		default:
			// should not get here....
			throw new LogicException('Invalid accessor type');
		}
	}

	/**
	 * Output html similar to json, but with type information and indentation
	 * @param string $key
	 * @param mixed $val
	 * @param mixed $ptype
	 * @param int $depth
	 * @return string
	 */
	function _cms_output_var($key,$val,$ptype = null,$depth = 0)
	{
		$type = gettype($val);
		$out = '';
		$depth_str = '&nbsp;&nbsp;&nbsp;';
		$acc = _cms_output_accessor($ptype,$key,$depth);
		if( is_object($val) ) {
			$o_items = get_object_vars($val);

			$out .= str_repeat($depth_str,$depth);
			$out .= "{$acc} <em>(object of type: ".get_class($val).')</em> = {';
			if( $o_items ) $out .= '<br />';
			foreach( $o_items as $o_key => $o_val ) {
				$out .= _cms_output_var($o_key,$o_val,$type,$depth+1);
			}
			$out .= str_repeat($depth_str,$depth).'}<br />';
		}
		elseif( is_array($val) ) {
			$out .= str_repeat($depth_str,$depth);
			$out .= "{$acc} <em>($type)</em> = [<br />";
			foreach( $val as $a_key => $a_val ) {
				$out .= _cms_output_var($a_key,$a_val,$type,$depth+1);
			}
			$out .= str_repeat($depth_str,$depth).']<br />';
		}
		elseif( is_callable($val) ) {
			$out .= str_repeat($depth_str,$depth)."{$acc} <em>($type)</em> = callable<br />";
		}
		else {
			$out .= str_repeat($depth_str,$depth);
			if( $depth == 0 ) {
				$out .= '$'.$key;
			}
			else {
				$out .= '.'.$key;
			}
			$out .= " <em>($type)</em> = $val<br />";
		}
		return $out;
	}
} //namespace

namespace {

use function get_template_vars\_cms_output_var;

function smarty_function_get_template_vars($params, $template)
{
	$tpl_vars = $template->getTemplateVars();
	$str = '<pre>';
	foreach( $tpl_vars as $key => $value ) {
		$str .= _cms_output_var($key,$value);
	}
	$str .= '</pre>';
	if( isset($params['assign']) ){
		$template->assign(trim($params['assign']),$str);
		return;
	}
	return $str;
}

function smarty_cms_about_function_get_template_vars()
{
	echo <<<'EOS'
<p>Author: Robert Campbell &lt;calguy1000@cmsmadesimple.org&gt;</p>
<p>Version: 1.0</p>
<p>
Change History:<br />
None
</p>
EOS;
}

} //namespace
