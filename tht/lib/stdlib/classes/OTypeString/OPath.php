<?php

namespace o;

class PathTypeString extends OTypeString {

    protected $stringType = '';
    protected $errorClass = '';
    protected $drive = '';

    protected $isAbsolute = false;

    // TODO: can probably do this validation at compile time instead
    // TODO: Add a compileTimeValidate function for all TypeStrings.
    function __construct($sPath) {

        $sPath = trim($sPath);

        if (preg_match('#^([a-zA-Z]:)#', $sPath, $m)) {
            $this->drive = $m[1];
            $sPath = preg_replace('#^([a-zA-Z]:)#', '', $sPath);
        }

        $sPath = self::resolveBuiltinRoots($sPath);
        $sPath = Security::validateFilePath($sPath);
        $this->isAbsolute = Tht::isAbsolute($sPath);

        if ($this->stringType == 'dir' && is_file($sPath)) {
            $this->error("Can't create `dir` TypeString from file: `$sPath`");
        }
        else if ($this->stringType == 'file' && is_dir($sPath)) {
            $this->error("Can't create `file` TypeString from dir: `$sPath`");
        }

        parent::__construct($sPath);
    }

    function _call($fn, $args=[], $validationList='', $checkReturn=true) {

        Tht::module('Meta')->u_fail_if_template_mode();

        // Validate each argument against a validation pattern.
        // There should be exactly one pattern for every argument.
        $fargs = [];
        $validationPatterns = explode("|", $validationList);
        foreach ($args as $a) {
            $fargs []= $this->checkArg($a, array_shift($validationPatterns));
        }

        $perfDetail = is_string($args[0]) ? $args[0] : '';
        $perfTask = Tht::module('Perf')->u_start('File.' . $fn, $perfDetail);

        $returnVal = \call_user_func_array($fn, $fargs);

        $perfTask->u_stop();

        // Die on a false return value
        if ($checkReturn && $returnVal === false) {
            $relevantFile = '';
            if (isset($fargs[0])) { $relevantFile = $fargs[0]; }
            $this->error("File function failed on: `" . $relevantFile . "`");
        }

        return $returnVal;
    }

    function assertAbsolute($method) {
        if (!$this->isAbsolute) {
            $sPath = $this->u_render_string();
            $this->error("Can't call method `$method` on relative path: `$sPath`");
        }
    }

    function toObjectString() {

        $c = preg_replace('/.*\\\\/', '', get_class($this));

        // Show the last 30 chars instead of the first 30
        // 30 is the limit set in OClass
        $str = $this->u_render_string();
        if (mb_strlen($str) > 29) {
            $str = '…' . v($str)->u_right(29);
        }

        return OClass::getObjectString($c, $str, true);
    }

    // Validate argument against pattern (TODO: clean up names)
    // 'path': resolve and validate path
    // 'file': is a file
    // 'logfile': is a file in logs dir
    // 'dir': 'is a dir
    // 'exists': require path to exist
    function checkArg($a, $pattern) {

        if (OTypeString::isa($a)) {
            $a = $a->u_render_string();
        }

        if (preg_match('/path|dir|file/', $pattern)) {

            $path = $a;
            $relPath = Tht::getRelativePath('app', $path);

            $exists = file_exists($path);

            if (str_contains($pattern, 'exists')) {
                if (!$exists) {
                    $this->error("Path does not exist: `" . $relPath . "`");
                }
            }

            if ($exists) {
                if (str_contains($pattern, 'dir')) {
                    if (!is_dir($path)) {
                        $this->error("Path is not a directory: `" . $relPath . "`");
                    }
                }

                // Also matches 'logfile'
                if (str_contains($pattern, 'file')) {
                    if (!is_file($path)) {
                        $this->error("Path is not a file: `" . $relPath . "`");
                    }
                }
            }
            else if (str_contains($pattern, 'file')) {
                $parent = $this->getParent($path);
                if (!is_dir($parent)) {
                    $this->error("Parent dir does not exist for file: `" . $relPath . "`");
                }
            }

            $a = $path;
        }

        return $a;
    }

    function clearStateCache() {
        clearstatcache(false, $this->u_render_string());
    }

    function validatePath($path) {

        return Security::validateFilePath($path);
    }

