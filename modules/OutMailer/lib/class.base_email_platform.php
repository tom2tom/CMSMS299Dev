<?php
/*
This file is part of CMS Made Simple module: OutMailer
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file OutMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/outmailer
*/
namespace OutMailer;

use OutMailer\PrefCrypter;
use CMSMS\Crypto;
use CMSMS\FormUtils;
use CMSMS\Utils as AppUtils;
use RuntimeException;
use stdClass;
use const CMS_DB_PREFIX;
use function cmsms;
//use function CMSMS\log_info;

abstract class base_email_platform
{
    const STAT_OK = 'email_sent';
    const STAT_NOTSENT = 'email_notsent';
    const STAT_ERROR_OTHER = 'email_error_other';
    const STAT_ERROR_AUTH = 'email_error_auth';
    const STAT_ERROR_LIMIT = 'email_error_limit';
    const STAT_ERROR_INVALID_DATA = 'email_error_invalid_data';
    const STAT_ERROR_BLOCKED = 'email_error_blocked_number';

    const DELIVERY_OK = 'email_delivery_ok';
    const DELIVERY_PENDING = 'email_delivery_pending';
    const DELIVERY_INVALID = 'email_delivery_invalid';
    const DELIVERY_UNKNOWN = 'email_delivery_unknown';
    const DELIVERY_BILLING = 'email_delivery_billing';
    const DELIVERY_OTHER = 'email_delivery_other';

    protected $mod;
    protected $gate_id;
    protected $msg;
    protected $statusmsg;
    protected $use_curl;
    protected $status;
    protected $emailid;

    public function __construct($mod)
    {
        if (!function_exists('curl_version')) {
            throw new RuntimeException("External email processing by OutMailer requires PHP's cURL operations");
        }
        $this->mod = $mod;
        $this->gate_id = 0;
        self::reset();
    }

    //Perform pre-message-send initialisation or checks,if any
    //Returns nothing
    abstract protected function setup();

    /**
    reset:
    Clear all cached data
    */
    public function reset()
    {
        $this->num = '';
        $this->fromnum = '';
        $this->msg = '';
        $this->use_curl = 0;
        $this->status = self::STAT_NOTSENT;
        $this->statusmsg = '';
    }

    public function UseCurl($flag = true)
    {
        $this->use_curl = ($flag) ? 1 : 0;
    }

    /**
    get_status:
    Returns string describing (short-form) status, one of the const's defined above
    */
    public function GetStatus()
    {
        return $this->status;
    }

