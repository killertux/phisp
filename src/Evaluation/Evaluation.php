<?php

namespace Clemente\Phisp\Evaluation;

use Clemente\Phisp\Parser\Token;
use Clemente\Phisp\Parser\TokenType;

class Evaluation {

	public function __construct() {
		$this->enviroment = Environment::createDefault();
	}

	public function evaluate(Token $token): Token {
		if ($token->token_type === TokenType::LIST) {
			if (empty($token->operand)) {
				return $token;
			}
			$evaluated_list = $this->evaluate_ast($token);
			assert($evaluated_list->token_type === TokenType::LIST, "We should always have a list here");
			/** @var Token $first */
			$first = array_shift($evaluated_list->operand);
			if ($first->token_type !== TokenType::FUNCTION) {
				throw new RuntimeException("Expect function. Got {$first->token_type} at {$first->location}", $first->location);
			}
			$callable = $first->operand;
			return $callable($first, ...$evaluated_list->operand);
		}
		return $this->evaluate_ast($token);
	}

	public function evaluate_ast(Token $token): Token {
		return match($token->token_type) {
			TokenType::SYMBOL => $this->enviroment->getCallable($token),
			TokenType::LIST, TokenType::VECTOR => new Token(
				$token->token_type,
				$token->location,
				array_map(fn(Token $token) => $this->evaluate($token), $token->operand)
			),
			TokenType::HASHMAP => new Token(
				$token->token_type,
				$token->location,
				array_map(fn(array $key_value) => [$key_value[0], $this->evaluate($key_value[1])], $token->operand)
			),
			default => $token
		};
	}
}
