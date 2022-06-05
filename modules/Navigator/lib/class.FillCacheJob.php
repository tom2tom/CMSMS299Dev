<?php
/*
Navigator module async job definition: re-populate the nav data cache
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that licence, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace Navigator;

use CMSMS\Async\OnceJob;
use CMSMS\LoadedDataType;
use CMSMS\Lone;

class FillCacheJob extends OnceJob
{
    #[\ReturnTypeWillChange]
    public function __construct()
    {
        parent::__construct([
         'name' => 'Navigator\\FillCache',
         'module' => 'Navigator'
        ]);
    }

    /**
     * Perform the task
     * @return int indicating execution status: 0 = failed, 1 = no need to do anything, 2 = success
     */
    public function execute()
    {
        $cache = Lone::get('LoadedData');
        $obj = new LoadedDataType('navigator_data','Navigator\\Utils::fill_cache');
        $cache->add_type($obj);
//TODO upstream ATM  $cache->release('navigator_data');
        $cache->get('navigator_data');
        return 2;
    }

    public function run()
    {
    }
}
