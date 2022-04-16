<?php
/*
The interface for interacting with deprecated pseudocron tasks
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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
namespace CMSMS;

/**
 * An interface for interacting with pseudocron tasks.
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 * @since 1.8 as CmsRegularTask
 * @deprecated since 2.2 See CMSMS\Async\RegularTask and CMSMS\Async\Job
 */
interface IRegularTask
{
  /**
   * Get the name for this task. Actually, the full classname, namespaced as appropriate.
   *
   * @return string
   */
  public function get_name();


  /**
   * Get the description for this task.
   *
   * @return string
   */
  public function get_description();


  /**
   * Test if the task should be executed having regard to the supplied time argument
   *
   * @param   int $time The time at which any comparisons for execution should be performed.  If empty the current time is assumed.
   * @return boolean TRUE if the task should be executed, FALSE otherwise.
   */
  public function test($time = 0);


  /**
   * Execute a given task
   *
   * @param  int $time The time at which the task should consider the execution occurred at.  Assume the current time if empty.
   * @return bool TRUE on success, FALSE otherwise.
   */
  public function execute($time = 0);


  /**
   * Execute steps that should be taken on success of this task.
   * This method is called after the execute method if that method returned TRUE.
   *
   * @param  int $time The time at which the task should consider the execution occurred at.  Assume the current time if empty.
   */
  public function on_success($time = 0);


  /**
   * Execute steps that should be taken on failure of this task.
   * This method is called after the execute method if that method returned FALSE.
   *
   * @param  int $time The time at which the task should consider the execution occurred at.  Assume the current time if empty.
   */
  public function on_failure($time = 0);

} // interface

\class_alias('CMSMS\IRegularTask', 'CmsRegularTask', false);
