<?php

namespace o;

// TODO: test all on Windows (path separator)

// Not implemented. No plans to, unless necessary.
//   chdir, getcwd, is_link, disk_free_space, disk_total_space
//   is_readable, is_writeable, is_executable

class u_File extends StdModule {

    function _call ($fn, $args=[], $validationList, $checkReturn=true) {

        Tht::module('Perf')->u_start('File.' . $fn, $args[0]);

        Tht::module('Meta')->u_no_template_mode();

        // validate each argument against a validation pattern
        $validationPatterns = explode("|", $validationList); 
        $fargs = [];
        foreach ($args as $a) {
            $fargs []= $this->checkArg($a, array_shift($validationPatterns));
        }

        $returnVal = \call_user_func_array($fn, $fargs);

        // Die on a false return value
        if ($checkReturn && $returnVal === false) {
            $relevantFile = ''; 
            if (isset($fargs[0])) { $relevantFile = $fargs[0]; }
            Tht::error("File function failed on `" . $relevantFile . "`");
        }

        Tht::module('Perf')->u_stop();

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
            $a = $this->validatePath($a);
        }
        if (strpos($pattern, 'exists') !== false) {
            // path must exist
            if (!file_exists($a)) {
                Tht::error("File does not exist: `" . Tht::getRelativePath('root', $a) . "`");
            }
        }

