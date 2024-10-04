<?php

namespace o;

class FileTypeString extends PathTypeString {

    protected $stringType = 'file';
    protected $errorClass = 'File';

    var $suggestMethod = [
        'open' => 'read(), readEachLine()',
    ];

    // COMMON
    //=========================================

    function u_is_file() {

        $this->ARGS('', func_get_args());

        $this->assertAbsolute('isFile');

        return $this->u_exists();
    }

    function u_is_dir() {

        $this->ARGS('', func_get_args());

        $this->assertAbsolute('isDir');

        return false;
    }


    // FILE
    //=========================================



    function u_read($flags = null) {

        $this->ARGS('m', func_get_args());

        $this->assertAbsolute('read');

        $flags = $this->flags($flags, [
            'join' => false,
            'keepBlanks' => false,
        ]);

        // TODO: just test that error message is user-friendly when running out of memory from reading a large file.
        // $currentMemBytes = Tht::module('System')->u_get_memory_usage();
        // $maxMemMb = intval(ini_get('memory_limit'));
        // $maxMemBytes = $maxMemMb * 1024 * 1024;
        // $fileSizeBytes = $this->u_get_size();

        // if ($currentMemBytes + $fileSizeBytes >= $maxMemBytes) {
        //     $file = $this->u_render_string();
        //     $fileSizeMb = floor($fileSizeBytes / (1024 * 1024));
        //     $this->addErrorHelpLink('readEachLine');
        //     $this->error("Reading file will exceed max memory limit.  File: `$file`  Memory Limit: $maxMemMb MB");
        // }

        if ($flags['join']) {
            $content = $this->_call('file_get_contents', [$this], 'file,exists');
            return rtrim($content, "\r\n");  // file_get_contents adds a newline at the end
        }
        else if (!$flags['keepBlanks']) {
            unset($flags['join']);
            $filtered = [];
            $this->u_read_each_line(function($line) use (&$filtered) { $filtered []= $line; }, $flags);
            return $filtered;
        }
        else {
            return $this->_call('file', [$this, FILE_IGNORE_NEW_LINES], 'file,exists|*');
        }

    }

    function u_read_each_line($fn, $flags = null) {

        $this->ARGS('cm', func_get_args());

        $this->assertAbsolute('readEachLine');

        $flags = $this->flags($flags, [
            'keepBlanks' => false,
        ]);

        $handle = $this->_call('fopen', [$this, 'r'], 'file,exists|*', true);

        $retVal = null;
        while (true) {

            $line = fgets($handle);
            if ($line === false) { break; }

            $line = rtrim($line, "\n");

            if (!$flags['keepBlanks']) {
                // Perf: is it better to check the first character before running regex?
                if ($line === '' || preg_match('/^\s+$/', $line)) {
                    continue;
                }
            }

            $ret = $fn($line);

            if ($ret !== NULL_NORETURN) {
                $retVal = $ret;
                break;
            }
        }

        fclose($handle);

        return $retVal;
    }

    function u_count_lines($flags = null) {

        $this->ARGS('m', func_get_args());

        $this->assertAbsolute('countLines');

        $flags = $this->flags($flags, [
            'keepBlanks' => false,
        ]);

        $numLines = 0;
        $this->u_read_each_line(function($l) use (&$numLines){
            $numLines += 1;
            return NULL_NORETURN;
        }, $flags);

        return $numLines;
    }

    function u_get_size($units='B', $flags=null) {

        $this->ARGS('sm', func_get_args());

        $this->assertAbsolute('getSize');

        $flags = $this->flags($flags, [
            'ifExists' => false,
        ]);

        if (!$this->u_exists()) {
            if ($flags['ifExists']) { return 0; }
            $path = $this->u_render_string();
            $this->addErrorHelpLink('getSize');
            $this->error("File for `getSize` does not exist: `$path`  Try: Pass in `-ifExists` argument");
        }

        $bytes = $this->_call('filesize', [$this], 'file,exists');

        if ($units !== 'B') {
            return v($bytes)->u_from_bytes_to($units);
        }
        else {
            return $bytes;
        }
    }

    function u_get_mime_type() {

        $this->ARGS('', func_get_args());

        $this->assertAbsolute('getMimeType');

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return $this->_call('finfo_file', [$finfo, $this], '*|file,exists', true);
    }










    function u_delete($flags=null) {

        $this->ARGS('m', func_get_args());

        $this->assertAbsolute('delete');

        $flags = $this->flags($flags, [
            'ifExists' => false,
        ]);

        if (!$this->u_exists()) {
            if ($flags['ifExists']) { return $this; }
            $path = $this->u_render_string();
            $this->addErrorHelpLink('delete');
            $this->error("File to delete does not exist: `$path`  Try: Pass in `-ifExists` argument");
        }

        $this->_call('unlink', [$this], 'file,exists');

        return $this;
    }

    function u_copy(PathTypeString $destPath, $flags=null) {

        $this->ARGS('*m', func_get_args());

        $this->assertAbsolute('copy');

        $flags = $this->flags($flags, [
            'overwrite' => false,
        ]);

        if ($destPath->u_string_type() == 'dir') {
            $destPath = $destPath->u_append_path(new FileTypeString($this->u_path_parts()['fileName']));
        }

        // Error if dest exists.  If user wants different behavior, let them check first with exists().
        if ($destPath->u_exists() && !$flags['overwrite']) {
            $sDest = $destPath->u_render_string();
            $this->addErrorHelpLink('copy');
            $this->error("Destination path for `copy` already exists: `$sDest`  Try: Pass in `-overwrite` flag.");
        }

        $this->_call('copy', [$this, $destPath], 'file,exists|path');

        return $destPath;
    }

