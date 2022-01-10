<?php
/*
News module script: post / send article-publication approval notices.
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

//use CMSMS\AdminAlerts\SimpleAlert;
use CMSMS\TemplateOperations;
use CMSMS\Utils;
use function CMSMS\sanitizeVal;

$addr = trim($this->GetPreference('email_to'));
if ($addr) {

if ($title) {
    $title = sanitizeVal(strip_tags($title), CMSSAN_NONPRINT);
} else {
    $title = $this->Lang('error_detailed', $this->Lang('notitlegiven'));
}
if ($summary) {
    $summary = sanitizeVal(strip_tags($summary), CMSSAN_NONPRINT);
} else {
    $summary = $this->Lang('none'); //TODO better default
}
if (!$longstart) {
    $longstart = $this->Lang('none');
}
if (!$longend) {
    $longend = $this->Lang('none');
}
$tplname = $this->GetPreference('email_template');
if (!$tplname) {
    $obj = TemplateOperations::get_default_template_by_type('News::approvalmessage');
    $tplname = $obj->name;
}

try {
    $tpl2 = $smarty->CreateTemplate($this->GetTemplateResource($tplname));
    $tpl2->assign('title', $title)
      ->assign('summary', $summary)
      ->assign('startdate', $longstart)
      ->assign('enddate', $longend);
    $message = $tpl2->fetch();

    //$addr = trim($this->GetPreference('email_to')); see above
    //if ($addr) {
        $subject = $this->GetPreference('email_subject');
        if (!$subject) {
            $subject = $this->Lang('subject_newnews');
        }
        Utils::send_email($addr, $subject, $message); //,$additional_headers = [], string $additional_params = '')
    } catch (Throwable $t) {
        // TODO handle error
    }
} // email address

/* see DraftAlertJob which handles this in bundles
$alert = new SimpleAlert(['Approve News','Modify News']);
$alert->name = 'News Publication Approval Request';
$alert->title = $this->Lang('subject_newnews');
$alert->msg = $message;
$alert->priority = $alert::PRIORITY_LOW;
$alert->save();
*/
