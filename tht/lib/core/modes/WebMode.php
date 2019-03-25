<?php

namespace o;

class WebMode {

    static private $SETTINGS_KEY_ROUTE = 'routes';
    static private $ROUTE_HOME = 'home';

    static private $requestHeaders = [];
    static private $routeParams = [];

    static public function main() {

        Security::initResponseHeaders();

        if (Tht::getConfig('downtime')) {
            self::downtimePage(Tht::getConfig('downtime'));
        }

        $controllerFile = self::initRoute();
        if ($controllerFile) {
            self::executeWebController($controllerFile);
        }

        PrintBuffer::flush();
        HitCounter::add();
    }

    static private function downtimePage($file) {
        http_response_code(503);
        $downPage = Tht::module('File')->u_document_path($file);
        if ($file !== true && file_exists($downPage)) {
            print(file_get_contents($downPage));
        }
        else {
            $font = Tht::module('Css')->u_sans_serif_font();
            echo "<div style='padding: 2rem; text-align: center; font-family: $font'><h1>Temporarily Down for Maintenance</h1><p>Sorry for the inconvenience.  We'll be back soon.</p></div>";
        }
        Tht::exitScript(0);
    }

    static private function initRoute () {

        $path = self::getScriptPath();
        if ($path == '/counter' && Security::isAdmin()) {
            HitCounter::counterPanel();
            return;
        }

        Tht::module('Perf')->u_start('tht.route');
        $controllerFile = self::getControllerForPath($path);
        Tht::module('Perf')->u_stop();
        return $controllerFile;
    }

    static public function runStaticRoute($route) {

        $routes = Tht::getTopConfig('routes');
        if (!isset($routes[$route])) { return false; }

        $file = Tht::path('pages', $routes[$route]);
        Tht::executeWebController($file);

        Tht::exitScript(0);
    }

    static private function getScriptPath() {
        $path = Tht::module('Request')->u_url()['path'];
        Security::validateRoutePath($path);
        return $path;
    }

    static private function getControllerForPath($path) {

        $routes = Tht::getTopConfig(self::$SETTINGS_KEY_ROUTE);

        if (defined('BASE_URL') ) {
            $path = preg_replace('#' . BASE_URL . '#', '', $path);
            if ($path == '') { $path = '/'; }
        }

        if (isset($routes[$path])) {
            // static path
            return Tht::path('pages', $routes[$path]);
        }
        else {
            $c = self::getDynamicController($routes, $path);
            return $c === false ? self::getPublicController($path) : $c;
        }
    }

    // path with dynamic parts e.g. '/blog/{articleId}'
    static private function getDynamicController($routes, $path) {

        $pathParts = explode('/', ltrim($path, '/'));
        $numPathParts = count($pathParts);

        $routeTargets = [];

        foreach (uv($routes) as $match => $controllerPath) {
            if (strpos($match, '{') === false) {
                continue;
            }
            $params = [];
            $matchParts = explode('/', ltrim($match, '/'));
            $numMatchParts = count($matchParts);

            $routeTargets[strtolower('/' . $controllerPath)] = true;

            if ($numMatchParts === $numPathParts) {
                $isMatch = true;
                foreach (range(0, $numMatchParts - 1) as $i) {
                    $mPart = $matchParts[$i];

                    if ($mPart[0] === '{' && $mPart[strlen($mPart)-1] === '}') {
                        // route placeholder
                        $token = substr($mPart, 1, strlen($mPart)-2);
                        if (preg_match('/[^a-zA-Z0-9]/', $token)) {
                            Tht::configError("Route placeholder `{$token}` should only"
                                . " contain letters and numbers (no spaces).");
                        }
                        $params[$token] = $pathParts[$i];
                    }
                    else {
                        if ($mPart !== $pathParts[$i]) {
                            $isMatch = false;
                            break;
                        }
                    }
                }

                if ($isMatch) {
                    self::$routeParams = $params;
                    return Tht::path('pages', $controllerPath);
                }
            }
        }

        return false;
    }

    static private function getPublicController($path) {

        $apath = '';
        if ($path === '/') {
            $apath = self::$ROUTE_HOME;
        }
        else {
            // convert dash-case URL to camelCase file path
            $parts = explode('/', $path);
            $camelParts = [];
            foreach ($parts as $p) {
                $camelParts []= v($p)->u_to_camel_case();
            }
            $camelParts []= array_pop($camelParts);
            $apath = implode('/', $camelParts);
        }

        $thtPath = Tht::path('pages', Tht::getThtFileName($apath));

        if (!file_exists($thtPath)) {
            $thtPath = Tht::path('pages', Tht::getThtFileName('default'));
            if (!file_exists($thtPath)) {
                Tht::errorLog("Entry file not found for path: `$path`");
                Tht::module('Web')->u_send_error(404);
            }
        }

        return $thtPath;
    }

    static private function executeWebController ($controllerFile) {

        Tht::module('Perf')->u_start('tht.executeRoute', Tht::stripAppRoot($controllerFile));

        $userFunction = '';
        if (strpos($controllerFile, '@') !== false) {
            list($controllerFile, $userFunction) = explode('@', $controllerFile, 2);
        }

        Compiler::process($controllerFile, true);

        self::callAutoFunction($controllerFile, $userFunction);

        Tht::module('Perf')->u_stop();
    }

    static private function callAutoFunction($controllerFile, $userFunction) {

        $nameSpace = ModuleManager::getNamespace(Tht::getFullPath($controllerFile));

        $fullController = $nameSpace . '\\u_' . basename($controllerFile);
        $fullUserFunction = $nameSpace . '\\u_' . $userFunction;

        $mainFunction = 'main';
        $req = Tht::module('Request');

        if ($req->u_is_ajax()) {
            $mainFunction = 'ajax';
        } else if ($req->u_method() === 'POST') {
            $mainFunction = 'post';
        }
        $fullMainFunction = $nameSpace . '\\u_' . $mainFunction;


        $callFunction = '';
        if ($userFunction) {
            if (!function_exists($fullUserFunction)) {
                Tht::configError("Function `$userFunction` not found for route target `$fullController`");
            }
            $callFunction = $fullUserFunction;
        }
        else if (function_exists($fullMainFunction)) {
            $callFunction = $fullMainFunction;
        }

        if ($callFunction) {
            try {
                $ret = call_user_func($callFunction);
                if (OTagString::isa($ret)) {
                    Tht::module('Web')->sendByType($ret);
                }

            } catch (ThtException $e) {
                ErrorHandler::handleThtException($e, Tht::getPhpPathForTht($controllerFile));
            }
        }
    }

    static public function getWebRouteParam ($key) {
        if (!isset(self::$routeParams[$key])) {
            throw new ThtException ("Route param '$key' does not exist.");
        }
        return self::$routeParams[$key];
    }
}
