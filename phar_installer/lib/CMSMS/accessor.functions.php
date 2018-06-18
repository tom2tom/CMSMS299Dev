<?php

namespace __installer\CMSMS;

use __installer\CMSMS\cms_smarty;
use __installer\CMSMS\langtools;
use __installer\CMSMS\nlstools;

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
