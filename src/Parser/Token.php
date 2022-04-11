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
			TokenType::NUMBER, TokenType::SYMBOL => (string) $this->operand,
			TokenType::STRING => '"' . $this->operand . '"',
			TokenType::BOOL => $this->operand ? 'true' : 'false',
			TokenType::NIL => 'nil',
		};
	}
}
