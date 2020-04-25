<?php
# Enum-class that identifies various standard core capabilities
# Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# BUT withOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use CMSMS\BasicEnum;

/**
 * An enum that identifies various standard core capabilities
 *
 * @package CMS
 * @license GPL
 * @see CMSModule::HasCapability
 */
final class CoreCapabilities extends BasicEnum
{
	/**
	 * A constant indicating the module supports admin. searches
	 */
	const ADMINSEARCH = 'AdminSearch';

	/**
	 * A constant for a capability indicating the module provides content block types
	 */
	const CONTENT_BLOCKS = 'contentblocks';

	/**
	 * A constant for a capability indicating that the module provides custom content types
	 */
	const CONTENT_TYPES = 'content_types';

	/**
	 * A constant indicating that the module is core/system
	 * @since 2.9
	 */
	const CORE_MODULE = 'coremodule';

	/**
	 * A constant indicating that the module handles events
	 */
	const EVENTS = 'handles_events';

	/**
	 * A constant indicating that the module manages async jobs
	 * @since 2.9
	 */
	const JOBS_MODULE = 'handles_jobs';

	/**
	 * A constant indicating that the module handles admin console logins
	 * @since 2.9
	 */
	const LOGIN_MODULE = 'handles_login';

	/**
	 * A constant indicating that the module is a plugin module
	 */
	const PLUGIN_MODULE = 'plugin';

	/**
	 * A constant indicating that the module sets [non-]static route(s) during construction and/or initialization
	 * @since 2.3
	 */
	const ROUTE_MODULE = 'routable';

	/**
	 * A constant indicating that the module provides frontend search functionality.
	 */
	const SEARCH_MODULE = 'handles_search';

	/**
	 * A constant indicating that the module can contribute to site
	 * operations in accord with recorded preferences/settings. In effect,
	 * a generalisation of WYSIWYG_MODULE, SYNTAX_MODULE etc but geared
     * toward value-management via admin UI
	 * @since 2.9
	 */
	const SITE_SETTINGS = 'handles_sitevars';

	/**
	 * A constant indicating that the module is a syntax editor module.
	 * @since 2.0
	 */
	const SYNTAX_MODULE = 'handles_syntax';

	/**
	 * A constant indicating that the module provides pseudocron tasks
	 * See also JOBS_MODULE
	 */
	const TASKS = 'tasks'; // string used pre-2.0

	/**
	 * A constant indicating that the module contributes to site
	 * operations as they apply to individual admin users, in accord
	 * with recorded user preferences/settings. In effect, a
	 * generalisation of WYSIWYG_MODULE, SYNTAX_MODULE etc but
	 * geared toward value-management via admin UI
	 * @since 2.9
	 */
	const USER_SETTINGS = 'handles_uservars';

	/**
	 * A constant indicating that the module is a page-content editor module
	 * @since 2.0
	 */
	const WYSIWYG_MODULE = 'handles_wysiwyg';

} // class

// back compatibility shiv
\class_alias(CoreCapabilities::class, 'CmsCoreCapabilities', false);
