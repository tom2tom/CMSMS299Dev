<?php
/*
Reflectable method-definitions for form-tag creation
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
If not, see <https://www.gnu.org/licenses/licenses.html>.
*/
namespace CMSMS;

/**
 * Form-tag creation method-definitions - for reflection
 *
 * The methods here are intended to at least make sure that id is included
 * in the|each element's name, and that the|each element is syntax-compliant.
 * Typically a default class is applied or added.
 *
 * @since 3.0
 */
interface IFormTags
{
   /**
	* Returns a form element representing a tooltip help link.
	*
	* @param string $helptext	The help text to be shown on mouse over
	* @param string $linktext	The text to be shown as the link, default to a simple question mark
	* @param string $forcewidth Forces another width of the popup box than the one set in admin css
	* @param string $classname	An alternative classname for the a-link of the tooltip
	* @param string $href		The URL or URL-portion to use in the href portion of the generated link
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters, and/or anything else relevant to the created tag.
	*
	* @return string
	*/

	public function CreateTooltip($helptext, $linktext = '?', $forcewidth = '',
					$classname = 'admin-tooltip admin-tooltip-box', $href = '', $attrs = []);
   /**
	* Returns a form element representing a tooltip-enabled href link.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $action		The action that this form should do when the link is clicked
	* @param string $returnid	The id to eventually return to when the module is finished it's task
	* @param string $contents	The text that will have to be clicked to follow the link
	* @param string $tooltiptext The helptext to be shown as tooltip-popup
	* @param string $params		An array of params to be included in the URL of the link.	 These should be in a $key=>$value format
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateTooltipLink($id, $action, $returnid, $contents,
					$tooltiptext, $params = [], $attrs = []);
   /**
	* Returns a form element representing a fieldset and legend.
	*
	* @param mixed  $id			    string|null The id given to the module on execution, or falsy for admin hence 'm1_' (not really used yet, but might be later)
	* @param string $name			The html name of the element
	* @param string $legend_text	The legend_text for this fieldset, if applicaple
	* @param string $addtext		Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param string $addtext_legend	Any additional text to be added into the legend tag when rendered
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/

	public function CreateFieldsetStart($id, $name, $legend_text = '', $addtext = '', $addtext_legend = '', $attrs = []);
   /**
	* Returns a form element representing the end of a fieldset.
	* This is just a wrapper around </fieldset>. It's here for consistency.
	*
	* @return string
	*/

	public function CreateFieldsetEnd();
   /**
	* Returns a form element representing the start of a module form,
	* optimized for frontend use.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $returnid	The page id to eventually return to when the module is finished it's task
	* @param string $action		The name of the action that this form should do when the form is submitted
	* @param string $method		Method to use for the form tag.  Defaults to 'post'
	* @param string $enctype	Optional enctype to use, Good for situations where files are being uploaded
	* @param bool	$inline		A flag to determine if actions should be handled inline (no moduleinterface.php -- only works for frontend)
	* @param string $idsuffix	Text to append to the end of the id and name of the form
	* @param array  $params		Extra parameters to pass along when the form is submitted
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateFrontendFormStart(
		$id,
		$returnid,
		$action = 'default',
		$method = 'post',
		$enctype = '',
		$inline = true,
		$idsuffix = '',
		$params = [],
		$attrs = []
	);

   /**
	* Returns a form element representing the start of a module form.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $action		The action that this form should do when the form is submitted
	* @param string $returnid	The page id to eventually return to when the module is finished it's task
	* @param string $method		Method to use for the form tag.  Defaults to 'post'
	* @param string $enctype	Optional enctype to use, Good for situations where files are being uploaded
	* @param bool	$inline		A flag to determine if actions should be handled inline (no moduleinterface.php -- only works for frontend)
	* @param string $idsuffix	Text to append to the end of the id and name of the form
	* @param array  $params		Extra parameters to pass along when the form is submitted
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateFormStart(
		$id,
		$action = 'default',
		$returnid = '',
		$method = 'POST',
		$enctype = '',
		$inline = false,
		$idsuffix = '',
		$params = [],
		$addtext = '',
		$attrs = []
	);

   /**
	* Returns a form element representing the end of the a module form.
	* This is just a wrapper around </form>. It's here for consistency.
	*
	* @return string
	*/