    static public function resolveBuiltinRoots($path) {

        if ($path && $path[0] == '~') {
            $path = 'home:' . ltrim($path, '~');
        }

        preg_match('#^([a-z]+):(.)#', $path, $m);

        if (!$m) { return $path; }

        $rootToken = $m[1];
        $nextChar = $m[2];

        $tokens = [
            'app'    => Tht::path('app'),
            'files'  => Tht::path('files'),
            'public' => $_SERVER['DOCUMENT_ROOT'] ?? Tht::path('public'),
            'home'   => $_SERVER['HOME'] ?? getenv('HOME') ?: getenv('USERPROFILE'), // USERPROFILE = Windows
        ];

        if (isset($tokens[$rootToken])) {
            if ($nextChar !== '/') {
                Tht::error("Missing `/` after path root.  Try: `$rootToken:/`");
            }
            $rootPath = $tokens[$rootToken];
            if (!$rootPath) {
                Tht::error("Can't find server path for root key: `$rootToken`");
            }
            $path = preg_replace('#^' . $rootToken . ':#', $rootPath, $path);
        }
        else {
            $try = ErrorHandler::getFuzzySuggest($rootToken, array_keys($tokens));
            if (!$try) { $try = "Try: `app` `files` `public` `home`"; }
            Tht::error("Invalid root key: `$rootToken`  $try");
        }

        return $path;
    }

    static public function create($path, $s='') {

        if (PathTypeString::isa($path)) {
            return $path;
        }

        $path = self::resolveBuiltinRoots($path);

        if (is_dir($path)) {
            return new DirTypeString ($path);
        }
        else {
            return new FileTypeString ($path);
        }
    }

    function u_render_string() {

        $str = parent::u_render_string();

        return $this->drive . $str;
    }

    protected function u_z_escape_param($v) {

        return Security::sanitizeForFileName($v);
    }

    function u_exists() {

        $this->ARGS('', func_get_args());

        return $this->_call('file_exists', [$this], 'path', false);
    }

    function u_not_exists() {

        $this->ARGS('', func_get_args());

        return $this->_call('file_exists', [$this], 'path', false);
    }



    // PATH
    //=========================================

    function u_path_parts() {

        $this->ARGS('', func_get_args());

        $info = $this->_call('pathinfo', [$this->str], 'path');

        $dirs = explode('/', trim($info['dirname'], '/'));
        $dirList = OList::create([]);
        foreach ($dirs as $d) {
            if ($d !== '.' && $d !== '') {
                $dirList []= $d;
            }
        }

        // Add root '/'
        if ($this->u_is_absolute()) {
            $dirList->u_push_first('/');
        }

        $parts = OMap::create([
            'drive' => $this->drive,
            'dirs' => $dirList,
        ]);

        if ($this->stringType == 'file') {
            $parts['fileBase'] = $info['filename'];
            $parts['fileName'] = $info['basename'];
            $parts['fileExt']  = $info['extension'] ?? '';
        }
        else {
            $parts['dirs'] []= $info['filename'];
        }

        $parts['dirName'] = count($dirList) ? $dirList->u_last() : '';

        return $parts;
    }

    function u_is_relative() {

        $this->ARGS('', func_get_args());

        return !Tht::isAbsolute($this->str);
    }

    function u_is_absolute() {

        $this->ARGS('', func_get_args());

        return Tht::isAbsolute($this->str);
    }

    function u_has_root_dir($rootPath) {

        $this->ARGS('*', func_get_args());

        OTypeString::assertType($rootPath, 'dir');

        $sFullPath = $this->u_render_string();
        $sRootPath = $rootPath->u_render_string();

        return Tht::hasRootPath($sFullPath, $sRootPath);
    }

    function u_remove_root_dir($rootPath) {

        $this->ARGS('*', func_get_args());

        OTypeString::assertType($rootPath, 'dir');

        $sFullPath = $this->u_render_string();
        $sRootPath = $rootPath->u_render_string();
        $sEndPath = Tht::stripRootPath($sFullPath, $sRootPath);

        $origType = $this->u_string_type();

        return OTypeString::create($origType, $sEndPath);
    }

    function validateAbsoluteArg($fnName, $argNum, $path) {

        if (!$path->u_is_absolute()) {
            $this->pathValidationError($fnName, $argNum, $path, 'an absolute');
        }

        return $path->u_render_string();
    }

