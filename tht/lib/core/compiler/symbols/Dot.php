<?php

namespace o;

class S_Dot extends S_Infix {

    var $type = SymbolType::MEMBER;

    function asLeft($p) {

        $p->next();
        $this->space(' .x', true);

        $sMember = $p->symbol;
        $sMember->updateType(SymbolType::MEMBER_VAR);

        $this->setKids([ $p->assignmentLeftSide, $sMember ]);
        $p->next();

        return $this;
    }

    // Dot member.  foo.bar
    function asInner ($p, $objName) {

        $p->next();
        $this->space('N.x', true);
        $sMember = $p->symbol;

        if ($sMember->token[TOKEN_TYPE] !== TokenType::WORD) {

            ErrorHandler::addSubOrigin('dot');

            $suggest = '';
            if ($sMember->token[TOKEN_TYPE] == TokenType::VAR) {
                $suggest = 'Try: Square brackets. Ex: `$var[$key]`';
            }

            $p->error("Expected a field name.  Ex: `\$user.name` $suggest");
        }

        $sMember->updateType(SymbolType::MEMBER_VAR);

        $this->setKids([ $objName, $sMember ]);
        $p->next();

        return $this;
    }
}
