<?php declare(strict_types=1);

namespace Clemente\Phisp\Parser;

class ParserException extends \Exception {

	public Location $location;

	public function __construct(string $message, Location $location) {
		parent::__construct($message);
		$this->location = $location;
	}

	public function toNop(): Token {
		return new Token(TokenType::NOP, $this->location, $this->getMessage());
	}
}
