<?php
/*
Navigator module data structure to support PHP optimisation
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
 * Class of context-independent data usable for generating a navigation item
 * See also the Navigator\Node class which includes a NodeStatic property
 * @since 2.0
 */
class NodeStatic
{
    public $accesskey;
    public $alias;
    public $children; //id's array maybe empty
    public $created; // timestamp
    public $hierarchy; // 'friendly' format like '2.4.1'
    public $id; // numeric identifier
    public $inmenu; // whether this content will actually be included in menus
    public $menutext; // specialize()'d raw_menutext
    public $modified; // timestamp
    public $name;
    public $raw_menutext;
    public $tabindex;
    public $title; // same as $name c.f. $titleattribute
    public $titleattribute;
    public $type; // lower-cased content-type
    public $url; // page access URL
    // formerly extended-only properties
    public $extra1;
    public $extra2;
    public $extra3;
    public $image;
    public $target;
    public $thumbnail;
}
