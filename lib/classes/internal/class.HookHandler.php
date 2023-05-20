<?php
/*
Hook handler class
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace CMSMS\internal;

use CMSMS\HookOperations;
use InvalidArgumentException;

/**
 * Class to represent a hook handler.
 * @since 2.2
 *
 * @internal
 * @ignore
 */
class HookHandler
{
    /**
     * @var callable hook-processor function
     */
    public $callable;

    /**
     * @var int HookOperations::PRIORITY_*
     */
    public $priority;

    /**
     * Constructor.
     * @param callable $handler
     * @param int $priority Optional since 3.0, Default HookOperations::PRIORITY_NORMAL
     * @throws InvalidArgumentException if $handler is not callable
     */
    public function __construct($handler, int $priority = HookOperations::PRIORITY_NORMAL)
    {
        if (is_callable($handler, true)) {
            $this->callable = $handler;
            if ($priority <= 0) {
                $this->priority = HookOperations::PRIORITY_NORMAL;
            } else {
                $this->priority = max(HookOperations::PRIORITY_HIGH,min(HookOperations::PRIORITY_LOW,(int)$priority));
            }
        } else {
            throw new InvalidArgumentException('Invalid callable provided to '.__METHOD__);
        }
    }
}
