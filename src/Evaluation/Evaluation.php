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
			return $this->evaluateList($token);
		}
		return $this->evaluate_ast($token);
	}

	public function evaluate_ast(Token $token): Token {
		return match($token->token_type) {
			TokenType::SYMBOL => $this->enviroment->get($token),
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

	private function evaluateList(Token $token) {
		if (empty($token->operand)) {
			return $token;
		}
		/** @var Token $first_operand */
		$first_operand = $token->operand[0];
		if ($first_operand->token_type !== TokenType::SYMBOL) {
			throw new RuntimeException("Expected a symbol. Found a $first_operand->token_type->value at $first_operand->location", $first_operand->location);
		}
		return match ($first_operand->operand) {
			'def!' => $this->evaluateDef($token),
			default => $this->evaluateSymbol($token),
		};
	}

	private function evaluateSymbol(Token $token): Token {
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

	private function evaluateDef(Token $token): Token {
		/** @var Token $def */
		$def = array_shift($token->operand);
		assert("def!" === $def->operand);
		$count_params = count($token->operand);
		if ($count_params != 2) {
			throw new RuntimeException("def! should receive 2 parameters. $count_params received at $def->location", $def->location);
		}
		/** @var Token $symbol_name */
		$symbol_name = array_shift($token->operand);
		/** @var Token $symbol_name */
		$symbol_data = $this->evaluate(array_shift($token->operand));
		if ($symbol_name->token_type !== TokenType::SYMBOL) {
			throw new RuntimeException("First parameter of def! should be a symbol name. $symbol_name->token_type->value received at $symbol_name->location", $symbol_name->location);
		}
		$this->enviroment->set($symbol_name, $symbol_data);
		return $symbol_data;
	}
}
