<?php

namespace o;

class DirTypeString extends PathTypeString {

    protected $stringType = 'dir';
    protected $errorClass = 'File';

    protected $suggestMethod = [

        'createdir'     => 'makeDir()',
        'create'        => 'makeDir()',
        'renamedir'     => 'moveDir()',
        'rename'        => 'moveDir()',
    ];

    // COMMON
    //=========================================

    function u_is_file() {

        $this->ARGS('', func_get_args());
        return false;
    }

    function u_is_dir() {

        $this->ARGS('', func_get_args());
        return $this->u_exists();
    }

    function u_make_dir($perms='775') {

        $this->ARGS('s', func_get_args());

        if ($this->u_exists()) {
            return false;
        }
        $perms = octdec($perms);

        $this->_call('mkdir', [$this, $perms, true], 'path|num|*', true);

        return true;
    }

    function u_delete_dir($flags=null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'ifExists' => false,
        ]);

        $dirPath = $this->validatePath($this->u_render_string());

        if (!$this->u_exists()) {
            if ($flags['ifExists']) { return true; }
            $this->error("Directory to delete does not exist: `$dirPath`");
        }

        return $this->deleteDirRecursive($dirPath);
    }

    function deleteDirRecursive($dirPath) {

        $this->ARGS('s', func_get_args());

        $dirPath = $this->validatePath($dirPath);

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

    function u_copy_dir($dest) {

        $this->ARGS('*', func_get_args());

        OTypeString::assertType($dest, 'dir');

        $source = $this->u_render_string();
        $dest = $dest->u_render_string();

        return $this->copyDirRecursive($source, $dest);
    }

    function copyDirRecursive($source, $dest) {

        $source = $this->validatePath($source);
        $dest = $this->validatePath($dest);

        if (!is_dir($dest)) {
            $this->_call('mkdir', [$dest, 0755], 'path|*');
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
                $this->_call('copy', [$subSource, $subDest], 'file,exists|path');
            }
        }

        closedir($dirHandle);

        // Prevent race condition in Windows
        $dirHandle = null;

        return true;
    }

    function u_move_dir($destPath) {

        $this->ARGS('*', func_get_args());

        // Error if dest exists.  If user wants different behavior, let them check first with exists().
        if ($destPath->u_exists()) {
            $sDest = $destPath->u_render_string();
            $this->error("Destination path for `moveDir` already exists: `$sDest`");
        }

        $this->_call('rename', [$this, $destPath], 'dir,exists|path');
    }


    // DIR
    //=========================================




    // TODO: flag to ignore dotfiles?
    // TODO: file ext filter?
    function u_loop_dir($fn, $flags = null) {

        $this->ARGS('cm', func_get_args());

        $flags = $this->flags($flags, [
            'deep'   => false,
            'filter' => 'files|dirs|all',
        ]);

        $dirPath = $this->validatePath($this->u_render_string());

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

            $filePath = $isDir ? new DirTypeString($subPath) : new FileTypeString ($subPath);

            $ret = $fn($filePath);

            if ($ret !== null) {
                return $ret;
            }
        }

        return NULL_NORETURN;
    }

    function u_read_dir($flags = null) {

        $flags ??= OMap::create([]);

        $this->ARGS('m', func_get_args());

        $paths = [];
        $fn = function($filePath) use (&$paths) {
            $paths []= $filePath;
        };

        $this->u_loop_dir($fn, $flags);

        $pathList = OList::create($paths);

        $pathList->u_sort(function($a, $b){
            return $a->u_render_string() <=> $b->u_render_string();
        });

        return $pathList;
    }

    // Immutable Transforms
    //-------------------------------------------------

    function u_append_path(\o\PathTypeString $relPath) {

        $this->ARGS('*', func_get_args());

        $sRelPath = $this->validateRelativeArg('appendPath', 1, $relPath);

        $joinedPath = $this->u_render_string() . '/' . $sRelPath;
        $joinedPath = $this->validatePath($joinedPath, true);

        return PathTypeString::create($joinedPath);
    }



}
