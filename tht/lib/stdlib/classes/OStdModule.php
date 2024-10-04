<?php

namespace o;

// TODO: factor out commonalities with OModule & OClass

class OStdModule extends OClass implements \JsonSerializable {

    function u_type() {
        return 'module';
    }

    function bareClassName() {
        return $this->getClass();
    }

    function getClass() {

        $c = str_replace('o\\u_', '', get_called_class());
        return $c;
    }

    function error($msg) {

        ErrorHandler::addOrigin('stdModule.' . strtolower($this->getClass()));

        Tht::error($msg);
    }

    function addErrorHelpLink($method = '') {
        ErrorHandler::setStdLibHelpLink('module', $this->getClass(), $method);
    }

    // TODO: duplicated with OClass
    function argumentError($msg, $method) {

        $methodToken = v($method)->u_to_token_case('-');
        $methodLabel = $method;

        $label = $this->getClass() . '.' . $methodLabel;

        ErrorHandler::setHelpLink('/manual/module/' . strtolower($this->getClass()) . '/' . $methodToken, $label);
        ErrorHandler::addOrigin('stdModule.' . strtolower($this->getClass()));

        Tht::error($msg);
    }

    function ARGS($sig, $args) {

        $err = validateFunctionArgs($sig, $args);

        if ($err) {
            $this->argumentError($err['msg'], unu_($err['function']));
        }
    }

    function __set($k, $v) {
        Tht::error("Can't set field on a standard module: `$k`");
    }

    function __get($f) {
        $f = unu_($f);
        Tht::error("Can't get field on a standard module: `$f`  Try: Call method `$f()`");
    }

    function toObjectString() {
        return OClass::getObjectString(
            $this->cleanPackageName(get_called_class()) . ' Module'
        );
    }

    // TODO: some overlap with OClass
    function __call($method, $args) {

        $method = unu_($method);

        $suggest = $this->getSuggestedMethod($method);
        $c = $this->getClass();

        if (!$suggest) {

            // Look if method is in other Std Modules
            $possibles = [];
            foreach (StdLibModules::$files as $lib) {
                if (method_exists(Tht::module($lib), u_($method))) {
                    $possibles []= '`' . $lib . '.' . $method . '()`';
                }
            }

            if (count($possibles)) {
                $suggest = 'Try: ' . implode(', ', $possibles);
            }
        }
        else {
            // Insert module name
            $suggest = preg_replace('/`(\w+)/', '`' . $c . '.$1', $suggest);
        }

        ErrorHandler::setHelpLink('/manual/module/' . strtolower($c), $c);
        $this->error("Unknown module method: `$c.$method()`  $suggest");
    }
}
