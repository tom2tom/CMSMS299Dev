<?php

namespace __installer\CMSMS;

function startswith(string $haystack, string $needle) : bool
{
  return (strncmp($haystack,$needle,strlen($needle)) == 0);
}

function endswith(string $haystack, string $needle) : bool
{
    $o = strlen( $needle );
    if ( $o > 0 && $o <= strlen($haystack) ) {
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
