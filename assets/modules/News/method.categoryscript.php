<?php
/*
Page-resources generator for category add/edit actions
Copyright (C) 2022-2023 CMS Made Simple Foundation News module installation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

$js = <<<EOS
$(function() {
  $('.cmsfp_elem').on('change', function() {
    var img = $(this).val();
    if (img) {
      $('.yesimage').show();
    } else {
      $('.yesimage').hide();
    }
    return false;
  }).triggerHandler('change');
  $('[name$="cancel"]').on('click', function() {
    $(this).closest('form').attr('novalidate', 'novalidate');
  });
});
EOS;
add_page_foottext($out);
