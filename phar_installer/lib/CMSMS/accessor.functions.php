<?php

namespace cms_installer\CMSMS;

use cms_installer\CMSMS\cms_smarty;
use cms_installer\CMSMS\langtools;
use cms_installer\CMSMS\nlstools;

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
