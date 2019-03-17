<?php

use cms_installer\cms_smarty;
use cms_installer\langtools;
use cms_installer\nlstools;
use cms_installer\wizard\wizard;

namespace cms_installer {

/**
 * @return installer_base object
 */
function get_app()
{
	return installer_base::get_instance();
}

/**
 * @return cms_smarty object, a Smarty subclass
 */
function smarty()
{
	return cms_smarty::get_instance();
}

/**
 * @return nlstools object
 */
function nls()
{
	return nlstools::get_instance();
}

/**
 * @return langtools object
 */
function translator()
{
	return langtools::get_instance();
}

function startswith(string $haystack, string $needle) : bool
{
	return (strncmp($haystack,$needle,strlen($needle)) == 0);
}

function endswith(string $haystack, string $needle) : bool
{
	$o = strlen( $needle );
	if( $o > 0 && $o <= strlen($haystack) ) {
		return strpos($haystack, $needle, -$o) !== false;
	}
	return false;
}

function joinpath(...$args) : string
{
	if (is_array($args[0])) {
		$args = $args[0];
	}
	$path = implode(DIRECTORY_SEPARATOR, $args);
	return str_replace(['\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR],
		 [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $path);
}

function lang(...$args)
{
	try {
		return langtools::get_instance()->translate($args);
	}
	catch( Exception $e ) {
		return '';
	}
}

} //namespace

namespace {

function verbose_msg(string $str) : string
{
	$obj = wizard::get_instance()->get_step();
	if( method_exists($obj,'verbose') ) return $obj->verbose($str);
	return '';
}

function status_msg(string $str) : string
{
	$obj = wizard::get_instance()->get_step();
	if( method_exists($obj,'message') ) return $obj->message($str);
	return '';
}

function error_msg(string $str) : string
{
	$obj = wizard::get_instance()->get_step();
	if( method_exists($obj,'error') ) return $obj->error($str);
	return '';
}

} //namespace
