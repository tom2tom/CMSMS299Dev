------------------------
Creating a CMSMS Release
------------------------

Creating a CMSMS release involves these steps:
  a: Do all of the required changes to the CMSMS branch in question (change the version.php, update the changelog, make sure those files are committed)
  b: Create the <installer-root>/assets/upgrade/<version> directory and its appropriate files
     MANIFEST.DAT -- this file is created with the 'create_manifest.php' script (see below)
     (a MANIFEST.DAT.GZ file is acceptable too)
     upgrade.php  -- (optional) the script to do any changes to the database or settings
        note: when this script is executed $db is available, the CMSMS api is created, however smarty is not available.
     readme.txt   -- (optional) readme file for display in the upgrade assistant
     changelog.txt -- (recommended) a text file describing specific changes to that version
     preprocess_files.php -- (optional) executed at the start of step 7 (if files step is enabled) to perform various tasks related to files
       This is useful if files must be moved around within the installation, and the manifest process cannot do it automatically.
       note: when this script is executed $destdir is available, however the CMSMS api is not.
  c: optionally delete sub-directories in <installer-root>/assets/upgrade that are no longer necessary.
  d: optionally edit file <installer-root>/assets/config.ini to specify the minimum upgrade version.
  d: commit those changes to SVN
  e: build the release packages (see below)
  f: ** Begin distribution process **
     - remember to create an svn tag if distributing

---------------------------
Building the files manifest
---------------------------

1.  Change dir into the <installer-root>/build directory

2.  Execute the create_manifest.php script
    - this requires the php-cli package (see notes in the build-release section below)
    - it is assumed to be running on a *NIX operating system
    - it requires sufficient identification of two sets of source files. One such set (here called 'to')
      is 'current', the other (called 'from') is the base against which differences will be calculated.
      Either or both sources may be local (stored on the running system), or in the CMSMS subversion
      repo, or in a git repo somewhere.
      By way of example, with both file-sets in svn, the script would require a root directory,
      a 'from' subpath, and a 'to' subpath e.g:
       root directory:  http://svn.cmsmadesimple.org/svn/cmsmadesimple
       from subpath:    branches/2.2.whatever
       to subpath:      trunk
    - it expects subversion and/or git to be installed, according to which of the source files are not local
    - it retrieves non-local sources, and (accounting for files that don't belong in a release)
      compares the file-sets to identify addions/changes/deletions.

3.  Copy the generated MANIFEST.DAT.GZ file into the <installer-root>/assets/upgrade/<version> directory

-----------------------------
Building release packages
-----------------------------

1.  Change dir into the <installer-root>/build directory
    Note:  You only need the phar_installer directory to do a build... but be careful that it is from the proper branch of CMSMS.

2.  Execute the build_release.php script
    -- execute build_release.php -h for help
    ** This script is only tested on linux (I'm allergic to windoze)

    ** This script has some pre-requisites

    a: the php-cli package
       (from ubuntu:  sudo apt-get install php5-cgi)

    b: the php-cli package must be allowed (in its configuration) to create phar files
       (from ubuntu:  vi /etc/php5/cli/php.ini; set phar.readonly = Off;)

    c: subversion must to be installed and configured, if retrieving source-files from
       the CMSMS subversion repo
       (from ubuntu:  sudo apt-get install subversion)

    d: git must to be installed and configured, if retrieving source-files from
       a specified git repo
       (from ubuntu:  sudo apt-get install git)

    e: zip must be installed and configured
       (from ubuntu:  sudo apt-get install zip)

    ** This script executes multiple steps

    a: Retrieves source files from the specified non-local source (if any)

    b: Then filters out all files that do not belong in the release
       (i.e: scripts, tests, svn and git files, backup files etc)

    c: Checksum files are created in the 'out' directory.

    d: The files in the release are compressed into data/data.tar.gz
       The version.php file for the trunk version is also copied here for convenience in knowing what the user will be installing or upgrading to.

    e: A self-contained executable .phar file is created and renamed to .php (because most http servers don't accept .phar extensions by default)

    f: That .php file is compressed into a .zip file (this makes the file easy to share on a server, as the http server won't try to execute it)

    g: The installer and the data.tar.gz are compressed into a zip file (which allows CMSMS to be installed on older systems)

-----------------------
Running the .phar file
-----------------------

Most Apache servers are not configured (by default) to execute php for .phar files.  Here are two
solutions for that:
  1.  Ensure the phar filename has extension '.php'.
      (The build_release script does that, and then encapsulates the .php file into a .zip file)

  2.  Tell Apache to that .phar files may be executed
      i.e: add the following to the .htaccess file (may require changing for different server configs)

      <FilesMatch "\.ph(ar|p3?|tml)$">
        SetHandler application/x-httpd-php
      </FilesMatch>

