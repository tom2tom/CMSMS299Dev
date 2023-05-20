<?php
/*
Search module utilities class
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace Search;

use CMSMS\Events;
use CMSMS\Lone;
use PorterStemmer; // TODO support non-english lang
use Search; // search-module class in global space
use const CMS_DB_PREFIX;
use function cmsms;
use function CMSMS\de_entitize;

/**
 * @since 2.0
 */
class Utils
{
    /**
     *
     * @param Search-module object $module
     * @param string $phrase
     * @return array
     */
    public static function StemPhrase(Search $module, string $phrase) : array
    {
        // strip out smarty tags
        $phrase = preg_replace(['/{.*?}/', '/[\{\}]/'], [' ', ''], $phrase);

        // strip out html and php stuff
        $phrase = strip_tags($phrase);

        // add spaces between tags
        $phrase = str_replace(['<', '>'], [' <', '> '], $phrase);

        // escape meta characters
        $phrase = preg_quote($phrase);

        // strtolower isn't friendly to other charsets
        $phrase = preg_replace_callback('/([A-Z]+?)/', function($matches) {
            return strtolower($matches[1]);
        }, $phrase);

        // split into words
        $words = preg_split('/[\s,!.;:\?()+\-\/\\\\]+/u', $phrase);

        // ignore 1-char number and anything else 2 chars or less
        $words = array_filter($words, function($a) {
            return ($l = strlen($a)) > 2 || ($l > 1 && is_numeric($a));
        });

        // ignore stop words
        $words = self::RemoveStopWordsFromArray($module, $words);

        // stem words
        $stemmed_words = [];
        if ($module->GetPreference('usestemming', 0)) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'PorterStemmer.class.php';
            $stemmer = new PorterStemmer();
        } else {
            $stemmer = null; // no object
        }

