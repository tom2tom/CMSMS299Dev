<?php
/*
Class for engaging with a LoadedData values-set
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace CMSMS;

/**
 * Class for engaging with a LoadedData values-set
 * @since 2.99
 * @since 2.0 as CMSMS\internal\global_cachable
 */
class LoadedDataType
{
    private $name;
    private $fetchcb;
    private $uses = []; // stash of retrieval-parameters, for a possible refresh

    /**
     * Constructor
     * @param string $name Type identifier
     * @param callable $fetcher Data retriever
     */
    public function __construct($name, callable $fetcher)
    {
        $this->name = trim($name);
        $this->fetchcb = $fetcher;
    }

    /**
     * Get this data-type's identifier
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * Get recorded fetch-parameters, if any
     * @since 2.99
     * @return array like [arg(s)1, arg(s)2, ...], maybe empty
     * i.e. suitable for recursive merging
     */
    public function get_uses()
    {
        return $this->uses;
    }

    /**
     * Get this data-type's value, or one of them, if $args are presented
     * @param bool $force whether the fetcher should force-retrieve
     * @param varargs $args since 2.99 optional argument(s) for the callable
     * @return mixed
     */
    public function fetch(bool $force, ...$args)
    {
        if ($args && !$this->in_array_multi($args, $this->uses)) {
            $this->uses[] = $args;
        }
        return ($this->fetchcb)($force, ...$args);
    }

    protected function in_array_multi($needle, $haystack) {
        foreach ($haystack as $item) {
            if ($item === $needle || (is_array($item) && $this->in_array_multi($needle, $item))) {
                return true;
            }
        }
        return false;
    }
}
