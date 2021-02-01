<?php
/*
This file is part of CMS Made Simple module: CMSMailer
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file CMSMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/cmsmailer
*/
namespace CMSMailer\gateways;

use CMSMailer\base_email_gateway;
use CMSMailer\Utils;

class sendinblue_email_gateway extends base_email_gateway
{
    private const SENDIBL_API_URL = 'https://developers.sendinblue.com/reference';
//    private $rawstatus;

/*  public function __construct($mod)
    {
        parent::__construct($mod);
    }
*/
    protected function setup()
    {
        include_once __DIR__.DIRECTORY_SEPARATOR.'gateway-autoloader.php';
    }

    public function get_name()
    {
        return 'Sendinblue';
    }

    public function get_alias()
    {
        return 'sendinblue';
    }

    public function get_description()
    {
        return $this->mod->Lang('description_sendinblue'); // TODO OR db-recorded value
    }

    public function AddAddress(){}
    public function RemoveAddress(){}
    public function ModifyAddress(){}
    public function GetAddresses(){}

    public function SetConfirmto(){}
    public function GetConfirmto(){}

    public function SetFrom($from){}
    public function RemoveFrom($from){}

    public function SetContent($msg, $is_html){}

    public function Send(){}
    public function SendCmd(){}

    protected function prep_command(){}

    public function upsert_tables()
    {
        $gid = Utils::setgate($this);
        if ($gid) {
            parent::set_gateid($gid);
            $mod = $this->mod;
            //setprops() argument $props = array of arrays, each with [0]=title [1]=apiname [2]=value [3]=encrypt
            Utils::setprops($gid, [
             [$mod->Lang('username'), 'username', null, 0],
             [$mod->Lang('password'), 'password', null, 1],
             [$mod->Lang('from'), 'from', null, 0],
             [$mod->Lang('reference'), 'ref', null, 0]
            ]);
        }
        return $gid;
    }

    public function custom_setup(&$tplvars, $padm)
    {
        foreach ($tplvars['data'] as &$ob) {
            if ($ob->signature == 'password') {
                $ob->size = 20;
                break;
            }
        }
        unset($ob);
        if ($padm) {
            $tplvars['help'] = $this->mod->Lang('info_urlcheck', self::SENDIBL_API_URL, self::get_name());
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
