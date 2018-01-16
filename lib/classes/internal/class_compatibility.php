<?php

/* TODO evaluate these
use cms_admin_tabs as CmsAdminTabs;
use cms_admin_utils as CmsAdminUtils;
use cms_cache_driver as CmsCacheDriver;
use cms_cache_handler as CmsCacheHandler;
use cms_config as CmsConfig;
use cms_content_tree as CmsContentTree;
use cms_cookies as CmsCookies;
use cms_filecache_driver as CmsFilecacheDriver;
use cms_http_request as CmsHttpRequest;
use cms_mailer as CmsMailer;
use cms_module_smarty_plugin_manager as CmsModuleSmartyPluginManager;
use cms_route_manager as CmsRouteManager;
use cms_siteprefs as CmsSiteprefs;
use cms_tree as CmsTree;
use cms_tree_operations as CmsTreeOperations;
use cms_url as CmsUrl;
use cms_userprefs as CmsUserprefs;
use cms_utils as CmsUtils;
use CMSModule as CmsModule;
use CMSModuleContentType as CmsModuleContentType;
class_alias('CmsModule','CMSModule',false); //for autoloader?
*/

global $DONT_LOAD_SMARTY;
if (!isset($DONT_LOAD_SMARTY)) {
	class_alias('\CMSMS\internal\Smarty','Smarty_CMS',false);
}