    /**
    get_statusmsg:
    Returns string describing (long-form) status ...
    */
/*    public function get_statusmsg()
    {
        return $this->statusmsg;
    }
*/
    /**
    send:
    Initiate the message transmission(after all relevant parameters are set up)
    */
/*    public function send()
    {
        $this->emailid = '';

        // check to make sure we have necessary data
        $this->setup();
        if ($this->num == '' || $this->msg == '') {
            $this->status = self::STAT_ERROR_INVALID_DATA;
            return false;
        }

        if (!Utils::ip_can_send($this->mod, getenv('REMOTE_ADDR'))) {
            $this->status = self::STAT_ERROR_LIMIT;
            return false;
        }

        // next prepare the output
        $cmd = $this->prep_command();
        if ($cmd === false || $cmd == '') {
            $this->status = self::STAT_ERROR_INVALID_DATA;
            return false;
        }

        // send it
        $res = $this->command($cmd);

        // interpret result
        $this->parse_result($res);
        $this->statusmsg = Utils::get_msg($this->mod, $this->num, $this->status, $this->msg, $this->get_raw_status());
        $success = ($this->status == self::STAT_OK);
        if ($success) {
            if ($this->mod->GetPreference('logsends')) {
                Utils::log_send(getenv('REMOTE_ADDR'), $this->num, $this->msg);
            }
            log_info(OutMailer::AUDIT_SEND, OutMailer::MODNAME, $this->statusmsg);
        }
        return $success;
    }
*/
    //for internal use only
    //get parameter stored (for some platforms) when operation result-message was parsed
/*    public function get_emailid()
    {
        return $this->emailid;
    }
*/
    /**
     *
     * @return string [x]html for echo into admin display, or maybe empty
     */
    public function get_setup_form() : string
    {
        $mod = $this->mod;
        $pmod = $mod->CheckPermission('Modify Email Gateways');
        $db = cmsms()->GetDb();
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.'module_outmailer_platforms WHERE alias=?';
        if (!$pmod) {
            $sql .= ' AND enabled=1';
        }
        $alias = $this->get_alias();
        $gdata = $db->GetRow($sql, [$alias]);
        if (!$gdata) {
            return '';
        }
        if (!$pmod) {
/*          $smarty = $mod->GetActionTemplateObject();
            if (!$smarty) {
                global $smarty;
            }
            $smarty->assign([
                'gatetitle' => $gdata['title'],
                'default' => ($gdata['active']) ? $mod->Lang('yes') : ''
            ]);
            return $mod->ProcessTemplate('gatedata_use.tpl');
*/
            $smarty = $mod->GetActionTemplateObject();
            $tpl = $smarty->createTemplate($mod->GetTemplateResource('gatedata_use.tpl')); //,null,null,$smarty);
            $tpl->assign([
                'gatetitle' => $gdata['title'],
                'default' => ($gdata['active']) ? $mod->Lang('yes') : ''
            ]);
            return $tpl->fetch();
        }

        $parms = [];
        $sql = 'SELECT gate_id,title,plainvalue,encvalue,apiname,signature,encrypt,enabled FROM '.CMS_DB_PREFIX.'module_outmailer_props WHERE gate_id=?';
        if (!$pmod) {
            $sql .= ' AND enabled=1';
        }
        $sql .= ' ORDER BY apiorder';
        $gid = (int)$gdata['id'];
        $res = $db->GetArray($sql, [$gid]);
        if ($res) {
            $pw = PrefCrypter::decrypt_preference(PrefCrypter::MKEY);
            foreach ($res as &$row) {
                $ob = (object)$row;
                //adjustments
                if ($ob->encrypt) {
                    $ob->value = Crypto::decrypt_string(''.$ob->encvalue, $pw);
                } else {
                    $ob->value = $ob->plainvalue;
                }
                unset($ob->plainvalue, $ob->encvalue);
                $ob->space = $alias.'~'.$ob->apiname.'~'; //for platform-data 'namespace'
                $parms[] = $ob;
            }
            unset($row, $pw); //faster garbage-collection for $pw
            $pw = null;
        }
        $dcount = count($parms);
        if ($dcount == 0) {
            $ob = new stdClass();
            $ob->title = $mod->Lang('error_nodatafound');
            $ob->value = '';
            $ob->apiname = false; //prevent input-object creation
            $ob->space = '';
            $parms[] = $ob;
        }

        $tplvars = [
            'gatetitle' => $gdata['title'],
            'data' => $parms,
            'dcount' => $dcount,
            'space' => $alias, //for platform-data 'namespace'
            'gateid' => $gid
        ];

        if ($pmod) {
            $tplvars += [
                'help' => '',
                'title_title' => $mod->Lang('title'),
                'title_value' => $mod->Lang('value'),
                'title_encrypt' => $mod->Lang('encrypt'),
                'title_apiname' => $mod->Lang('apiname'),
                'title_enabled' => $mod->Lang('enabled'),
                'title_help' => $mod->Lang('help'),
                'title_select' => $mod->Lang('select')
            ];

            $id = 'm1_'; //module admin instance-id is hard-coded (OR $smarty->tpl_vars['actionid']->value)
            $text = $mod->Lang('add_parameter');
            $theme = AppUtils::get_theme_object();
            $addicon = $theme->DisplayImage('icons/system/newobject.gif', $text, '', '', 'systemicon');
            $args = ['gate_id' => $gid];
//            $tplvars['additem'] = $mod->CreateLink($id, 'opengate', '', $addicon, $args).' '.
//                $mod->CreateLink($id, 'opengate', '', $text, $args);
            $tplvars['additem'] = FormUtils::create_action_link($mod, [
                'modid' => $id,
                'action' => 'opengate',
                'contents' => $addicon,
                'params' => $args,
            ]).' '.FormUtils::create_action_link($mod, [
                'modid' => $id,
                'action' => 'opengate',
                'contents' => $text,
                'params' => $args,
            ]);
/*            if ($dcount > 0) {
                $tplvars['btndelete'] = $mod->CreateInputSubmit($id, $alias.'~delete',
                    $mod->Lang('delete'), 'title="'.$mod->Lang('delete_tip').'"');
                //confirmation js applied in $(document).ready() - see action.defaultadmin.php
            }
*/
        }
        // anything else to set up for the template
        $this->custom_setup($tplvars, $pmod); //e.g. each $ob->size
/*      $smarty = $mod->GetActionTemplateObject();
        if (!$smarty) {
            global $smarty;
        }
        $smarty->assign($tplvars);
        $tplname = ($pmod) ? 'gatedata_admin.tpl' : 'gatedata_mod.tpl';
        return $mod->ProcessTemplate($tplname);
*/
        $smarty = $mod->GetActionTemplateObject();
        $tplname = ($pmod) ? 'gatedata_admin.tpl' : 'gatedata_mod.tpl';
        $tpl = $smarty->createTemplate($mod->GetTemplateResource($tplname)); //,null,null,$smarty);
        $tpl->assign($tplvars);
        return $tpl->fetch();
    }

