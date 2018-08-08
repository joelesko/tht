<?php

namespace o;

// TODO: test all on Windows (path separator)

// Not implemented. No plans to, unless necessary.
//   chdir, getcwd, is_link, disk_free_space, disk_total_space
//   is_readable, is_writeable, is_executable

class u_File extends StdModule {

    private $skipSandbox = false; 

    function __construct() {
        $this->skipSandbox = Security::isOpenFileSandbox();
    }

    function _call ($fn, $args=[], $validationList='', $checkReturn=true) {

        Tht::module('Meta')->u_no_template_mode();

        // validate each argument against a validation pattern
        $validationPatterns = explode("|", $validationList); 
        $fargs = [];
        foreach ($args as $a) {
            $fargs []= $this->checkArg($a, array_shift($validationPatterns));
        }

        Tht::module('Perf')->u_start('File.' . $fn, $args[0]);
        $returnVal = \call_user_func_array($fn, $fargs);
        Tht::module('Perf')->u_stop();

        // Die on a false return value
        if ($checkReturn && $returnVal === false) {
            $relevantFile = ''; 
            if (isset($fargs[0])) { $relevantFile = $fargs[0]; }
            Tht::error("File function failed on `" . $relevantFile . "`");
        }

        return $returnVal;
    }

    // Validate argument against pattern
    function checkArg($a, $pattern) {

        if (strpos($pattern, '*') !== false) {
            // internally set, is ok
            return $a;
        }
        if (strpos($pattern, 'string') !== false) {
            // TODO: check type
            return $a;
        }
        else if (strpos($pattern, 'num') !== false) {
            // TODO: check type
            return $a;
        }
        if (preg_match('/path|dir|file/', $pattern)) {
            // a path
            // TODO: check is_dir or is_file
            $a = Security::validatePath($a, !$this->skipSandbox);
        }
        if (strpos($pattern, 'exists') !== false) {
            // path must exist
            if (!file_exists($a)) {
                Tht::error("File does not exist: `" . Tht::getRelativePath('data', $a) . "`");
            }
        }

        return $a;
    }



    // READS

    function u_read ($fileName, $single=false) {
        if ($single) {
            return $this->_call('file_get_contents', [$fileName], 'file,exists');
        } else {
            return $this->_call('file', [$fileName, FILE_IGNORE_NEW_LINES], 'file,exists|*');
        }
    }

    function u_read_lines ($fileName, $fn) {
        $handle = $this->_call('fopen', [$fileName, ['r']], $fileName, true);
        $accum = [];
        while (true) {
            $line = fgets($handle);
            if ($line === false) { break; }
            $line = rtrim("\n");
            $ret = $fn($line);
            if ($ret === false) {
                break;
            }
            if (get_class($ret) !== 'ONothing' && $ret !== true) {
                $accum []= $ret;
            }
        }
        fclose($handle);
        return $accum;
    }



    // WRITES

    function u_write ($filePath, $data, $mode='replace') {
        $data = uv($data);
        if (is_array($data)) {
            $data = implode($data, "\n");
        }
        $mode = trim(strtolower($mode));

        if (!in_array($mode, ['replace', 'append', 'restore'])) {
            Tht::error("Unknown write mode `$mode`. Supported modes: `replace` (default), `append`, `restore`");
        }

        // Only write if the file does not exist
        if ($mode == 'restore' && $this->u_exists($filePath)) {
            return false;
        }

        // Make sure parent dir exists
        $parentPath = $this->u_parent_dir($filePath);
        if (!$this->u_is_dir($parentPath)) {
            Tht::error("Parent dir does not exist: `$parentPath`");
        }
    
        $arg = $mode == 'append' ? LOCK_EX|FILE_APPEND : LOCK_EX;
        return $this->_call('file_put_contents', [$filePath, $data, $arg], 'file|*|*');
    }

    function u_log ($data, $fileName='app.log') {

        if (is_array($data) || is_object($data)) {
            $data = Tht::module('Json')->u_format($data);
        } else {
            $data = trim($data);
            $data = str_replace("\n", '\\n', $data);
        }
        $line = '[' . strftime('%Y-%m-%d %H:%M:%S') . "]  " . $data . "\n";

        return $this->_call('file_put_contents', [Tht::path('files', $fileName), $line, LOCK_EX|FILE_APPEND], 'file|string|*');
    }


