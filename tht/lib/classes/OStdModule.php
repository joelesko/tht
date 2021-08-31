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

        $c = preg_replace('/o\\\\u_/', '', get_called_class());
        return $c == 'SystemX' ? 'System' : $c;
    }

    function error($msg, $contextMethod='') {

        ErrorHandler::addOrigin('stdModule.' . strtolower($this->getClass()));
        $this->addErrorHelpLink($contextMethod);

        Tht::error($msg);
    }

    function addErrorHelpLink($method = '') {
        ErrorHandler::setStdLibHelpLink('module', $this->getClass(), $method);
    }

    // TODO: duplicated with OClass
    function argumentError($msg, $method) {

        $methodToken = v($method)->u_slug();
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

    function __set ($k, $v) {
        Tht::error("Can not set field `$k` on a standard module.");
    }

    function __get ($f) {

        $try = '`' . unu_($f) . '()`';
        // TODO: check if method actually exists
        $this->error("Unknown field. Did you mean to call method $try?");
    }

    function toStringToken() {
        return OClass::getStringToken(
            $this->cleanPackageName(get_called_class()) . ' Module'
        );
    }


    // TODO: some overlap with OClass
    function __call ($method, $args) {

        $method = unu_($method);

        $c = $this->getClass();

        $suggestion = $this->getSuggestedMethod($method);

        if (!$suggestion) {
            // Look if method is in other Modules
            $possibles = [];
            foreach (LibModules::$files as $lib) {
                if (method_exists(Tht::module($lib), $method)) {
                    $possibles []= $lib . '.' . $method;
                }
            }
            if (count($possibles)) {
                $suggestion = implode(', ', $possibles);
            }
        }

        $suggest = $suggestion ? " Try: " . $suggestion : '';

        ErrorHandler::setHelpLink('/manual/module/' . strtolower($c), $c);
        $this->error("Unknown method `$method` for module `$c`. $suggest");
    }
}
