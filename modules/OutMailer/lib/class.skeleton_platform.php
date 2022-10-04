<?php
/*
This file is part of CMS Made Simple module: OutMailer
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file OutMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/outmailer
*/
namespace OutMailer\platforms;

use OutMailer\base_email_platform;

//class name must be like 'somealias_platform'
class skeleton_platform extends base_email_platform
{
    //TODO specific name and real URL for API reference
    const SKEL_API_URL = 'https://somewhere.com/...';
//    private $rawstatus;

/*  public function __construct($mod)
    {
        parent::__construct($mod);
    }
*/
    protected function setup()
    {
        //TODO
        include_once __DIR__.DIRECTORY_SEPARATOR.'platform-autoloader.php';
    }

    public function get_name()
    {
        //TODO
        return 'My Name';
    }

    public function get_alias()
    {
        //must be this class' name less the trailing '_platform'
        //TODO
        return 'skeleton';
    }

    public function get_description()
    {
        //string from class-method or database table
        return '';
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

    protected function prep_command()
    {
        //get 'public' parameters for interface
        $gid = parent::get_gateid(self::get_alias());
        $parms = Utils::getprops($this->mod, $gid);
        if (
         $parms['whatever']['value'] == false ||
         $parms['someother']['value'] == false
        ) {
            $this->status = parent::STAT_ERROR_AUTH;
            return false;
        }
        //convert $parms data format if needed
        //MORE $parms - to, from, body etc, format-adjusted as needed
        $str = Utils::implode_with_key($parms);
        $str = some_url.'?'.str_replace('&amp;', '&', $str);
        return $str;
    }

    public function upsert_tables()
    {
        $gid = Utils::setgate($this);
        if ($gid) {
            // more stuff
        }
        return $gid;
    }

    public function custom_setup(&$tplvars, $pmod)
    {
        //e.g.
        foreach ($tplvars['data'] as &$ob) {
            //set stuff e.g. $ob->size, $ob->help
        }
        unset($ob);
        if ($pmod) {
            $tplvars['help'] = $this->mod->Lang('info_urlcheck', self::SKEL_API_URL, self::get_name());
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
