<?php
/*
Class passchk: functions for password entropy checking
Derived from work (C) 2008-2019 Tyler Akins <fidian@rumkin.com>
See http://rumkin.com/tools/password/passchk.php

This file is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of the License, or (at your
option) any later version.

This file is distributed in the hope that it will be useful but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
General Public License for more details.

You should have received a copy of that license along with passchk.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace passchk;

final class passchk
{
    private $Frequency_Table = [];

    /*
    The compressed frequency-data is as follows:
    Each three letters represent a base-95 encoded number indicating the chance
    that this combination comes next. Subtract the value of ' ' from each
    of the three, then ((((first_value * 95) + second_value) * 95) + third_value)
    gives the odds that this pair is grouped together.  The first is "  "
    (non-alpha chars), then " a", " b" ... " y", " z", "a ", "aa", "ab",
    and so on.  If the data are unpacked successfully, there should be a
    really large number for "qu".
    */
    private function Parse_Frequency()
    {
        require_once __DIR__.DIRECTORY_SEPARATOR.'frequency.php';

        $os = ord(' ');
        $l = (int)(strlen($Frequency_List) / 3) * 3;
        for ($i = 0; $i < $l; $i += 3) {
            $c = ord($Frequency_List[$i]) - $os;
            $c /= 95;
            $c += ord($Frequency_List[$i + 1]) - $os;
            $c /= 95;
            $c += ord($Frequency_List[$i + 2]) - $os;
            $this->Frequency_Table[] = $c / 95;
        }
        unset($Frequency_List);
        $Frequency_List = null;
    }

    /*
    $ch is a byte from a lower-cased string
    */
    private function Get_Index($ch)
    {
        $o = ord($ch);
        if ($o < 97) { // 97 == ord('a')
            return 0;
        } elseif ($o <= 122) { // 122 == ord('z')
            return $o - 96; // 1-based index
        } elseif ($o >= 128) {
            // fake something for non-ASCII chars
            while ($o > 122) {
                $o -= 31; // = 128 - 97
            }
            return $o;
        } else {
            return 0;
        }
    }

    private function Get_Charset_Size($pass)
    {
        $chars = 0;
        foreach ([
            ['/[a-z]/', 26],
            ['/[A-Z]/', 26],
            ['/[0-9]/', 10],
            ['/\s/', 1],
            ['/[!@#$%^&*()]/', 10],
            ['/([^\w!@#$%^&*()\x00-\x20\x7f-\xff]|_)/', 22], // OR  '[`~\-_+=:;\'"<,>.?\/\\|{}\[\]]' , 22
            ['/\x80-\xff/', 128],
        ] as $bundle) {
            if (preg_match($bundle[0], $pass)) {
                $chars += $bundle[1];
            }
        }
        return max($chars, 1);
    }

    public function Check($pass)
    {
        $l = strlen($pass);
        if ($l > 1) {
            if (!$this->Frequency_Table) {
                $this->Parse_Frequency();
            }
            $bits = 0;
            $plower = strtolower($pass);
            $charSet = log10($this->Get_Charset_Size($pass)) / log10(2);
            $aidx = $this->Get_Index($plower[0]);
            $ll = strlen($plower); // prob. == $l
            for ($b = 1; $b < $ll; $b++) {
                $bidx = $this->Get_Index($plower[$b]);
                $c = 1.0 - $this->Frequency_Table[$aidx * 27 + $bidx];
                $bits += $charSet * $c * $c; // squared = assmume they are good guessers
                $aidx = $bidx;
            }
            return (int)$bits;
        }
        return 0;
    }
} // class
