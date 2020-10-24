<?php
/*
Search module utilities class
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

use CMSMS\ContentOperations;
use CMSMS\Events;
use CMSMS\ModuleOperations;
//use CMSMS\SystemCache;
use PorterStemmer;
use Search;
use const CMS_DB_PREFIX;
use const NON_INDEXABLE_CONTENT;
use function cmsms;

/**
 * @since 2.9
 */
class Utils
{
    /**
     *
     * @param Search-module object $module
     * @param string $phrase
     * @return array
     */
    public static function StemPhrase(Search &$module, string $phrase) : array
    {
        // strip out smarty tags
        $phrase = preg_replace('/{.*?}/', ' ', $phrase);
        $phrase = preg_replace('/[\{\}]/', '', $phrase);

        // strip out html and php stuff
        $phrase = strip_tags($phrase);

        // add spaces between tags
        $phrase = str_replace('<',' <',$phrase);
        $phrase = str_replace('>','> ',$phrase);

        // escape meta characters
        $phrase = preg_quote($phrase);

        // strtolower isn't friendly to other charsets
        $phrase = preg_replace_callback('/([A-Z]+?)/', function($matches)
            {
                return strtolower($matches[1]);
            }, $phrase);

        // split into words
        $words = preg_split('/[\s,!.;:\?()+-\/\\\\]+/u', $phrase);

        // strip off anything 2 chars or less
        $words = array_filter($words, function ($a)
            {
                return (strlen($a) >= 3);
            });

        // ignore stop words
        $words = $module->RemoveStopWordsFromArray($words);

        // stem words
        $stemmed_words = [];
        $stemmer = null;
        if ($module->GetPreference('usestemming', 0)) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'PorterStemmer.class.php';
            $stemmer = new PorterStemmer();
        }

