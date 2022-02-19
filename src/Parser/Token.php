<?php

namespace Clemente\Phisp\Parser;

class Token
{
    public function __construct(
        public TokenType $token_type,
        public Location $location
    ) {
    }
}
