<?php

namespace PHPArchive;

use RuntimeException;
use UnexpectedValueException;

/**
 * Class Tar
 *
 * Creates or extracts Tar archives. Supports gz and bzip compression
 *
 * Long pathnames (>100 chars) are supported in POSIX ustar and GNU longlink formats.
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @package PHPArchive
 * @license MIT
 */
final class Tar extends Archive
{
    const COMPRESS_AUTO = -1;
    const COMPRESS_NONE = 0;
    const COMPRESS_GZIP = 1;
    const COMPRESS_BZIP = 2;

    protected $comptype = self::COMPRESS_AUTO;

    /**
     * Set the compression level and type
     *
     * @param int $level Compression level (0 to 9)
     * @param int $type Type of compression to use (use COMPRESS_* constants)
     * @throws UnexpectedValueException
     */
    public function setCompression($level = 9, $type = self::COMPRESS_AUTO)
    {
        $this->compressioncheck($type);
        if ($level < 0 || $level > 9) {
            throw new UnexpectedValueException('Compression level should be between -1 and 9');
        }

        if ($level == 0) {
            $this->comptype = self::COMPRESS_NONE;
        } else {
            $this->comptype  = $type;
        }
        if ($type == self::COMPRESS_NONE) {
            $this->complevel = 0;
        } else {
            $this->complevel = $level;
        }
    }

    /**
     * Open an existing TAR file for reading
     *
     * @param string $file
     * @throws RuntimeException
     * @throws ArchiveIllegalCompressionException
     */
    public function open($file)
    {
        // update compression to match file
        if ($this->comptype == self::COMPRESS_AUTO) {
            $this->setCompression($this->complevel, $this->filetype($file));
        }

        // open file handles
        if ($this->comptype === self::COMPRESS_GZIP) {
            $this->fh = @gzopen($file, 'rb');
        } elseif ($this->comptype === self::COMPRESS_BZIP) {
            $this->fh = @bzopen($file, 'r');
        } else {
            $this->fh = @fopen($file, 'rb');
        }

        if (!$this->fh) {
            throw new RuntimeException('Cannot open file for reading: '.$file);
        }
        $this->file = $file;
        $this->closed = false;
    }

    /**
     * List the contents of a TAR archive
     *
     * The archive is closed after reading its contents, because rewinding is not possible in bzip2 streams.
     * Reopen the archive with open() to do additional operations
     *
     * @throws RuntimeException
     * @returns nested array of file properties
     */
    public function contents()
    {
        if ($this->closed || !$this->file) {
            throw new RuntimeException('Cannot read from a closed archive');
        }

        $result = array();
        while ($read = $this->readbytes(512)) {
            $header = $this->parseHeader($read);
            if (!is_array($header)) {
                continue;
            }

            $this->skipbytes(ceil($header['size'] / 512) * 512);
            $result[] = get_object_vars($this->header2fileinfo($header));
        }

        $this->close();
        if ($result) {
            if (!usort($result, function ($a,$b)
                {
                  return strnatcmp($a['path'], $b['path']);
                })) {
                throw new RuntimeException('Cannot sort filepaths in the archive');
            }
        }
        return $result;
    }

    /**
     * List the contents of a TAR archive
     *
     * The archive is closed after reading its contents, because rewinding is not possible in bzip2 streams.
     * Reopen the archive with open() to do additional operations
     *
     * @throws RuntimeException
     * @returns associative array of file properties, each member like $filepath=>isdir($filepath);
     */
    public function brief_contents()
    {
        if ($this->closed || !$this->file) {
            throw new RuntimeException('Cannot read from a closed archive');
        }

        $result = array();
        while ($read = $this->readbytes(512)) {
            $header = $this->parseHeader($read);
            if (!is_array($header)) {
                continue;
            }
            $path = trim($header['filename'], ' /\\');
            $result[$path] = (bool) $header['typeflag'];

            $this->skipbytes(ceil($header['size'] / 512) * 512);
        }

        $this->close();
        if ($result) {
            if (!ksort($result, SORT_NATURAL)) {
                throw new RuntimeException('Cannot sort filepaths in the archive');
            }
        }
        return $result;
    }