    function u_move(PathTypeString $destPath, $flags=null) {

        $this->ARGS('*m', func_get_args());

        $this->assertAbsolute('move');

        $flags = $this->flags($flags, [
            'overwrite' => false,
        ]);

        if ($destPath->u_string_type() == 'dir') {
            $destPath = $destPath->u_append_path(new FileTypeString($this->u_path_parts()['fileName']));
        }

        // Error if dest exists.  If user wants different behavior, let them check first with exists().
        if ($destPath->u_exists() && !$flags['overwrite']) {
            $sDest = $destPath->u_render_string();
            $this->addErrorHelpLink('move');
            $this->error("Destination path for `move` already exists: `$sDest`  Try: Pass in `-overwrite` flag.");
        }

        $this->_call('rename', [$this, $destPath], 'file,exists|path');

        return $destPath;
    }






    function u_write($data, $flags = null) {

        $this->ARGS('*m', func_get_args());

        $flags = $this->flags($flags, [
            'ifNotExists' => false,
        ]);

        $this->assertAbsolute('write');

        // TODO: might need to lock the file between writing and checking if it exists.
        // TODO: Create "transaction" system? (or just an flock function that takes a callback)
        if ($this->u_exists() && $flags['ifNotExists']) {
            return $this;
        }

        $flags['isAppend'] = false;

        $this->write($data, $flags);

        return $this;
    }

    function u_append($data) {

        $this->ARGS('*', func_get_args());

        $this->assertAbsolute('append');

        $flags['isAppend'] = true;

        $this->write($data, $flags);

        return $this;
    }

    function write($data, $flags) {

        if (OList::isa($data)) {
            $data = rtrim($data->u_join("\n")) . "\n";
        }
        else if (OTypeString::isa($data)) {
            $data = $data->u_render_string();
        }
        else if (!is_string($data)) {
            $gotType = v($data)->u_type();
            $file = $this->u_render_string();
            $this->error("Can only write string or list of strings to file: `$file`  Got: `$gotType`");
        }

        $arg = $flags['isAppend'] ? LOCK_EX|FILE_APPEND : LOCK_EX;

        $ret = $this->_call('file_put_contents', [$this, $data, $arg], 'file|*|*');

        $this->clearStateCache();

        return $ret;
    }

    function u_to_url() {

        $this->ARGS('', func_get_args());

        $this->assertAbsolute('toUrl');

        $fullPath = $this->validatePath($this->u_render_string());

        $publicDir = new DirTypeString(Tht::path('public'));
        $pagesDir = new DirTypeString(Tht::path('pages'));

        $relPath = '';
        if ($this->u_has_root_dir($pagesDir)) {
            $relPath = Tht::getRelativePath('pages', $fullPath);
            $relPath = v($relPath)->u_remove_right('.tht');
        }
        else if ($this->u_has_root_dir($publicDir)) {
            $relPath = Tht::getRelativePath('public', $fullPath);
        }
        else {
            $this->error("Path must be relative to `code/public` or `code/pages` directory.  Got: `$fullPath`");
        }

        return new UrlTypeString ($relPath);
    }


    // Immutable Transforms
    //-------------------------------------------------

    function u_with_file_name($newFileName) {

        $this->ARGS('s', func_get_args());

        // TODO: allow unicode, but disallow punctuation?
        if (preg_match('#([^a-zA-Z0-9_\-\.])#', $newFileName, $m)) {
            $this->error("Unsupported character `$m[1]` in file name: `$newFileName`  Allowed characters: `a-z A-Z 0-9 _ - .`");
        }

        $rootPath = $this->u_parent_dir();

        $sPath = $rootPath ? $rootPath->u_render_string() : '';
        $sPath .= '/' . $newFileName;

        return PathTypeString::create($sPath);
    }

    function u_with_file_base($newBaseName) {

        $this->ARGS('s', func_get_args());

        // TODO: allow unicode, but disallow punctuation?
        if (preg_match('#([^a-zA-Z0-9_\-\.])#', $newBaseName, $m)) {
            $this->error("Unsupported character `$m[1]` in file base: `$newBaseName`  Allowed characters: `a-z A-Z 0-9 _ -`");
        }

        $origExt = $this->u_path_parts()['fileExt'];

        return $this->u_with_file_name($newBaseName . '.' . $origExt);
    }

    function u_with_file_ext($newExt) {

        $this->ARGS('S', func_get_args());

        if (preg_match('#^\.#', $newExt)) {
            $this->error("Please remove leading dot from extension: `$newExt`");
        }

        if (preg_match('#([^a-z0-9.])#', $newExt, $m)) {
            $this->error("Unsupported character `$m[1]` in file extension: `$newExt`  Allowed characters: `a-z 0-9 .`");
        }

        $origBaseName = $this->u_path_parts()['fileBase'];

        // Don't add dot if ext is blank
        if ($newExt) { $newExt = '.' . $newExt; }

        return $this->u_with_file_name($origBaseName . $newExt);
    }
}