	public function CreateFormEnd();
   /**
	* Returns a form element representing an input textbox.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the element
	* @param string $value		The initial value of the textbox, if any
	* @param string $size		The number of columns wide the textbox should be displayed
	* @param string $maxlength	The maximum number of characters to be allowed to be entered
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputText($id, $name, $value = '', $size = '10', $maxlength = '255', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing a label for an input field.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the input element this label is associated with
	* @param string $labeltext	The text in the label
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateLabelForInput($id, $name, $labeltext = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input textbox with label.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the element
	* @param string $value		The initial value of the textbox, if any
	* @param string $size		The number of columns wide the textbox should be displayed
	* @param string $maxlength	The maximum number of characters to be allowed to be entered
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param string $label		The text for label
	* @param string $labeladdtext Any additional text to be added into the tag
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputTextWithLabel(
		$id,
		$name,
		$value = '',
		$size = '10',
		$maxlength = '255',
		$addtext = '',
		$label = '',
		$labeladdtext = '',
		$attrs = []
	);

   /**
	* Returns a form element representing an input of type color.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $addtext Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputColor($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type date.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $addtext Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputDate($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type datetime.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $addtext Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputDatetime($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type datetime-local.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $addtext Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputDatetimeLocal($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type month.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $addtext Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputMonth($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type week.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $addtext Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputWeek($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type time.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $addtext Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputTime($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type number.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $addtext Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputNumber($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type range.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $addtext Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputRange($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type email.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $size	The number of columns wide the textbox should be displayed
	* @param string $maxlength The maximum number of characters to be allowed to be entered
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputEmail($id, $name, $value = '', $size = '10', $maxlength = '255', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input textbox of type tel.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $size	The number of columns wide the textbox should be displayed
	* @param string $maxlength The maximum number of characters to be allowed to be entered
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputTel($id, $name, $value = '', $size = '10', $maxlength = '255', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type search.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $size	The number of columns wide the textbox should be displayed
	* @param string $maxlength The maximum number of characters to be allowed to be entered
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputSearch($id, $name, $value = '', $size = '10', $maxlength = '255', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input of type URL.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The initial value of the textbox, if any
	* @param string $size	The number of columns wide the textbox should be displayed
	* @param string $maxlength The maximum number of characters to be allowed to be entered
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputUrl($id, $name, $value = '', $size = '10', $maxlength = '255', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing a file-selector field.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $accept The MIME-type to be accepted, default is all
	* @param string $size	The number of columns wide the textbox should be displayed. Deprecated since 3.0 - use $attrs[] instead
	* @param string $addtext Any additional text to be added into the tag
	*
	* @return string
	*/
	public function CreateInputFile($id, $name, $accept = '', $size = '10', $addtext = '', $attrs = []);

