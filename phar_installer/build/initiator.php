#!/usr/bin/env php
<?php

$archname = '%s';
$from = __DIR__.DIRECTORY_SEPARATOR.$archname;
$todir = dirname(__DIR__);
$todir = str_replace('phar://', '', $todir); // TODO ensure this is site-root or a descendant
$todir = strtr($todir, '\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR); // TODO find site-root dir dirname() until ?
$to = $todir.DIRECTORY_SEPARATOR.$archname;
$installer_top = $todir.DIRECTORY_SEPARATOR.'installer';

$rrmdir = function(string $path, bool $keepdirs = false)
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
};

try {
 if (is_dir($installer_top)) {
  $rrmdir($installer_top);
 }
 copy($from, $to);
 $phar = new PharData($to);
 $phar->extractTo($todir);
 unlink($to);
 unset($phar);
 $phar = null;
 @unlink($installer_top.DIRECTORY_SEPARATOR.'README.TXT');
 $from = __DIR__.DIRECTORY_SEPARATOR.'README-PHAR.TXT';
 $to = $installer_top.DIRECTORY_SEPARATOR.'README-PHAR.TXT';
 copy($from, $to);
} catch (Throwable $t) {
 die($t->GetMessage()."\n");
}

$path = Phar::running(false);
unlink($path);

$host = $_SERVER['HTTP_HOST'] ?? '';
$uri = $_SERVER['REQUEST_URI'] ?? '';
if ($host && $uri) {
 $p = strrpos($uri, '/', -1); // ignore initiator.php
 $p = strrpos($uri, '/', $p - strlen($uri) - 1); // ignore phar-file name
 $to = '//'.$host.substr($uri, 0, $p).'/installer/index.php';
 header('Location: '.$to);
}
die("Unable to locate the installer-start-script.<br />You can point your browser to &lt;ROOT URL&gt;/installer/index.php");