    /**
    handle_setup_form:
    @params: array of parameters provided after admin form 'submit'
    Parses relevant @params into stored data, or deletes stored data if so instructed
    */
    public function handle_setup_form($params)
    {
        $alias = $this->get_alias();
        $db = cmsms()->GetDb();

        $gid = (int)$params[$alias.'~gate_id'];
        unset($params[$alias.'~gate_id']);

        $this->custom_save($params); //any platform-specific adjustments to $params
        $delete = isset($params[$alias.'~delete']);

        $srch = [' ', "'", '"', '=', '\\', '/', '\0', "\n", "\r", '\x1a'];
        $repl = ['', '', '', '', '', '', '', '', '', ''];
        $conds = [];

        if ($delete) {
            unset($params[$alias.'~delete']);
            $sql12 = 'DELETE FROM '.CMS_DB_PREFIX.'module_outmailer_props WHERE gate_id=? AND apiname=?';
        }
        //accumulate data (in any order) into easily-usable format
        foreach ($params as $key => $val) {
            //$key is like 'clickatell~user~title'
            if (strpos($key, $alias) === 0) {
                $parts = explode('~', $key); //hence [0]=$alias,[1]=apiname-field value,[2](mostly)=fieldname to update
                if ($parts[2] && $parts[2] != 'sel' && !$delete) {
                    //foil injection-attempts
                    $parts[2] = str_replace($srch, $repl, $parts[2]);
                    if (preg_match('/[^\w~@#\$%&?+-:|]/', $parts[2])) {
                        continue;
                    }
                    if ($parts[1]) {
                        $parts[1] = str_replace($srch, $repl, $parts[1]);
                        if (preg_match('/[^\w~@#\$%&?+-:|]/', $parts[1])) {
                            continue;
                        }
                    } else {
                        $parts[1] = 'todo';
                    }
                    if (!array_key_exists($parts[1], $conds)) {
                        $conds[$parts[1]] = [];
                    }
                    $conds[$parts[1]][$parts[2]] = $val;
                } elseif ($delete && $parts[2] == 'sel') {
                    $db->Execute($sql12, [$gid, $parts[1]]);
                }
            }
        }
        if ($delete) {
            return;
        }

        $pmod = $this->mod->CheckPermission('Modify Email Gateways');
        $pw = PrefCrypter::decrypt_preference(PrefCrypter::MKEY);
        $o = 1;
        foreach ($conds as $apiname => &$data) {
            $enc = (isset($data['encrypt'])) ? 1 : 0;
            $data['encrypt'] = $enc;
            if ($enc) {
                $data['encvalue'] = Crypto::encrypt_string(''.$data['plainvalue'], $pw);
                $data['plainvalue'] = null;
            } else {
                $data['encvalue'] = null;
            }
            if ($pmod) {
                $data['enabled'] = (isset($data['enabled'])) ? 1 : 0;
            }
            $sql = 'UPDATE '.CMS_DB_PREFIX.'module_outmailer_props SET '
                .implode('=?,', array_keys($data)).
                '=?,signature=CASE WHEN signature IS NULL THEN ? ELSE signature END,apiorder=? WHERE gate_id=? AND apiname=?';
            //NOTE any record for a new parameter includes apiname='todo' & signature=NULL
            $sig = ($apiname != 'todo') ? $apiname : $data['apiname'];
            $args = array_merge(array_values($data), [$sig, $o, $gid, $apiname]);
            $db->Execute($sql, $args);
            ++$o;
        }
        unset($data, $pw);
        $pw = null;
    }

