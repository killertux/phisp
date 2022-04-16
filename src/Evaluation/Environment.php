<?php

namespace Clemente\Phisp\Evaluation;

use Clemente\Phisp\Parser\Location;
use Clemente\Phisp\Parser\Token;
use Clemente\Phisp\Parser\TokenType;

class Environment {

	public function __construct(
		private array $environment,
		public ?Environment $parent
	) {}

	public static function createDefault(): self {
		return new self(
			Core::getCore(),
			null
		);
	}

	public function find(string $symbol): ?Token {
		return $this->environment[$symbol] ?? $this->parent?->find($symbol);
	}

	public function set(Token $symbol_token, Token $data): void {
		assert($symbol_token->token_type === TokenType::SYMBOL, 'Should always be a symbol');
		$this->environment[$symbol_token->operand] = $data;
	}

	public function get(Token $token): Token {
		assert($token->token_type === TokenType::SYMBOL, 'Should always be a symbol');
		$data = $this->find($token->operand);
		return $data !== null ? new Token($data->token_type, $token->location, $data->operand) : throw new RuntimeException("Symbol $token->operand not found", $token->location);
	}

}
