<?php
/*
Interface for file-picking modules
Copyright (C) 2016-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
 * Interface for modules that provide file-pick functionality.
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 * @since 2.2 as FilePicker
 */
interface IFilePicker
{
    /**
     * Given a profile name and other data, return a suitable profile by name, or return a default profile
     *
     * @param mixed $profile_name string the desired profile name to load | falsy
     * @param string $dir Optional topmost-folder filepath
     * @param int $uid Optional admin user id
     * @return CMSMS\FilePickerProfile
     */
    public function get_profile_or_default($profile_name, string $dir = '', int $uid = 0) : FilePickerProfile;

    /**
     * Get the default profile for the specified parameters.
     *
     * @param string $dir Optional topmost-folder filepath
     * @param int $uid Optional admin user id
     * @return CMSMS\FilePickerProfile
     */
    public function get_default_profile(string $dir = '', int $uid = 0) : FilePickerProfile;

    /**
     * Get the URL to be accessed to populate the filepicker display
     *
     * @return string
     */
    public function get_browser_url() : string;

    /**
     * Get data for setting up a file-browse process
     * @since 3.0
     *
     * @param array $params Assoc. array of values to be used
     * @param bool $framed Optional flag, whether the display will be
     *  embedded in an iframe, or a specified DOM element. Default true.
     * @return array
     */
    public function get_browsedata(array $params, bool $framed = true) : array;

    /**
     * Generate HTML & related js, css for an input field that can be used to specify a selected file.
     *
     * @param string $name The name-attribute for the input field
     * @param mixed $value The initial/current value for the input field. null or -1 treated as empty string
     * @param CMSMS\FilePickerProfile $profile The profile to use when building the filepicker interface
     * @param bool   $required Optional flag, whether a choice must be provided in the generated element. Default false
     * @return string
     */
    public function get_html(string $name, /*mixed */$value, FolderControls $profile, bool $required = false) : string;
} // interface

\class_alias('CMSMS\IFilePicker', 'CMSMS\FilePicker');
