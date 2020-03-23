<?php

use CMSMS\AppState;

if (!AppState::test_state(AppState::STATE_INSTALL)
 && CmsApp::get_instance()->JOBTYPE < 2) {
	class_alias('CMSMS\internal\Smarty', 'Smarty_CMS', false);
}
