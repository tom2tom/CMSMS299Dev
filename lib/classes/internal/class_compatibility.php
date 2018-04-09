<?php

global $CMS_JOB_TYPE, $DONT_LOAD_SMARTY;
if ($CMS_JOB_TYPE < 2 && !isset($DONT_LOAD_SMARTY)) {
	class_alias('\CMSMS\internal\Smarty', 'Smarty_CMS', false);
}
