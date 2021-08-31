<?php

namespace o;

// Not implemented. No plans to, unless necessary:
//   chdir, getcwd, is_link

// TODO:
//   is_readable, is_writeable, is_executable (getPerms?)
//   disk_free_space, disk_total_space

class u_File extends OStdModule {

    static private $EXT_TO_MIME = null;
    static private $MIME_TO_EXT = null;

    var $suggestMethod = [
        'size' => 'getSize',
        'open' => 'read',
    ];

    private $isSandboxDisabled = false;

    function _call ($fn, $args=[], $validationList='', $checkReturn=true) {

        if (!$this->isSandboxDisabled) {
            Tht::module('Meta')->u_no_template_mode();
        }

        // Validate each argument against a validation pattern
        $validationPatterns = explode("|", $validationList);
        $fargs = [];
        foreach ($args as $a) {
            $fargs []= $this->checkArg($a, array_shift($validationPatterns));
        }

        $perfDetail = is_string($args[0]) ? $args[0] : '';
        Tht::module('Perf')->u_start('File.' . $fn, $perfDetail);

        $returnVal = \call_user_func_array($fn, $fargs);

        Tht::module('Perf')->u_stop();

        // Die on a false return value
        if ($checkReturn && $returnVal === false) {
            $relevantFile = '';
            if (isset($fargs[0])) { $relevantFile = $fargs[0]; }
            $this->error("File function failed on `" . $relevantFile . "`");
        }

        return $returnVal;
    }

    function xDangerDisableSandbox() {

        $this->isSandboxDisabled = true;
    }

    // Validate argument against pattern (TODO: clean up names)
    // 'path': resolve and validate path
    // 'file': is a file
    // 'logfile': is a file in logs dir
    // 'dir': 'is a dir
    // 'exists': require path to exist
    function checkArg($a, $pattern) {

        if (preg_match('/path|dir|file/', $pattern)) {

            $allowOutsideSandbox = ($this->isSandboxDisabled || $pattern == 'logfile');
            $path = $this->validatePath($a, $allowOutsideSandbox);
            $relPath = Tht::getRelativePath('data', $path);

            $exists = file_exists($path);

            if (strpos($pattern, 'exists') !== false) {
                if (!$exists) {
                    $this->error("Path does not exist: `" . $relPath . "`");
                }
            }

            if ($exists) {
                if (strpos($pattern, 'dir') !== false) {
                    if (!is_dir($path)) {
                        $this->error("Path is not a directory: `" . $relPath . "`");
                    }
                }

                // Also matches 'logfile'
                if (strpos($pattern, 'file') !== false) {
                    if (!is_file($path)) {
                        $this->error("Path is not a file: `" . $relPath . "`");
                    }
                }
            }

            $a = $path;
        }

        return $a;
    }

    function validatePath($path, $allowOutsideSandbox=false) {

        return Security::validateFilePath($path, $allowOutsideSandbox);
    }


    // META

    function u_x_danger_no_sandbox() {

        $this->ARGS('', func_get_args());

        $f = new u_File();
        $f->xDangerDisableSandbox();

        return $f;
    }


    // READS

    function u_read ($fileName, $flags = null) {

        $this->ARGS('sm', func_get_args());

        $flags = $this->flags($flags, [
            'join' => false,
        ]);

        if ($flags['join']) {
            return $this->_call('file_get_contents', [$fileName], 'file,exists');
        }

        return $this->_call('file', [$fileName, FILE_IGNORE_NEW_LINES], 'file,exists|*');
    }

    function u_read_lines ($fileName, $fn) {

        $this->ARGS('sc', func_get_args());

        $handle = $this->_call('fopen', [$fileName, 'r'], 'file,exists|*', true);

        $retVal = false;
        while (true) {

            $line = fgets($handle);

            if ($line === false) { break; }

            $line = rtrim($line, "\n");
            $ret = $fn($line);

            if ($ret !== false) {
                $retVal = $ret;
                break;
            }
        }

        fclose($handle);

        return $retVal;
    }



    // WRITES

    function u_write($filePath, $data) {

        $this->ARGS('s*', [$filePath, $data]);

        return $this->write($filePath, $data, false);

    }

    function u_append($filePath, $data) {

        $this->ARGS('s*', [$filePath, $data]);

        return $this->write($filePath, $data, true);

    }

