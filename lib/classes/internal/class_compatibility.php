<?php

global $DONT_LOAD_SMARTY;
if (!isset($DONT_LOAD_SMARTY)) {
	class_alias('\CMSMS\internal\Smarty', 'Smarty_CMS', false);
}
