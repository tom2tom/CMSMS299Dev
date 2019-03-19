<?php
# TemplateCache class: cache template data for frontend requests.
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS\internal;

use cms_cache_handler;
use CmsApp;
use CmsLayoutTemplate;
use CmsLayoutTemplateType;
use CmsLogicException;
use const TMP_CACHE_LOCATION;
use function cms_join_path;

/**
 *  A simple class to handle remembering and preloading template data for
 *  frontend requests.
 *
 * @package CMS
 * @author Robert Campbell
 * @internal
 * @ignore
 *
 * @since 2.0
 */
class TemplateCache
{
  private static $_instance;
  private $_cache;
  private $_key;

  public function __construct()
  {
	  if( !CmsApp::get_instance()->is_frontend_request() ) throw new CmsLogicException('This class can only be instantiated on a frontend request');
	  if( self::$_instance ) throw new CmsLogicException('Only one instance of this class is permitted');
	  self::$_instance = TRUE;

	  $this->_key = md5($_SERVER['REQUEST_URI'].serialize($_GET));
	  $data = cms_cache_handler::get_instance()->get('template_cache');
	  if( $data ) {
		  $this->_cache = $data;
		  if( isset($this->_cache[$this->_key]) ) {
			  LayoutTemplateOperations::load_bulk_templates($this->_cache[$this->_key]['templates']);
			  if( isset($this->_cache[$this->_key]['types']) ) CmsLayoutTemplateType::load_bulk($this->_cache[$this->_key]['types']);
		  }
	  }
  }

  public function __destruct()
  {
      // update the cache;
      if( !CmsApp::get_instance()->is_frontend_request() ) return;

      $dirty = FALSE;
      $t1 = LayoutTemplateOperations::get_loaded_templates();
      if( is_array($t1) ) {
          $t2 = [];
          if( isset($this->_cache[$this->_key]['templates']) ) $t2 = $this->_cache[$this->_key]['templates'];
          $x = array_diff($t1,$t2);
          if( $x ) {
              $this->_cache[$this->_key]['templates'] = $t1;
              $dirty = TRUE;
          }
      }

      $t1 = CmsLayoutTemplateType::get_loaded_types();
      if( is_array($t1) ) {
          $t2 = [];
          if( isset($this->_cache[$this->_key]['types']) ) $t2 = $this->_cache[$this->_key]['types'];
          $x = array_diff($t1,$t2);
          if( $x ) {
              $this->_cache[$this->_key]['types'] = $t1;
              $dirty = TRUE;
          }
      }

      if( $dirty ) cms_cache_handler::get_instance()->set('template_cache',$this->_cache);
  }

  public static function clear_cache()
  {
	  if( !defined('TMP_CACHE_LOCATION') ) return;
	  $fn = cms_join_path(TMP_CACHE_LOCATION,'template_cache');
	  @unlink($fn);
  }
} // class
