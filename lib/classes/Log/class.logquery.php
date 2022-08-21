<?php
/*
Class ...
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\Log;

//use CMSMS\Log\dbstorage;
use CMSMS\Log\logfilter;

class logquery
{
    private $_realquery;

    public function __construct(logfilter $filter)
    {
        $this->_realquery = new dbquery($filter); // TODO relevant store c.f. logger->_store
    }

    #[\ReturnTypeWillChange]
    public function __set(string $key, $value)// : void
    {
        $this->_realquery->$key = $value;
    }

    #[\ReturnTypeWillChange]
    public function __get(string $key)// : mixed
    {
        return $this->_realquery->$key;
    }

    //DbQueryBase-compatible methods

    public function execute()
    {
        $this->_realquery->execute();
    }

    public function GetObject()
    {
        return $this->_realquery->GetObject();
    }

    public function GetMatches()
    {
        return $this->_realquery->GetMatches();
    }
}