    /**
     * List the folders in a TAR archive
     *
     * This identifies items which are explicitly a folder, plus ancestor(s) of other items
     * The archive is closed after reading its contents, because rewinding is not possible in bzip2 streams.
     * Reopen the archive with open() to do additional operations
     *
     * @throws RuntimeException
     * @return array of folder paths
     */
    public function folder_contents()
    {
        if ($this->closed || !$this->file) {
            throw new RuntimeException('Cannot read from a closed archive');
        }

        $result = array();
        while ($read = $this->readbytes(512)) {
            $header = $this->parseHeader($read);
            if (!is_array($header)) {
                continue;
            }
            $path = trim($header['filename'], ' /\\');
            if ($header['typeflag']) {
                $result[] = $path;
            } else {
                $result[] = dirname($path);
            }

            $this->skipbytes(ceil($header['size'] / 512) * 512);
        }

        $this->close();
        if ($result) {
            $result = array_unique($result, SORT_STRING);
            if ($p = array_search('', $result, true) !== false) {
                unset($result[$p]);
            }
            if ($result && !sort($result, SORT_NATURAL)) {
                throw new RuntimeException('Cannot sort filepaths in the archive');
            }
        }
        return $result;
    }

    /**
     * Extract an existing TAR archive
     *
     * The $strip parameter allows you to strip a certain number of path components from the filenames
     * found in the tar file, similar to the --strip-components feature of GNU tar. This is triggered when
     * an integer is passed as $strip.
     * Alternatively a fixed string prefix may be passed in $strip. If the filename matches this prefix,
     * the prefix will be stripped. It is recommended to give prefixes with a trailing slash.
     *
     * By default this will extract all files found in the archive. You can restrict the output using the $include
     * and $exclude parameter. Both expect a full regular expression (including delimiters and modifiers). If
     * $include is set, only files that match this expression will be extracted. Files that match the $exclude
     * expression will never be extracted. Both parameters can be used in combination. Expressions are matched against
     * stripped filenames as described above.
     *
     * The archive is closed after reading the contents, because rewinding is not possible in bzip2 streams.
     * Reopen the file with open() again if you want to do additional operations
     *
     * @param string $outdir the target directory for extracting
     * @param int|string $strip either the number of path components or a fixed prefix to strip
     * @param string $exclude an optional regular expression for item-filepaths to exclude
     * @param string $include an optional regular expression for item-filepaths to include
     * @throws RuntimeException
     * @return nested array of file properties
     */
    public function extract($outdir, $strip = '', $exclude = '', $include = '')
    {
        if ($this->closed || !$this->file) {
            throw new RuntimeException('Cannot read from a closed archive');
        }

        $outdir = rtrim($outdir, ' /\\');
        if (!is_dir($outdir)) {
            if (!@mkdir($outdir, 0777, true)) {
                throw new RuntimeException("Cannot create directory '$outdir'");
            }
        }

        $extracted = array();
        while ($dat = $this->readbytes(512)) {
            // read the file header
            $header = $this->parseHeader($dat);
            if (!is_array($header)) {
                continue;
            }
            $fileinfo = $this->header2fileinfo($header);

            // skip unwanted files
            if (!strlen($fileinfo->getPath()) || !$fileinfo->match($include, $exclude)) {
                $this->skipbytes(ceil($header['size'] / 512) * 512);
                continue;
            }

            // apply strip rules
            $fileinfo->strip($strip);

            // create output directory
            $output = $outdir.DIRECTORY_SEPARATOR.$fileinfo->getPath();
            $directory = ($fileinfo->getIsdir()) ? $output : dirname($output);
            @mkdir($directory, 0777, true);

            // extract data
            if (!$fileinfo->getIsdir()) {
                $fp = @fopen($output, "wb");
                if (!$fp) {
                    throw new RuntimeException('Cannot open file for writing: '.$output);
                }

                $n = floor($header['size'] / 512);
                for ($i = 0; $i < $n; $i++) {
                    fwrite($fp, $this->readbytes(512), 512);
                }
                if (($header['size'] % 512) != 0) {
                    fwrite($fp, $this->readbytes(512), $header['size'] % 512);
                }

                fclose($fp);
                @touch($output, $fileinfo->getMtime());
                @chmod($output, $fileinfo->getMode());
            } else {
                $this->skipbytes(ceil($header['size'] / 512) * 512); // the size is usually 0 for directories
            }

            if (is_callable($this->callback)) {
                call_user_func($this->callback, $fileinfo);
            }
            $extracted[] = get_object_vars($fileinfo);
        }

        $this->close();
        return $extracted;
    }

