<?php
/*
This file is part of CMS Made Simple module: OutMailer
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file OutMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/outmailer
*/
namespace OutMailer;

//use OutMailer; module object in global namespace
//use CMSMS\Utils as AppUtils;
use CMSMS\Crypto;
use CMSMS\FormUtils;
use CMSMS\Lone;
use const CMS_DB_PREFIX;
use function cms_join_path;

class Utils
{
    /**
     *
     * @param mixed $mod optional OutMailer module instance
     * @return array maybe empty
     */
    public static function get_platforms_full($mod = null) : array
    {
        $db = Lone::get('Db');
        $aliases = $db->getCol('SELECT alias FROM '.CMS_DB_PREFIX.'module_outmailer_platforms WHERE enabled>0');
        if (!$aliases) {
            return [];
        }
        $bp = cms_join_path(__DIR__, 'platforms', '');
        if ($mod === null) {
            $mod = AppUtils::get_module('OutMailer');
        }
        $objs = [];
        foreach ($aliases as $one) {
            $classname = $one.'_platform';
            $spaced = 'OutMailer\platforms\\'.$classname;
            if (!class_exists($spaced)) {
                include $bp.'class.'.$classname.'.php';
            }
            $obj = new $spaced($mod);
            //return array, so other keys may be added, upstream
            $objs[$one] = ['obj' => $obj];
        }
        return $objs;
    }

    /**
     *
     * @param bool $title optional flag default false
     * @param mixed $mod optional OutMailer module instance
     * @return mixed platform class | null
     */
    public static function get_platform(bool $title = false, $mod = null)
    {
        $db = Lone::get('Db');
        $alias = ($title) ?
            $db->getOne('SELECT alias FROM '.CMS_DB_PREFIX.'module_outmailer_platforms WHERE title=? AND enabled>0', [$title]) :
            $db->getOne('SELECT alias FROM '.CMS_DB_PREFIX.'module_outmailer_platforms WHERE active>0 AND enabled>0');
        if ($alias) {
            $classname = $alias.'_platform';
            $spaced = 'OutMailer\platforms\\'.$classname;
            if (!class_exists($spaced)) {
                $fn = cms_join_path(__DIR__, 'platforms', 'class.'.$classname.'.php');
                require $fn;
            }
            if ($mod === null) {
                $mod = AppUtils::get_module('OutMailer');
            }
            $obj = new $spaced($mod);
            if ($obj) {
                return $obj;
            }
        }
        return null;
    }

    /**
     *
     * @param $mod OutMailer module instance
     * @param string $classname
     * @return bool
     */
    public static function setgate_full($mod, string $classname) : bool
    {
        $spaced = 'OutMailer\platforms\\'.$classname;
        if (!class_exists($spaced)) {
            $fn = cms_join_path(__DIR__, 'platforms', 'class.'.$classname.'.php');
            if (is_file($fn)) {
                include $fn;
            } else {
                return false;
            }
        }
        $obj = new $spaced($mod);
        if ($obj) {
            return self::setgate($obj);
        }
        return false;
    }

    /**
     *
     * @param mixed $obj platform class object
     * @return mixed int gate id | false
     */
    public static function setgate($obj)
    {
        $alias = $obj->get_alias();
        if (!$alias) {
            return false;
        }
        $title = $obj->get_name();
        if (!$title) {
            return false;
        }
        $desc = $obj->get_description();
        if (!$desc) {
            $desc = null;
        }

        $db = Lone::get('Db');
        //upsert, sort-of
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX.'module_outmailer_platforms WHERE alias=?';
        $gid = $db->getOne($sql, [$alias]);
        if (!$gid) {
            $sql = 'INSERT INTO '.CMS_DB_PREFIX.'module_outmailer_platforms (alias,title,description) VALUES (?,?,?)';
            $db->execute($sql, [$alias, $title, $desc]);
            $gid = $db->Insert_ID();
        } else {
            $gid = (int)$gid;
            $sql = 'UPDATE '.CMS_DB_PREFIX.'module_outmailer_platforms SET title=?,description=? WHERE id=?';
            $db->execute($sql, [$title, $desc, $gid]);
        }
        return $gid;
    }

