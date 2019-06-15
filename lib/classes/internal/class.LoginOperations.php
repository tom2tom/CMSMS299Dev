<?php
#login methods class
#Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS\internal;

use cms_cookies;
use cms_siteprefs;
use cms_utils;
use CmsApp;
use CMSMS\AppState;
use CMSMS\User;
use CMSMS\UserOperations;
use LogicException;
use RuntimeException;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function cleanValue;

final class LoginOperations
{
    //TODO namespaced global variables here
    /**
     * @ignore
     */
    private static $_instance = null;
    /**
     * @ignore
     */
    private static $_loginkey;
    /**
     * @ignore
     */
    private static $_data;

    /**
     * @ignore
     */
    private function __construct()
    {
        self::$_loginkey = $this->_get_salt();
    }

    /* *
     * @ignore
     */
    private function __clone() {}

    /**
     * Get the instance of this class.
     * @return LoginOperations
     */
    public static function get_instance() : self
    {
        if( !self::$_instance ) { self::$_instance = new self(); }
		return self::$_instance;
    }

    public function deauthenticate()
    {
        cms_cookies::erase(self::$_loginkey);
        unset($_SESSION[self::$_loginkey],$_SESSION[CMS_USER_KEY]);
    }

    /**
     * Get current or newly-generated salt
     * @ignore
     */
    private function _get_salt() : string
    {
        if( !AppState::test_state(AppState::STATE_INSTALL) ) {
            $salt = cms_siteprefs::get('loginsalt');
            if( !$salt ) {
                $salt = $this->create_csrf_token();
                cms_siteprefs::set('loginsalt',$salt);
                global_cache::release('site_preferences');
            }
            return $salt;
        }
        else {  //must avoid siteprefs circularity
            return $this->create_csrf_token();
        }
    }

    /**
     * validate the user
     * @param mixed? $uid
     * @param mixed? $hash
     * @return boolean
     */
    private function _check_passhash($uid,$hash) : bool
    {
        // we already validated that payload was not corrupt
        $user = UserOperations::get_instance()->LoadUserByID((int)$uid);
        if( !$user ) {
            return FALSE;
        }
        if( !$user->active ) {
            return FALSE;
        }
        $hash = (string) $hash;
        if( !$hash ) {
            return FALSE;
        }

        $data = get_object_vars($user);
        unset($data['password']);
        $data[] = __FILE__;

        return password_verify(json_encode($data), $hash);
    }

    /**
     * save session/cookie data
     *
     * @param User $user
     * @param mixed $effective_user Optional User | null
     * @return boolean TRUE always
     * @throws LogicException
     */
    public function save_authentication(User $user,$effective_user = null)
    {
        if( $user->id < 1 || empty($user->password) ) throw new RuntimeException('User information invalid for '.__METHOD__);

        $private_data = [
        'uid' => $user->id,
        'username' => $user->username,
        'eff_uid' => null,
        'eff_username' => null,
        ];
        if( $effective_user && $effective_user->id > 0 && $effective_user->id != $user->id ) {
            $private_data['eff_uid'] = $effective_user->id;
            $private_data['eff_username'] = $effective_user->username;
        }

        $data = get_object_vars($user);
        unset($data['password']); //actual (maybe-changed) P/W hash not useful here
        $data[] = __FILE__;

        $private_data['hash'] = password_hash(json_encode($data), PASSWORD_DEFAULT);
        $enc = base64_encode(json_encode($private_data));
        $hash = sha1($this->_get_salt() . $enc);
        $_SESSION[self::$_loginkey] = $hash.'::'.$enc;
        cms_cookies::set(self::$_loginkey,$_SESSION[self::$_loginkey]);

        self::$_data = null;
        return true;
    }

    public function create_csrf_token()
    {
        return cms_utils::random_string(12, true);
    }

    /* @return mixed array | null : previously- or currently-generated data */
    private function _get_data()
    {
        if( !empty(self::$_data) ) return self::$_data;

        // use session- and/or cookie-data to check whether user is authenticated
        if( isset($_SESSION[self::$_loginkey]) ) {
            $private_data = $_SESSION[self::$_loginkey];
        }
        elseif( isset($_COOKIE[self::$_loginkey]) ) {
            $private_data = $_SESSION[self::$_loginkey] = cleanValue($_COOKIE[self::$_loginkey]);
        }
        else {
            $private_data = null;
        }

        if( !$private_data ) return;
        $parts = explode('::',$private_data,2);
        if( count($parts) != 2 ) return;

        if( $parts[0] != sha1($this->_get_salt() . $parts[1]) ) return; // payload corrupted
        $private_data = json_decode(base64_decode( $parts[1]), TRUE);

        if( !is_array($private_data) ) return;
        if( empty($private_data['uid']) ) return;
        if( empty($private_data['username']) ) return;
        if( empty($private_data['hash']) ) return;

        // now authenticate the passhash
        if( !CmsApp::get_instance()->is_frontend_request() && !$this->_check_passhash($private_data['uid'],$private_data['hash']) ) return;

        // if we get here, the user is authenticated.
        if( isset($_GET[CMS_SECURE_PARAM_NAME]) ) {
            $_SESSION[CMS_USER_KEY] = $_GET[CMS_SECURE_PARAM_NAME];
        }
        elseif( !isset($_SESSION[CMS_USER_KEY]) ) {
            // if we don't have a user key.... we generate a new csrf token.
            $_SESSION[CMS_USER_KEY] = $this->create_csrf_token();
        }

        self::$_data = $private_data;
        return $private_data;
    }

    /**
     *
     * @return boolean
     * @throws LogicException
     */
    public function validate_requestkey()
    {
        // asume we are authenticated
        // now we validate that the request has the user key in it somewhere.
        if( !isset($_SESSION[CMS_USER_KEY]) ) throw new RuntimeException('Internal: User key not found in session.');

        $v = $_REQUEST[CMS_SECURE_PARAM_NAME] ?? '<no$!tgonna!$happen>';

        // validate the key in the request against what we have in the session.
        if( $v != $_SESSION[CMS_USER_KEY] ) {
//            $config = cms_config::get_instance();
//            if( !isset($config['stupidly_ignore_xss_vulnerability']) )
            return FALSE;
        }
        return TRUE;
    }

    public function get_loggedin_uid()
    {
        $data = $this->_get_data();
        if( !$data ) return;
        return (int) $data['uid'];
    }

    public function get_loggedin_username()
    {
        $data = $this->_get_data();
        if( !$data ) return;
        return trim($data['username']);
    }

    public function get_loggedin_user()
    {
        $uid = $this->get_loggedin_uid();
        if( $uid < 1 ) return;
        $user = UserOperations::get_instance()->LoadUserByID($uid);
        return $user;
    }

    public function get_effective_uid()
    {
        $data = $this->_get_data();
        if( !$data ) return;
        if( !empty($data['eff_uid']) ) return $data['eff_uid'];
        return $this->get_loggedin_uid();
    }

    public function get_effective_username()
    {
        $data = $this->_get_data();
        if( !$data ) return;
        if( !empty($data['eff_username']) ) return $data['eff_username'];
        return $this->get_loggedin_username();
    }

    public function set_effective_user(User $e_user = null)
    {
        $li_user = $this->get_loggedin_user();
        if( $e_user && $e_user->id == $li_user->id ) return;

        $new_key = $this->save_authentication($li_user,$e_user);
        return $new_key;
    }
}