    // PATHS
    // TODO: clean up and document

    function u_path_info ($path) {
        $info = $this->_call('pathinfo', [$path], 'path,exists');
        $realPath = realpath($info['dirname']);
        return [
            'dirList'       => explode(DIRECTORY_SEPARATOR, ltrim($realPath, DIRECTORY_SEPARATOR)),
            'dirPath'       => $realPath,
            'fileNameShort' => $info['filename'],
            'fileName'      => $info['basename'],
            'fileExt'       => $info['extension']
        ];
    }

    function u_join_path ($parts) {
        $path = implode('/', uv($parts));
        $path = Security::validatePath($path, false);
        return $path;
    }

    function u_clean_path ($path) {
        return Security::validatePath($path, false);
    }

    function u_full_path ($relPath) {
        if (Tht::isMode('fileSandbox')) {
            // TODO: relPath must be relative
            return Tht::path('files', $relPath);
        }
        return $this->_call('realpath', [$relPath], 'path');
    }

    function u_relative_path ($fullPath, $rootPath) {

        $rootPath = Security::validatePath($rootPath, false);
        $fullPath = Security::validatePath($fullPath, false);

        // TODO: both must be absolute

        if (!$this->u_has_root_path($fullPath, $rootPath)) {
            Tht::error('Root path not found in full path.', [ 'fullPath' => $fullPath, 'rootPath' => $rootPath ]);
        }

        $relPath = substr($fullPath, strlen($rootPath));
        $relPath = ltrim($relPath, '/');

        return $relPath;
    }

    function u_root_path ($fullPath, $relPath) {

        $relPath  = Security::validatePath($relPath, false);
        $fullPath = Security::validatePath($fullPath, false);

        // TODO: asset rel and absolute

        if (!$this->u_has_root_path($fullPath, $rootPath)) {
            Tht::error('Root path not found in full path.', [ 'fullPath' => $fullPath, 'rootPath' => $rootPath ]);
        }

        $relPath = substr($fullPath, strlen($rootPath));
        $relPath = ltrim($relPath, '/');

        return $relPath;
    }

    // TODO: don't work in substrings.  Work in path segments.
    // TODO: LockStrings?

    function u_has_root_path($fullPath, $rootPath) {
        $fullPath = Security::validatePath($fullPath, false);
        $rootPath = Security::validatePath($rootPath, false);
        return strpos($fullPath, $rootPath) === 0;
    }

    function u_has_relative_path($fullPath, $relPath) {
        $fullPath = Security::validatePath($fullPath, false);
        $relPath  = Security::validatePath($relPath, false);
        $offset = strlen($fullPath) - strlen($relPath);
        return strpos($fullPath, $relPath) === $offset;
    }

    function u_is_relative_path($p) {
        $p = Security::validatePath($p, false);
        return $p[0] !== '/';
    }

    function u_is_absolute_path($p) {
        $p = Security::validatePath($p, false);
        return $p[0] === '/';
    }

    // TODO: different for abs and rel paths
    // TODO: bounds check
    function u_parent_dir($p) {
        $p = Security::validatePath($p, false);
        $parentPath = preg_replace('~/.*?$~', '', $p);
        return strlen($parentPath) ? $parentPath : '/';
    }



    // MOVE, etc.

    function u_delete ($filePath) {
        if (is_dir($filePath)) {
            Tht::error("Argument 1 for `delete` must not be a directory: `$filePath`.  Suggestion: `File.deleteDir()`");
        } 
        return $this->_call('unlink', [$filePath], 'file');
    }

    function u_delete_dir ($dirPath) {

        $checkPath = Security::validatePath($dirPath, !$this->skipSandbox);
        if (is_dir($checkPath)) {
            $this->deleteDirRecursive($dirPath);
        } 
        else {
            Tht::error("Argument 1 for `deleteDir` is not a directory: `$dirPath`");
        }
    }

