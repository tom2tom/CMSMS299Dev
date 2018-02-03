<?php

namespace __appbase;

function startswith($haystack,$needle)
{
  return (strncmp($haystack,$needle,strlen($needle)) == 0);
}

function endswith($haystack,$needle)
{
  return (substr($haystack,-strlen($needle)) == $needle);
}

?>
