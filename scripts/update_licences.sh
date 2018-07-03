#!/bin/bash
# script to update copyright-year strings in .php source files
# uses sed & gawk, scans the first 5 lines of relevant files

#this is where the things are executed from, must be parent of twigs
SHAREDROOT='..'
#dirs to scan
TWIGS="lib admin assets uploads tests phar_installer"
#TWIGS="admin"
#TWIGS="tests"

THISYEAR=$(date +%04Y)
KEYYEAR=$(date +%-y)
LASTYEAR=$((${KEYYEAR}-1))
if [ "$LASTYEAR" -lt "10" ]; then
 PATTERN=0[0-$LASTYEAR]
else
 LASTTEN=$((${LASTYEAR}/10))
 if [ "$LASTTEN" -gt "1" ]; then
  LASTTEN="[1-$LASTTEN]"
 fi
 LASTONE=$((${LASTYEAR}%10))
 if [ "$LASTONE" -gt "0" ]; then
  LASTONE="[0-$LASTONE]"
 fi
 PATTERN="0[0-9]\\|$LASTTEN$LASTONE"
fi

KILLARG='/^#.*as a special exception.*$/,/^#\s*$/d'

PATTERN1="^\\(#\s*\\)CMS - CMS Made Simple"
#REPL1="\1This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>"
REPL1="\1..."
SEDARG1="1,2s~$PATTERN1~$REPL1~"

LONGPATTERN="^\\(\s*#\?\s*\\)[(][Cc][)].*20\\($PATTERN\\)-20\\($PATTERN\\).*Ted Kulp.*"
SHORTPATTERN="^\\(\s*#\?\s*\\)[(][Cc][)].*20\\($PATTERN\\).*Ted Kulp.*"
#echo "short = $SHORTPATTERN"
#echo "long = $LONGPATTERN"
SEDARG2A="1,5s~$LONGPATTERN~\1Copyright (C) 20\2-20\3 Ted Kulp <ted@cmsmadesimple.org>~"
SEDARG2B="1,5s~$SHORTPATTERN~\1Copyright (C) 20\2-$THISYEAR Ted Kulp <ted@cmsmadesimple.org>~"
#echo -e "sed patterns =\n$SEDARG2A\n$SEDARG2B\n"

LONGPATTERN2="^\\(\s*#\?\s*\\)[(][Cc][)].*20\\($PATTERN\\)-20\\($PATTERN\\).*[tT]he\s\+CMSMS\s\+Dev\\(elopment\\)\?\s\+Team.*"
SHORTPATTERN2="^\\(\s*#\?\s*\\)[(][Cc][)].*20\\($PATTERN\\).*[tT]he\s\+CMSMS\s\+Dev\\(elopment\\)\?\s\+Team.*"

SEDARG3A="1,6s~$LONGPATTERN2~\1Copyright (C) 20\2-$THISYEAR CMS Made Simple Foundation <foundation@cmsmadesimple.org>~"
SEDARG3B="1,6s~$SHORTPATTERN2~\1Copyright (C) 20\2-$THISYEAR CMS Made Simple Foundation <foundation@cmsmadesimple.org>~"
#echo -e "sed patterns =\n$SEDARG3A\n$SEDARG3B\n"

#PATTERN5="^#\s*Visit our homepage at: http:\/\/www.cmsmadesimple.org"
#SEDARG5="1,10{/$PATTERN5/d;}"
PATTERN5="^\\(#\s*\\)Visit our homepage at.*cmsmadesimple.org"
REPL5="\1This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>"
SEDARG5="1,10s~$PATTERN5~$REPL5~"

PATTERN6="WARRANthe TY"
REPL6="WARRANTY"
SEDARG6="1,40s~$PATTERN6~$REPL6~"

PATTERN8="^\\(\s*#\?\s*\\)along with this program[;.] [iI]f not.*$"
REPL8="\1along with this program. If not, see <https://www.gnu.org/licenses/>."
SEDARG8="1,40s~$PATTERN8~$REPL8~"

PATTERN9="^.*Foundation.*Inc.*Boston.*"
SEDARG9="1,40{/$PATTERN9/d;}"

PATTERN10="^\\(\s*#\?\s*\\)\$[iI]d.*\$"
REPL10="\1\$Id\$"
SEDARG10="1,40s~$PATTERN10~$REPL10~"

PATTERN11="^\\(\s*#\?\s*\\)Or read it online.*gnu.*licenses.*"
SEDARG11="20,40{/$PATTERN11/d;}"

cd $SHAREDROOT
SHAREDROOT=$(pwd)
echo "execute from $SHAREDROOT"
WORKFILE=./fileyears.txt
rm -f $WORKFILE

echo "process files in scanned dirs = $TWIGS"
# update the array usages
for dir in $TWIGS; do
  LEAVES=$(find -L $dir -type f -name \*.php -not -wholename \*svn\* -not -wholename \*git\* -exec echo -n "{} " \;)
  for LEAF in $LEAVES; do

# produce a 5-field description like -rw-rw-r-- 1   9108 2007 ACL-description.txt
# of which we use only the year
    LONGTEXT=$(ls -Gg --time-style=+%04Y $LEAF)
    MODYEAR=$(echo $LONGTEXT | gawk '{ print $4 }')
#    if [ "$MODYEAR" -lt "$THISYEAR" ]; then
#      echo "$LEAF is old"
#    else
    if [ "$MODYEAR" -ge "$THISYEAR" ]; then
#      echo "$LEAF needs to be checked"
      rm -f  $LEAF-newyear >/dev/null
      sed -e "$SEDARG1" -e "$SEDARG2A" -e "$SEDARG2B" -e "$SEDARG3A" -e "$SEDARG3B" -e "$KILLARG" -e "$SEDARG5" -e "$SEDARG6" -e "$SEDARG8" -e "$SEDARG9" -e "$SEDARG10" -e "$SEDARG11" $LEAF > $LEAF-newyear
      OLDCS=$(md5sum $LEAF | gawk '{ print $1 }')
      NEWCS=$(md5sum $LEAF-newyear | gawk '{ print $1 }')
      if [ "$OLDCS" = "$NEWCS" ] ; then
        echo "stet $LEAF"
        rm -f  $LEAF-newyear >/dev/null
      else
        if [ "$TWIGS" = "tests" ]; then
           mv -f $LEAF $LEAF-old >/dev/null
        fi
        mv -f $LEAF-newyear $LEAF >/dev/null
        echo "$LEAF has been updated"
      fi
    fi
  done
done

rm -f $WORKFILE
