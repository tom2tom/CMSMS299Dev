<?php
/*
This file is part of CMS Made Simple module: CMSMailer
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file CMSMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/cmsmailer
*/
namespace CMSMailer;

use CMSMailer;
use CMSMS\Crypto;
use CMSMS\FormUtils;
use CMSMS\Utils as AppUtils;
use const CMS_DB_PREFIX;
use function cms_join_path;
use function cmsms;

class Utils
{
    /**
     *
     * @param mixed $mod optional CMSMailer module instance
     * @return array maybe empty
     */
    public static function get_gateways_full($mod = null) : array
    {
        $db = cmsms()->GetDb();
        $aliases = $db->GetCol('SELECT alias FROM '.CMS_DB_PREFIX.'module_cmsmailer_gates WHERE enabled>0');
        if (!$aliases) {
            return [];
        }
        $bp = cms_join_path(__DIR__, 'gateways', '');
        if ($mod === null) {
            $mod = AppUtils::get_module('CMSMailer');
        }
        $objs = [];
        foreach ($aliases as $one) {
            $classname = $one.'_email_gateway';
            $spaced = 'CMSMailer\\gateways\\'.$classname;
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
     * @param mixed $mod optional CMSMailer module instance
     * @return mixed gateway class | null
     */
    public static function get_gateway(bool $title = false, $mod = null)
    {
        $db = cmsms()->GetDb();
        $alias = ($title) ?
            $db->GetOne('SELECT alias FROM '.CMS_DB_PREFIX.'module_cmsmailer_gates WHERE title=? AND enabled>0', [$title]) :
            $db->GetOne('SELECT alias FROM '.CMS_DB_PREFIX.'module_cmsmailer_gates WHERE active>0 AND enabled>0');
        if ($alias) {
            $classname = $alias.'_email_gateway';
            $spaced = 'CMSMailer\\gateways\\'.$classname;
            if (!class_exists($spaced)) {
                $fn = cms_join_path(__DIR__, 'gateways', 'class.'.$classname.'.php');
                require $fn;
            }
            if ($mod === null) {
                $mod = AppUtils::get_module('CMSMailer');
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
     * @param $mod CMSMailer module instance
     * @param string $classname
     * @return bool
     */
    public static function setgate_full($mod, string $classname) : bool
    {
        $spaced = 'CMSMailer\\gateways\\'.$classname;
        if (!class_exists($spaced)) {
            $fn = cms_join_path(__DIR__, 'gateways', 'class.'.$classname.'.php');
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
     * @param mixed $obj gateway class object
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

        $db = cmsms()->GetDb();
        //upsert, sort-of
        $sql = 'SELECT gate_id FROM '.CMS_DB_PREFIX.'module_cmsmailer_gates WHERE alias=?';
        $gid = $db->GetOne($sql, [$alias]);
        if (!$gid) {
            $sql = 'INSERT INTO '.CMS_DB_PREFIX.'module_cmsmailer_gates (alias,title,description) VALUES (?,?,?)';
            $db->Execute($sql, [$alias, $title, $desc]);
            $gid = $db->Insert_ID();
        } else {
            $gid = (int)$gid;
            $sql = 'UPDATE '.CMS_DB_PREFIX.'module_cmsmailer_gates set title=?,description=? WHERE gate_id=?';
            $db->Execute($sql, [$title, $desc, $gid]);
        }
        return $gid;
    }

    /**
     *
     * @param $mod CMSMailer module instance
     */
    public static function refresh_gateways($mod)
    {
        $bp = cms_join_path(__DIR__, 'gateways', '');
        $files = glob($bp.'class.*email_gateway.php');
        if (!$files) {
            return;
        }
        $db = cmsms()->GetDb();
        $sql = 'SELECT gate_id FROM '.CMS_DB_PREFIX.'module_cmsmailer_gates WHERE alias=?';
        $found = [];
        foreach ($files as &$one) {
            include_once $one;
            $classname = str_replace([$bp, 'class.', '.php'], ['', '', ''], $one);
            $space = 'CMSMailer\\gateways\\'.$classname;
            $obj = new $space($mod);
            $alias = $obj->get_alias();
            $res = $db->GetOne($sql, [$alias]);
            if (!$res) {
                $res = $obj->upsert_tables();
            }
            $found[] = $res;
        }
        unset($one);

        $fillers = implode(',', $found);
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.'module_cmsmailer_gates WHERE gate_id NOT IN ('.$fillers.')';
        $db->Execute($sql);
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.'module_cmsmailer_props WHERE gate_id NOT IN ('.$fillers.')';
        $db->Execute($sql);
    }

    /**
     *
     * @param int $gid
     * @param array $props each member an array, with
     *  [0]=title [1]=apiname [2]=value [3]=encrypt
     */
    public static function setprops(int $gid, array $props)
    {
        $db = cmsms()->GetDb();
        $pref = CMS_DB_PREFIX;
        //upsert, sort-of
        //NOTE new parameters added with apiname 'todo' & signature NULL
        $sql1 = <<<EOS
UPDATE {$pref}module_cmsmailer_props SET title=?,value=?,encvalue=?,
signature = CASE WHEN signature IS NULL THEN ? ELSE signature END,
encrypt=?,apiorder=? WHERE gate_id=? AND apiname=?
EOS;
        $sql2 = <<<EOS
INSERT INTO {$pref}module_cmsmailer_props (gate_id,title,value,encvalue,apiname,signature,encrypt,apiorder)
SELECT ?,?,?,?,?,?,?,? FROM (SELECT 1 AS dmy) Z WHERE NOT EXISTS
(SELECT 1 FROM {$pref}module_cmsmailer_props T1 WHERE T1.gate_id=? AND T1.apiname=?)
EOS;
        $o = 1;
        foreach ($props as &$data) {
            if ($data[3]) {
                $a1 = [$data[0], null, $data[2], $data[1], 1, $o, $gid, $data[1]];
                $a2 = [$gid, $data[0], null, $data[2], $data[1], $data[1], 1, $o, $gid, $data[1]];
            } else {
                $a1 = [$data[0], $data[2], null, $data[1], 0, $o, $gid, $data[1]];
                $a2 = [$gid, $data[0], $data[2], null, $data[1], $data[1], 0, $o, $gid, $data[1]];
            }
            $db->Execute($sql1, $a1);
            $db->Execute($sql2, $a2);
            ++$o;
        }
        unset($data);
    }

    /**
     *
     * @param $mod CMSMailer module instance UNUSED
     * @param int $gid gateway enumerator/id
     * @return array each key = signature-field value, each value = array with keys
     *   'apiname' and 'value' (for which the actual value is decrypted if relevant)
     */
    public static function getprops($mod, int $gid) : array
    {
        $pw = PrefCrypter::decrypt_preference(PrefCrypter::MKEY);
        $db = cmsms()->GetDb();
        $props = $db->GetAssoc('SELECT signature,apiname,value,encvalue,encrypt FROM '.CMS_DB_PREFIX.
         'module_cmsmailer_props WHERE gate_id=? AND enabled>0 ORDER BY apiorder',
         [$gid]);
        foreach ($props as &$row) {
            if ($row['encrypt']) {
                $row['value'] = Crypto::decrypt_string($row['encvalue'], $pw);
            }
            unset($row['encrypt'], $row['encvalue']);
        }
        unset($row, $pw);
        $pw = null;
        return $props;
    }

    /**
     *
     * @param $mod CMSMailer module instance
     * @return string, delivery-reports URL, accesses the 'devreport' action of this module
     */
    public static function get_reporturl($mod) : string
    {
        //construct frontend-url (so no admin login is needed)
//      $url1 = $mod->CreateLink('_', 'devreport', 1, '', [], '', true);
        $url = FormUtils::create_action_link($mod, [
         'modid' => '_',
         'action' => 'devreport',
         'returnid' => 1, // fake value
         'onlyhref' => true,
        ]);
        //strip the fake returnid, so that the default will be used
        $sep = strpos($url, '&amp;');
        return substr($url, 0, $sep);
    }

    /**
     * @param type $mod
     * @param $parms (if it exists) is either a Lang key or one of the
     *  CMSMailer\base_email_gateway::STAT_* constants
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
                if ($ip && $parms[0] != base_email_gateway::STAT_NOTSENT) {
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
  public static function log_send($ip_address, $mobile, $msg, $statuCMSMailer = '')
    {
        $db = cmsms()->GetDb();
        $sql = 'INSERT INTO '.CMS_DB_PREFIX.'module_cmsmailer_sent (mobile,ip,msg,sdate) VALUES (?,?,?,NOW())';
        $db->Execute($sql, [$mobile, $ip_address, $msg]);
    }

    public static function clean_log(&$mod = null, $time = 0)
    {
        if (!$time) {
            $time = time();
        }
        if ($mod === null) {
            $mod = AppUtils::get_module('CMSMailer');
        }
        $days = $mod->GetPreference('logdays', 1);
        if ($days < 1) {
            $days = 1;
        }
        $time -= $days * 86400;
        $db = cmsms()->GetDb();
        if ($mod->GetPreference('logsends')) {
            $limit = $db->DbTimeStamp($time);
            $db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_cmsmailer_sent WHERE sdate<'.$limit);
        }
        $db->Execute('DELETE FROM '.CMS_DB_PREFIX.'adminlog WHERE timestamp<? AND (item_id='.CMSMailer::AUDIT_SEND.
        ' OR item_id = '.CMSMailer::AUDIT_DELIV.') AND item_name='.'CMSMailer', [$time]);
    }

    public static function ip_can_send(&$mod, $ip_address)
    {
        $db = cmsms()->GetDb();
        $t = time();
        $now = $db->DbTimeStamp($t);

        $limit = $mod->GetPreference('hourlimit', 0);
        if ($limit > 0) {
            $date = $db->DbTimeStamp($t - 3600);
            $sql = 'SELECT COUNT(mobile) AS num FROM '.CMS_DB_PREFIX.
             "module_cmsmailer_sent WHERE ip=? AND (sdate BETWEEN $date and $now)";
            $num = $db->GetOne($sql, [$ip_address]);
            if ($num > $limit) {
                return false;
            }
        }
        $limit = $mod->GetPreference('daylimit', 0);
        if ($limit > 0) {
            $date = $db->DbTimeStamp($t - 24 * 3600);
            $sql = 'SELECT COUNT(mobile) AS num FROM '.CMS_DB_PREFIX.
             "module_cmsmailer_sent WHERE ip=? AND (sdate BETWEEN $date and $now)";
            $num = $db->GetOne($sql, [$ip_address]);
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
