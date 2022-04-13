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
			[
				'+' => 	new Token(
					TokenType::FUNCTION,
					new Location(0, 0), //TODO: Better handle this cases
					function(Token $symbol_token, Token ...$numbers): Token {
						assertAtLestNumberParams($symbol_token, 2, ...$numbers);
						assertAllSameType(TokenType::NUMBER, ...$numbers);
						return new Token(TokenType::NUMBER, $symbol_token->location, array_sum(array_column($numbers, 'operand')));
					}
				),
				'*' => new Token(
					TokenType::FUNCTION,
					new Location(0, 0), //TODO: Better handle this cases,
					function(Token $symbol_token, Token ...$numbers): Token {
						assertAtLestNumberParams($symbol_token, 2, ...$numbers);
						assertAllSameType(TokenType::NUMBER, ...$numbers);
						$result = 1;
						foreach (array_column($numbers, 'operand') as $number) {
							$result *= $number;
						}
						return new Token(TokenType::NUMBER, $symbol_token->location, $result);
					}
				),
				'-' => new Token(
					TokenType::FUNCTION,
					new Location(0, 0), //TODO: Better handle this cases,
					function(Token $symbol_token, Token ...$numbers): Token {
						assertAtLestNumberParams($symbol_token, 2, ...$numbers);
						assertAllSameType(TokenType::NUMBER, ...$numbers);
						$result = array_shift($numbers)->operand;
						foreach (array_column($numbers, 'operand') as $number) {
							$result -= $number;
						}
						return new Token(TokenType::NUMBER, $symbol_token->location, $result);
					}
				),
				'/' => new Token(
					TokenType::FUNCTION,
					new Location(0, 0), //TODO: Better handle this cases,
					function(Token $symbol_token, Token ...$numbers): Token {
						assertAtLestNumberParams($symbol_token, 2, ...$numbers);
						assertAllSameType(TokenType::NUMBER, ...$numbers);
						$result = array_shift($numbers)->operand;
						foreach (array_column($numbers, 'operand') as $number) {
							$result /= $number;
						}
						return new Token(TokenType::NUMBER, $symbol_token->location, $result);
					}
				),
			],
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
