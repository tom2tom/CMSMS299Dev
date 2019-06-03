#!/usr/bin/env php
<?php

function joinpath(...$segments)
{
	$path = implode(DIRECTORY_SEPARATOR, $segments);
	return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
}

function rrmdir($path, $keepdirs = false)
{
	if (is_dir($path)) {
		$res = true;
		$iter = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path,
				FilesystemIterator::CURRENT_AS_PATHNAME |
				FilesystemIterator::SKIP_DOTS
			),
			RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($iter as $p) {
			if (is_dir($p)) {
				if ($keepdirs || !@rmdir($p)) {
					$res = false;
				}
			} elseif (!@unlink($p)) {
				$res = false;
			}
		}
		if ($res && !$keepdirs) {
			$res = @rmdir($path);
		}
		return $res;
	}
	return false;
}

function current_root($dir)
{
	while ($dir !== '.' && !is_dir(joinpath($dir, 'admin')) && !is_dir(joinpath($dir, 'phar_installer'))) {
		$dir = dirname($dir);
	}
	return $dir;
}

$phardir = dirname(__DIR__); //parent, a.k.a. phar_installer
$srcdir = current_root(__DIR__); //ancestor of this script's place

$tmpdir = joinpath($phardir, 'source'); //place for sources to go into data.tar.gz
rrmdir($tmpdir);
$datadir = joinpath($phardir, 'data'); //place for data.tar.gz etc
rrmdir($datadir);
$outdir = joinpath($phardir, 'out'); //place for script results/output
rrmdir($outdir, true);
