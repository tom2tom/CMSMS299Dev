<?php

namespace PHPArchive;

use RuntimeException;
use InvalidArgumentException;

class Archive
{
    protected $callback = null;
    protected $closed = true;
    protected $complevel = 9;
    protected $fh;
    protected $file = '';
    protected $memory = '';
    protected $writeaccess = false;

    /**
     * Set a callback function to be called whenever a file is added or extracted.
     *
     * The callback is called with a FileInfo object as parameter. You can use this to show progress
     * info during an operation.
     *
     * @param callable $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * Extract a file specified by its path from an existing archive
     *
     * @param string $outdir the target directory for extracting
     * @param string $path the archive-relative (i.e. stored) filepath
     * @param int|string $strip either the number of path components or a fixed prefix to strip
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function extractFile($outdir, $path, $strip = '')
    {
        $path = trim($path, ' /\\');
        if ($path === '') {
            throw new InvalidArgumentException("Invalid file path '$path' specified");
        }

        foreach (array('~','@','#','&','|','!','`') as $char) {
            if (strpos($path, $char) === false) {
                $tmp = str_replace(array('//','\\\\','\\'), array('/','/','/'), $path);
                $tmp = str_replace('/', '[/\\]', $tmp);
                $this->extract($outdir, $strip, '', $char.$tmp.$char);
                return;
            }
        }
        throw new RuntimeException("Cannot convert path '$path' to regex pattern");
    }
}
