<?php
# Module: DesignManager - A CMSMS addon module to provide template management.
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

final class DesignManager extends CMSModule
{
    public function GetFriendlyName()  { return $this->Lang('friendlyname'); }
    public function GetVersion()  { return '1.1.6'; }
    public function MinimumCMSVersion()  { return '2.1'; }
    public function LazyLoadAdmin() { return true; }
    public function LazyLoadFrontend() { return true; }
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function HasAdmin() { return true; }
    public function GetAdminSection() { return 'layout'; }
    public function IsAdminOnly() { return true; }
    public function AllowAutoInstall() { return true; }
    public function GetHelp() { return $this->Lang('help_module'); }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.inc'); }
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }

    public function VisibleToAdminUser()
    {
        return $this->CheckPermission('Add Templates') ||
            $this->CheckPermission('Modify Templates') ||
            $this->CheckPermission('Manage Stylesheets') ||
            $this->CheckPermission('Manage Designs') ||
            count(LayoutTemplateOperations::get_editable_templates(get_userid()));
    }

    public function DoAction($name,$id,$params,$returnid='')
    {
        $smarty = CmsApp::get_instance()->GetSmarty();
        $smarty->assign('mod',$this);
        return parent::DoAction($name,$id,$params,$returnid);
    }

    public function GetHeaderHTML()
    {
        $out = '';
        $urlpath = $this->GetModuleURLPath();

        $fmt = '<link rel="stylesheet" type="text/css" href="%s/%s" />';
        $cssfiles = [
        'css/module.css',
        ];
        foreach( $cssfiles as $one ) {
            $out .= sprintf($fmt,$urlpath,$one)."\n";
        }
        return $out;
    }

    public function GetAdminMenuItems()
    {
        $out = [];
        if( $this->VisibleToAdminUser() ) $out[] = CmsAdminMenuItem::from_module($this);

        if( $this->CheckPermission('Modify Site Preferences') ) {
            $obj = new CmsAdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'layout';
            $obj->title = $this->Lang('title_designmanager_settings');
            $obj->description = $this->Lang('desc_designmanager_settings');
            $obj->action = 'admin_settings';
			$obj->icon = false;
            $out[] = $obj;
        }
        return $out;
    }

    public function GetEventHelp( $eventname )
    {
        return lang('event_help_'.$eventname);
    }

    public function GetEventDescription( $eventname )
    {
        return lang('event_desc_'.$eventname);
    }

	/**
	 * A module method for handling module response with ajax actions, returning a JSON encoded response.
	 * @param  string $status The status of returned response, in example error, success, warning, info
	 * @param  string $message The message of returned response
	 * @param  mixed $data A string or array of response data
	 * @return string Returns a string containing the JSON representation of provided response data
	 */
	public function GetJSONResponse($status, $message, $data = null)
	{

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

			$handlers = ob_list_handlers();
			for ($cnt = 0; $cnt < count($handlers); $cnt++) { ob_end_clean(); }

			header('Content-type:application/json; charset=utf-8');

			if ($data) {
				$json = json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
			} else {
				$json = json_encode(['status' => $status, 'message' => $message]);
			}

			echo $json;
			exit;
		}

		return false;
	}
} // class