        foreach ($words as $word) {
            $word = trim($word);
            $word = trim($word, ' \'"');
            $word = trim($word);
            if (strlen($word) < 3) continue;

            //trim words get rid of wrapping quotes
            if (is_object($stemmer)) {
                $stemmed_words[] = $stemmer->stem($word, true);
            }
            else {
                $stemmed_words[] = $word;
            }
        }
        return $stemmed_words;
    }

    /**
     *
     * @param Search-module object $module
     * @param string $modname
     * @param type $id
     * @param string $attr
     * @param string $content
     * @param type $expires
     */
    public static function AddWords(Search &$module, string $modname = 'Search', $id = -1, string $attr = '', string $content = '', $expires = null)
    {
        $db = $module->GetDb();
        $module->DeleteWords($modname, $id, $attr);

        $non_indexable = strpos($content, NON_INDEXABLE_CONTENT);
        if ($non_indexable !== false) return;

        Events::SendEvent('Search', 'SearchItemAdded', [ $modname, $id, $attr, &$content, $expires ]);

        if ($content != '') {
            //Clean up the content
            $content = html_entity_decode($content);
            $stemmed_words = $module->StemPhrase($content);
            $tmp = array_count_values($stemmed_words);
            if (!is_array($tmp) || !count($tmp)) return;
            $words = [];
            foreach( $tmp as $key => $val) {
                $words[] = [$key, $val];
            }

            $q = 'SELECT id FROM '.CMS_DB_PREFIX.'module_search_items WHERE module_name=?';
            $parms = [$modname];

            if ($id != -1) {
                $q .= ' AND content_id=?';
                $parms[] = $id;
            }
            if ($attr != '') {
                $q .= ' AND extra_attr=?';
                $parms[] = $attr;
            }
            $db->BeginTrans();
            $dbresult = $db->Execute($q, $parms);

            if ($dbresult && $dbresult->RecordCount() > 0 && $row = $dbresult->FetchRow()) {
                $itemid = (int) $row['id'];
            }
            else {
                $itemid = (int) $db->GenID(CMS_DB_PREFIX.'module_search_items_seq');//OR use $db->Insert_ID();
                $db->Execute('INSERT INTO '.CMS_DB_PREFIX.'module_search_items (id, module_name, content_id, extra_attr, expires) VALUES (?,?,?,?,?)', [$itemid, $modname, $id, $attr, ($expires != NULL ? trim($db->DbTimeStamp($expires), "'") : NULL) ]);
            }

            $stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX."module_search_index (item_id, word, count) VALUES ($itemid,?,?)");
            foreach ($words as $row) {
                $stmt->Execute($row);
            }
            $stmt->close();
            $db->CommitTrans();
        }
    }

    /**
     *
     * @param Search-module object $module
     * @param string $modname
     * @param type $id
     * @param string $attr
     */
    public static function DeleteWords(Search &$module, string $modname = 'Search', $id = -1, string $attr = '')
    {
        $parms = [$modname];
        $q = 'DELETE FROM '.CMS_DB_PREFIX.'module_search_items WHERE module_name=?';
        if ($id != -1) {
            $q .= ' AND content_id=?';
            $parms[] = $id;
        }
        if ($attr != '') {
            $q .= ' AND extra_attr=?';
            $parms[] = $attr;
        }
        $db = $module->GetDb();
        $db->BeginTrans();
        $db->Execute($q, $parms);
        //Ruud suggestion: migrate this to async task and/or index item_id field
        $db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_search_index WHERE item_id NOT IN (SELECT id FROM '.CMS_DB_PREFIX.'module_search_items)');
        $db->CommitTrans();
        Events::SendEvent( 'Search', 'SearchItemDeleted', [ $modname, $id, $attr ]);
    }

    /**
     *
     * @param Search-module object $module
     */
    public static function Reindex(Search &$module)
    {
        @set_time_limit(999);
        $module->DeleteAllWords();

        // have to load all the content, and properties, (in chunks)
        $full_list = array_keys(cmsms()->GetHierarchyManager()->getFlatList());
        $n = count($full_list);
        $nperloop = min(200,$n);
        $contentops = ContentOperations::get_instance();
//		$cache = SystemCache::get_instance();
        $offset = 0;

        while( $offset < $n) {
            // figure out the content to load.
            $idlist = [];
            for( $i = 0; $i < $nperloop && $offset+$i < $n; $i++) {
                $idlist[] = $full_list[$offset+$i];
            }
            $offset += $i;
            $idlist = array_unique($idlist);

            // load the content for this list
            $contentops->LoadChildren(-1,TRUE,FALSE,$idlist);

            // index each content page.
            foreach( $idlist as $one) {
                $content_obj = $contentops->LoadContentFromId($one); //TODO ensure relevant content-object?
                $parms = ['content'=>$content_obj];
//                self::DoEvent($module,'Core','ContentEditPost',$parms); //WHAAT ? not changed
//                $cache->delete($one,'tree_pages'); RUBBISH
            }
        }

        $modops = ModuleOperations::get_instance();
        $modules = $modops->GetInstalledModules();
        foreach( $modules as $name) {
            if (!$name || $name == 'Search') continue;
            $modinst = $modops->get_module_instance($name);
            if (is_object($modinst) && method_exists($modinst, 'SearchReindex')) {
                $modinst->SearchReindex($module);
            }
        }
    }

    /**
     *
     * @param Search-module object $module
     * @param string $originator
     * @param string $eventname
     * @param array $params
     */
    public static function DoEvent(Search &$module, string $originator, string $eventname, array &$params)
    {
        if ($originator != 'Core') return;

        switch ($eventname) {
        case 'ContentEditPost':
            $content = $params['content'];
            if (!is_object($content)) return;

            $db = $module->GetDb();
            //Ruud suggestion: defer deletion to next search_AddWords() call
            $module->DeleteWords($module->GetName(), $content->Id(), 'content');
            if ($content->Active() && $content->IsSearchable()) {

                $text = str_repeat(' '.$content->Name(), 2) . ' ';
                $text .= str_repeat(' '.$content->MenuText(), 2) . ' ';

                $props = $content->Properties();
                if ($props) {
                    foreach( $props as $k => $v) {
                        $text .= $v.' ';
                    }
                }

                // here check for a string to see
                // if module content is indexable at all
                $non_indexable = (strpos($text, NON_INDEXABLE_CONTENT) !== false)?1:false;
                $text = trim(strip_tags($text));
                if ($text && !$non_indexable) $module->AddWords($module->GetName(), $content->Id(), 'content', $text);
            }
            break;

        case 'ContentDeletePost':
            $content = $params['content'];
            if (!isset($content)) return;
            $module->DeleteWords($module->GetName(), $content->Id(), 'content');
            break;

        case 'ModuleUninstalled':
            $module_name = $params['name'];
            $module->DeleteWords($module_name);
            break;
        }
    }

    /**
     *
     * @param string $text
     * @return string
     */
    public static function CleanupText(string $text) : string
    {
        $text = strip_tags($text);
        return $text;
    }
} //class