    function write($filePath, $data, $isAppend) {

        $this->ARGS('s*', [$filePath, $data]);

        $data = unv($data);
        if (is_array($data)) {
            $data = implode("\n", $data);
        }

        // Make sure parent dir exists
        $parentPath = $this->u_parent_dir($filePath);
        if ($parentPath && !$this->u_is_dir($parentPath)) {
            $this->error("Parent dir does not exist: `$parentPath`");
        }

        $arg = $isAppend ? LOCK_EX|FILE_APPEND : LOCK_EX;

        return $this->_call('file_put_contents', [$filePath, $data, $arg], 'path|*|*');
    }

    function u_log ($data, $fileName='app.log') {

        $this->ARGS('*s', func_get_args());

        if (is_array($data) || is_object($data)) {
            $data = Tht::module('Json')->u_format($data);
        }
        else {
            $data = trim($data);
            $data = str_replace("\n", '\\n', $data);
        }

        $line = '[' . strftime('%Y-%m-%d %H:%M:%S') . "]  " . $data . "\n";

        return $this->_call('file_put_contents',
            [Tht::path('logs', $fileName), $line, LOCK_EX|FILE_APPEND],
            'logfile|string|*');
    }

    // TODO: implement later.
    // public function u_truncate($fileName, $keepNumBytes) {

    //     $this->ARGS('sI', func_get_args());

    //     $fh = $this->_call('fopen', [$fileName, 'r+'], 'file,exists|*', true);

    //     flock($fh, LOCK_EX);
    //     ftruncate($fh, $keepNumBytes);
    //     fclose($fh);

    //     clearstatcache($fileName);

    //     // Truncate bytes at the start of file if negative $keepNumBytes (for log rotation)
    //     // $size = filesize($filename);

    //     // $fh = fopen($filename,"r+");
    //     // $start = ftell($fh);
    //     // fseek($fh, -$maxfilesize, SEEK_END);
    //     // $drop = fgets($fh);
    //     // $offset = ftell($fh);

    //     // for ($x=0; $x < $maxfilesize; $x++){
    //     //     fseek($fh, $offset + $x);
    //     //     $c = fgetc($fh);
    //     //     fseek($fh, $x);
    //     //     fwrite($fh, $c);
    //     // }
    //     // ftruncate($fh, $maxfilesize - strlen($drop));
    //     // fclose($fh);

    //     return true;
    // }

    public function u_count_lines($fileName) {

        $this->ARGS('s', func_get_args());

        $fh = $this->_call('fopen', [$fileName, 'r'], 'file,exists', true);
        flock($fh, LOCK_SH);

        $numLines = 0;
        while (true){
            $line = fgets($fh);
            if ($line === false) { break; }
            $numLines += 1;
        }

        flock($fh, LOCK_UN);
        fclose($fh);

        return $numLines;
    }


    // PATHS

    function u_path_parts ($path) {

        $this->ARGS('s', func_get_args());

        $path = str_replace('\\', '/', $path);
        $this->validatePath($path, true);
        $info = $this->_call('pathinfo', [$path]);

        $dirs = explode('/', trim($info['dirname'], '/'));
        $dirList = [];
        foreach ($dirs as $d) {
            if ($d !== '.' && $d !== '') {
                $dirList []= $d;
            }
        }

        return OMap::create([
            'dirPathParts'  => $dirList,
            'dirPath'       => $info['dirname'],
            'fileNameShort' => $info['filename'],
            'fileName'      => $info['basename'],
            'fileExt'       => isset($info['extension']) ? $info['extension'] : '',
            'path'          => $path,
        ]);
    }

    function u_join_path () {

        $parts = func_get_args();
        $path = implode('/', unv($parts));
        $path = $this->validatePath($path, true);

        return $path;
    }

    // function u_clean_path ($path) {

    //     $this->ARGS('s', func_get_args());

    //     return $this->validatePath($path, true);
    // }

    // function u_full_path ($relPath) {

    //     $this->ARGS('s', func_get_args());

    //     return Tht::normalizeWinPath(
    //         $this->_call('realpath', [$relPath], 'path')
    //     );
    // }

    function u_strip_root_path ($fullPath, $rootPath) {

        $this->ARGS('ss', func_get_args());

        $fullPath = $this->validateAbsoluteArg('1st', '$fullPath', $fullPath);
        $rootPath = $this->validateAbsoluteArg('2nd', '$rootPath', $rootPath);

        if (!$this->u_has_root_path($fullPath, $rootPath)) {
            // TODO: include values in output, for comparison (and ensure raw paths are shown)
            $this->error('Root path not found in full path.');
        }

        $remainder = substr($fullPath, strlen($rootPath));
        $remainder = ltrim($remainder, '/');

        return $remainder;
    }