        foreach ($words as $word) {
            $word = trim($word, " \t\n\r\0\x0B\"'");
            $n = is_numeric($word);
            if ((($l = strlen($word)) < 2 && $n) || $l < 3) {
                continue; // should never happen - shorts filtered before
            }
            //trim words get rid of wrapping quotes
            if (!$n && is_object($stemmer)) {
                $stemmed_words[] = $stemmer->stem($word, true);
            } else {
                $stemmed_words[] = $word;
            }
        }
        return $stemmed_words;
    }

    /**
     * @internal
     */
    public static function unlockdb()
    {
        $db = Lone::get('Db');
        $db->execute('UNLOCK TABLES');
    }

    /**
     *
     * @param Search-module object $module
     * @param string $modname optional
     * @param int $id optional
     * @param string $attr optional extra_attr field value
     * @param string $content optional
     * @param mixed $expires  optional timestamp | null
     */
    public static function AddWords(Search $module, string $modname = 'Search', int $id = -1, string $attr = '', string $content = '', $expires = null)
    {
        self::DeleteWords($modname, $id, $attr);

        if (strpos($content, Search::NON_INDEXABLE_CONTENT) !== false) {
            return;
        }

        Events::SendEvent('Search', 'SearchItemAdded', [$modname, $id, $attr, &$content, $expires]);

        if ($content !== '') {
            //Clean up the content
            $content = de_entitize($content);
            $stemmed_words = self::StemPhrase($module, $content);
            $tmp = array_count_values($stemmed_words);
            if (!$tmp) {
                return;
            }

            $q = 'SELECT id FROM '.CMS_DB_PREFIX.'module_search_items WHERE module_name=?';
            $parms = [$modname];

            if ($id != -1) {
                $q .= ' AND content_id=?';
                $parms[] = $id;
            }
            if ($attr) {
                $q .= ' AND extra_attr=?';
                $parms[] = $attr;
            }
            $db = Lone::get('Db');
            //pre-prepare this to reduce later delay n' raciness
            $stmt = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.'module_search_index (item_id,word,`count`) VALUES (?,?,?)');

            $rst = $db->execute($q, $parms);
            if ($rst && $rst->RecordCount() > 0 && $row = $rst->FetchRow()) {
                $itemid = (int)$row['id'];
            } else {
                $itemid = $db->genID(CMS_DB_PREFIX.'module_search_items_seq');
                $until = ($expires) ? $db->DbTimeStamp($expires, false) : null;
                $db->execute('INSERT INTO '.CMS_DB_PREFIX.'module_search_items
(id,
module_name,
content_id,
extra_attr,
expires)
VALUES (?,?,?,?,?)', [
$itemid,
$modname,
$id,
$attr,
$until,
]);
            }
            if ($rst) {
                $rst->Close();
            }
            register_shutdown_function('Search\\Utils::unlockdb');
            $db->execute('LOCK TABLES '.CMS_DB_PREFIX.'module_search_index WRITE'); // no InnoDB >> no transactions TODO MyISAM workaround
            foreach ($tmp as $word => $times) {
                $db->execute($stmt, [$itemid, $word, $times]);
            }
            $db->execute('UNLOCK TABLES');
            $stmt->close();
        }
    }

    /**
     *
     * @param string $modname optional module-name to match Default 'Search'
     * @param int $id optional content_id field value to match
     * @param string $attr optional extra_attr field value to match
     */
    public static function DeleteWords(string $modname = 'Search', int $id = -1, string $attr = '')
    {
        $db = Lone::get('Db');
        register_shutdown_function('Search\\Utils::unlockdb');
        $db->execute('LOCK TABLES '.CMS_DB_PREFIX.'module_search_items WRITE, '.CMS_DB_PREFIX.'module_search_index WRITE');
        // TODO stored procedure instead of all this?
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
        $q .= ' ORDER BY id DESC'; //optimise for delete operation
//      $db->BeginTrans(); // for InnoDB tables TODO MyISAM workaround
        $scrubs = $db->getCol($q, $parms);
        if ($scrubs) {
          /* dB-server max_allowed_packet value will be >= 1 MB, which
             is sufficient for commands with say 125,000 record-ids in
             a 9,999,999-record table
             but we will limit here to 1000-record batches
          */
            $batches = array_chunk($scrubs, 1000, true);
            foreach ($batches as &$one) {
                $in = implode(',', $one);
                $db->execute('DELETE FROM '.CMS_DB_PREFIX."module_search_items WHERE id IN ($in)");
                //Ruud suggestion: migrate this to async task, but that might timeout other tasks
                $db->execute('DELETE FROM '.CMS_DB_PREFIX."module_search_index WHERE item_id IN ($in)");
                usleep(100000); // play nicely with the world
            }
            unset($one);
        }
//      $db->CommitTrans(); //TODO MyISAM workaround
        $db->execute('UNLOCK TABLES');
        //TODO remove shutdown handler 'unlockdb' if such is possible
        Events::SendEvent('Search', 'SearchItemDeleted', [$modname, $id, $attr]);
    }

    public static function DeleteAllWords()
    {
        $db = Lone::get('Db');
        register_shutdown_function('Search\\Utils::unlockdb');
        $db->execute('LOCK TABLES '.CMS_DB_PREFIX.'module_search_index WRITE, '.CMS_DB_PREFIX.'module_search_items WRITE , '.CMS_DB_PREFIX.'module_search_words WRITE');
        $db->execute('TRUNCATE '.CMS_DB_PREFIX.'module_search_index');
        $db->execute('TRUNCATE '.CMS_DB_PREFIX.'module_search_items');
        $db->execute('TRUNCATE '.CMS_DB_PREFIX.'module_search_words');
        $db->execute('UNLOCK TABLES');

        Events::SendEvent('Search', 'SearchAllItemsDeleted');
    }

    public static function RemoveStopWordsFromArray(Search $module, array $words)
    {
        $curval = $module->GetPreference('stopwords');
        if (!$curval) {
            $curval = $module->DefaultStopWords();
        }
        $stop_words = preg_split("/\,+/", $curval);
        return array_diff($words, $stop_words);
    }

    /**
     * Update the search words table
     * @param Search-module object $module
     * @param string $phrase single phrase whose presence|count is to be updated
     * @param array $words words whose presence|count is to be updated
     */
    public static function UpdateWords(Search $module, string $phrase, array $words)
    {
        $mode = $module->GetPreference('savephrases', 1); // before LOCK_TABLES (dunno why that leaks)
        $db = Lone::get('Db');
        register_shutdown_function('Search\\Utils::unlockdb');
        $db->execute('LOCK TABLES '.CMS_DB_PREFIX.'module_search_words WRITE');
        if (!$mode) {
            // word-upserts needed
            $stmt1 = $db->prepare('SELECT `count` FROM '.CMS_DB_PREFIX.'module_search_words WHERE word = ?');
            $stmt2 = $db->prepare('UPDATE '.CMS_DB_PREFIX.'module_search_words SET `count` = ? WHERE word = ?');
            $stmt3 = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.'module_search_words (word,`count`) VALUES (?,1)');
            foreach ($words as $word) {
                $tmp = $db->getOne($stmt1, [$word]);
                if ($tmp) {
                    $db->execute($stmt2, [++$tmp, $word]);
                } else {
                    $db->execute($stmt3, [$word]);
                }
            }
            $stmt1->close();
            $stmt2->close();
            $stmt3->close();
        } else {
            // phrase-upsert needed
            $q = 'SELECT `count` FROM '.CMS_DB_PREFIX.'module_search_words WHERE word = ?';
            $tmp = $db->getOne($q, [$phrase]);
            if ($tmp) {
                $q = 'UPDATE '.CMS_DB_PREFIX.'module_search_words SET `count` = ? WHERE word = ?';
                $db->execute($q, [++$tmp, $phrase]);
            }
            else {
                $q = 'INSERT INTO '.CMS_DB_PREFIX.'module_search_words (word,`count`) VALUES (?,1)';
                $db->execute($q, [$phrase]);
            }
        }
        $db->execute('UNLOCK TABLES');
    }

    /**
     *
     * @param Search-module object $module
     */
    public static function Reindex(Search $module)
    {
        set_time_limit(999);
        self::DeleteAllWords();

        // must load all content and properties (in chunks)
        $ptops = cmsms()->GetHierarchyManager();
        $full_list = array_keys($ptops->get_flatlist());
        $n = count($full_list);
        $nperloop = min(200, $n);
        $contentops = Lone::get('ContentOperations');
//      $cache = Lone::get('SystemCache');
        $offset = 0;

        while ($offset < $n) {
            // figure out the content to load
            $idlist = [];
            for ($i = 0; $i < $nperloop && $offset + $i < $n; ++$i) {
                $idlist[] = $full_list[$offset + $i];
            }
            $offset += $i;
            $idlist = array_unique($idlist);

            // load the content for this list
            $contentops->LoadChildren(-1, true, false, $idlist);

            // index each content page
            foreach ($idlist as $one) {
                $content_obj = $contentops->LoadContentFromId($one); //TODO ensure relevant content-object?
                $parms = ['content' => $content_obj];
                $module->DoEvent('Core', 'ContentEditPost', $parms);
//NOPE keep it  $cache->delete($one, 'site_pages');
            }
        }

        $modops = Lone::get('ModuleOperations');
        $availmodules = $modops->GetInstalledModules();
        foreach ($availmodules as $modname) {
            if (!$modname || $modname == 'Search') {
                continue;
            }
            $mod = $modops->get_module_instance($modname);
            if (is_object($mod) && method_exists($mod, 'SearchReindex')) {
                $mod->SearchReindex($module);
            }
        }
    }

    /**
     * Remove html tags from supplied string
     * @param string $text
     * @return string
     */
    public static function CleanupText(string $text) : string
    {
        return strip_tags($text);
    }

    /**
     * Remove unwanted chars from the supplied string
     * Specifically, inter-word spaces, newline char(s),
     *  multiple adjacent comma's, leading/trailing comma's
     * @param string $text ','-separated search-words
     * @return string
     */
    public static function CleanWords(string $text) : string
    {
        $flat = strtr($text, [",\r\n"=>',', "\r\n"=>',', ",\r"=>',', ",\n"=>',', ', '=>',', ' ,'=>',', "\n"=>',', "\r"=>',']);
        return trim(strtr($flat, [',,'=>',']), ' ,');
    }
} //class