        return $a;
    }

    function fixSeparator ($rawPath) {
        return str_replace('\\', '/', $rawPath);
    }

    // [security]
    function validatePath ($path) {

        // TODO: must not start with '/'
        // TODO: force relative to data/files

        $path = $this->fixSeparator($path);

        if (!strlen($path)) {
            Tht::error("File path cannot be empty: `$path`");
        }
        if (v('' . $path)->u_is_url()) {
            Tht::error("Remote URL not allowed: `$path`");
        }
        if (strpos($path, '..') !== false) {
            Tht::error("Parent shortcut `..` not allowed in path: `$path`");
        }
        return $path;
    }


    // READS

    function u_read ($fileName, $single=false) {
        if ($single) {
            return u_File::_call('file_get_contents', [$fileName], 'file,exists');
        } else {
            return u_File::_call('file', [$fileName, FILE_IGNORE_NEW_LINES], 'file,exists|*');
        }
    }

    function u_for_lines ($fileName, $fn) {
        $handle = u_File::_call('fopen', [$fileName, ['r']], $fileName, true);
        $accum = [];
        while (true) {
            $line = fgets($handle);
            if ($line === false) { break; }
            $line = rtrim("\n");
            $ret = $fn($line);
            if ($ret !== false) { $accum []= $ret; }
        }
        fclose($handle);
        return $accum;
    }



    // WRITES

    function u_write ($fileName, $data, $mode='replace') {
        $data = uv($data);
        if (is_array($data)) {
            $data = implode($data, "\n");
        }
        $mode = trim(strtolower($mode));

        if (!in_array($mode, ['replace', 'append', 'restore'])) {
            Tht::error("Unknown write mode `$mode`. Supported modes: `replace` (default), `append`, `restore`");
        }

        // only write if the file foes not exist
        if ($mode == 'restore') {
            if (u_File::u_file_exists($fileName)) {
                return false;
            }
        }
        $arg = $mode == 'append' ? LOCK_EX|FILE_APPEND : LOCK_EX;
        return u_File::_call('file_put_contents', [$fileName, $data, $arg], 'file|*|*');
    }

    function u_log ($data, $fileName='app.log') {

        // TODO: clean this up
        // if (!$fileName) {
        //     $out = OBare::formatPrint([$data]);
        //     Tht::errorLog($out);
        //     return;
        // }

        if (is_array($data) || is_object($data)) {
            $data = Tht::module('Json')->u_format($data);
        } else {
            $data = trim($data);
            $data = str_replace("\n", '\\n', $data);
        }
        $line = '[' . strftime('%Y-%m-%d %H:%M:%S') . "]  " . $data . "\n";
        return u_File::_call('file_put_contents', [Tht::path('logs', $fileName), $line, LOCK_EX|FILE_APPEND], 'file|string|*');
    }


    // INFO

    function u_path_info ($path) {
        $info = u_File::_call('pathinfo', [$path], 'path,exists');
        $realPath = realpath($info['dirname']);
        return [
            'dirList'       => explode(DIRECTORY_SEPARATOR, ltrim($realPath, DIRECTORY_SEPARATOR)),
            'dirPath'       => $realPath,
            'fileNameShort' => $info['filename'],
            'fileName'      => $info['basename'],
            'fileExt'       => $info['extension']
        ];
    }

    function u_path ($parts) {
        $path = implode('/', uv($parts));
        $path = u_File::u_clean_path($path);
        return $path;
    }

    function u_clean_path ($path) {
        $path = preg_replace('![/\\\\]+!', '/', $path);
        $path = rtrim($path, '/');
        return $path;
    }


    // PATHS


    function u_full_path ($relPath) {
        return u_File::_call('realpath', [$relPath], 'path');
    }

    function u_relative_path ($fullPath, $basePath) {

        $basePath = u_File::u_clean_path($basePath);
        $fullPath = u_File::u_clean_path($fullPath);

        $fullBase = substr($fullPath, 0, strlen($basePath));
        if (strtolower($fullBase) !== strtolower($basePath)) {
            Tht::error('Base path not found in full path.', [ 'fullPath' => $fullPath, 'basePath' => $basePath ]);
        }

        $relPath = substr($fullPath, strlen($basePath));
        $relPath = ltrim($relPath, '/');

        return $relPath;
    }

    function u_is_relative_path($p) {
        $this->validatePath($p);
        return $p[0] !== '/';
    }

    function u_is_absolute_path($p) {
        $this->validatePath($p);
        return $p[0] === '/';
    }



    // MOVE, etc.

    function u_delete ($filePath) {
        if (is_dir($filePath)) {
            Tht::error("Argument 1 for `delete` must not be a directory: `$filePath`.  Suggestion: `File.deleteDir()`");
        } 
        return u_File::_call('unlink', [$filePath], 'file');
    }

    function u_delete_dir ($dirPath) {

        if (is_dir($dirPath)) {
            u_File::deleteDirRecursive($dirPath);
        } 
        else {
            Tht::error("Argument 1 for `deleteDir` is not a directory: `$dirPath`");
        }
    }

    function deleteDirRecursive ($dirPath) {

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
                u_File::deleteDirRecursive($subPath);
            } else {
                // delete file
                u_File::_call('unlink', [$subPath], 'file');
            }
        }
        closedir($dirHandle);

        u_File::_call('rmdir', [$dirPath], 'dir');
    }

    function u_copy ($source, $dest) {
        if (is_dir($source)) {
            Tht::error("Argument 1 for `copy` must not be a directory: `$source`.  Suggestion: `File.copyDir()`");
        } 
        return u_File::_call('copy', [$source, $dest], 'file,exists|path');
    }

    function u_copy_dir ($source, $dest) {
        if (is_dir($source)) {
            u_File::copyDirRecursive($source, $dest);
        } 
        else {
            Tht::error("Argument 1 for `copyDir` is not a directory: `$source`");
        }
    }

    function copyDirRecursive($source, $dest) {
        
        if (!is_dir($dest)) {
            u_File::_call('mkdir', [$dest, 0755]);
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
                u_File::copyDirRecursive($subSource, $subDest);
            } else {
                // copy file
                u_File::_call('copy', [$subSource, $subDest]);
            }
        }

        closedir($dirHandle);
    }

    function u_move ($oldName, $newName) {
        return u_File::_call('rename', [$oldName, $newName], 'path,exists|path');
    }

    function u_exists ($path) {
        return u_File::_call('file_exists', [$path], 'path', false);
    }

    function u_find ($pattern, $dirOnly=false) {
        $flags = $dirOnly ? GLOB_BRACE|GLOB_ONLYDIR : GLOB_BRACE;
        return u_File::_call('glob', [$pattern, $flags], 'string|*');
    }

    function u_touch ($file, $time=null, $atime=null) {
        if (!$time) { $time = time(); }
        if (!$atime) { $atime = time(); }
        return u_File::_call('touch', [$file, $time, $atime], 'file|num|num');
    }


    // DIRS

    function u_make_dir ($dir, $perms=0775) {
        if (file_exists($dir)) {
            return false;
        }
        return u_File::_call('mkdir', [$dir, $perms, true], 'dir|num|*', null);
    }

    function u_open_dir ($d) {
        $dh = u_File::_call('opendir', [$d], 'dir,exists');
        return new \FileDir ($dh);
    }

    // TODO: integrate with find/glob?
    // TODO: recursive
    // TODO: functional interface for better perf?
    function u_read_dir ($d, $filter = 'none') {

        if (!in_array($filter, ['none', 'files', 'dirs'])) {
            Tht::error("Unknown filter `$filter`. Supported filters: `none` (default), `files`, `dirs`");
        }

        $files = u_File::_call('scandir', [$d], 'dir,exists');
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
        return u_File::_call('filesize', [$f], 'file,exists');
    }

    function u_get_modify_time ($f) {
        return u_File::_call('filemtime', [$f], 'path,exists');
    }

    function u_get_create_time ($f) {
        return u_File::_call('filectime', [$f], 'path,exists');
    }

    function u_get_access_time ($f) {
        return u_File::_call('fileatime', [$f], 'path,exists');
    }

    function u_is_dir ($f) {
        return u_File::_call('is_dir', [$f], 'path', false);
    }

    function u_is_file ($f) {
        return u_File::_call('is_file', [$f], 'path', false);
    }

}