	/* *
	* Returns a form element representing a file upload input.
	* @deprecated alias for this method: CreateFileUploadInput()
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the element
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param int	$size		The size of the text field associated with the file upload field.  Some browsers may not respect this value
	* @param int	$maxlength	The maximim length of the content of the text field associated with the file upload field.  Some browsers may not respect this value
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
//	public function CreateInputFileUpload($id, $name, $addtext = '', $size = '10', $maxlength = '255', $attrs = []);

   /**
	* Returns a form element representing a password input.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	 	he html name of the element
	* @param string $value		The initial value of the textbox, if any
	* @param string $size		The number of columns wide the textbox should be displayed
	* @param string $maxlength	The maximum number of characters to be allowed to be entered
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputPassword($id, $name, $value = '', $size = '10', $maxlength = '255', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing a hidden field.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the element
	* @param string $value		The initial value of the field, if any
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputHidden($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing a checkbox.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the element
	* @param string $value		The value returned from the input if selected
	* @param string $selectedvalue The current value. If equal to $value the checkbox is selected
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputCheckbox($id, $name, $value = '', $selectedvalue = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing a submit button.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the element
	* @param string $value		The button label
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param string $image		Use an image instead of a regular button
	* @param string $confirmtext Optional text to display in a confirmation message
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputSubmit($id, $name, $value = '', $addtext = '', $image = '', $confirmtext = '', $attrs = []);

   /**
	* Returns a form element representing a reset button.
	*
	* @param mixed  $id		string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name	The html name of the element
	* @param string $value	The button label
	* @param string $addtext Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputReset($id, $name, $value = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing a dropdown list.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the element
	* @param string $items		An array of items to put into the dropdown list... they should be $key=>$value pairs
	* @param string $selectedindex The default selected index of the dropdown list.  Setting to -1 will result in the first choice being selected
	* @param string $selectedvalue The default selected value of the dropdown list.  Setting to '' will result in the first choice being selected
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputDropdown($id, $name, $items, $selectedindex = -1, $selectedvalue = '', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing an input field with datalist options.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the element
	* @param string $value		The initial value of the textbox, if any
	* @param string $items		An array of items to put into the list... they should be $key=>$value pairs
	* @param string $size		The number of columns wide the textbox should be displayed
	* @param string $maxlength	The maximum number of characters to be allowed to be entered
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputDataList($id, $name, $value, $items, $size = '10', $maxlength = '255', $addtext = '', $attrs = []);

   /**
	* Returns a form element representing a multi-select list.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the element
	* @param string $items		An array of items to put into the list... they should be $key=>$value pairs
	* @param string $selecteditems An array of items in the list that should default to selected
	* @param string $size		The number of rows to be visible in the list (before scrolling)
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param bool	 $multiple	Flag indicating whether multiple selections are allowed (defaults to true)
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputSelectList($id, $name, $items, $selecteditems = [], $size = 3, $addtext = '', $multiple = true, $attrs = []);

   /**
	* Returns form elements representing a set of radio buttons.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $name		The html name of the element
	* @param string $items		An array of items to create as radio buttons... they should be $key=>$value pairs
	* @param string $selectedvalue The default selected index of the radio group.	 Setting to -1 will result in the first choice being selected
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param string $delimiter	A delimiter to throw between each radio button, e.g., a <br /> tag or something for formatting
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateInputRadioGroup($id, $name, $items, $selectedvalue = '', $addtext = '', $delimiter = '', $attrs = []);

   /**
	* Returns a form element representing a textarea.
	* Takes WYSIWYG preference into consideration if called from the admin side.
	*
	* @param bool	$enablewysiwyg Should we try to create a WYSIWYG for this textarea?
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $text		The text to initially display in the element
	* @param string $name		The html name of the element
	* @param string $classname	The CSS class to associate this textarea with
	* @param string $htmlid		The html id of this element
	* @param string $encoding	The encoding to use for the content
	* @param string $stylesheet	The text of the stylesheet associated to this content. Only used for certain WYSIWYGs
	* @param string $cols		The number of characters wide (columns) the resulting textarea should be
	* @param string $rows		The number of characters high (rows) the resulting textarea should be
	* @param string $forcewysiwyg	The wysiwyg-system to be forced even if the user has chosen another one
	* @param string $wantedsyntax	The language the content should be syntaxhightlighted as
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateTextArea(
		$enablewysiwyg,
		$id,
		$text,
		$name,
		$classname = '',
		$htmlid = '',
		$encoding = '',
		$stylesheet = '',
		$cols = '',
		$rows = '',
		$forcewysiwyg = '',
		$wantedsyntax = '',
		$addtext = '',
		$attrs = []
	);

   /**
	* Returns a form element representing a syntaxarea.
	* Takes Syntax hilighter preference into consideration if called
	* from the admin side.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $text		The text to initially display in the element
	* @param string $name		The html name of the element
	* @param string $classname	The CSS class to associate this textarea with
	* @param string $htmlid	The html id of this element
	* @param string $encoding	The encoding to use for the content
	* @param string $stylesheet The text of the stylesheet associated to this content.	 Only used for certain WYSIWYGs
	* @param string $cols		The number of characters wide (columns) the resulting textarea should be
	* @param string $rows		The number of characters high (rows) the resulting textarea should be
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateSyntaxArea(
		$id,
		$text,
		$name,
		$classname = '',
		$htmlid = '',
		$encoding = '',
		$stylesheet = '',
		$cols = '80',
		$rows = '15',
		$addtext = '',
		$attrs = []
	);

   /**
	* Returns a form element representing a link.
	*
	* @param mixed  $id				string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $returnid		The page-id to eventually return to when the module is finished it's task
	* @param string $action			The action that this form should do when the link is clicked
	* @param string $contents		The displayed clickable text for the link
	* @param string $params			An array of parameters to be included in the URL of the link.	 These should be in a $key=>$value format
	* @param string $warn_message	Text to display in a javascript warning box.  If the user click no, the link is not followed by the browser
	* @param bool	$onlyhref		Flag to determine if only the URL should be returned
	* @param bool	$inline			Flag to determine if actions should be handled inline (no moduleinterface.php -- only works for frontend)
	* @param string $addtext		Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param bool	$targetcontentonly Flag indicating that the output of this link should target the content area of the destination page
	* @param string $prettyurl		An optional pretty URL segment (relative to the root of the site) to use when generating the link
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @deprecated use CreateLink() with swapped parameters $action, $returnid
	*
	* @return string
	*/
	public function CreateFrontendLink(
		$id,
		$returnid,
		$action,
		$contents = '',
		$params = [],
		$warn_message = '',
		$onlyhref = false,
		$inline = true,
		$addtext = '',
		$targetcontentonly = false,
		$prettyurl = '',
		$attrs = []
	);

