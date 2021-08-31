<?php

namespace o;

class SymbolTable {

    private $parser = null;
    private $symbols = null;
    private $kids = null;
    private $i = 0;
    private $size = 0;

    function __construct ($size, $parser) {

        $this->parser = $parser;

        // Extra padding for astLists. +10-15% is realistic.
        // Add 10 to account for really small files.
        $this->size = floor($size * 1.5) + 10;

        $this->symbols = new \SplFixedArray ($this->size);
        $this->kids = new \SplFixedArray ($this->size);
    }

    function add ($s) {
        $s->symbolId = $this->i;
        $this->update($s);
        $this->i += 1;
    }

    function update ($s) {
        if ($s->symbolId >= $this->size) {
            // This SHOULD never happen, but just in case...
            Tht::error('(Core) SymbolTable is not large enough to add new symbol.');
        }
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
        }
        else {
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
            $sym->token[TOKEN_SPACE],
            $sym->token[TOKEN_TYPE],
            $sym->type,
            $sym->token[TOKEN_VALUE]
        ]);

        return $c;
    }

    function decompress ($symbol) {

        $s = explode(TOKEN_SEP, $symbol, 6);

        return [
            'id' => $s[0],
            'pos' => explode(',', $s[1]),
            'space' => $s[2],
            // Skip [3]. Don't need token type.
            'type' => $s[4],
            'value' => $s[5]
        ];
    }
}
