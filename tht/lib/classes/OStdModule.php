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

    function error($msg, $args=null, $contextMethod='') {
        ErrorHandler::setErrorDoc('/manual/module/' . strtolower($this->getClass()), $this->getClass());
        ErrorHandler::addOrigin('stdModule.' . strtolower($this->getClass()));
        Tht::error($msg);
    }

    function argumentError($msg, $method) {
        $methodToken = v($method)->u_to_token_case('-');
        $methodLabel = v($method)->u_to_camel_case();
        $label = $this->getClass() . '.' . $methodLabel;
        ErrorHandler::setErrorDoc('/manual/module/' . strtolower($this->getClass()) . '/' . $methodToken, $label);
        ErrorHandler::addOrigin('stdModule.' . strtolower($this->getClass()));
        Tht::error($msg);
    }

    function ARGS($sig, $args) {
        $err = ARGS($sig, $args);
        if ($err) {
            $this->argumentError($err['msg'], unu_($err['function']));
        }
    }

    function __set ($k, $v) {
        Tht::error("Can't set field `$k` on a standard module.");
    }

    function __get ($f) {
        $try = '`.' . unu_($f) . '()`';
        // TODO: check if method actually exists
        $this->error("Unknown field. Did you mean to call method $try?");
    }

    function __toString() {
        return '<<<' . Tht::cleanPackageName(get_called_class()) . '>>>';
    }

    function jsonSerialize() {
        return $this->__toString();
    }

    // TODO: some overlap with OClass
    function __call ($method, $args) {

        $c = $this->getClass();

        $suggestion = '';
        if (property_exists($this, 'suggestMethod')) {
            $umethod = strtolower(unu_($method));
            $suggestion = isset($this->suggestMethod[$umethod]) ? $c . '.' . $this->suggestMethod[$umethod] : '';
        }

        if (!$suggestion) {
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

        $method = unu_($method);

        $suggest = $suggestion ? " Try: `"  . $suggestion . "`" : '';

        ErrorHandler::setErrorDoc('/manual/module/' . strtolower($c), $c);
        $this->error("Unknown method `$method` for module `$c`. $suggest");
    }
}
