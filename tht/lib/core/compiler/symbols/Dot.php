<?php

namespace o;

class S_Dot extends S_Infix {
    var $type = SymbolType::MEMBER;

    // Dot member.  foo.bar
    function asInner ($p, $objName) {
        $p->next();
        $this->space('N.x', true);
        $sMember = $p->symbol;
        if ($sMember->token[TOKEN_TYPE] !== TokenType::WORD) {
            $p->error('Expected a field name.  Ex: `user.name`');
        }
        $sMember->updateType(SymbolType::MEMBER_VAR);
        $name = $sMember->token[TOKEN_VALUE];
        if (!($name[0] >= 'a' && $name[0] <= 'z')) {
            $p->error("Member `$name` must be lowerCamelCase.");
        }
        $this->setKids([ $objName, $sMember ]);
        $p->next();

        return $this;
    }
}
