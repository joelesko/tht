<?php

namespace o;

class SymbolTable {

    private $parser = null;
    private $symbols = null;
    private $kids = null;
    private $i = 0;

    function __construct ($size, $parser) {
        $this->parser = $parser;

        // Extra padding for sequences. +10-15% is realistic
        $paddedSize = floor($size * 1.5);
        $this->symbols = new \SplFixedArray ($paddedSize);
        $this->kids = new \SplFixedArray ($paddedSize);
    }

    function add ($s) {
        $s->symbolId = $this->i;
        $this->update($s);
        $this->i += 1;
    }

    function update ($s) {
        $this->symbols[$s->symbolId] = $this->compress($s);
    }

    function get ($i) {
        return $this->decompress($this->symbols[$i]);
    }

    function setKids ($parentId, $kids) {
        foreach ($kids as $kid) {
            $this->addKid($parentId, $kid);
        }
    }

    function addKid ($parentId, $kid) {
        if (!$kid) {
            $p = $this->get($parentId);
            $token = [];
            $token[TOKEN_POS] = $p['pos'][0] . ',' . $p['pos'][1];
            $this->parser->error("Incomplete expression.", $token);
        }
        $kidId = $kid->symbolId;
        if (isset($this->kids[$parentId])) {
            $this->kids[$parentId] .= ',' . $kidId;
        } else {
            $this->kids[$parentId] = $kidId;
        }
    }

    function getFirst () {
        return $this->get(0);
    }

    function getKids ($parentId) {
        $kids = [];
        if (isset($this->kids[$parentId])) {
            $kidIds = explode(',', $this->kids[$parentId]);
            foreach ($kidIds as $kidId) {
                $kids []= $this->get($kidId);
            }
        }
        return $kids;
    }

    function compress ($sym) {
        $c = implode(TOKEN_SEP, [
            $sym->symbolId,
            $sym->token[TOKEN_POS],
            $sym->token[TOKEN_TYPE],
            $sym->type,
            $sym->token[TOKEN_VALUE]
        ]);
        return $c;
    }

    function decompress ($symbol) {
        $s = explode(TOKEN_SEP, $symbol, 5);
        // Don't need tokenType $s[2]
        return [
            'id' => $s[0],
            'pos' => explode(',', $s[1]),
            'type' => $s[3],
            'value' => $s[4]
        ];
    }
}
