------------------------
Creating a CMSMS Release
------------------------

Creating a CMSMS release involves these steps:
  a: Do all of the required changes to the CMSMS branch in question (change the version.php, update the changelog, make sure those files are committed)
  b: Create the <installer-root>/assets/upgrade/<version> directory and its appropriate files
     MANIFEST.DAT -- this file is created with the 'create_manifest.php' script (see below)
     (a MANIFEST.DAT.gz file is acceptable too)
     upgrade.php  -- (optional) the script to do any changes to the database or settings
        note: when this script is executed $db is available, the CMSMS API is created, however smarty is not available.
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
    ** This script has been tested/used only on linux
    -- execute create_manifest.php -h for help

    - the script requires the php-cli package (see notes in the build-release
      section below)
    - the script will read parameters from a config file if available. That file
      is named 'create_manifest.ini' and may be located in the same directory as
      the script, or in the user's 'home' directory if such exists.
    - the script requires sufficient identification of two sets of source files.
      One such set (here called 'to') will be considered to be the 'release'
      set. The other set (called 'from') is the base against which differences
      will be calculated, normally the prior release. Either or both filesets
      may be local (stored on the running system), or in the CMSMS subversion
      repo, or in a git repo somewhere.
      By way of example, with both file-sets in CMSMS svn, the parameters
      supplied to the script would include something like:
       --from=svn://branches/2.2.whatever --to=svn://trunk
    - if the script is running on a windows system, all relevant configuration
      parameters must be provided in the config file or as command arguments.
      On any *NIX system, there is scope for entering some of the parameters
      interactively, when prompted.
    - the script expects subversion and/or git to be installed, as appropriate
      to retrieve non-local fileset(s)
    - the script retrieves any non-local files, and (ignoring the files that
      don't belong in a release) compares the filesets to identify added,
      changed and deleted files.

3.  If necessary, copy the generated MANIFEST.DAT[.gz] file into the
     <installer-root>/assets/upgrade/<version> directory.
    If the script is run from inside the <installer-root> tree (as would normally
    be the case), the manifest is automatically copied to the right place.

-----------------------------
Building release packages
-----------------------------

1.  Change dir into the <installer-root>/build directory
    Note:  You only need the phar_installer directory to do a build... but be careful that it is from the intended version of CMSMS.

2.  Execute the build_release.php script
    ** This script has been tested/used only on linux
    -- execute build_release.php -h for help

    ** This script has some pre-requisites

    a: the php-cli package
       (from ubuntu:  sudo apt-get install php5-cgi)

    b: the php-cli package must be allowed (in its configuration) to create phar files
       (from ubuntu:  vi /etc/php5/cli/php.ini; set phar.readonly = Off;)

    c: subversion must be installed and configured, if retrieving source-files from
       the CMSMS subversion repo
       (from ubuntu:  sudo apt-get install subversion)

    d: git must be installed and configured, if retrieving source-files from
       a specified git repo
       (from ubuntu:  sudo apt-get install git)

    e: zip must be installed and configured
       (from ubuntu:  sudo apt-get install zip)

    ** This script does the following

    a: Retrieves source files from specified non-local source(s) (if any).

    b: Filters out the files that do not belong in a release (i.e: scripts,
       tests, svn and git files, backup files etc).

    c: Creates checksum files in the 'out' directory.

    d: Prepares a release from the 'to' fileset. Specifically, its files are compressed
       into file data.tar.gz in the 'data' directory, and file version.php is also copied
       into that directory in uncompressed form.

    e: Creates a self-contained executable .phar file, renamed to .php (because
       most web servers don't accept .phar extensions by default)

    f: Compresses that .php file into a zip file (which makes the file easier to share,
       as no web server will try to execute it)

    g: Compresses the installer and the data.tar.gz into a zip file (which allows
       installation on older systems)

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

