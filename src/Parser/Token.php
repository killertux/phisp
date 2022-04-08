<?php

namespace Clemente\Phisp\Parser;

class Token
{
    public function __construct(
        public TokenType $token_type,
        public Location $location,
        public $operand,
    ) {
    }

    public function __toString(): string {
        return match ($this->token_type) {
            TokenType::LIST => '(' . implode(' ', $this->operand) . ')',
            TokenType::NUMBER => (string) $this->operand,
        };
    }
}
