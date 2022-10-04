<?php
/*
HTMLEditor module action: import editor files to the website, from CDN etc
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

//if( some worthy test fails ) exit;
if(!$this->CheckPermission('Modify Site Preferences') ) exit;

$fp = cms_join_path(__DIR__, 'lib', 'summernote', 'summernote-lite.min.js');
if (!is_file($fp)) {
	$val = $this->SetPreference('source_url');
/*	TODO retrieve from there the main script plus all related plugins etc
	if (ini_get('allow_url_fopen')) {
		file_get_contents($val.'/*');
	OR
		stream_get_contents(fopen($val.'/*', 'rb'));
	} elseif (cURL available) {
		get_remote_data($val.'/*'); // GET requests
	}
	save in & below dirname($fp);
*/
}
$this->SetPreference('source_url', $this->GetModuleURLPath().'/lib/summernote');
$this->Redirect($id,'defaultadmin', $returnid);

/*
PHP cURL function, auto-follows recursive redirections
source: https://github.com/ttodua/useful-php-scripts
echo get_remote_data("http://example.com"); // GET
echo get_remote_data("http://example.com", "var2=something&var3=blabla"); // POST
* /
function get_remote_data($url, $post_paramtrs=false, $curl_opts=[])
{
	$c = curl_init();
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	//if parameters were passed to this function, then transform into POST method.. (if you need GET request, then simply change the passed URL)
	if ($post_paramtrs) {
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_POSTFIELDS, (is_array($post_paramtrs)? http_build_query($post_paramtrs) : $post_paramtrs));
	}
	curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_COOKIE, 'CookieName1=Value;');
	$headers[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:76.0) Gecko/20100101 Firefox/76.0";
	$headers[] = "Pragma: ";  $headers[]= "Cache-Control: max-age=0";
	if (!empty($post_paramtrs) && !is_array($post_paramtrs) && is_object(json_decode($post_paramtrs))) {
		$headers[] = 'Content-Type: application/json'; $headers[]= 'Content-Length: '.strlen($post_paramtrs);
	}
	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($c, CURLOPT_MAXREDIRS, 10);
	//if SAFE_MODE or OPEN_BASEDIR is set,then FollowLocation cant be used.. so...
	$follow_allowed = !(ini_get('open_basedir') || ini_get('safe_mode'));
	if ($follow_allowed) {
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
	}
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 9);
	curl_setopt($c, CURLOPT_REFERER, $url);
	curl_setopt($c, CURLOPT_TIMEOUT, 60);
	curl_setopt($c, CURLOPT_AUTOREFERER, true);
	curl_setopt($c, CURLOPT_ENCODING, '');
	curl_setopt($c, CURLOPT_HEADER, !empty($extra['return_array']));
	//set extra options if passed
	if (!empty($curl_opts)) {
		foreach($curl_opts as $key=>$value) {
			curl_setopt($c, constant($key), $value);
		}
	}
	$data = curl_exec($c);
	if(!empty($extra['return_array'])) {
		 preg_match("/(.*?)\r\n\r\n((?!HTTP\/\d\.\d).*)/si",$data, $x); preg_match_all('/(.*?): (.*?)\r\n/i', trim('head_line: '.$x[1]), $headers_, PREG_SET_ORDER); foreach($headers_ as $each){ $header[$each[1]] = $each[2]; }   $data=trim($x[2]);
	}
	$status=curl_getinfo($c); curl_close($c);
	// if redirected, then get that redirected page
	if ($status['http_code']==301 || $status['http_code']==302) {
		//if we FOLLOWLOCATION was not allowed, then re-get REDIRECTED URL
		//p.s. We dont need "else", because if FOLLOWLOCATION was allowed, then we wouldnt have come to this place, because 301 could already auto-followed by curl  :)
		if (!$follow_allowed) {
			//if REDIRECT URL is found in HEADER
			if( empty($redirURL)) {
				if(!empty($status['redirect_url'])) { $redirURL=$status['redirect_url']; }
			}
			//if REDIRECT URL is found in RESPONSE
			if (empty($redirURL)) {
				preg_match('/(Location:|URI:)(.*?)(\r|\n)/si', $data, $m);
				if (!empty($m[2])) { $redirURL=$m[2]; }
			}
			//if REDIRECT URL is found in OUTPUT
			if (empty($redirURL)) {
				preg_match('/moved\s\<a(.*?)href\=\"(.*?)\"(.*?)here\<\/a\>/si',$data,$m);
				if (!empty($m[1])){ $redirURL=$m[1]; }
			}
			//if URL found, then re-use this function again, for the found url
			if (!empty($redirURL)) {
				$t = debug_backtrace();
				return call_user_func($t[0]["function"], trim($redirURL), $post_paramtrs);
			}
		}
	}
	// if not redirected, and not "status 200" page, then error..
	elseif ( $status['http_code'] != 200 ) {
		$data = "ERRORCODE22 with $url<br><br>Last status codes:".json_encode($status)."<br><br>Last data got:$data";
	}
	//URLS correction
	$answer = (!empty($extra['return_array']) ? array('data'=>$data, 'header'=>$header, 'info'=>$status) : $data);
	return $answer;
}
*/