    /**
     * Create a new TAR file
     *
     * If $file is empty, the file will be created in memory
     *
     * @param string $file
     * @throws RuntimeException
     * @throws ArchiveIllegalCompressionException
     */
    public function create($file = '')
    {
        $this->file   = $file;
        $this->memory = '';
        $this->fh     = 0;

        if ($this->file) {
            // determine compression
            if ($this->comptype == self::COMPRESS_AUTO) {
                $this->setCompression($this->complevel, $this->filetype($file));
            }

            if ($this->comptype === self::COMPRESS_GZIP) {
                $this->fh = @gzopen($this->file, 'wb'.$this->complevel);
            } elseif ($this->comptype === self::COMPRESS_BZIP) {
                $this->fh = @bzopen($this->file, 'w');
            } else {
                $this->fh = @fopen($this->file, 'wb');
            }

            if (!$this->fh) {
                throw new RuntimeException('Cannot open file for writing: '.$this->file);
            }
        }
        $this->writeaccess = true;
        $this->closed      = false;
    }

    /**
     * Add a file to the current TAR archive using an existing file in the filesystem
     *
     * @param string $file path to the original file
     * @param string|FileInfo $fileinfo either the name to us in archive (string) or a FileInfo object with all meta data, empty to take from original
     * @throws RuntimeException if the file changes while reading it, the archive will be corrupt and should be deleted
     * @throws RuntimeException if there was trouble reading the given file, it was not added
     * @throws Exception trouble reading file info, it was not added
     */
    public function addFile($file, $fileinfo = '')
    {
        if (is_string($fileinfo)) {
            $fileinfo = FileInfo::fromPath($file, $fileinfo);
        }

        if ($this->closed) {
            throw new RuntimeException('Archive has been closed, files can no longer be added');
        }

        $fp = @fopen($file, 'rb');
        if (!$fp) {
            throw new RuntimeException('Cannot open file for reading: '.$file);
        }

        // create file header
        $this->writeFileHeader($fileinfo);

        // write data
        $read = 0;
        while (!feof($fp)) {
            $data = fread($fp, 512);
            $read += strlen($data);
            if ($data === false) {
                break;
            }
            if ($data === '') {
                break;
            }
            $packed = pack("a512", $data);
            $this->writebytes($packed);
        }
        fclose($fp);

        if ($read != $fileinfo->getSize()) {
            $this->close();
            throw new RuntimeException("The size of $file changed while reading, archive corrupted. read $read expected ".$fileinfo->getSize());
        }

        if (is_callable($this->callback)) {
            call_user_func($this->callback, $fileinfo);
        }
    }

