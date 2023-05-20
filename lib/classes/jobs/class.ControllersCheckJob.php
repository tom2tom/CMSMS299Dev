<?php
/*
Class ControllersCheckJob: periodic integrity checks and reinstatement of .htaccess|web.config files
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\jobs;

use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMSMS\Lone;
use const CMS_ROOT_PATH;
use const CONFIG_FILE_LOCATION;
use function cms_join_path;
use function get_server_permissions;

class ControllersCheckJob extends CronJob
{
    private $places = [
        'ROOT/BASE',
        'TMP/BASE',
        'ROOT/lib/BASE',
        'ROOT/doc/BASE',
        'ADMIN/BASE',
        'ADMIN/themes/BASE',
        'USERTAGS/BASE',
        'UPLOADS/BASE',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\ControllersCheck';
        $this->frequency = RecurType::RECUR_HALFDAILY;
    }

    /**
     * @ignore
     * @return int 0|1|2 indicating execution status
     */
    public function execute()
    {
        $str = $_SERVER['SERVER_SOFTWARE'] ?? '';
        if (!$str) {
            $str = ''.PHP_SAPI;
        }
        if (stripos($str, 'apache') !== false) { $base = '.htaccess'; }
        elseif (stripos($str, 'iis') !== false) { $base = 'web.config'; }
        else { return 0; }

        $config = Lone::get('Config');
        $admin = $config['admin_path'];
        $tags = $config['usertags_path'];
        $uploads = $config['uploads_path'];
        $fromb = cms_join_path(CMS_ROOT_PATH, 'lib', 'security') . DIRECTORY_SEPARATOR;
        $modes = get_server_permissions();
        $res = 1;

        foreach ($this->places as $tpl) {
            $fp = str_replace([
            '/',
            'ROOT',
            'TMP',
            'ADMIN',
            'USERTAGS',
            'UPLOADS',
            'BASE',
            ],[
            DIRECTORY_SEPARATOR,
            CMS_ROOT_PATH,
            basename(CONFIG_FILE_LOCATION),
            $admin,
            $tags,
            $uploads,
            $base,
            ], $tpl);
            if (is_file($fp)) {
                if (!is_readable($fp)) {
                    chmod($fp, $modes[1]);
                    if (!is_writable($fp)) {
                        //TODO handle fatal error
                    }
                }
                if (0) { // TODO content/type/mode changed
                    //reinstate
                    $from = $this->original($fromb, $fp, $base);
                    copy($from, $fp);
                    chmod($fp, $modes[0]);
                    $res = 2;
                }
            } else {
                //reinstate
                $from = $this->original($fromb, $fp, $base);
                copy($from, $fp);
                chmod($fp, $modes[0]);
                $res = 2;
            }
        }
        return $res;
    }

    /**
     * @access private
     * @ignore
     * @param string $fromb filepath of backup security files
     * @param string $of filepath of file to be reinstated
     * @param string $base type i.e. web.config or .htaccess
     * @return string
     */
    private function original(string $fromb, string $of, string $base) : string
    {
        return $fromb .'TODO';
    }
}
