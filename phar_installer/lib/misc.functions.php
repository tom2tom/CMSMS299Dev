<?php

namespace cms_installer {

use cms_installer\cms_smarty;
use cms_installer\installer_base;
use cms_installer\langtools;
use cms_installer\nlstools;
use Throwable;

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
	return new nlstools();
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
		return langtools::get_instance()->translate(...$args);
	}
	catch( Throwable $t ) {
		return '';
	}
}

} //cms_installer namespace

namespace {

use cms_installer\wizard\wizard;

// functions to generate GUI-installer messages

function verbose_msg(string $str)
{
	$obj = wizard::get_instance()->get_step();
	if( method_exists($obj,'verbose') ) $obj->verbose($str);
}

function status_msg(string $str)
{
	$obj = wizard::get_instance()->get_step();
	if( method_exists($obj,'message') ) $obj->message($str);
}

function error_msg(string $str)
{
	$obj = wizard::get_instance()->get_step();
	if( method_exists($obj,'error') ) $obj->error($str);
}

} //global namespace