    function validateRelativeArg($fnName, $argNum, $path) {

        if (!$path->u_is_relative()) {
            $this->pathValidationError($fnName, $argNum, $path, 'a relative');
        }

        return $path->u_render_string();
    }

    function pathValidationError($fnName, $argNum, $path, $mustBe) {
        $sPath = $path->u_render_string();
        if ($argNum) {
            $this->error("Argument #$argNum of `$fnName()` must be $mustBe path.  Got: `$sPath`");
        }
        else {
            $this->error("Object calling `$fnName()` must be $mustBe path.  Got: `$sPath`");
        }
    }

    function u_parent_dir() {

        $this->ARGS('', func_get_args());

        $sParentPath = $this->getParent($this->u_render_string());

        if (!$sParentPath) { return NULL_NOTFOUND; }

        return new DirTypeString($sParentPath);
    }







    // function u_has_end_path($endPath) {

    //     $this->ARGS('*', func_get_args());

    //     $sFullPath = $this->validateAbsoluteArg('hasEndPath', 0, $this);
    //     $sEndPath = $this->validateRelativeArg('hasEndPath', 1, $endPath);

    //     return Tht::hasEndPath($sFullPath, $sEndPath);
    // }



    // function u_remove_end_path($endPath) {

    //     $this->ARGS('*', func_get_args());

    //     $sFullPath = $this->validateAbsoluteArg('removeEndPath', 0, $this);
    //     $sEndPath  = $this->validateRelativeArg('removeEndPath', 1, $endPath);

    //     $sRootPath = Tht::stripEndPath($sFullPath, $sEndPath);

    //     return PathTypeString::create($sRootPath);
    // }


    // TODO: check for errors - root, etc.
    function getParent($p) {

        $p = rtrim($p, '/');

        if (!str_contains($p, '/')) { return null; }

        // Remove last path element
        $p = preg_replace('#/([^/]*?)$#', '', $p);

        return $p;
    }


    // DATE / PERMS
    //=========================================

    function u_set_date($date, $dateId = 'both') {

        $this->ARGS('*s', func_get_args());

        if (!u_Date_Object::isa($date)) {
            $type = v($date)->u_type();
            $this->error("Argument #1 for `setDate` must be a Date object. Got: `$type`");
        }

        $options = ['both', 'modified', 'accessed'];
        if (!in_array($dateId, $options)) {
            $suggest = ErrorHandler::getFuzzySuggest($dateId, $options, false);
            $this->error("Unknown option for `setDate` argument #1: `$dateId`  $suggest");
        }

        $mdate = $date;
        $adate = $date;

        if ($dateId == 'modified') {
            $adate = $this->u_get_date()['accessed'];
        }
        else if ($dateId == 'accessed') {
            $mdate = $this->u_get_date()['modified'];
        }

        $mtime = $mdate->u_unix_time();
        $atime = $adate->u_unix_time();

        $this->_call('touch', [$this, $mtime, $atime], 'file|num|num');

        $this->clearStateCache();

        return $this;
    }

    function getDate($dateFn) {
        $unixTime = $this->_call($dateFn, [$this], 'path,exists');
        return Tht::module('Date')->createFromUnixTime($unixTime);
    }

    function u_get_date() {

        $this->ARGS('', func_get_args());

        $this->clearStateCache();

        $stat = $this->_call('stat', [$this], 'path,exists');

        // Note: ctime is NOT "create" time on Unix.
        $dates = OMap::create([
            'modified' => Tht::module('Date')->createFromUnixTime($stat['mtime']),
            'accessed' => Tht::module('Date')->createFromUnixTime($stat['atime']),
            'created?' => Tht::module('Date')->createFromUnixTime($stat['ctime']),
        ]);

        return $dates;
    }

    function u_get_perms() {

        $this->ARGS('', func_get_args());

        $r = $this->_call('is_readable',   [$this], 'path,exists', false);
        $w = $this->_call('is_writable',   [$this], 'path,exists', false);
        $x = $this->_call('is_executable', [$this], 'path,exists', false);

        return OMap::create([
            'read' => $r,
            'write' => $w,
            'execute' => $x,
        ]);
    }

 }


