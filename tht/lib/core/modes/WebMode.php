<?php

namespace o;

class WebMode {

    static private $SETTINGS_KEY_ROUTE = 'routes';
    static private $ROUTE_HOME = 'home';

    static private $DISALLOWED_PATH_CHARS_REGEX = '/[^a-z0-9\-\/\.]/';

    static private $requestHeaders = [];
    static private $routeParams = [];
    static private $printBuffer = [];



    // WEB
    //---------------------------------------------

    static public function main() {

        Security::initResponseHeaders();

        if (Tht::getConfig('downtime')) {
            WebMode::downtimePage(Tht::getConfig('downtime'));
        }
        $controllerFile = WebMode::initRoute();
        if ($controllerFile) {
            WebMode::executeWebController($controllerFile);
        }

        WebMode::flushWebPrintBuffer();
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
        exit(0);
    }

    static private function initRoute () {

        Tht::module('Perf')->u_start('tht.route');

        $path = WebMode::getScriptPath();

        $controllerFile = WebMode::getControllerForPath($path);

        Tht::module('Perf')->u_stop();

        return $controllerFile;
    }

    static public function runStaticRoute($route) {
        $routes = Tht::getTopConfig('routes');
        if (!isset($routes[$route])) { return false; }
        $file = Tht::path('pages', $routes[$route]);
        Tht::executeWebController($file);
        exit(0);
    }

    static private function getScriptPath() {

        $path = Tht::module('Web')->u_request()['url']['path'];

        // Validate route name
        // all lowercase, no special characters, hyphen separators, no trailing slash
        $pathSize = strlen($path);

        $isTrailingSlash = $pathSize > 1 && $path[$pathSize-1] === '/';
        if (preg_match(self::$DISALLOWED_PATH_CHARS_REGEX, $path) || $isTrailingSlash)  {
            Tht::errorLog("Path `$path` is not valid");
            Tht::module('Web')->u_send_error(404);
        }

        return $path;
    }

    static private function getControllerForPath($path) {

        $routes = Tht::getTopConfig(WebMode::$SETTINGS_KEY_ROUTE);

        if (defined('BASE_URL') ) {
            $path = preg_replace('#' . BASE_URL . '#', '', $path);
            if ($path == '') { $path = '/'; }
        }

        if (isset($routes[$path])) {
            // static path
            return Tht::path('pages', $routes[$path]);
        }
        else {
            $c = WebMode::getDynamicController($routes, $path);
            return $c === false ? WebMode::getPublicController($path) : $c;
        }
    }

    static private function getDynamicController($routes, $path) {

        // path with dynamic parts
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
                        $val = preg_replace(self::$DISALLOWED_PATH_CHARS_REGEX, '', $pathParts[$i]); 
                        $params[$token] = $val;
                    } 
                    else {
                        if ($mPart !== $pathParts[$i]) {
                            $isMatch = false;
                            break;
                        }
                    }
                }

                if ($isMatch) {
                    WebMode::$routeParams = $params;
                    return Tht::path('pages', $controllerPath);
                }
            }
        }

        $camelPath = strtolower(v($path)->u_to_camel_case());
        if (isset($routeTargets[$camelPath]) || $camelPath == '/' . WebMode::$ROUTE_HOME) {
            Tht::errorLog("Direct access to route not allowed: `$path`");
            Tht::module('Web')->u_send_error(404);
        }

        return false;
    }

    static private function getPublicController($path) {

        $apath = '';
        if ($path === '/') {
            $apath = WebMode::$ROUTE_HOME;
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
            Tht::errorLog("Entry file not found for path: `$path`");
            Tht::module('Web')->u_send_error(404);
        }

        return $thtPath; 
    }

    static private function executeWebController ($controllerName) {

        Tht::module('Perf')->u_start('tht.executeMain', $controllerName);

        $dotExt = '.' . Tht::getExt();
        if (strpos($controllerName, $dotExt) === false) {
            Tht::configError("Route file `$controllerName` requires `$dotExt` extension in `" . Tht::$FILE['configFile'] ."`.");
        }

        $userFunction = '';
        $controllerFile = $controllerName;
        if (strpos($controllerName, '@') !== false) {
            list($controllerFile, $userFunction) = explode('@', $controllerName, 2);
        }

        Source::process($controllerFile, true);
        
        WebMode::callAutoFunction($controllerFile, $userFunction);

        Tht::module('Perf')->u_stop();
    }

    static private function callAutoFunction($controllerFile, $userFunction) {

        $nameSpace = ModuleManager::getNamespace(Tht::getFullPath($controllerFile));

        $fullController = $nameSpace . '\\u_' . basename($controllerFile);
        $fullUserFunction = $nameSpace . '\\u_' . $userFunction;

        $mainFunction = 'main';
        $web = Tht::module('Web');
        $req = uv($web->u_request());

        if ($req['isAjax']) {
            $mainFunction = 'ajax';
        } else if ($req['method'] === 'POST') {
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
                if (OLockString::isa($ret)) {
                    Tht::module('Web')->sendByType($ret);
                }

            } catch (ThtException $e) {
                ErrorHandler::handleThtException($e, Tht::getPhpPathForTht($controllerFile));
            }
        }
    }

    static function getWebRequestHeader ($key) {
        return Tht::data('requestHeaders', $key);
    }

    static function getWebRequestHeaders () {
        return Tht::data('requestHeaders', '*');
    }

    static function getWebRouteParam ($key) {
        if (!isset(WebMode::$routeParams[$key])) {
            throw new ThtException ("Route param '$key' does not exist.");
        }
        return WebMode::$routeParams[$key];
    }

    static public function queuePrint($s) {
        WebMode::$printBuffer []= $s;
    }

    // Send the output of all print() statements
    static private function flushWebPrintBuffer() {
        if (!count(WebMode::$printBuffer)) { return; }

        $zIndex = 99998;  //  one less than error page

        echo "<style>\n";
        echo ".tht-print { white-space: pre; border: 0; border-left: solid 16px #787fff; padding: 4px 32px; margin: 4px 0 0;  font-family: " . u_Css::u_monospace_font() ."; }\n";
        echo ".tht-print-panel { position: fixed; top: 0; left: 0; z-index: $zIndex; width: 100%; padding: 24px 32px 24px; font-size: 18px; background-color: rgba(255,255,255,0.98);  -webkit-font-smoothing: antialiased; color: #222; box-shadow: 0 4px 4px rgba(0,0,0,0.15); max-height: 400px; overflow: auto;  }\n";
        echo "</style>\n";

        echo "<div class='tht-print-panel'>\n";
        foreach (WebMode::$printBuffer as $b) {
            echo "<div class='tht-print'>" . $b . "</div>\n";
        }
        echo "</div>";
    }

}