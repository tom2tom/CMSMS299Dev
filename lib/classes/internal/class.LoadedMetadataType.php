<?php
/*
Class for engaging with a LoadedMetadata values-set
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\internal;

/**
 * Class for engaging with a LoadedMetadata values-set
 * @since 2.99
 * @see also LoadedDataType class
 */
class LoadedMetadataType
{
    private $name;
    private $prop;
    private $fetchcb;
    private $uses = []; // stash of retrieval-parameters, for a possible refresh

    /**
     * Constructor
     * @param string $name Main part of the type-identifier
     * @param string $prop Secondary part of the type-identifier
     * @param callable $fetcher Data retriever
     */
    public function __construct(string $name, string $prop, callable $fetcher)
    {
        $this->name = trim($name);
        $this->prop = trim($prop);
        $this->fetchcb = $fetcher;
    }

    /**
     * Get this data-type's identifier
     * @return string like type:::subtype
     */
    public function get_name()
    {
        return $this->name.':::'.$this->prop; // separator is LoadedData::SUB_SEP
    }

    /**
     * Get recorded fetch-parameters
     * @since 2.99
     * Get recorded fetch-parameters, if any
     * @return array like ['capable_modules => ['ability' => [args1, args2, ...]]]
     * i.e. suitable for recursive merging
     */
    public function get_uses()
    {
        return [$this->name => [$this->prop => $this->uses]];
    }

    /**
     * Get one of this data-type's values
     * @param bool $force whether the fetcher should force-retrieve
     * @param varargs $args at least 1 argument, sometimes more, for the callable
     * @return mixed
     */
    public function fetch(bool $force, ...$args)
    {
        if (!$this->in_array_multi($args, $this->uses)) {
            $this->uses[] = $args;  // array, maybe empty
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
