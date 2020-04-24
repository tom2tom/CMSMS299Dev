<?php
/*
Search module class: ItemCollection
Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace Search;

use Search\Utils;

/**
 * @since 2.3
 */

class ItemCollection
{
    public $_ary = [];
    public $maxweight = 1;

    public function AddItem($title, $url, $txt, $weight = 1, $module = '', $modulerecord = 0)
    {
        if( $txt == '' ) $txt = $url;
        $exists = false;

        foreach ($this->_ary as $oneitem) {
            if ($url == $oneitem->url) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $newitem = new StdClass();
            $newitem->url = $url;
            $newitem->urltxt = Utils::CleanupText($txt);
            $newitem->title = $title;
            $newitem->intweight = (int)$weight;
            if ((int)$weight > $this->maxweight) $this->maxweight = (int)$weight;
            if (!empty($module) ) {
                $newitem->module = $module;
                if((int)$modulerecord > 0 )	$newitem->modulerecord = $modulerecord;
            }
            $this->_ary[] = $newitem;
        }
    }

    public function CalculateWeights()
    {
		foreach ($this->_ary as &$oneitem) {
            $oneitem->weight = (int)($oneitem->intweight / $this->maxweight) * 100;
        }
		unset($oneitem);
    }

    public function Sort()
    {
        usort($this->_ary, function($a,$b)
        {
            return ($a->urltxt <=> $b->urltxt);
        });
    }
} // class

