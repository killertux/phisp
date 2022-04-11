<?php

namespace Clemente\Phisp\Parser;

class Parser
{

	private int $position;
	private Location $location;

	public function __construct(
		private string $code
	) {
		$this->location = new Location(1, 0);
		$this->position = -1;
	}

	public function nextNode(): ?Token {
		$char = $this->nextValidChar();
		if ($char === '(') {
			return $this->parseList();
		} elseif ($char === '"') {
			return $this->parseString();
		} elseif (is_numeric($char)) {
			return $this->parseNumber();
		} elseif ($char === '-' && is_numeric($this->peekNext())) {
			return $this->parseNumber();
		} elseif ($char === null) {
			return null;
		} else {
			return $this->parseSymbol();
		}
	}

	private function parseList(): Token {
		$location = clone $this->location;
		$nodes = [];
		while (($char = $this->peekNextValid()) !== ')') {
			if ($char === null) {
				throw new \Exception("Unclosed list at $this->location");
			}
			$nodes[] = $this->nextNode();
		}
		assert($this->nextValidChar() === ')', 'A list should always be closed here');
		return new Token(TokenType::LIST, $location, $nodes);
	}

	private function parseNumber(): Token {
		$location = clone $this->location;
		$number = $this->currentChar();
		while (is_numeric($this->peekNext())) {
			$number .= $this->nextChar();
		}
		return new Token(TokenType::NUMBER, $location, (float)$number);
	}

	private function parseSymbol(): Token {
		$location = clone $this->location;
		$symbol_name = $this->currentChar();
		while (($char = $this->peekNext()) !== null) {
			if (!$this->isValidSymbol($char)) {
				break;
			}
			$symbol_name .= $this->nextChar();
		}
		switch ($symbol_name) {
			case 'true':
				return new Token(TokenType::BOOL, $location, true);
			case 'false':
				return new Token(TokenType::BOOL, $location, false);
			case 'nil':
				return new Token(TokenType::NIL, $location, null);
			default:
				return new Token(TokenType::SYMBOL, $location, $symbol_name);
		}
	}

	private function parseString(): Token {
		$location = clone $this->location;
		$string = '';
		while (($char = $this->nextChar()) !== '"') {
			if ($char === null) {
				throw new \Exception("Unclosed string at $this->location");
			}
			if ($char === '\\') {
				match($char = $this->nextChar()) {
					'"' => $string .= '"',
					"'" => $string .= "'",
					'n' => $string .= "\n",
					'\\' => $string .= "\\",
					default => throw new \Exception("Cannot parse \\$char at " . $this->location),
				};
				continue;
			}
			$string .= $char;
		}
		assert($char === ')', 'A string should always be closed here');
		return new Token(TokenType::STRING, $location, $string);
	}

	private function nextChar(): ?string {
		if ($this->position + 1 !== strlen($this->code)) {
			$this->position++;
			$next_char = $this->code[$this->position];
			if ($next_char == "/n") {
				$this->location->column = 1;
				$this->location->row++;
			} else {
				$this->location->column++;
			}
			return $next_char;
		}
		return null;
	}

	private function nextValidChar(): ?string {
		$next = $this->nextChar();
		if ($this->isWhiteSpace($next ?? '')) {
			return $this->nextValidChar();
		}
		return $next;
	}

	private function currentChar(): string {
		return $this->code[$this->position];
	}

	private function peekNext(int $position = null): ?string {
		$position ??= $this->position;
		if ($position + 1 !== strlen($this->code)) {
			return $this->code[$position + 1];
		}
		return null;
	}

	private function peekNextValid(int $position = null): ?string {
		$position ??= $this->position;
		$next = $this->peekNext($position);
		if ($this->isWhiteSpace($next ?? '')) {
			return $this->peekNextValid($position + 1);
		}
		return $next;
	}

	private function isWhiteSpace(string $char): bool {
		return ctype_space($char) || $char === ',';
	}

	private function isValidSymbol(string $char): bool {
		return !(
			$char === '(' ||
			$char === ')' ||
			$char === '[' ||
			$char === ']' ||
			$char === ',' ||
			ctype_space($char)
		);
	}
}