    // these for single|array
    abstract public function AddAddress();
    abstract public function RemoveAddress();
    abstract public function ModifyAddress();
    abstract public function GetAddress();

    abstract public function SetConfirmto();
    abstract public function GetConfirmto();

    abstract public function SetFrom($from);
    abstract public function RemoveFrom($from);

    abstract public function SetList();
    abstract public function GetList();
    abstract public function GetLists();
    abstract public function GetListAddresses();

    abstract public function SetContent($msg, $is_html);
    abstract public function GetContent();

    abstract public function SetWebhook($name, $url);

    abstract public function Send();
    abstract public function SendCmd();

    abstract public function Connect();
    abstract public function DisConnect();

    //For internal use only
    //Record or update platform-specific details in the module's database tables
    //Returns key-value of the row added to the gates-table, for the platform
    abstract public function upsert_tables();

    //For internal use only
    //Setup platform-specific details for defaultadmin action
    //$pmod = boolean, TRUE if current user has 'Modify Email Gateways' permission
    abstract public function custom_setup(&$tplvars, $pmod);

    //For internal use only
    //Process platform-specific details after 'submit' in defaultadmin action
    abstract public function custom_save($params);

    /**
    get_name:
    Returns string which is the(un-translated) platform identifier
    */
    abstract public function get_name();

    /**
    get_alias:
    Returns string which is the(un-translated) platform alias,used for classname etc
    */
    abstract public function get_alias();

    /**
    get_description:
    Returns string which is a(translated) brief description of the platform
    */
    abstract public function get_description();

    /**
    support_custom_sender:
    Returns boolean TRUE/FALSE according to whether the platform allows use of
    a user-specified source-phone-number(which might need to be a number pre-arranged
    with the platform supplier)
    */
//    abstract public function support_custom_sender();

    /**
    process_delivery_report:
    Interpret message-delivery report (details in $_REQUEST) and return resultant
    string, suitable for public display or logging
    */
//    abstract public function process_delivery_report();

    //For internal use only
    //Get string returned by platform in response to message-send process
//    abstract public function get_raw_status();

    protected function set_gateid($gid)
    {
        $this->gate_id = (int)$gid;
    }

    protected function get_gateid($alias, $force = false)
    {
        if ($force || !$this->gate_id) {
            $db = cmsms()->GetDb();
            $sql = 'SELECT id FROM '.CMS_DB_PREFIX.'module_outmailer_platforms WHERE alias=?';
            $gid = $db->GetOne($sql, [$alias]);
            $this->gate_id = (int)$gid;
        }
        return $this->gate_id;
    }

    //perform the send
    //may need to be over-ridden in some platform-specific subclasses
/*    protected function command($cmd)
    {
        $this->check_curl();
        $res = '';
        $res = ($this->use_curl == 0) ?
            $this->send_fopen($cmd) :
            $this->send_curl($cmd);
        return $res;
    }
*/
    //Construct the actual string to be sent to the platform
    //Returns that string,or else empty string or literal FALSE upon failure
    //May return dummy e.g. 'good' or ' ' if such string isn't needed
    abstract protected function prep_command();

    //Interpret $res(string or object,usually) returned from last message-send process
    abstract protected function parse_result($res);

    private function send_fopen($cmd)
    {
        $res = '';
        $fh = @fopen($cmd, 'r');
        if ($fh) {
            while ($line = @fgets($fh, 1024)) {
                $res .= $line;
            }
            fclose($fh);
            return $res;
        } else {
            return false;
        }
    }

    private function send_curl($cmd)
    {
        $ch = curl_init($cmd);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
/*      if ($this->curl_use_proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$this->curl_proxy);
            curl_setopt($ch,CURLOPT_PROXYUSERPWD,$this->curl_proxyuserpwd);
        }
*/
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    private function check_curl()
    {
        if (!$this->use_curl) {
            if (function_exists('curl_version')) {
                $this->use_curl = true;
            }
        }
    }
}
