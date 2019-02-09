<?php

namespace cms_installer\CMSMS;

use cms_installer\CMSMS\cms_smarty;
use cms_installer\CMSMS\langtools;
use cms_installer\CMSMS\nlstools;

/**
 *
 * @return type
 */
function smarty()
{
  return cms_smarty::get_instance();
}

/**
 *
 * @return type
 */
function nls()
{
  return nlstools::get_instance();
}

/**
 *
 * @return type
 */
function translator()
{
  return langtools::get_instance();
}