    /**
     * Add a file to the current TAR archive using the given $data as content
     *
     * @param mixed  $fileinfo either the name to use in archive (string) or a FileInfo object with all meta data
     * @param string $data     binary content of the file to add
     * @throws RuntimeException
     */
    public function addData($fileinfo, $data)
    {
        if (is_string($fileinfo)) {
            $fileinfo = new FileInfo($fileinfo);
        }

        if ($this->closed) {
            throw new RuntimeException('Archive has been closed, files can no longer be added');
        }

        $len = strlen($data);
        $fileinfo->setSize($len);
        $this->writeFileHeader($fileinfo);

        for ($s = 0; $s < $len; $s += 512) {
            $this->writebytes(pack("a512", substr($data, $s, 512)));
        }

        if (is_callable($this->callback)) {
            call_user_func($this->callback, $fileinfo);
        }
    }

    /**
     * Add the closing footer to the archive if in write mode, close all file handles
     *
     * After a call to this function no more data can be added to the archive, for
     * read access no reading is allowed anymore
     *
     * "Physically, an archive consists of a series of file entries terminated by an end-of-archive entry,
     * which consists of two 512 blocks of zero bytes"
     *
     * @link http://www.gnu.org/software/tar/manual/html_chapter/tar_8.html#SEC134
     * @throws RuntimeException
     */
    public function close()
    {
        if ($this->closed) {
            return;
        } // we did this already

        // write footer
        if ($this->writeaccess) {
            $this->writebytes(pack("a512", ""));
            $this->writebytes(pack("a512", ""));
        }

        // close file handles
        if ($this->file) {
            if ($this->comptype === self::COMPRESS_GZIP) {
                gzclose($this->fh);
            } elseif ($this->comptype === self::COMPRESS_BZIP) {
                bzclose($this->fh);
            } else {
                fclose($this->fh);
            }

            $this->file = '';
            $this->fh   = 0;
        }

        $this->writeaccess = false;
        $this->closed      = true;
    }

    /**
     * Returns the created in-memory archive data
     *
     * This implicitly calls close() on the Archive
     * @throws RuntimeException
     */
    public function getArchive()
    {
        $this->close();

        if ($this->comptype === self::COMPRESS_AUTO) {
            $this->comptype = self::COMPRESS_NONE;
        }

        if ($this->comptype === self::COMPRESS_GZIP) {
            return gzencode($this->memory, $this->complevel);
        }
        if ($this->comptype === self::COMPRESS_BZIP) {
            return bzcompress($this->memory);
        }
        return $this->memory;
    }

    /**
     * Save the created in-memory archive data
     *
     * Note: It is more memory effective to specify the filename in the create()
     * function and let the library work on the new file directly.
     *
     * @param string $file
     * @throws RuntimeException
     * @throws ArchiveIllegalCompressionException
     */
    public function save($file)
    {
        if ($this->comptype === self::COMPRESS_AUTO) {
            $this->setCompression($this->complevel, $this->filetype($file));
        }

        if (!@file_put_contents($file, $this->getArchive())) {
            throw new RuntimeException('Cannot write to file: '.$file);
        }
    }

    /**
     * Read from the open file pointer
     *
     * @param int $length bytes to read
     * @return string
     */
    protected function readbytes($length)
    {
        if ($this->comptype === self::COMPRESS_GZIP) {
            return @gzread($this->fh, $length);
        } elseif ($this->comptype === self::COMPRESS_BZIP) {
            return @bzread($this->fh, $length);
        } else {
            return @fread($this->fh, $length);
        }
    }

    /**
     * Write to the open file-handle or memory
     *
     * @param string $data
     * @throws RuntimeException
     * @return int number of bytes written
     */
    protected function writebytes($data)
    {
        if (!$this->file) {
            $this->memory .= $data;
            $written = strlen($data);
        } elseif ($this->comptype === self::COMPRESS_GZIP) {
            $written = @gzwrite($this->fh, $data);
        } elseif ($this->comptype === self::COMPRESS_BZIP) {
            $written = @bzwrite($this->fh, $data);
        } else {
            $written = @fwrite($this->fh, $data);
        }
        if ($written === false) {
            throw new RuntimeException('Failed to write to archive stream');
        }
        return $written;
    }

