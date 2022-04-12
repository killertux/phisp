<?php

namespace Clemente\Phisp\Evaluation;

use Clemente\Phisp\Parser\Location;
use Clemente\Phisp\Parser\Token;
use Clemente\Phisp\Parser\TokenType;

class RuntimeException extends \Exception {

	public Location $location;

	public function __construct(string $message, Location $location) {
		parent::__construct($message);
		$this->location = $location;
	}

	public function toNop(): Token {
		return new Token(TokenType::NOP, $this->location, $this->getMessage());
	}
}
