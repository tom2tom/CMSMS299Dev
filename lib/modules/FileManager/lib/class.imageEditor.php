<?php
#FileManager module class for manipulating images
#Copyright (C) 2006-2018 Morten Poulsen <morten@poulsen.org>
#Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace FileManager;

/**
 * Public utility class used to manipulate instances of images.
 */
final class imageEditor
{
	private function __construct() {}

	/**
	 * process a resize on a instance of image
	 *
	 * @param image the instance of image
	 * @param mimeType the mimetype of the image
	 * @param image_width the new width
	 * @param image_height the new height
	 *
	 * @return image instance of the image resized
	 **/
	public static function resize($image, $mimeType, $image_width, $image_height){

		$newImage = @imagecreatetruecolor($image_width, $image_height);
		if ($mimeType && ($mimeType == image_type_to_mime_type(IMAGETYPE_GIF) || $mimeType == image_type_to_mime_type(IMAGETYPE_PNG))) {
			//Keep transparency
			imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
			imagealphablending($newImage, false);
			imagesavealpha($newImage, true);
		}

		imagecopyresampled($newImage, $image, 0, 0, 0, 0, $image_width, $image_height, imagesx($image), imagesy($image));
		return $newImage;
	}

	/**
	 * process a crop on a instance of image
	 *
	 * @param image the instance of image
	 * @param mimeType the mimetype of the image
	 * @param crop_x the x position to begin the crop (top-left)
	 * @param crop_y the y position to begin the crop (top-left)
	 * @param crop_width the width to end the crop (from the left to the right)
	 * @param crop_height the height to end the crop (from the top to the bottom)
	 *
	 * @return image instance of the image cropped
	 **/
	public static function crop($image, $mimeType, $crop_x, $crop_y, $crop_width, $crop_height){

		$newImage = @imagecreatetruecolor($crop_width, $crop_height);
		if ($mimeType && ($mimeType == image_type_to_mime_type(IMAGETYPE_GIF) || $mimeType == image_type_to_mime_type(IMAGETYPE_PNG))) {
			//Keep transparency
			imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
			imagealphablending($newImage, false);
			imagesavealpha($newImage, true);
		}

		imagecopyresampled($newImage, $image, 0, 0, $crop_x, $crop_y, $crop_width, $crop_height, $crop_width, $crop_height);
		return $newImage;
	}

	/**
	 * return the mimetype of a file
	 *
	 * @param path the path of the file
	 *
	 * @return mime the mimetype of the file
	 **/
	public static function getMime($path){
		$info = getimagesize($path);
		if (!$info) {
			return false;
		}
		$mime = image_type_to_mime_type($info[2]);
		if($mime != image_type_to_mime_type(IMAGETYPE_JPEG)
			&& $mime != image_type_to_mime_type(IMAGETYPE_GIF)
			&& $mime != image_type_to_mime_type(IMAGETYPE_PNG)){
			return false;
		}
		return $mime;
	}

	/**
	 * return the width of a file
	 *
	 * @param path the path of the file
	 *
	 * @return int the width of the file
	 **/
	public static function getWidth($path){
		$info = getimagesize($path);
		if (!$info) {
			return false;
		}
		return $info[0];
	}

	/**
	 * Will load the file $path and return an instance of image
	 *
	 * @param path the path of the file
	 *
	 * @return image instance of the image
	 **/
	public static function open($path) {

		$mimeType = self::getMime($path);
		if (!$mimeType){
			return 'INVALID IMAGE TYPE';
		}

		if ($mimeType == image_type_to_mime_type(IMAGETYPE_JPEG)) {
			return imagecreatefromjpeg($path);
		}
		else if ($mimeType == image_type_to_mime_type(IMAGETYPE_GIF)) {
			return imagecreatefromgif($path);
		}
		else if ($mimeType == image_type_to_mime_type(IMAGETYPE_PNG)) {
			return imagecreatefrompng($path);
		}

		return NULL;
	}

	/**
	 * Will save the instance of $image into the file $path
	 *
	 * @param image instance of the image
	 * @param path the path of the file
	 * @param mimeType the mimetype of the image
     * @return bool
	 **/
	public static function save($image, $path, $mimeType){
		if ($mimeType == image_type_to_mime_type(IMAGETYPE_JPEG)) {
			return imagejpeg($image, $path);
		} else if ($mimeType == image_type_to_mime_type(IMAGETYPE_GIF)) {
			return imagegif($image, $path);
		} else if ($mimeType == image_type_to_mime_type(IMAGETYPE_PNG)) {
			imagesavealpha($image, true);
			return imagepng($image, $path);
		}
	}
}