    function deleteDirRecursive ($dirPath) {

        $dirPath = Security::validatePath($dirPath, !$this->skipSandbox);

        // recursively delete dir contents
        $dirHandle = opendir($dirPath);
        while (true) {
            $file = readdir($dirHandle);
            if (!$file) { break; }
            if ($file === "." || $file === "..") {
                continue;
            }
            $subPath = $dirPath . "/" . $file;

            if (is_dir($subPath)) {
                $this->deleteDirRecursive($subPath);
            } else {
                // delete file
                $this->_call('unlink', [$subPath], 'file');
            }
        }
        closedir($dirHandle);

        $this->_call('rmdir', [$dirPath], 'dir');
    }

    function u_copy ($source, $dest) {
        if (is_dir($source)) {
            Tht::error("Argument 1 for `copy` must not be a directory: `$source`.  Suggestion: `File.copyDir()`");
        } 
        return $this->_call('copy', [$source, $dest], 'file,exists|path');
    }

    function u_copy_dir ($source, $dest) {
        if (is_dir($source)) {
            $this->copyDirRecursive($source, $dest);
        } 
        else {
            Tht::error("Argument 1 for `copyDir` is not a directory: `$source`");
        }
    }

    function copyDirRecursive($source, $dest) {
        
        if (!is_dir($dest)) {
            $this->_call('mkdir', [$dest, 0755]);
        }

        // recursively copy dir contents
        $dirHandle = opendir($source);
        while (true) {
            $file = readdir($dirHandle);
            if (!$file) { break; }
            if ($file === "." || $file === "..") {
                continue;
            }
            $subSource = $source . "/" . $file;
            $subDest = $dest . "/" . $file;

            if (is_dir($subSource)) {
                $this->copyDirRecursive($subSource, $subDest);
            } else {
                // copy file
                $this->_call('copy', [$subSource, $subDest], '');
            }
        }

        closedir($dirHandle);
    }

    function u_move ($oldName, $newName) {
        return $this->_call('rename', [$oldName, $newName], 'path,exists|path');
    }

    function u_exists ($path) {
        return $this->_call('file_exists', [$path], 'path', false);
    }

    function u_find ($pattern, $dirOnly=false) {
        $flags = $dirOnly ? GLOB_BRACE|GLOB_ONLYDIR : GLOB_BRACE;
        return $this->_call('glob', [$pattern, $flags], 'string|*');
    }

    function u_touch ($file, $time=null, $atime=null) {
        if (!$time) { $time = time(); }
        if (!$atime) { $atime = time(); }
        return $this->_call('touch', [$file, $time, $atime], 'file|num|num');
    }


    // DIRS

    function u_make_dir ($dir, $perms=0775) {
        if ($this->u_exists($dir)) {
            return false;
        }
        return $this->_call('mkdir', [$dir, $perms, true], 'dir|num|*', null);
    }

    function u_open_dir ($d) {
        $dh = $this->_call('opendir', [$d], 'dir,exists');
        return new \FileDir ($dh);
    }

    // TODO: integrate with find/glob?
    // TODO: recursive
    // TODO: functional interface for better perf?
    function u_read_dir ($d, $filter = 'none') {

        if (!in_array($filter, ['none', 'files', 'dirs'])) {
            Tht::error("Unknown filter `$filter`. Supported filters: `none` (default), `files`, `dirs`");
        }

        $files = $this->_call('scandir', [$d], 'dir,exists');
        if ($filter && $filter !== 'none') {
            $filteredFiles = [];
            foreach ($files as $f) {
                $isDir = is_dir($f);
                if ($filter === 'dirs') {
                    if ($isDir) {  $filteredFiles []= $f;  }
                } else if ($filter === 'files') {
                    if (!$isDir) {  $filteredFiles []= $f;  }
                }
            }
            $files = $filteredFiles;
        }

        return $files;
    }


    // FILE ATTRIBUTES

    function u_get_size ($f) {
        return $this->_call('filesize', [$f], 'file,exists');
    }

    function u_get_modify_time ($f) {
        return $this->_call('filemtime', [$f], 'path,exists');
    }

    function u_get_create_time ($f) {
        return $this->_call('filectime', [$f], 'path,exists');
    }

    function u_get_access_time ($f) {
        return $this->_call('fileatime', [$f], 'path,exists');
    }

    function u_is_dir ($f) {
        return $this->_call('is_dir', [$f], 'path', false);
    }

    function u_is_file ($f) {
        return $this->_call('is_file', [$f], 'path', false);
    }

}