    /**
     *
     * @param $mod OutMailer module instance
     */
    public static function refresh_platforms($mod)
    {
        $bp = cms_join_path(__DIR__, 'platforms', '');
        $files = glob($bp.'class.*email_platform.php');
        if (!$files) {
            return;
        }
        $db = Lone::get('Db');
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX.'module_outmailer_platforms WHERE alias=?';
        $found = [];
        foreach ($files as &$one) {
            include_once $one;
            $classname = str_replace([$bp, 'class.', '.php'], ['', '', ''], $one);
            $spaced = 'OutMailer\platforms\\'.$classname;
            $obj = new $spaced($mod);
            $alias = $obj->get_alias();
            $res = $db->getOne($sql, [$alias]);
            if (!$res) {
                $res = $obj->upsert_tables();
            }
            $found[] = $res;
        }
        unset($one);

        $fillers = implode(',', $found);
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.'module_outmailer_platforms WHERE id NOT IN ('.$fillers.')';
        $db->execute($sql);
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.'module_outmailer_props WHERE gate_id NOT IN ('.$fillers.')';
        $db->execute($sql);
    }

    /**
     *
     * @param int $gid TODO for table field: id or platform_id ??
     * @param array $props each member an array, with
     *  [0]=title [1]=apiname [2]=value [3]=encrypt
     */
    public static function setprops(int $gid, array $props)
    {
        $db = Lone::get('Db');
        $pref = CMS_DB_PREFIX;
        //upsert, sort-of
        //NOTE new parameters added with apiname 'todo' & signature NULL
        $sql1 = <<<EOS
UPDATE {$pref}module_outmailer_props SET title=?,plainvalue=?,encvalue=?,
signature = CASE WHEN signature IS NULL THEN ? ELSE signature END,
encrypt=?,apiorder=? WHERE gate_id=? AND apiname=?
EOS;
        //just in case (platform_id,apiname) is not unique-indexed by the db
        $sql2 = <<<EOS
INSERT INTO {$pref}module_outmailer_props (platform_id,title,plainvalue,encvalue,apiname,signature,encrypt,apiorder)
SELECT ?,?,?,?,?,?,?,? FROM (SELECT 1 AS dmy) Z WHERE NOT EXISTS
(SELECT 1 FROM {$pref}module_outmailer_props T1 WHERE T1.platform_id=? AND T1.apiname=?)
EOS;
        //10 params needed
        $o = 1;
        foreach ($props as &$data) {
            if ($data[3]) {
                $a1 = [$data[0], null, $data[2], $data[1], 1, $o, $gid, $data[1]];
                $a2 = [$gid, $data[0], null, $data[2], $data[1], $data[1], 1, $o, $gid, $data[1]];
            } else {
                $a1 = [$data[0], $data[2], null, $data[1], 0, $o, $gid, $data[1]];
                $a2 = [$gid, $data[0], $data[2], null, $data[1], $data[1], 0, $o, $gid, $data[1]];
            }
            $db->execute($sql1, $a1);
            $db->execute($sql2, $a2);
            ++$o;
        }
        unset($data);
    }

    /**
     *
     * @param $mod OutMailer module instance UNUSED
     * @param int $gid platform enumerator/id
     * @return array each key = signature-field value, each value = array with keys
     *   'apiname' and 'value' (for which the actual value is decrypted if relevant)
     */
    public static function getprops($mod, int $gid) : array
    {
        $pw = PrefCrypter::decrypt_preference(PrefCrypter::MKEY);
        $db = Lone::get('Db');
        $props = $db->getAssoc('SELECT signature,apiname,plainvalue,encvalue,encrypt FROM '.CMS_DB_PREFIX.
         'module_outmailer_props WHERE gate_id=? AND enabled>0 ORDER BY apiorder',
         [$gid]);
        foreach ($props as &$row) {
            if ($row['encrypt']) {
                $row['value'] = Crypto::decrypt_string($row['encvalue'], $pw);
            } else {
                $row['value'] = $row['plainvalue'];
            }
            unset($row['encrypt'], $row['plainvalue'], $row['encvalue']);
        }
        unset($row, $pw);
        $pw = null;
        return $props;
    }

    /**
     *
     * @param $mod OutMailer module instance
     * @return string, delivery-reports URL, accesses the 'devreport' action of this module
     */
    public static function get_reporturl($mod) : string
    {
        //construct frontend-url (so no admin login is needed)
//      $url1 = $mod->CreateLink('_', 'devreport', 1, '', [], '', true);
        $url = FormUtils::create_action_link($mod, [
         'getid' => '_',
         'action' => 'devreport',
         'returnid' => 1, // fake value
         'onlyhref' => true,
         'format' => 2,
        ]);
        //strip the fake returnid, so that the default will be used
        $sep = strpos($url, '&amp;');
        return substr($url, 0, $sep);
    }

