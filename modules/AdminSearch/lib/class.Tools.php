<?php
/*
AdminSearch module static utility-methods class.
Copyright (C) 2012-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file AdminSearch.module.php
*/
namespace AdminSearch;

use CMSMS\DeprecationNotice;
use CMSMS\Lone;
use CMSMS\Utils;
use const CMS_DEPREC;
use function get_userid;

class Tools
{
    public static function get_slave_classes()
    {
        $key = 'slaves'.get_userid(false);
        $cache = Lone::get('SystemCache');
        $results = $cache->get($key, __CLASS__);
        if (!$results) {
            // cache needs populating
            //TODO force upon module installation
            $results = [];

            // get module search-slaves
            $mod = Utils::get_module('AdminSearch');
            $modulelist = $mod->GetModulesWithCapability('AdminSearch');
            if ($modulelist) {
                foreach ($modulelist as $module_name) {
                    $mod = Utils::get_module($module_name);
                    if (!is_object($mod)) {
                        continue;
                    }
                    if (!method_exists($mod, 'get_adminsearch_slaves')) {
                        continue;
                    }
                    $classlist = $mod->get_adminsearch_slaves();
                    if ($classlist) {
                        foreach ($classlist as $class_name) {
/* TODO don't assume all slave-classes are namespaced (if not supplied as such)
                            if( strpos($class_name,'\\') === false ) {
                                $class_name = $module_name.'\\'.$class_name;
                            }
*/
                            $obj = new $class_name();
                            if (!is_object($obj)) {
                                continue;
                            }
                            if (!is_subclass_of($class_name, 'AdminSearch\Base_slave')) {
                                continue;
                            }
                            $name = $obj->get_name();
                            if (!$name) {
                                continue;
                            }
                            if (isset($results[$name])) {
                                continue;
                            }
                            $tmp = [
                             'module' => $module_name,
                             'class' => $class_name,
                             'name' => $name,
                            ];
                            $tmp['description'] = $obj->get_description();
                            $tmp['section_description'] = $obj->get_section_description();
                            $results[$name] = $tmp;
                        }
                    }
                }
                if ($results) {
                    //TODO proper UTF-8 sort
                    ksort($results, SORT_LOCALE_STRING | SORT_FLAG_CASE);
                }
            }

            // cache the results
            $cache->set($key, $results, __CLASS__);
        }

        return $results;
    }

    /**
     * Get a shortened variant of $text
     * @deprecated since 1.2 Instead use Base_slave::summarize($text,$len)
     *
     * @param string $text
     * @param int $len
     * @return string
     */
    public static function summarize($text, $len = 255) : string
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','AdminSearch\Base_slave::summarize'));
        return Base_slave::summarize($text, $len);
    }
}
