<?php

namespace o;

// TODO: test all on Windows (path separator)

class u_File extends StdModule {

    function _call ($fn, $args=[], $checkFileExists='', $checkReturn=true) {

        Owl::module('Perf')->u_start('File.' . $fn, $args[0]);

        Owl::module('Meta')->u_no_template_mode();

        if ($checkFileExists) {
            $this->validatePath($checkFileExists);
            if (!file_exists($checkFileExists)) {
                $checkFileExists = $this->fixSeparator($checkFileExists);
                Owl::error("File does not exist: " . Owl::getRelativePath('root', $checkFileExists));
            }
        }

        // validate all arguments, unless it's a non-path arg wrapped in an array
        $fargs = [];
        foreach ($args as $a) {
            if (is_array($a)) {
                $fargs []= $a[0];
            } else {
                $fargs []= $this->validatePath($a);
            }
        }

        $ret = \call_user_func_array($fn, $fargs);

        if (!$checkReturn && $ret === FALSE) {
            $fileToCheck = $checkFileExists ?: '';
            if (!$fileToCheck && isset($fargs[0])) {  $fileToCheck = $fargs[0];  }
            Owl::error("File Error: " . realpath($fileToCheck));
        }

        Owl::module('Perf')->u_stop();

        return $ret;
    }

    function fixSeparator ($rawPath) {
        return str_replace('\\', '/', $rawPath);
    }

    // [security]
    function validatePath ($path) {

        if (!strlen($path)) {
            Owl::error('File path cannot be empty: `' . $path . '`');
        }
        if (v($path)->u_is_url()) {
            Owl::error('Remote URL not allowed: ' . $path);
        }
        if (strpos($path, '..') !== false) {
            Owl::error("Parent shortcut '..' not allowed: " . $path);
        }
        return $path;
    }


    // READS

    function u_read ($fileName, $single=false) {
        if ($single) {
            return u_File::_call('file_get_contents', [$fileName], $fileName, true);
        } else {
            return u_File::_call('file', [$fileName, [FILE_IGNORE_NEW_LINES]], $fileName, true);
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
            Owl::error("Unknown write mode '$mode'.\n\nSupported modes: replace (default), append, restore");
        }

        if ($mode == 'restore') {
            if (u_File::u_file_exists($fileName)) {
                return false;
            }
        }
        $arg = $mode == 'append' ? [LOCK_EX|FILE_APPEND] : [LOCK_EX];
        return u_File::_call('file_put_contents', [$fileName, [$data], $arg]);
    }

    // function u_append ($fileName, $data) {
    //     $data = uv($data);
    //     if (is_array($data)) {
    //         $data = implode($data, "\n");
    //     }
    //     return u_File::_call('file_put_contents', [$fileName, [$data], [LOCK_EX|FILE_APPEND]]);
    // }


    function u_log ($data, $fileName='app.log') {

        // TODO: clean this up
        // if (!$fileName) {
        //     $out = OBare::formatPrint([$data]);
        //     Owl::errorLog($out);
        //     return;
        // }

        if (is_array($data) || is_object($data)) {
            $data = Owl::module('Json')->u_format($data);
        } else {
            $data = trim($data);
            $data = str_replace("\n", '\\n', $data);
        }
        $line = '[' . strftime('%Y-%m-%d %H:%M:%S') . "]  " . $data . "\n";
        return u_File::_call('file_put_contents', [Owl::path('logs', $fileName), [$line], [LOCK_EX|FILE_APPEND]]);
    }


    // INFO

    function u_path_info ($path) {
        $info = u_File::_call('pathinfo', [realpath($path)], $path);
        return [
            'dirs'      => explode(DIRECTORY_SEPARATOR, $info['dirname']),
            'dirPath'   => $info['dirname'],
            'baseName'  => $info['basename'],
            'fileName'  => $info['filename'],
            'extension' => $info['extension']
        ];
    }

    function u_path ($parts) {
        $path = implode('/', $parts);
        $path = u_File::u_clean($path);
        return $path;
    }

    function u_clean ($path) {
        $path = preg_replace('![/\\\\]+!', '/', $path);
        $path = rtrim($path, '/');
        return $path;
    }


    // PATHS

    function u_data_path ($relPath) {
        return Owl::getAppDataPath($relPath);
    }

    function u_document_path ($relPath) {
        return Owl::makePath(Owl::getPhpGlobal('server', 'DOCUMENT_ROOT'), $relPath);
    }

