<?php declare(strict_types=1);

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
			TokenType::VECTOR => '[' . implode(' ', $this->operand) . ']',
			TokenType::HASHMAP => '{' . implode(' ', array_map(fn ($key_value) => "$key_value[0] $key_value[1]", $this->operand)) . '}',
			TokenType::NUMBER, TokenType::SYMBOL => (string) $this->operand,
			TokenType::KEYWORD => ':' . (string) $this->operand,
			TokenType::STRING => '"' . addcslashes($this->operand, "\\\n\"") . '"',
			TokenType::BOOL => $this->operand ? 'true' : 'false',
			TokenType::NIL => 'nil',
			TokenType::NOP => (string) $this->operand,
			TokenType::FUNCTION => '(function)',
		};
	}
}
