<?php
/*
Search module action: dosearch. This can be initiated from a frontend search (default action)
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\Events;
use CMSMS\TemplateOperations;
use Search\ItemCollection;
use Search\Utils;
use function CMSMS\specialize;
use function CMSMS\log_error;

//if (some worthy test fails) exit;

if (!empty($params['resulttemplate'])) {
    $template = trim($params['resulttemplate']);
} else {
    $tpl = TemplateOperations::get_default_template_by_type('Search::searchresults');
    if (!is_object($tpl)) {
        log_error('No default summary template found',$this->GetName().'::dosearch');
        $this->ShowErrorPage('No default summary template found');
        return;
    }
    $template = $tpl->get_name();
}

$tpl = $smarty->createTemplate($this->GetTemplateResource($template)); //,null,null,$smarty);

//TODO $params['origreturnid'] usage?

if ($params['searchinput']) {
    $phrase = $params['searchinput']; // used verbatim, but escaped

    $searchstarttime = microtime(true);
    Events::SendEvent('Search','SearchInitiated',[$phrase]); // no modification
    $words = array_values(Utils::StemPhrase($this, $phrase)); // can't properly escape whole $phrase here

    // Update the search words table
    Utils::UpdateWords($this, $phrase, $words);

    $col = new ItemCollection();
    $nb_words = count($words);
//  $max_weight = 1;

    $searchphrase = '';
    if ($nb_words > 0) {
//      $searchphrase = implode(' OR ', array_fill(0, $nb_words, 'word = ?'));
        $ary = [];
        foreach ($words as $word) {
            $word = trim($word);
//          $ary[] = 'word = ' . $db->qStr(specialize($word));
//          $ary[] = 'word = ' . $db->qStr($word); // since 2.0 specialchars ok
            $ary[] = 'word = \''.$db->escStr($word)."'"; // since 2.0 specialchars ok
        }
        $searchphrase = implode(' OR ', $ary);
    }
//    $val = 100000000 * 25;
    $pref = CMS_DB_PREFIX;
    $query = <<<EOS
SELECT DISTINCT I.module_name, I.content_id, I.extra_attr, COUNT(*) AS nb, SUM(IDX.`count`) AS total_weight
FROM {$pref}module_search_items I
INNER JOIN {$pref}module_search_index IDX ON I.id = IDX.item_id
WHERE ($searchphrase) AND (I.expires IS NULL OR I.expires >= NOW())
EOS;
    if (isset( $params['modules'])) {
        $modules = explode(',', $params['modules']);
        for ($i = 0, $n = count($modules); $i < $n; $i++) {
            $modules[$i] = $db->qStr($modules[$i]);
        }
        $query .= ' AND I.module_name IN ('.implode(',',$modules).')';
    }
    $query .= ' GROUP BY I.module_name, I.content_id, I.extra_attr';
    if (empty($params['use_or'])) {
        //this is an AND query
        $query .= " HAVING COUNT(*) >= $nb_words";
    }
    $query .= ' ORDER BY nb DESC, total_weight DESC';
    $rst = $db->execute($query);
    if ($rst) {
        $ptops = $gCms->GetHierarchyManager();
        while (!$rst->EOF) {
            //Handle internal (templates, content, etc) first...
            if ($rst->fields['module_name'] == $this->GetName()) {
                if ($rst->fields['extra_attr'] == 'content') {
                    //Content is easy... just grab it out of hierarchy manager and toss the url in
                    $node = $ptops->get_node_by_id($rst->fields['content_id']);
                    if (isset($node)) {
                        $content = $node->get_content();
                        if ($content && $content->Active()) $col->AddItem($content->Name(), $content->GetURL(), $content->Name(), $rst->fields['total_weight']);
                    }
                }
            } else {
                $thepageid = $this->GetPreference('resultpage',-1);
                if ($thepageid == -1) $thepageid = $returnid;
                if (isset($params['detailpage'])) {
                    $tmppageid = $ptops->find_by_identifier($params['detailpage'],false);
                    if ($tmppageid) $thepageid = $tmppageid;
                }
                if ($thepageid == -1) $thepageid = $returnid;

                //Start looking at modules...
                $modulename = $rst->fields['module_name'];
                $moduleobj = $this->GetModuleInstance($modulename);
                if ($moduleobj) {
                    if (method_exists($moduleobj, 'SearchResultWithParams')) {
                        // search through the params, for all the passthru ones
                        // and get only the ones matching this module name
                        $parms = [];
                        foreach( $params as $key => $value) {
                            $str = 'passthru_'.$modulename.'_';
                            if (preg_match( "/$str/", $key) > 0) {
                                $name = substr($key,strlen($str));
                                if ($name != '') $parms[$name] = $value;
                            }
                        }
                        $searchresult = $moduleobj->SearchResultWithParams( $thepageid, $rst->fields['content_id'], $rst->fields['extra_attr'], $parms);
                        if (count($searchresult) == 3) {
                            $col->AddItem($searchresult[0], $searchresult[2], $searchresult[1], $rst->fields['total_weight'], $modulename, $rst->fields['content_id']);
                        }
                    } elseif (method_exists($moduleobj, 'SearchResult')) {
                        $searchresult = $moduleobj->SearchResult($thepageid, $rst->fields['content_id'], $rst->fields['extra_attr']);
                        if (count($searchresult) == 3) {
                            $col->AddItem($searchresult[0], $searchresult[2], $searchresult[1], $rst->fields['total_weight'], $modulename, $rst->fields['content_id']);
                        }
                    }
                }
            }
            $rst->MoveNext();
        }
        $rst->Close();

        $col->CalculateWeights();
        if ($this->GetPreference('alpharesults', 0)) {
            $col->Sort();
        }
        $results = $col->_ary;
    } else {
        $results = [];
    }

    // now we're gonna do some post processing on the results
    // and replace the search terms with <span class="searchhilite">term</span>

    $newresults = [];
    foreach ($results as $result) {
        $title = specialize($result->title);
        $txt = specialize($result->urltxt);
        foreach ($words as $word) {
            $word = preg_quote($word);
            $title = preg_replace('/\b('.$word.')\b/i', '<span class="searchhilite">$1</span>', $title);
            $txt = preg_replace('/\b('.$word.')\b/i', '<span class="searchhilite">$1</span>', $txt);
        }
        $result->title = $title;
        $result->urltxt = $txt;
        $newresults[] = $result;
    }
    $col->_ary = $newresults;

    $tpl->assign('phrase',$phrase);

    Events::SendEvent('Search','SearchCompleted',[&$phrase, &$col->_ary]); // any modification useless!

    $searchendtime = microtime(true);
    $tpl->assign('itemcount', count($col->_ary))
     ->assign('results', $col->_ary)
     ->assign('searchwords', $words)
     ->assign('timetook', ($searchendtime - $searchstarttime));
} else {
    $tpl->assign([
     'itemcount' => 0,
     'phrase' => '',
     'results' => 0,
     'timetook' => 0,
    ]);
}

$tpl->assign([
 'noresultsfound' => $this->Lang('noresultsfound'),
 'searchresultsfor' => $this->Lang('searchresultsfor'),
 'timetaken' => $this->Lang('timetaken'),
 'use_or_text' => $this->Lang('use_or'),
]);
$tpl->display();
