<?php

namespace o;

trait InputValidatorRuleParser {

    public function getRuleMapForString($paramName, $rulesetString) {

        if ($rulesetString == '') {
            return $this->defaultRuleMap;
        }

        $ruleMap = $this->parseRulesetString($rulesetString);
        $this->checkRuleMapNames($paramName, $ruleMap);
        $mergedRuleMap = $this->mergeRuleMap($ruleMap);

        return $mergedRuleMap;
    }

    function checkRuleMapNames($paramName, $ruleMap) {

        if (!isset($ruleMap['type'])) {
            $this->error("Missing validation type for input param: `$paramName`");
        }

        $typeRule = $ruleMap['type'];
        if (!isset($this->typeToRuleMap[$typeRule])) {
            $this->error("Unknown validation type `$typeRule` for input param: `$paramName`");
        }

        foreach ($ruleMap as $ruleName => $ruleValue) {

            if ($ruleName == 'type') { continue; }

            if (!array_key_exists($ruleName, $this->defaultRuleMap)) {
                $this->error("Unknown validation rule `$ruleName` for input param: `$paramName`");
            }

            if ($ruleName != 'type' && isset($this->typeToRuleMap[$ruleName])) {
                $this->error("Extra validation type `$ruleName` for input param: `$paramName`");
            }
        }
    }

    // Merge higher-level rules into the default rulemap (overriding the default)
    function mergeRuleMap($ruleMap) {

        // Merge the `type` rulemap into the default rulemap
        $mergedRuleMap = array_merge(
            $this->defaultRuleMap,
            $this->typeToRuleMap[$ruleMap['type']]
        );

        // Merge in individual rules
        foreach ($ruleMap as $ruleName => $ruleValue) {
            if ($ruleName != 'type') {
                $mergedRuleMap[$ruleName] = $ruleValue;
            }
        }

        return $mergedRuleMap;
    }

    // Convert 'foo|bar:123' ---> ['type' => 'foo', 'bar' => 123]
    function parseRulesetString($sRuleset) {

        if (OMap::isa($sRuleset)) {
            return $sRuleset;
        }

        $rules = explode('|', $sRuleset);

        $typeRule = array_shift($rules);
        $rulesetMap = [
            'type' => $typeRule
        ];

        foreach ($rules as $r) {
            $parts = explode(':', $r, 2);
            if (count($parts) == 1) {
                $rulesetMap[$parts[0]] = true;
            }
            else {
                $rulesetMap[$parts[0]] = $parts[1];
            }
        }

        return $rulesetMap;
    }

}