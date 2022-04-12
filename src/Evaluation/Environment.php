<?php

namespace Clemente\Phisp\Evaluation;

use Clemente\Phisp\Parser\Token;
use Clemente\Phisp\Parser\TokenType;

class Environment {

	private function __construct(
		private array $environment
	) {}

	public static function createDefault(): self {
		return new self(
			[
				'+' => function(Token $symbol_token, Token ...$numbers): Token {
					assertAtLestNumberParams($symbol_token, 2, ...$numbers);
					assertAllSameType(TokenType::NUMBER, ...$numbers);
					return new Token(TokenType::NUMBER, $symbol_token->location, array_sum(array_column($numbers, 'operand')));
				},
				'*' => function(Token $symbol_token, Token ...$numbers): Token {
					assertAtLestNumberParams($symbol_token, 2, ...$numbers);
					assertAllSameType(TokenType::NUMBER, ...$numbers);
					$result = 1;
					foreach (array_column($numbers, 'operand') as $number) {
						$result *= $number;
					}
					return new Token(TokenType::NUMBER, $symbol_token->location, $result);
				},
				'-' => function(Token $symbol_token, Token ...$numbers): Token {
					assertAtLestNumberParams($symbol_token, 2, ...$numbers);
					assertAllSameType(TokenType::NUMBER, ...$numbers);
					$result = array_shift($numbers)->operand;
					foreach (array_column($numbers, 'operand') as $number) {
						$result -= $number;
					}
					return new Token(TokenType::NUMBER, $symbol_token->location, $result);
				},
				'/' => function(Token $symbol_token, Token ...$numbers): Token {
					assertAtLestNumberParams($symbol_token, 2, ...$numbers);
					assertAllSameType(TokenType::NUMBER, ...$numbers);
					$result = array_shift($numbers)->operand;
					foreach (array_column($numbers, 'operand') as $number) {
						$result /= $number;
					}
					return new Token(TokenType::NUMBER, $symbol_token->location, $result);
				},
			],
		);
	}

	public function getCallable(Token $token): Token {
		assert($token->token_type === TokenType::SYMBOL, 'Should always be a symbol');
		return array_key_exists($token->operand, $this->environment)?
			new Token(TokenType::FUNCTION, $token->location, $this->environment[$token->operand]) :
			throw new RuntimeException("Symbol $token->operand not found", $token->location);
	}

}

function assertAllSameType(TokenType $token_type, Token ...$tokens): void {
	foreach ($tokens as $token) {
		if ($token->token_type !== $token_type) {
			throw new RuntimeException("Expected $token_type->value. Got $token->token_type. At $token->location", $token->location);
		}
	}
}

function assertAtLestNumberParams(Token $symbol_token, int $n_params, Token ...$params): void {
	$count = count($params);
	if ($count < $n_params) {
		throw new RuntimeException("Expected at least $n_params params. $count found. At $symbol_token->location", $symbol_token->location);
	}
}
