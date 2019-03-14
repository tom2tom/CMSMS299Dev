<?php

namespace cms_installer;

use cms_installer\cms_smarty;
use cms_installer\langtools;
use cms_installer\nlstools;

/**
 *
 * @return cms_smarty object, a Smarty subclass
 */
function smarty()
{
  return cms_smarty::get_instance();
}

/**
 *
 * @return nlstools object
 */
function nls()
{
  return nlstools::get_instance();
}

/**
 *
 * @return langtools object
 */
function translator()
{
  return langtools::get_instance();
}