    /**
     * Skip forward in the open file pointer
     *
     * This is basically a wrapper around seek() (and a workaround for bzip2)
     *
     * @param int $bytes seek to this position
     */
    protected function skipbytes($bytes)
    {
        if ($this->comptype === self::COMPRESS_GZIP) {
            @gzseek($this->fh, $bytes, SEEK_CUR);
        } elseif ($this->comptype === self::COMPRESS_BZIP) {
            // there is no seek in bzip2, we simply read on
            // bzread allows to read a max of 8kb at once
            while($bytes) {
                $toread = min(8192, $bytes);
                @bzread($this->fh, $toread);
                $bytes -= $toread;
            }
        } else {
            @fseek($this->fh, $bytes, SEEK_CUR);
        }
    }

    /**
     * Write the given file meta data as header
     *
     * @param FileInfo $fileinfo
     * @throws RuntimeException
     */
    protected function writeFileHeader(FileInfo $fileinfo)
    {
        $this->writeRawFileHeader(
            $fileinfo->getPath(),
            $fileinfo->getUid(),
            $fileinfo->getGid(),
            $fileinfo->getMode(),
            $fileinfo->getSize(),
            $fileinfo->getMtime(),
            $fileinfo->getIsdir() ? '5' : '0'
        );
    }

    /**
     * Write a file header to the stream
     *
     * @param string $name
     * @param int $uid
     * @param int $gid
     * @param int $perm
     * @param int $size
     * @param int $mtime
     * @param string $typeflag Set to '5' for directories
     * @throws RuntimeException
     */
    protected function writeRawFileHeader($name, $uid, $gid, $perm, $size, $mtime, $typeflag = '')
    {
        // handle filename length restrictions
        $prefix  = '';
        $namelen = strlen($name);
        if ($namelen > 100) {
            $file = basename($name);
            $dir  = dirname($name);
            if (strlen($file) > 100 || strlen($dir) > 155) {
                // we're still too large, let's use GNU longlink
                $this->writeRawFileHeader('././@LongLink', 0, 0, 0, $namelen, 0, 'L');
                for ($s = 0; $s < $namelen; $s += 512) {
                    $this->writebytes(pack("a512", substr($name, $s, 512)));
                }
                $name = substr($name, 0, 100); // cut off name
            } else {
                // we're fine when splitting, use POSIX ustar
                $prefix = $dir;
                $name   = $file;
            }
        }

        // values are needed in octal
        $uid   = sprintf("%6s ", decoct($uid));
        $gid   = sprintf("%6s ", decoct($gid));
        $perm  = sprintf("%6s ", decoct($perm));
        $size  = sprintf("%11s ", decoct($size));
        $mtime = sprintf("%11s", decoct($mtime));

        $data_first = pack("a100a8a8a8a12A12", $name, $perm, $uid, $gid, $size, $mtime);
        $data_last  = pack("a1a100a6a2a32a32a8a8a155a12", $typeflag, '', 'ustar', '', '', '', '', '', $prefix, "");

        for ($i = 0, $chks = 0; $i < 148; $i++) {
            $chks += ord($data_first[$i]);
        }

        for ($i = 156, $chks += 256, $j = 0; $i < 512; $i++, $j++) {
            $chks += ord($data_last[$j]);
        }

        $this->writebytes($data_first);

        $chks = pack("a8", sprintf("%6s ", decoct($chks)));
        $this->writebytes($chks.$data_last);
    }

