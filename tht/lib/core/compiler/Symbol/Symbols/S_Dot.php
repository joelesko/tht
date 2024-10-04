<?php

namespace o;

class S_Dot extends S_InfixSticky {

    var $type = SymbolType::MEMBER;

    function asLeft($p) {

        $p->next();
        $this->space(' .x');

        $sMember = $p->symbol;
        $sMember->updateType(SymbolType::MEMBER_VAR);

        $this->addKids([ $p->assignmentLeftSide, $sMember ]);
        $p->next();

        return $this;
    }

    // Dot member.  foo.bar
    function asInner($p, $objName) {

        $p->next();
        $this->space('N.x');
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

        $this->addKids([ $objName, $sMember ]);
        $p->next();

        return $this;
    }
}
