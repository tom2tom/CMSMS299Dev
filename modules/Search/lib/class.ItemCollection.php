<?php
/*
Search module class: ItemCollection
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace Search;

use Search\Utils;

/**
 * @since 2.0
 */

class ItemCollection
{
    public $_ary = []; //SearchItemData (was stdClass) objects
    public $maxweight = 1;

    public function AddItem($title, $url, $txt, $weight = 1, $module = '', $modulerecord = 0)
    {
        if ($txt == '') $txt = $url;
        $exists = false;

        foreach ($this->_ary as $oneitem) {
            if ($url == $oneitem->url) {
                $exists = true;
                break;
            }
        }

        if (!$exists ) {
            $newitem = new SearchItemData();
            $newitem->url = $url;
            $newitem->urltxt = Utils::CleanupText($txt);
            $newitem->title = $title;
            $newitem->intweight = (int)$weight;
            if ((int)$weight > $this->maxweight) $this->maxweight = (int)$weight;
            if (!empty($module) ) {
                $newitem->module = $module;
                if((int)$modulerecord > 0 ) $newitem->modulerecord = $modulerecord;
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
        usort($this->_ary, function($a,$b) {
            return ($a->urltxt <=> $b->urltxt);
        });
    }
} // class

// defined data structure to support PHP optimisation
class SearchItemData
{
    public $intweight;
    public $module;
    public $modulerecord;
    public $title;
    public $url;
    public $urltxt;
}