    /**
     * Decode the given tar file header
     *
     * @param string $block a 512 byte block containing the header data
     * @return array|false returns false when this was a null block
     * @throws RuntimeException
     */
    protected function parseHeader($block)
    {
        if (!$block || strlen($block) != 512) {
            throw new RuntimeException('Unexpected length of header');
        }

        // null byte blocks are ignored
        if (trim($block) === '') return false;

        for ($i = 0, $chks = 0; $i < 148; $i++) {
            $chks += ord($block[$i]);
        }

        for ($i = 156, $chks += 256; $i < 512; $i++) {
            $chks += ord($block[$i]);
        }

        $header = @unpack(
            "a100filename/a8perm/a8uid/a8gid/a12size/a12mtime/a8checksum/a1typeflag/a100link/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix",
            $block
        );
        if (!$header) {
            throw new RuntimeException('Failed to parse header');
        }

        $return['checksum'] = OctDec(trim($header['checksum']));
        if ($return['checksum'] != $chks) {
            throw new RuntimeException('Header does not match its checksum');
        }

        $return['filename'] = trim($header['filename']);
        $return['perm']     = OctDec(trim($header['perm']));
        $return['uid']      = OctDec(trim($header['uid']));
        $return['gid']      = OctDec(trim($header['gid']));
        $return['size']     = OctDec(trim($header['size']));
        $return['mtime']    = OctDec(trim($header['mtime']));
        $return['typeflag'] = $header['typeflag'];
        $return['link']     = trim($header['link']);
        $return['uname']    = trim($header['uname']);
        $return['gname']    = trim($header['gname']);

        // Handle ustar Posix compliant path prefixes
        if (trim($header['prefix'])) {
            $return['filename'] = trim($header['prefix']).'/'.$return['filename'];
        }

        // Handle Long-Link entries from GNU Tar
        if ($return['typeflag'] == 'L') {
            // following data block(s) is the filename
            $filename = trim($this->readbytes(ceil($return['size'] / 512) * 512));
            // next block is the real header
            $block  = $this->readbytes(512);
            $return = $this->parseHeader($block);
            // overwrite the filename
            $return['filename'] = $filename;
        }

        return $return;
    }

    /**
     * Creates FileInfo object from the given parsed header
     *
     * @param array $header
     * @return FileInfo object
     */
    protected function header2fileinfo($header)
    {
        $fileinfo = new FileInfo();
        $fileinfo->setGid($header['gid']);
        $fileinfo->setGroup($header['gname']);
        $fileinfo->setIsdir((bool)$header['typeflag']);
        $fileinfo->setMode($header['perm']);
        $fileinfo->setMtime($header['mtime']);
        $fileinfo->setOwner($header['uname']);
        $fileinfo->setPath($header['filename']);
        $fileinfo->setSize($header['size']);
        $fileinfo->setUid($header['uid']);

        return $fileinfo;
    }

    /**
     * Checks if the given compression type is available and throws an exception if not
     *
     * @param $comptype
     * @throws RuntimeException
     */
    protected function compressioncheck($comptype)
    {
        if ($comptype === self::COMPRESS_GZIP && !function_exists('gzopen')) {
            throw new RuntimeException('No gzip support available');
        }

        if ($comptype === self::COMPRESS_BZIP && !function_exists('bzopen')) {
            throw new RuntimeException('No bzip2 support available');
        }
    }

    /**
     * Guesses the wanted compression from the given file
     *
     * Uses magic bytes for existing files, the file extension otherwise
     *
     * You don't need to call this yourself. It's used when you pass self::COMPRESS_AUTO somewhere
     *
     * @param string $file
     * @return int
     */
    public function filetype($file)
    {
        // for existing files, try to read the magic bytes
        if (is_file($file) && is_readable($file) && filesize($file) > 5) {
            $fh = @fopen($file, 'rb');
            if (!$fh) return false;
            $magic = fread($fh, 5);
            fclose($fh);

            if (strpos($magic, "\x42\x5a") === 0) return self::COMPRESS_BZIP;
            if (strpos($magic, "\x1f\x8b") === 0) return self::COMPRESS_GZIP;
        }

        // otherwise rely on file name
        $file = strtolower($file);
        if (substr($file, -3) == '.gz' || substr($file, -4) == '.tgz') {
            return self::COMPRESS_GZIP;
        } elseif (substr($file, -4) == '.bz2' || substr($file, -4) == '.tbz') {
            return self::COMPRESS_BZIP;
        }

        return self::COMPRESS_NONE;
    }

}
