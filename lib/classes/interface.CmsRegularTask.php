<?php
#An interface to define how tasks should work.
#Copyright (C) 2004-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

/**
 * An interface to define how tasks should work.
 *
 * @package CMS
 * @license GPL
 * @since 1.8
 */
interface CmsRegularTask
{
  /**
   * Get the name for this task
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
   * Test if a function should be executed given the supplied time argument
   *
   * @param   int $time The time at which any comparisons for execution should be performed.  If empty the current time is assumed.
   * @returns boolean TRUE IF the task should be executed, FALSE otherwise.
   */
  public function test($time = '');


  /**
   * Execute a given task
   *
   * @param  int $time The time at which the task should consider the execution occurred at.  Assume the current time if empty.
   * @return bool TRUE on success, FALSE otherwise.
   */
  public function execute($time = '');


  /**
   * Execute steps that should be taken on success of this task.
   * This method is called after the execute step if the execute step returned TRUE.
   *
   * @param  int $time The time at which the task should consider the execution occurred at.  Assume the current time if empty.
   */
  public function on_success($time = '');


  /**
   * Execute steps that should be taken on failure of this task.
   * This method is called after the execute step if the execute step returned FALSE.
   *
   * @param  int $time The time at which the task should consider the execution occurred at.  Assume the current time if empty.
   */
  public function on_failure($time = '');

} // interface
