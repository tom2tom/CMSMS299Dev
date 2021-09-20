<?php
/*
This file is part of CMS Made Simple module: OutMailer
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file OutMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/outmailer
*/
namespace OutMailer\platforms;

use OutMailer\base_email_platform;
use OutMailer\Utils;

class mailchimp_platform extends base_email_platform
{
    private const MCHIMP_API_URL = 'https://mailchimp.com/developer/api/';
//    private $rawstatus;

/*  public function __construct($mod)
    {
        parent::__construct($mod);
    }
*/
    protected function setup()
    {
        include_once __DIR__.DIRECTORY_SEPARATOR.'platform-autoloader.php';
    }

    public function get_name()
    {
        return 'Mailchimp';
    }

    public function get_alias()
    {
        return 'mailchimp';
    }

    public function get_description()
    {
        return $this->mod->Lang('description_mailchimp'); // TODO OR db-recorded value
    }

    public function AddAddress(){}
    public function RemoveAddress(){}
    public function ModifyAddress(){}
    public function GetAddress(){}

    public function SetConfirmto(){}
    public function GetConfirmto(){}

    public function SetFrom($from){}
    public function RemoveFrom($from){}

    public function SetList(){}
    public function GetList(){}
    public function GetLists(){}
    public function GetListAddresses(){}

    public function SetContent($msg, $is_html){}
    public function GetContent(){}

    public function SetWebhook($name, $url){}

    public function Send(){}
    public function SendCmd(){}

    public function Connect(){}
    public function DisConnect(){}
    protected function prep_command(){}

    public function upsert_tables()
    {
        $gid = Utils::setgate($this);
        if ($gid) {
            parent::set_gateid($gid);
            $mod = $this->mod;
            //setprops() argument $props = array of arrays, each with [0]=title [1]=apiname [2]=value [3]=encrypt
            Utils::setprops($gid, [
             [$mod->Lang('username'), 'user', null, 0],
             [$mod->Lang('password'), 'password', null, 1],
             [$mod->Lang('apiid'), 'api_id', null, 0]
            ]);
        }
        return $gid;
    }

    public function custom_setup(&$tplvars, $pmod)
    {
        foreach ($tplvars['data'] as &$ob) {
            if ($ob->signature == 'password') {
                $ob->size = 20;
                break;
            }
        }
        unset($ob);
        if ($pmod) {
            $tplvars['help'] = $this->mod->Lang('info_urlcheck', self::MCHIMP_API_URL, self::get_name());
        }
    }

    public function custom_save($params){}

    public function process_delivery_report(){}

/*  public function get_raw_status()
    {
        return $this->rawstatus;
    }
*/
    protected function parse_result($str){}
}