    function u_strip_end_path ($fullPath, $endPath) {

        $this->ARGS('ss', func_get_args());

        $fullPath = $this->validateAbsoluteArg('1st', '$fullPath', $fullPath);
        $endPath  = $this->validateRelativeArg('2nd', '$endPath', $endPath);

        if (!$this->u_has_end_path($fullPath, $endPath)) {
            // TODO: include values in output, for comparison (and ensure raw paths are shown)
            $this->error('End path not found in full path.');
        }

        $remainder = substr($fullPath, 0, strlen($fullPath) - strlen($endPath));
        $remainder = rtrim($remainder, '/');

        return $remainder;
    }

    function u_has_root_path($fullPath, $rootPath) {

        $this->ARGS('ss', func_get_args());

        $fullPath = $this->validateAbsoluteArg('1st', '$fullPath', $fullPath);
        $rootPath = $this->validateAbsoluteArg('2nd', '$rootPath', $rootPath);

        // Make sure match doesn't cross dir boundaries
        if ($rootPath[strlen($rootPath) - 1] != '/') {
            $rootPath .= '/';
        }

        return strpos($fullPath, $rootPath) === 0;
    }

    function u_has_end_path($fullPath, $relPath) {

        $this->ARGS('ss', func_get_args());

        $fullPath = $this->validateAbsoluteArg('1st', '$fullPath', $fullPath);
        $relPath  = $this->validateRelativeArg('2nd', '$relativePath', $relPath);

        // Make sure match doesn't cross dir boundaries
        if ($relPath[0] != '/') {
            $relPath = '/' . $relPath;
        }

        $offset = strlen($fullPath) - strlen($relPath);

        return strpos($fullPath, $relPath) === $offset;
    }

    function validateAbsoluteArg($num, $name, $path) {

        $path = $this->validatePath($path, true);

        if (!$this->u_is_absolute($path)) {
            $this->error("$num argument (`$name`) must be an absolute path.");
        }

        return $path;
    }

    function validateRelativeArg($num, $name, $path) {

        $path = $this->validatePath($path, true);

        if (!$this->u_is_relative($path)) {
            $this->error("$num argument (`$name`) must be a relative path.");
        }

        return $path;
    }

    function u_is_relative($p) {

        $this->ARGS('s', func_get_args());

        return !$this->u_is_absolute($p);
    }

    function u_is_absolute($p) {

        $this->ARGS('s', func_get_args());

        $p = $this->validatePath($p, true);

        return preg_match('#^([A-Za-z]:)?/#', $p);
    }

    // TODO: different for abs and rel paths
    // TODO: bounds check

    // 'foo'
    // 'foo.txt'
    // 'dir/foo'
    // '/'
    // 'dir/foo/'
    function u_parent_dir($p) {

        $this->ARGS('s', func_get_args());

        $isRel = $this->u_is_relative($p);

        $p = rtrim($p, '/');
        $p = $this->validatePath($p, true);

        if (strpos($p, '/') === false) {
            return $isRel ? '' : '/';
        }

        $parentPath = preg_replace('#/.*?$#', '', $p);

        return $parentPath;
    }

    function u_app_path($relPath='') {

        $this->ARGS('s', func_get_args());

        return Tht::path('app', $relPath);
    }

    function u_public_path($relPath='') {

        $this->ARGS('s', func_get_args());

        return Tht::path('public', $relPath);
    }

    function u_public_url($fullPath) {

        $this->ARGS('s', func_get_args());

        $relPath = Tht::getRelativePath('public', $fullPath);

        return new UrlTypeString ($relPath);
    }



    // MOVE, etc.

    function u_delete ($filePath) {

        $this->ARGS('s', func_get_args());

        if (is_dir($filePath)) {
            $this->error("Argument 1 for `delete` must not be a directory: `$filePath`.  Try: `File.deleteDir()`");
        }

        if (!$this->u_exists($filePath)) {
            return true;
        }

        return $this->_call('unlink', [$filePath], 'file');
    }

    function u_delete_dir ($dirPath) {

        $this->ARGS('s', func_get_args());

        $checkPath = $this->validatePath($dirPath, $this->isSandboxDisabled);
        if (!is_dir($checkPath)) {
            $this->error("Argument 1 for `deleteDir` is not a directory: `$dirPath`");
        }

        return $this->deleteDirRecursive($dirPath);
    }