    function u_full_path ($relPath) {
        return u_File::_call('realpath', [$relPath], $relPath);
    }

    function u_relative_path ($fullPath, $basePath) {

        $basePath = u_File::u_clean($basePath);
        $fullPath = u_File::u_clean($fullPath);

        $fullBase = substr($fullPath, 0, strlen($basePath));
        if (strtolower($fullBase) !== strtolower($basePath)) {
            Owl::error('Base path not found in full path.', [ 'fullPath' => $fullPath, 'basePath' => $basePath ]);
        }

        $relPath = substr($fullPath, strlen($basePath));
        $relPath = ltrim($relPath, '/');

        return $relPath;
    }

    function u_is_relative_path($p) {
        return strlen($p) && $p[0] !== '/';
    }

    function u_is_absolute_path($p) {
        return strlen($p) && $p[0] === '/';
    }


    // MOVE, etc.

    function u_delete ($fileName) {
        return u_File::_call('unlink', [$fileName], $fileName);
    }

    function u_copy ($source, $dest) {
        return u_File::_call('copy', [$source, $dest], $source);
    }

    function u_move ($oldName, $newName) {
        return u_File::_call('rename', [$oldName, $newName], $oldName);
    }

    function u_exists ($path) {
        return u_File::_call('file_exists', [$path]);
    }

    function u_find ($pattern, $dirOnly=false) {
        $flags = $dirOnly ? GLOB_BRACE|GLOB_ONLYDIR : GLOB_BRACE;
        return u_File::_call('glob', [$pattern, [$flags]]);
    }

    function u_touch ($file, $time=null, $atime=null) {
        if (!$time) { $time = time(); }
        if (!$atime) { $atime = time(); }
        return u_File::_call('touch', [$file, [$time], [$atime]]);
    }


    // DIRS

    // TODO: make dir versions of delete, copy, move?

    function u_make_dir ($dir, $perms=0775) {
        if (file_exists($dir)) {
            return false;
        }
        return u_File::_call('mkdir', [$dir, [$perms], [true]], null, true);
    }

    // function u_set_current_dir ($d) {
    //     return u_File::_call('chdir', [$d]);
    // }
    //
    // function u_get_current_dir () {
    //     return u_File::_call('getcwd');
    // }

    function u_open_dir ($d) {
        $dh = u_File::_call('opendir', [$d], $d);
        return new \FileDir ($dh);
    }

    // TODO: integrate with find/glob?
    // TODO: recursive
    // TODO: functional interface for better perf
    function u_read_dir ($d, $filter = 'none') {

        if (!in_array($filter, ['none', 'files', 'dirs'])) {
            Owl::error("Unknown filter '$filter'.\n\nSupported filters: none (default), files, dirs");
        }

        $files = u_File::_call('scandir', [$d], $d);
        if ($filter) {
            $filteredFiles = [];
            foreach ($files as $f) {
                if ($filter === 'dirs' && is_dir($f)) {
                    $filteredFiles []= $f;
                } else if ($filter === 'files' && is_file($f)) {
                    $filteredFiles []= $f;
                }
            }
            $files = $filteredFiles;
        }

        return $files;
    }


    // FILE ATTRIBUTES

    function u_get_size ($f) {
        return u_File::_call('filesize', [$f], $f);
    }

    function u_get_modify_time ($f) {
        return u_File::_call('filemtime', [$f], $f);
    }

    function u_get_create_time ($f) {
        return u_File::_call('filectime', [$f], $f);
    }

    function u_get_access_time ($f) {
        return u_File::_call('fileatime', [$f], $f);
    }

    function u_is_dir ($f) {
        return u_File::_call('is_dir', [$f], $f, false);
    }

    function u_is_file ($f) {
        return u_File::_call('is_file', [$f], $f, false);
    }

    // function u_is_link ($f) {
    //     return u_File::_call('is_link', [$f], $f, false);
    // }


    // FILE SYSTEM

    // function u_free_disk_space ($dir) {
    //     return u_File::_call('disk_free_space', [$dir], '');
    // }
    //
    // function u_total_disk_space ($dir) {
    //     return u_File::_call('disk_total_space', [$dir], '');
    // }


    // PERMISSIONS

    // function u_can_read ($f) {
    //     return u_File::_call('is_readable', [$f], $f, false);
    // }
    //
    // function u_can_write ($f) {
    //     return u_File::_call('is_writeable', [$f], $f, false);
    // }
    //
    // function u_can_execute ($f) {
    //     return u_File::_call('is_executable', [$f], $f, false);
    // }


}

