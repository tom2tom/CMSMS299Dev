<?php
namespace cms_installer;

use Exception;
use ZipArchive;
use function cms_installer\lang;
use function cms_installer\startswith;

class manifest_reader
{
    private $_filename;
    private $_compressed;
    private $_type;
    private $_generated;
    private $_from_version;
    private $_from_name;
    private $_to_version;
    private $_to_name;
    private $_has_read = false;
    private $_added = [];
    private $_changed = [];
    private $_deleted = [];

    #[\ReturnTypeWillChange]
    public function __construct($dir)
    {
        if (!is_dir($dir)) {
            throw new Exception(lang('error_internal', 'mr100'));
        }
        foreach ([
            '.gz',
            '.bzip2',
            '.zip',
            '',
        ] as $ext) {
            $fn = $dir.DIRECTORY_SEPARATOR.'MANIFEST.DAT'.$ext;
            if (is_file($fn)) {
                $this->_filename = $fn;
                $this->_compressed = ($ext != '');
                $this->_type = ltrim($ext, '.');
                return;
            }
        }
        throw new Exception(lang('error_internal', 'mr101'));
    }

    public function get_generated()
    {
        $this->read();
        return $this->_generated;
    }

    public function to_version()
    {
        $this->read();
        return $this->_to_version;
    }

    public function to_name()
    {
        $this->read();
        return $this->_to_name;
    }

    public function from_version()
    {
        $this->read();
        return $this->_from_version;
    }

    public function from_name()
    {
        $this->read();
        return $this->_from_name;
    }

    public function get_added()
    {
        $this->read();
        return $this->_added;
    }

    public function get_changed()
    {
        $this->read();
        return $this->_changed;
    }

    public function get_deleted()
    {
        $this->read();
        return $this->_deleted;
    }

    protected function handle_header($line)
    {
        $cols = explode(':', $line);
        if (count($cols) != 2) {
            throw new Exception(lang('error_internal', 'mr102'));
        }
        $cols = array_map(function($s) { return trim($s); }, $cols);

        switch ($cols[0]) {
        case 'MANIFEST_GENERATED':
            $this->_generated = (int)$cols[1];
            break;
        case 'MANIFEST FROM VERSION':
            $this->_from_version = $cols[1];
            break;
        case 'MANIFEST FROM NAME':
            $this->_from_name = $cols[1];
            break;
        case 'MANIFEST TO VERSION':
            $this->_to_version = $cols[1];
            break;
        case 'MANIFEST TO NAME':
            $this->_to_name = $cols[1];
            break;
        }
    }

    protected function handle_line($line)
    {
        if (!$line) {
            return;
        }
        if (startswith($line, 'MANIFEST')) {
            return $this->handle_header($line);
        }

        $cols = explode('::', $line);
        $n = count($cols);
        if (!($n == 2 || $n == 3)) {
            throw new Exception(lang('error_internal', 'mr103'));
        }
        $cols = array_map(function($s) { return trim($s); }, $cols);

        switch ($cols[0]) {
        case 'ADDED':
            $this->_added[] = ($n == 2) ? ['filename' => $cols[1]] : ['filename' => $cols[2], 'checksum' => $cols[1]];
            break;
        case 'CHANGED':
            $this->_changed[] = ($n == 2) ? ['filename' => $cols[1]] : ['filename' => $cols[2], 'checksum' => $cols[1]];
            break;
        case 'DELETED':
            $this->_deleted[] = ($n == 2) ? ['filename' => $cols[1]] : ['filename' => $cols[2], 'checksum' => $cols[1]];
            break;
        default:
            throw new Exception(lang('error_internal', 'mr104'));
        }
    }

    protected function read()
    {
        if (!$this->_has_read) {
            // copy the manifest file to a temporary location
            $tmpdir = get_app()->get_tmpdir();
            $tmpname = tempnam($tmpdir, 'man');
            @copy($this->_filename, $tmpname);

            switch ($this->_type) {
                case 'gz':
                    $fopen = 'gzopen';
                    $fclose = 'gzclose';
                    $fgets = 'gzgets';
                    $feof = 'gzeof';
                    $handled = true;
                    break;
                case 'bzip2':
                    $handled = false;
                    break;
                case 'zip':
                    $handled = false;
                    break;
                default:
                    $fopen = 'fopen';
                    $fclose = 'fclose';
                    $fgets = 'fgets';
                    $feof = 'feof';
                    $handled = true;
                    break;
            }

            if ($handled) {
                $fh = $fopen($tmpname, 'r');
                if (!$fh) {
                    echo "DEBUG: $fopen on ".$this->_filename.'<br />';
                    throw new Exception(lang('error_internal', 'mr105'));
                }

                while (!$feof($fh)) {
                    $line = $fgets($fh);
                    $line = trim($line);
                    if ($line) {
                        $this->handle_line($line);
                    }
                }
                $fclose($fh);
            } else {
                switch ($this->_type) {
                    case 'bzip2':
                        $fh = bzopen($tmpname, 'r');
                        if (!$fh) {
                            echo "DEBUG: bzopen on ".$this->_filename.'<br />';
                            throw new Exception(lang('error_internal', 'mr105'));
                        }
                        $content = '';
                        while (!feof($fh)) {
                            $content .= bzread($fh, 8192);
                        }
                        bzclose($fh);
                        // standardize EOL's
                        $content = preg_replace(['~\r\n~','~\n~','~\r~'], ["\n","\n","\n"], $content);
                        $offs = 0;
                        while (1) {
                            $line = $this->string_gets($content, $offs);
                            if ($line === null) { break; }
                            $offs += strlen($line);
                            $line = trim($line);
                            if ($line) {
                                $this->handle_line($line);
                            }
                        }
                        break;
                    case 'zip':
                        $fh = ZipArchive::open($tmpname, ZipArchive::RDONLY);
                        if (!$fh) {
                            echo "DEBUG: ZipArchive::open on ".$this->_filename.'<br />';
                            throw new Exception(lang('error_internal', 'mr105'));
                        }
                        $content = $fh->getFromName('MANIFEST.DAT');
                        $fh->close();
                        // standardize EOL's
                        $content = preg_replace(['~\r\n~','~\n~','~\r~'], ["\n","\n","\n"], $content);
                        $offs = 0;
                        while (1) {
                            $line = $this->string_gets($content, $offs);
                            if ($line === null) { break; }
                            $offs += strlen($line);
                            $line = trim($line);
                            if ($line) {
                                $this->handle_line($line);
                            }
                        }
                    break;
                }
            }
            $this->_has_read = true;
        }
    }

    private function string_gets(string $source, int $offset = 0, string $delimiter = "\n")
    {
        static $len = null;

        if ($len === null) {
            $len = strlen($source);
        }
        if ($len <= $offset) {
            return null;
        }
        $delimiter_pos = strpos($source, $delimiter, $offset);
        if ($delimiter_pos === false) {
            return substr($source, $offset);
        }
        return substr($source, $offset, ($delimiter_pos - $offset) + strlen($delimiter));
    }
} // class
