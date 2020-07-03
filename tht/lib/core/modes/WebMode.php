<?php

namespace o;

class WebMode {

    static private $SETTINGS_KEY_ROUTE = 'routes';
    static private $ROUTE_HOME = 'home';

    static private $requestHeaders = [];
    static private $routeParams = [];
    static private $sideloadPage = '';

    static public function main($sideloadPage = false) {

        self::$sideloadPage = $sideloadPage;

        if (self::serveStaticFile()) {
            return false;
        }

        Security::initResponseHeaders();

        if (Tht::getConfig('downtime')) {
            self::downtimePage(Tht::getConfig('downtime'));
        }

        $controllerFile = self::initRoute();
        if ($controllerFile) {
            self::executeController($controllerFile);
        }

        self::onEnd();

        return true;
    }

    static public function onEnd() {
        PrintBuffer::flush();
        HitCounter::add();
        self::printPerf();
    }

    // Serve directly if requested a static file in testServer mode
    static private function serveStaticFile() {

        if (Tht::isMode('testServer')) {

            // Dotted filename
            if (preg_match('/\.[a-z0-9]{2,}$/', $_SERVER['SCRIPT_NAME'])) {
                return true;
            }

            // Need to construct path manually.
            // See: https://github.com/joelesko/tht/issues/2
            $path = $_SERVER["DOCUMENT_ROOT"] . $_SERVER['SCRIPT_NAME'];
            if ($_SERVER['SCRIPT_NAME'] !== '/' && file_exists($path)) {
                // if (is_dir($path)) {
                //     // just a warning
                //     Tht::startupError("Path `$path` can not be a page and also a directory under Document Root.");
                // }
                // is a static file
                if (!is_dir($path)) {
                    return true;
                }
            }
        }
        return false;
    }

    static private function printPerf () {
        if (!Tht::module('Request')->u_is_ajax() && Tht::module('Output')->sentResponseType == 'html') {
            Tht::module('Perf')->printResults();
        }
    }

    static private function downtimePage($file) {
        http_response_code(503);
        $downPage = Tht::module('File')->u_document_path($file);
        if ($file !== true && file_exists($downPage)) {
            print(file_get_contents($downPage));
        }
        else {
            $font = Tht::module('Css')->u_font('sansSerif');
            echo "<div style='padding: 2rem; text-align: center; font-family: $font'><h1>Temporarily Down for Maintenance</h1><p>Sorry for the inconvenience.  We'll be back soon.</p></div>";
        }
        Tht::exitScript(0);
    }

    static private function initRoute () {

        Tht::module('Perf')->u_start('tht.route');

        $path = self::getScriptPath();
        $controllerFile = self::getControllerForPath($path);

        Tht::module('Perf')->u_stop();

        return $controllerFile;
    }

    static public function runRoute($path) {

        $controllerFile = self::getControllerForPath($path);
        if ($controllerFile) {
            self::executeController($controllerFile);
        } else {
            Tht::error("No route found for path: `$path`");
        }
    }

    static public function runStaticRoute($route) {

        $routes = Tht::getTopConfig('routes');
        if (!isset($routes[$route])) { return false; }

        $file = Tht::path('pages', $routes[$route]);
        Tht::executeController($file);

        Tht::exitScript(0);
    }

    static private function getScriptPath() {
        if (self::$sideloadPage) {
            return self::$sideloadPage;
        }
        $path = Tht::module('Request')->u_url()->u_path();
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
            if ($c === false) {
                $c = self::getStaticController($path);
            }
            return $c;
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

    static private function getStaticController($path) {

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
                Tht::module('Output')->u_send_error(404);
            }
        }

        return $thtPath;
    }

    static private function executeController ($controllerFile) {

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

        Tht::module('Perf')->u_start('tht.php.callAutoFunction', Tht::stripAppRoot($controllerFile));

        $req = Tht::module('Request');

        $callFunction = '';
        if ($userFunction) {
            // function defined in app.jcon/routes
            // e.g. /foo: foo.tht@someFunction
            $fullUserFunction = $nameSpace . '\\u_' . v($userFunction)->u_to_token_case('_');
            if (!function_exists($fullUserFunction)) {
                $fullController = basename($controllerFile);
                Tht::configError("Function `$userFunction` not found for route target `$fullController`");
            }
            $callFunction = $fullUserFunction;
        }
        else if (isset(self::$routeParams['mode'])) {
            // e.g. modeFoo()
            $modeName = self::$routeParams['mode'];
            $modeName = v($modeName)->u_to_token_case('_');
            $fullAutoFunction = $nameSpace . '\\u_mode_' . $modeName;
            if (!function_exists($fullAutoFunction)) {
                Tht::module('Output')->u_send_error(404);
            }
            $callFunction = $fullAutoFunction;
        }
        else if ($req->u_method() === 'post') {
            // post()
            $fullPostFunction = $nameSpace . '\\u_post';

            $modeParam = Tht::getPhpGlobal('post', 'mode', '');
            if ($modeParam) {
                $modeName = v($modeParam)->u_to_token_case('_');
                $fullAutoFunction = $nameSpace . '\\u_mode_' . $modeName;
                if (!function_exists($fullAutoFunction)) {
                    Tht::module('Output')->u_send_error(404);
                }
                $callFunction = $fullAutoFunction;
            }
            else if (function_exists($fullPostFunction)) {
                $callFunction = $fullPostFunction;
            }
        }

        // Fall back to main()
        if (!$callFunction) {
            $fullMainFunction = $nameSpace . '\\u_main';
            if (function_exists($fullMainFunction)) {
                $callFunction = $fullMainFunction;
            }
        }

        if ($callFunction) {
            try {
                ErrorHandler::setTopLevelFunction($controllerFile, $callFunction);

                $ret = call_user_func($callFunction);

                if (UrlTypeString::isa($ret)) {
                    Tht::module('Output')->u_redirect($ret);
                }
                else if (OTypeString::isa($ret)) {
                    Tht::module('Output')->sendByType($ret);
                }
                else if (OMap::isa($ret)) {
                    Tht::module('Output')->u_send_json($ret);
                }

            } catch (ThtError $e) {
                ErrorHandler::handleThtRuntimeError($e, Tht::getPhpPathForTht($controllerFile));
            }
        }

        Tht::module('Perf')->u_stop();
    }

    static public function getWebRouteParam ($key) {
        if (!isset(self::$routeParams[$key])) {
            if (Security::isAdmin()) {
                Tht::error("Route param '$key' does not exist.");
            } else {
                Tht::module('Output')->u_send_error(404);
            }
        }
        return self::$routeParams[$key];
    }
}
