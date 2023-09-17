<?php
/*
Navigator module data structure
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace Navigator;

/**
 * Class of context-specific and context-independent data usable for
 * generating a navigation item.
 * @since 2.0
*/
class Node
{
    // Declared properties prevent deprecation warnings
    public $children_exist = FALSE; // whether child-page(s) are in the pages-cache TODO false always ok? (no pages cache)
    public $children; // id's | Node's array, might differ from corresponding $statics property (filtering etc)
    public $current; // whether this node represents the currently-requested page
    public $default; // whether this node represents the default page
    public $depth; // 1-based nodes-tree level
    public $has_children; // whether descending from this node is viable (various tests passed)
    public $parent; // whether this node is an ancestor of the node representing the currently-requested page
    public $statics; // corresponding NodeStatic of context-independent properties
/* others from CMSMS 2.2 NavigatorNode TODO
    public $accesskey;
    public $alias;
    public $created;
    public $extra1;
    public $extra2;
    public $extra3;
    public $hierarchy;
    public $id;
    public $image;
    public $menutext;
    public $modified;
    public $raw_menutext;
    public $tabindex;
    public $target;
    public $titleattribute;
    public $type;
    public $url;
*/
    #[\ReturnTypeWillChange]
    public function __get(string $prop)//: mixed
    {
       return $this->statics->$prop ?? null;
    }
}