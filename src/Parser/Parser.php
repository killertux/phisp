<?php declare(strict_types=1);

namespace Clemente\Phisp\Parser;

class Parser {

	private int $position;
	private Location $location;

	public function __construct(
		private string $code
	) {
		$this->location = new Location(1, 0);
		$this->position = -1;
	}

	public function nextNode(): Token {
		$char = $this->nextValidChar();
		if ($char === '(') {
			return $this->parseList();
		} elseif ($char === '[') {
			return $this->parseVector();
		} elseif ($char === '{') {
			return $this->parseHashMap();
		} elseif ($char === '"') {
			return $this->parseString();
		} elseif ($char === '\'') {
			return $this->parseMacro('quote');
		} elseif ($char === '`') {
			return $this->parseMacro('quasiquote');
		} elseif ($char === '~' && $this->peekNext() === '@') {
			$char = $this->nextChar();
			assert($char === '@', 'We should always have an @ here');
			return $this->parseMacro('splice-unquote');
		} elseif ($char === '~') {
			return $this->parseMacro('unquote');
		} elseif ($char === '@') {
			return $this->parseMacro('deref');
		} elseif ($char === '^' && $this->peekNext() === '{') {
			$char = $this->nextChar();
			assert($char === '{', 'We should always have an { here');
			return $this->parseMetadata();
		} elseif (is_numeric($char)) {
			return $this->parseNumber();
		} elseif ($char === '-' && is_numeric($this->peekNext())) {
			return $this->parseNumber();
		} elseif ($char === ':') {
			return $this->parseKeyword();
		} elseif ($char === null) {
			return new Token(TokenType::NOP, $this->location, '');
		} else {
			return $this->parseSymbol();
		}
	}

	private function parseList(): Token {
		$location = clone $this->location;
		$nodes = [];
		while (($char = $this->peekNextValid()) !== ')') {
			if ($char === null) {
				throw new ParserException("EOF: Unclosed list at $this->location", $this->location);
			}
			$nodes[] = $this->nextNode();
		}
		assert($this->nextValidChar() === ')', 'A list should always be closed here');
		return new Token(TokenType::LIST, $location, $nodes);
	}

	private function parseVector(): Token {
		$location = clone $this->location;
		$nodes = [];
		while (($char = $this->peekNextValid()) !== ']') {
			if ($char === null) {
				throw new ParserException("EOF: Unclosed vector at $this->location", $this->location);
			}
			$nodes[] = $this->nextNode();
		}
		assert($this->nextValidChar() === ']', 'A vector should always be closed here');
		return new Token(TokenType::VECTOR, $location, $nodes);
	}

	private function parseHashMap(): Token {
		$location = clone $this->location;
		$key_values = [];
		while (($char = $this->peekNextValid()) !== '}') {
			if ($char === null) {
				throw new ParserException("EOF: Unclosed hashmap at $this->location", $this->location);
			}
			$key = $this->nextNode();
			$value = $this->nextNode();
			if ($value->token_type === TokenType::NOP) {
				throw new ParserException("EOF: Key without a value at $this->location", $this->location);
			}
			$key_values[] = [$key, $value];
		}
		assert($this->nextValidChar() === '}', 'A hashmap should always be closed here');
		return new Token(TokenType::HASHMAP, $location, $key_values);
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
				throw new ParserException("EOF: Unclosed string at $this->location", $this->location);
			}
			if ($char === '\\') {
				match($char = $this->nextChar()) {
					'"' => $string .= '"',
					"'" => $string .= "'",
					'n' => $string .= "\n",
					'\\' => $string .= "\\",
					default => throw new ParserException("Cannot parse \\$char at " . $this->location, $this->location),
				};
				continue;
			}
			$string .= $char;
		}
		assert($char === '"', 'A string should always be closed here');
		return new Token(TokenType::STRING, $location, $string);
	}

	private function parseMacro(string $symbol): Token {
		$location = clone $this->location;
		$operand = $this->nextNode();
		return new Token(
			TokenType::LIST,
			$location, [
				new Token(TokenType::SYMBOL, $location, $symbol),
				$operand,
			]
		);
	}

	private function parseMetadata(): Token {
		$location = clone $this->location;
		$metadata = $this->parseHashMap();
		$operand = $this->nextNode();
		if ($operand->token_type === TokenType::NOP) {
			throw new ParserException("EOF. Missing data to associate with metadata at $this->location", $this->location);
		}
		return new Token(
			TokenType::LIST,
			$location, [
				new Token(TokenType::SYMBOL, $location, 'with-meta'),
				$operand,
				$metadata,
			]
		);
	}

	private function parseKeyword(): Token {
		$location = clone $this->location;
		$keyword_name = '';
		while (($char = $this->peekNext()) !== null) {
			if (!$this->isValidSymbol($char)) {
				break;
			}
			$keyword_name .= $this->nextChar();
		}
		return new Token(TokenType::KEYWORD, $location, $keyword_name);
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
		if ($next === ';') {
			while (($char = $this->nextChar()) !== null) {
				if ($char === "\n") {
					return $this->nextValidChar();
				}
			}
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
		if ($next === ';') {
			while (($char = $this->peekNext($position + 1)) !== null) {
				$position += 1;
				if ($char === "\n") {
					return $this->peekNextValid($position + 1);
				}
			}
			return $this->peekNextValid($position);
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