    function deleteDirRecursive ($dirPath) {

        $this->ARGS('s', func_get_args());

        $dirPath = $this->validatePath($dirPath, $this->isSandboxDisabled);

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

        // Prevent race condition in Windows
        $dirHandle = null;

        return $this->_call('rmdir', [$dirPath], 'dir');
    }

    function u_copy ($source, $dest) {

        $this->ARGS('ss', func_get_args());

        if (is_dir($source)) {
            $this->error("Argument 1 for `copy` must not be a directory: `$source`.  Suggestion: `File.copyDir()`");
        }

        return $this->_call('copy', [$source, $dest], 'file,exists|path');
    }

    function u_copy_dir ($source, $dest) {

        $this->ARGS('ss', func_get_args());

        if (!is_dir($source)) {
            $this->error("Argument 1 for `copyDir` is not a directory: `$source`");
        }

        return $this->copyDirRecursive($source, $dest);
    }

    function copyDirRecursive($source, $dest) {

        $this->ARGS('ss', func_get_args());

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
            }
            else {
                // copy file
                $this->_call('copy', [$subSource, $subDest], '');
            }
        }

        closedir($dirHandle);

        // Prevent race condition in Windows
        $dirHandle = null;

        return true;
    }

    function u_move ($oldName, $newName) {

        $this->ARGS('ss', func_get_args());

        return $this->_call('rename', [$oldName, $newName], 'path,exists|path');
    }

    function u_exists ($path) {

        $this->ARGS('s', func_get_args());

        return $this->_call('file_exists', [$path], 'path', false);
    }

    // TODO: Reimplement with regex, add path
    // function u_find ($pattern, $aflags=null) {

    //     $this->ARGS('sm', func_get_args());

    //     $flags = $aflags && $aflags['dirOnly'] ? GLOB_BRACE|GLOB_ONLYDIR : GLOB_BRACE;

    //     return $this->_call('glob', [$pattern, $flags], 'string|*');
    // }

    function u_touch ($file, $date=null, $flags=null) {

        $this->ARGS('s**', func_get_args());

        $flags = $this->flags($flags, [
            'stat'   => 'both|access|modify',
        ]);

        if (!$date) { $date = Tht::module('Date')->u_now(); }

        if (!u_Date_Object::isa($date)) {
            $this->error('Argument `$date` must be a Date object.');
        }

        $mdate = $date;
        $adate = $date;

        if ($flags['stat'] == 'modify') {
            $adate = $this->u_get_Access_Time($file);
        }
        else if ($flags['stat'] == 'access') {
            $mdate = $this->u_get_Modify_Time($file);
        }

        $mtime = $mdate->u_unix_time();
        $atime = $adate->u_unix_time();

        $this->_call('touch', [$file, $mtime, $atime], 'file|num|num');

        clearstatcache(false, $file);

        return EMPTY_RETURN;
    }


    // DIRS

    function u_make_dir ($dir, $perms='775') {

        $this->ARGS('ss', func_get_args());

        if ($this->u_exists($dir)) {
            return EMPTY_RETURN;
        }
        $perms = octdec($perms);

        $this->_call('mkdir', [$dir, $perms, true], 'path|num|*', true);

        return EMPTY_RETURN;
    }

    function u_open_dir ($d) {

        $this->ARGS('s', func_get_args());

        $dh = $this->_call('opendir', [$d], 'dir,exists');

        return new \FileDir ($dh);
    }

    function u_read_dir ($dirPath, $flags=null) {

        $this->ARGS('sm', func_get_args());

        $paths = [];
        $fn = function($fileInfo) use (&$paths) {
            if (!$this->isSandboxDisabled) {
                $path = Tht::getRelativePath('files', $fileInfo['path']);
            }
            $paths []= $path;
        };

        $this->u_loop_dir($dirPath, $fn, $flags);

        return OList::create($paths);
    }

    // TODO: flag to ignore dotfiles?
    // TODO: file ext filter?
    function u_loop_dir($dirPath, $fn, $flags=null) {

        $this->ARGS('scm', func_get_args());

        $flags = $this->flags($flags, [
            'deep'   => false,
            'filter' => 'files|dirs|all',
        ]);

        $dirPath = $this->validatePath($dirPath, $this->isSandboxDisabled);

        $dirStack = [$dirPath];
        $dirHandle = null;

        $ignoreFiles = ['.', '..', '.DS_Store', 'thumbs.db', 'desktop.ini'];

        if (!file_exists($dirPath)) {
            $this->error("Directory path does not exist: `$dirPath`");
        }
        else if (!is_dir($dirPath)) {
            $this->error("Path is not a directory: `$dirPath`");
        }

        $filter = $flags['filter'];

        while (true) {

            if (!$dirHandle) {
                if (count($dirStack)) {
                    $dirPath = array_pop($dirStack);
                    $dirHandle = opendir($dirPath);
                }
                else {
                    break;
                }
            }

            $file = readdir($dirHandle);
            if (!$file) {
                // last file in dir
                closedir($dirHandle);
                $dirHandle = null;
                continue;
            }
            else if (in_array($file, $ignoreFiles)) {
                continue;
            }

            $subPath = $dirPath . "/" . $file;
            $isDir = is_dir($subPath);

            $filter = $flags['filter'];

            if ($isDir) {
                if ($flags['deep']) {
                    $dirStack []= $subPath;
                }

                if ($filter != 'dirs' && $filter != 'all') {
                    continue;
                }
            }
            else {
                if ($filter != 'files' && $filter != 'all') {
                    continue;
                }
            }

            $fileInfo = $this->u_path_parts($subPath);

            $ret = $fn($fileInfo);

            if ($ret === true) {
                break;
            }
        }

        return EMPTY_RETURN;
    }


    // FILE ATTRIBUTES

    function u_get_size ($f) {

        $this->ARGS('s', func_get_args());

        return $this->_call('filesize', [$f], 'file,exists');
    }

    function u_get_modify_time ($f) {

        $this->ARGS('s', func_get_args());

        $unixTime = $this->_call('filemtime', [$f], 'path,exists');

        return Tht::module('Date')->createFromUnixTime($unixTime);
    }

    function u_get_create_time ($f) {

        $this->ARGS('s', func_get_args());

        $unixTime = $this->_call('filectime', [$f], 'path,exists');

        return Tht::module('Date')->createFromUnixTime($unixTime);
    }

    function u_get_access_time ($f) {

        $this->ARGS('s', func_get_args());

        $unixTime = $this->_call('fileatime', [$f], 'path,exists');

        return Tht::module('Date')->createFromUnixTime($unixTime);
    }

    function u_is_dir ($f) {

        $this->ARGS('s', func_get_args());

        return $this->_call('is_dir', [$f], 'path', false);
    }

    function u_is_file ($f) {

        $this->ARGS('s', func_get_args());

        return $this->_call('is_file', [$f], 'path', false);
    }

    function u_get_perms ($f) {

        $this->ARGS('s', func_get_args());

        $r = $this->_call('is_readable',   [$f], 'path,exists', false);
        $w = $this->_call('is_writable',   [$f], 'path,exists', false);
        $x = $this->_call('is_executable', [$f], 'path,exists', false);

        return OMap::create([
            'read' => $r,
            'write' => $w,
            'execute' => $x,
        ]);
    }

    // TODO: Should probably be in a Csv module?
    // function u_parse_csv($fileName, $flag='-default') {

    //      $flag = OFlag::createFromArg('parseCsv', $flag, '-assignKeys');

    //     $this->ARGS('sf', [$fileName, $flag]);

    //     $handle = $this->_call('fopen', [$fileName, 'r'], 'file,exists|*', true);
    //     flock($fh, LOCK_SH);

    //     $records = [];
    //     $keys = [];
    //     $lineNum = 0;

    //     while (true) {

    //         $lineData = fgetcsv($fh);
    //         if ($lineData === false) { break; }

    //         // Convert numeric fields to numbers
    //         foreach ($lineData as $i => $cell) {
    //             if (is_numeric($cell)) { $lineData[$i] = floatval($cell); }
    //         }

    //         if ($flag->is('-assignKeys')) {
    //             if ($lineNum == 0) {
    //                 $keys = OList::create($lineData);
    //             }
    //             else {
    //                 $records []= OList::create($lineData)->u_to_map($keys);
    //             }
    //             $lineNum += 1;
    //         }
    //         else {
    //             $records []= OList::create($lineData);
    //         }
    //     }

    //     flock($fh, LOCK_UN);
    //     fclose($fh);

    //     return OList::create($records);
    // }

    function u_get_mime_type ($f) {

        $this->ARGS('s', func_get_args());

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return $this->_call('finfo_file', [$finfo, $f], '*|file,exists', true);
    }

    function u_extension_to_mime_type($ext) {

        $this->ARGS('s', func_get_args());

        $this->initMimeMap();

        $ext = strtolower($ext);
        $ext = ltrim($ext, '.');

        if (isset(self::$EXT_TO_MIME[$ext])) {
            return self::$EXT_TO_MIME[$ext];
        }

        return 'application/octet-stream';
    }

    function u_mime_type_to_extension($mime) {

        $this->ARGS('s', func_get_args());

        $this->initMimeMap();

        $mime = strtolower($mime);

        if (isset(self::$MIME_TO_EXT[$mime])) {
            return self::$MIME_TO_EXT[$mime];
        }

        return '';
    }

    // A list of the most common MIME types.
    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Complete_list_of_MIME_types
    function initMimeMap() {

        if (self::$EXT_TO_MIME) {
            return;
        }

        self::$EXT_TO_MIME = [
           'aac'  => 'audio/aac',
           'abw'  => 'application/x-abiword',
           'arc'  => 'application/x-freearc',
           'avi'  => 'video/x-msvideo',
           'azw'  => 'application/vnd.amazon.ebook',
           'bin'  => 'application/octet-stream',
           'bmp'  => 'image/bmp',
           'bz'   => 'application/x-bzip',
           'bz2'  => 'application/x-bzip2',
           'css'  => 'text/css',
           'csv'  => 'text/csv',
           'doc'  => 'application/msword',
           'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
           'eot'  => 'application/vnd.ms-fontobject',
           'epub' => 'application/epub+zip',
           'flv'  => 'video/x-flv',
           'gz'   => 'application/gzip',
           'gif'  => 'image/gif',
           'htm'  => 'text/html',
           'html' => 'text/html',
           'ico'  => 'image/vnd.microsoft.icon',
           'ics'  => 'text/calendar',
           'jar'  => 'application/java-archive',
           'jpeg' => 'image/jpeg',
           'jpg'  => 'image/jpeg',
           'js'   => 'text/javascript',
           'json' => 'application/json',
           'mid'  => 'audio/midi',
           'midi' => 'audio/midi',
           'mjs'  => 'text/javascript',
           'mov'  => 'video/quicktime',
           'mp3'  => 'audio/mpeg',
           'mp4'  => 'video/mpeg',
           'mpeg' => 'video/mpeg',
           'mpkg' => 'application/vnd.apple.installer+xml',
           'odp'  => 'application/vnd.oasis.opendocument.presentation',
           'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
           'odt'  => 'application/vnd.oasis.opendocument.text',
           'oga'  => 'audio/ogg',
           'ogv'  => 'video/ogg',
           'ogx'  => 'application/ogg',
           'otf'  => 'font/otf',
           'png'  => 'image/png',
           'pdf'  => 'application/pdf',
           'php'  => 'application/php',
           'ppt'  => 'application/vnd.ms-powerpoint',
           'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
           'rar'  => 'application/x-rar-compressed',
           'rtf'  => 'application/rtf',
           'sh'   => 'application/x-sh',
           'svg'  => 'image/svg+xml',
           'swf'  => 'application/x-shockwave-flash',
           'tar'  => 'application/x-tar',
           'tif'  => 'image/tiff',
           'tiff' => 'image/tiff',
           'ts'   => 'video/mp2t',
           'ttf'  => 'font/ttf',
           'txt'  => 'text/plain',
           'vsd'  => 'application/vnd.visio',
           'wav'  => 'audio/wav',
           'weba' => 'audio/webm',
           'webm' => 'video/webm',
           'webp' => 'image/webp',
           'wmv'  => 'video/x-ms-wmv',
           'woff' => 'font/woff',
           'woff2' => 'font/woff2',
           'xhtml' => 'application/xhtml+xml',
           'xls'  => 'application/vnd.ms-excel',
           'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
           'xml'  => 'text/xml',
           'xul'  => 'application/vnd.mozilla.xul+xml',
           'zip'  => 'application/zip',
           '3gp'  => 'video/3gpp audio/3gpp',
           '3g2'  => 'video/3gpp2 audio/3gpp2',
           '7z'   => 'application/x-7z-compressed',
       ];

       self::$MIME_TO_EXT = array_flip(self::$EXT_TO_MIME);
    }

}