   /**
	* Returns a form element representing a link to a module action.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $action		The action that this form should do when the link is clicked
	* @param string $returnid	The page-id to eventually return to when the module is finished it's task
	* @param string $contents	The displayed clickable text for the link
	* @param string $params		An array of params to be included in the URL of the link. These should be in a $key=>$value format
	* @param string $warn_message	Text to display in a javascript warning box. If the user clicks no, the link is not followed by the browser
	* @param bool	$onlyhref	A flag to determine if only the href section should be returned
	* @param bool	$inline	A flag to determine if actions should be handled inline (no moduleinterface.php -- only works for frontend)
	* @param string $addtext	Any additional text to be added into the tag. Deprecated since 3.0 - use $attrs[] instead
	* @param bool	$targetcontentonly A flag to determine if the link should target the default content are of the destination page
	* @param string $prettyurl	An optional pretty URL segment (related to the root of the website) for a pretty URL
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters except $addtext, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateLink(
		$id,
		$action,
		$returnid = '',
		$contents = '',
		$params = [],
		$warn_message = '',
		$onlyhref = false,
		$inline = false,
		$addtext = '',
		$targetcontentonly = false,
		$prettyurl = '',
		$attrs = []
	);

   /**
	* Returns a form element representing a link to a site page.
	*
	* @param int	$pageid	The page id of the page we want to direct to
	* @param string $contents	The optional text or XHTML contents of the generated link
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateContentLink($pageid, $contents = '', $attrs = []);

   /**
	* Returns a form element representing a return-to-page link.
	*
	* @param mixed  $id			string|null The id given to the module on execution, or falsy for admin hence 'm1_'
	* @param string $returnid	The page-id to return to
	* @param string $contents	The displayed clickable text for the link
	* @param string $params		An array of parameters to be included in the URL of the link. These should be in a $key=>$value format
	* @param bool	$onlyhref	A flag to determine if only the href section should be returned
	* @param array  $attrs since 3.0 Element attributes. Each member like 'name'=>'value'. May include (and if so, will supersede) any of the aforementioned parameters, and/or anything else relevant to the created tag.
	*
	* @return string
	*/
	public function CreateReturnLink($id, $returnid, $contents = '', $params = [], $onlyhref = false, $attrs = []);
}
