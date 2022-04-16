<?php

namespace Clemente\Phisp\Evaluation;

use Clemente\Phisp\Parser\Location;
use Clemente\Phisp\Parser\Token;
use Clemente\Phisp\Parser\TokenType;

class Core {
	public static function getCore(): array {
		return[
			'+' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$numbers): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$numbers);
				assertAllSameType(TokenType::NUMBER, ...$numbers);
				return new Token(TokenType::NUMBER, $symbol_token->location, array_sum(array_column($numbers, 'operand')));
			})
			,
			'*' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$numbers): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$numbers);
				assertAllSameType(TokenType::NUMBER, ...$numbers);
				$result = 1;
				foreach (array_column($numbers, 'operand') as $number) {
					$result *= $number;
				}
				return new Token(TokenType::NUMBER, $symbol_token->location, $result);
			}),
			'-' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$numbers): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$numbers);
				assertAllSameType(TokenType::NUMBER, ...$numbers);
				$result = array_shift($numbers)->operand;
				foreach (array_column($numbers, 'operand') as $number) {
					$result -= $number;
				}
				return new Token(TokenType::NUMBER, $symbol_token->location, $result);
			}),
			'/' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$numbers): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$numbers);
				assertAllSameType(TokenType::NUMBER, ...$numbers);
				$result = array_shift($numbers)->operand;
				foreach (array_column($numbers, 'operand') as $number) {
					$result /= $number;
				}
				return new Token(TokenType::NUMBER, $symbol_token->location, $result);
			}),
			'list' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				return new Token(
					TokenType::LIST,
					$symbol_token->location,
					$params
				);
			}),
			'list?' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				assertAtLeastNumberParams($symbol_token, 1, ...$params);
				return new Token(
					TokenType::BOOL,
					$symbol_token->location,
					array_shift($params)->token_type === TokenType::LIST
				);
			}),
			'empty?' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				assertAtLeastNumberParams($symbol_token, 1, ...$params);
				return new Token(
					TokenType::BOOL,
					$symbol_token->location,
					empty(array_shift($params)->operand)
				);
			}),
			'count' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				assertAtLeastNumberParams($symbol_token, 1, ...$params);
				$first = array_shift($params);
				$count = match($first->token_type) {
					TokenType::VECTOR, TokenType::LIST => count($first->operand),
					default => 0,
				};
				return new Token(
					TokenType::NUMBER,
					$symbol_token->location,
					$count
				);
			}),
			'if' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$params);
				$condition = array_shift($params);
				$condition = match ($condition->token_type) {
					TokenType::NIL => false,
					TokenType::BOOL => $condition->operand,
					default => true,
				};
				$true = array_shift($params);
				$false = $params ? array_shift($params) : new Token(TokenType::NIL, $symbol_token->location, null);
				return $condition ? $true : $false;
			}),
			'=' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$params);
				$first = array_shift($params);
				$second = array_shift($params);
				return new Token(
					TokenType::BOOL,
					$symbol_token->location,
					compareTokens($first, $second),
				);
			}),
			'>' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$params);
				assertAllSameType(TokenType::NUMBER, ...$params);
				$first = array_shift($params);
				$second = array_shift($params);
				return new Token(
					TokenType::BOOL,
					$symbol_token->location,
					$first->operand > $second->operand
				);
			}),
			'>=' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$params);
				assertAllSameType(TokenType::NUMBER, ...$params);
				$first = array_shift($params);
				$second = array_shift($params);
				return new Token(
					TokenType::BOOL,
					$symbol_token->location,
					$first->operand >= $second->operand
				);
			}),
			'<' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$params);
				assertAllSameType(TokenType::NUMBER, ...$params);
				$first = array_shift($params);
				$second = array_shift($params);
				return new Token(
					TokenType::BOOL,
					$symbol_token->location,
					$first->operand < $second->operand
				);
			}),
			'<=' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$params);
				assertAllSameType(TokenType::NUMBER, ...$params);
				$first = array_shift($params);
				$second = array_shift($params);
				return new Token(
					TokenType::BOOL,
					$symbol_token->location,
					$first->operand <= $second->operand
				);
			}),
			'fn*' => self::createFunction(function (Environment $_, Token $symbol_token, Token ...$params): Token {
				assertAtLeastNumberParams($symbol_token, 2, ...$params);
				assertAllSameType(TokenType::LIST, $params[0]);
				$first = array_shift($params);
				assertAllSameType(TokenType::SYMBOL, ...$first->operand);
				var_dump($first);
				$second = array_shift($params);
				return new Token(
					TokenType::FUNCTION,
					$symbol_token->location,
					function (Environment $environment, Token $symbol_token, Token ...$params) use ($first, $second) {
						assertAtLeastNumberParams($symbol_token, count($first->operand), ...$params);
						$environment = new Environment([], $environment);
						for($i = 0; $i < count($first->operand); $i++) {
							$environment->set($first->operand[$i], $params[$i]);
						}
						return (new Evaluation($environment))->evaluate($second);
					}
				);
			})
		];
	}

	private static function createFunction(callable $function): Token {
		return new Token(
			TokenType::FUNCTION,
			new Location(0, 0), //TODO: Better handle this cases,
			$function
		);
	}
}


function assertAllSameType(TokenType $token_type, Token ...$tokens): void {
	foreach ($tokens as $token) {
		if ($token->token_type !== $token_type) {
			throw new RuntimeException("Expected $token_type->value. Got {$token->token_type->value}. At $token->location", $token->location);
		}
	}
}

function assertAtLeastNumberParams(Token $symbol_token, int $n_params, Token ...$params): void {
	$count = count($params);
	if ($count < $n_params) {
		throw new RuntimeException("Expected at least $n_params params. $count found. At $symbol_token->location", $symbol_token->location);
	}
}

function compareTokens(Token $first, Token $second): bool {
	return $first->token_type === $second->token_type &&
		match ($first->token_type) {
			TokenType::LIST, TokenType::VECTOR => compareArray($first->operand, $second->operand),
			default => $first->operand == $second->operand
		};
}

function compareArray(array $first, array $second): bool {
	if (count($first) !== count($second)) {
		return false;
	}
	$result = true;
	for($i = 0; $i < count($first); $i++) {
		$result = $result && compareTokens($first[$i], $second[$i]);
	}
	return $result;
}