    /**
     * @param type $mod
     * @param $parms (if it exists) is either a Lang key or one of the
     *  OutMailer\base_email_platform::STAT_* constants
     * @return string
     */
    public static function get_msg($mod, ...$parms) : string
    {
        $ip = getenv('REMOTE_ADDR');
        if ($parms) {
            if (lang_key_exists($mod->GetName(), ...$parms)) {
                $txt = $mod->Lang(...$parms);
                if ($ip) {
                    $txt .= ','.$ip;
                }
            } else {
                $txt = implode(',', $parms);
                if ($ip && $parms[0] != base_email_platform::STAT_NOTSENT) {
                    $txt .= ','.$ip;
                }
            }
            return $txt;
        }
        return $ip;
    }

    // This is a varargs function, 2nd argument (if it exists) may be a Lang key
    public static function get_delivery_msg($mod, ...$parms)
    {
        $ip = getenv('REMOTE_ADDR');
        if ($parms) {
            if (lang_key_exists($mod->GetName(), ...$parms)) {
                $txt = $mod->Lang(...$parms);
            } else {
                $txt = implode(',', $parms);
            }
            if ($ip) {
                $txt .= ','.$ip;
            }
            return $txt;
        }
        return $ip;
    }
/*
  public static function log_send($ip_address, $mobile, $msg, $statuOutMailer = '')
    {
        $db = Lone::get('Db');
        $sql = 'INSERT INTO '.CMS_DB_PREFIX.'module_outmailer_sent (mobile,ip,msg,sdate) VALUES (?,?,?,NOW())';
        $db->execute($sql, [$mobile, $ip_address, $msg]);
    }

    public static function clean_log(&$mod = null, $time = 0)
    {
        if (!$time) {
            $time = time();
        }
        if ($mod === null) {
            $mod = AppUtils::get_module('OutMailer');
        }
        $days = $mod->GetPreference('logdays', 1);
        if ($days < 1) {
            $days = 1;
        }
        $time -= $days * 86400;
        $db = Lone::get('Db');
        if ($mod->GetPreference('logsends')) {
            $limit = $db->DbTimeStamp($time);
            $db->execute('DELETE FROM '.CMS_DB_PREFIX.'module_outmailer_sent WHERE sdate<'.$limit);
        }
        $db->execute('DELETE FROM '.CMS_DB_PREFIX.'adminlog WHERE timestamp<? AND (item_id='.OutMailer::AUDIT_SEND.
        ' OR item_id = '.OutMailer::AUDIT_DELIV.') AND item_name='.'OutMailer', [$time]);
    }

    public static function ip_can_send(&$mod, $ip_address)
    {
        $db = Lone::get('Db');
        $t = time();
        $longnow = $db->DbTimeStamp($t);

        $limit = $mod->GetPreference('hourlimit', 0);
        if ($limit > 0) {
            $date = $db->DbTimeStamp($t - 3600);
            $sql = 'SELECT COUNT(mobile) AS num FROM '.CMS_DB_PREFIX.
             "module_outmailer_sent WHERE ip=? AND (sdate BETWEEN $date AND $longnow)";
            $num = $db->getOne($sql, [$ip_address]);
            if ($num > $limit) {
                return false;
            }
        }
        $limit = $mod->GetPreference('daylimit', 0);
        if ($limit > 0) {
            $date = $db->DbTimeStamp($t - 24 * 3600);
            $sql = 'SELECT COUNT(mobile) AS num FROM '.CMS_DB_PREFIX.
             "module_outmailer_sent WHERE ip=? AND (sdate BETWEEN $date AND $longnow)";
            $num = $db->getOne($sql, [$ip_address]);
            if ($num > $limit) {
                return false;
            }
        }
        return true;
    }
*/
    /* *
    implode_with_key:
    Implode @assoc into a string suitable for forming a URL string with multiple key/value pairs
    @assoc associative array, keys = parameter name, values = corresponding parameter values
    @inglue optional string, inner glue, default '='
    @outglue optional string, outer glue, default '&'
    Returns: string
    */
/*    public static function implode_with_key($assoc, $inglue = '=', $outglue = '&')
    {
        $ret = '';
        foreach ($assoc as $tk => $tv) {
            $ret .= $outglue.$tk.$inglue.$tv;
        }
        return substr($ret, strlen($outglue));
    }
*/
}
